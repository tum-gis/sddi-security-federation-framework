<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2\Storage;

use OAuth2\Storage\Pdo as BasePdo;
use SAML2\Storage\AuthorizationCodeInterface as AuthorizationCodeStorageInterface;
use SAML2\Storage\UserClaimsInterface as UserClaimsStorageInterface;
use SAML2\Storage\AccessTokenInterface as AccessTokenStorageInterface;
use SAML2\Storage\RefreshTokenInterface as RefreshTokenStorageInterface;
use SAML2\Storage\ClientInterface;

require 'AuthorizationCodeInterface.php';
require 'UserClaimsInterface.php';
require 'AccessTokenInterface.php';
require 'RefreshTokenInterface.php';
require 'ClientInterface.php';

class Pdo extends BasePdo implements AuthorizationCodeStorageInterface, UserClaimsStorageInterface, AccessTokenStorageInterface, RefreshTokenStorageInterface, ClientInterface
{

    /**
     * @var string
     */
    protected $driver_name;

    /**
     * @param mixed $connection
     * @param array $config
     *
     * @throws InvalidArgumentException
     */
    public function __construct($connection, $config = array())
    {
	parent::__construct($connection, $config);

	// the name of the database driver. e.g. mysql, pgsql, sqlite
        $this->driver_name = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        $this->config = array_merge(array(
            'client_table' => 'clients',
            'access_token_table' => 'access_tokens',
            'refresh_token_table' => 'refresh_tokens',
	    'claims_table' => 'claims',
	    'consent_table' => 'consents',
            'code_table' => 'authorization_codes',
            'jwt_table'  => 'oauth_jwt',
            'jti_table'  => 'oauth_jti',
            'scope_table'  => 'oauth_scopes',
            'public_key_table'  => 'oauth_public_keys',
        ), $config);

	if ($this->config['create_db'] == true)
	{
	    if ($this->driver_name == 'pgsql')
	    {
	        // test if the client table exists - if yes we can assume that all other tables exists too. If the result is "0" than we need to init the database
	        $stmt = $this->db->prepare(sprintf("SELECT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '%s')", $this->config['client_table']));
	        $stmt->execute();
    
	        $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	        if ($res[0]['exists'] === false)
	            $this->initPGSQL($config);
	    }
	    else
	    {
	        // test if the client table exists - if yes we can assume that all other tables exists too. If the result is "0" than we need to init the database
                $stmt = $this->db->prepare(sprintf("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = '%s'", $this->config['client_table']));
                $stmt->execute();
    
                $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	        if ($res == null)
	            $this->initSQL($config);
	    }
	}
    }

    public function getClientIds()
    {
        $stmt = $this->db->prepare(sprintf('SELECT client_id FROM %s', $this->config['client_table']));
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUserClientsDetails($user_id = null)
    {
        $stmt = $this->db->prepare(sprintf('SELECT * FROM %s WHERE user_id = :user_id', $this->config['client_table']));
        $stmt->execute(compact('user_id'));

	return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $access_token
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param int    $expires
     * @param string $scope
     * @return bool
     */
    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null, $auth_id = null)
    {

        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAccessToken($access_token)) {
            $stmt = $this->db->prepare(sprintf('UPDATE %s SET client_id=:client_id, expires=:expires, user_id=:user_id, auth_id:=auth_id, scope=:scope where access_token=:access_token', $this->config['access_token_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (access_token, client_id, expires, user_id, auth_id, scope) VALUES (:access_token, :client_id, :expires, :user_id, :auth_id, :scope)', $this->config['access_token_table']));
        }

        return $stmt->execute(compact('access_token', 'client_id', 'user_id', 'auth_id', 'expires', 'scope'));
    }


    /* UserClaimsInterface */
    public function getUserClaims($user_id, $claims)
    {   
        if (!$userDetails = $this->getUserDetails($user_id)) {
            return false;
        }
        
        $claims = explode(' ', trim($claims));
        $userClaims = array();
        
        // for each requested claim, if the user has the claim, set it in the response
        $validClaims = explode(' ', self::VALID_CLAIMS);
        $validClaims = explode(' ', self::EXTENDED_CLAIMS);
        foreach ($validClaims as $validClaim) { 
            if (in_array($validClaim, $claims)) {
                if ($validClaim == 'address') {
                    // address is an object with subfields
                    $userClaims['address'] = $this->getUserClaim($validClaim, $userDetails['address'] ?: $userDetails);
                } else {
                    $userClaims = array_merge($userClaims, $this->getUserClaim($validClaim, $userDetails));
                }
            }
        }
        if (!is_null($userDetails['subject_id']))
                $userClaims['sub'] = $this->getUUID($userDetails['subject_id']);
        else    
                $userClaims['sub'] = $this->getUUID($user_id);
        
        return $userClaims;
    }

    protected function getUserClaim($claim, $userDetails)
    {
        $userClaims = array();
        $claimValuesString = constant(sprintf('self::%s_CLAIM_VALUES', strtoupper($claim)));
        $claimValues = explode(' ', $claimValuesString);

        foreach ($claimValues as $value) {
            $userClaims[$value] = isset($userDetails[$value]) ? $userDetails[$value] : null;
        }

        if ((strtoupper($claim) == 'EMAIL') AND (!is_null($userDetails['email'])))
                $userClaims['email_verified'] = ($userDetails['email_verified'] == '0') ? false : true;

        if (strtoupper($claim) != 'PROFILE')
                return $userClaims;

        $given_name = (isset($userDetails['given_name'])) ? $userDetails['given_name'] : null;
        $middle_name = (isset($userDetails['middle_name'])) ? $userDetails['middle_name'] : null;
        $family_name = (isset($userDetails['family_name'])) ? $userDetails['family_name'] : null;
        if (is_null($middle_name))
        {
            if (is_null($given_name))
            {
                $userClaims['name'] = $family_name;
            }
            else
            {
                $userClaims['name'] = $given_name . " " . $family_name;
            }
        }
        else
        {
            if (is_null($given_name))
            {
                $userClaims['name'] = $middle_name . " " . $family_name;
            }
            else
            {
                $userClaims['name'] = $given_name . " " . $middle_name . " " . $family_name;
            }
        }

        $finalClaims = array();
        if ($userClaims) {
          foreach($userClaims as $key => $claim)
            if (!is_null($claim))
              $finalClaims += array($key => $claim);
        }

        return $finalClaims;
    }

    public function setUserClaims($username, $claims)
    {

        $values = array();
        if ($this->driver_name == "pgsql")
                $stmt = $this->db->prepare($sql = sprintf('INSERT INTO %s (username, subject_id, name, family_name, given_name, middle_name, nickname, preferred_username, profile, picture, website, gender, age, birthdate, zoneinfo, locale, updated_at, email, email_verified, scope, affiliation, profession, idp_country, idp_name, idp_identifier, idp_origin, home_town, auth_time) VALUES (:username, :subject_id, :name, :family_name, :given_name, :middle_name, :nickname, :preferred_username, :profile, :picture, :website, :gender, :age, :birthdate, :zoneinfo, :locale, :updated_at, :email, :email_verified, :scope, :affiliation, :profession, :idp_country, :idp_name, :idp_identifier, :idp_origin, :home_town, :auth_time) ON CONFLICT(username) DO UPDATE SET username=:username, subject_id=:subject_id, name=:name, family_name=:family_name, given_name=:given_name, middle_name=:middle_name, nickname=:nickname, preferred_username=:preferred_username, profile=:profile, picture=:picture, website=:website, gender=:gender, age=:age, birthdate=:birthdate, zoneinfo=:zoneinfo, locale=:locale, updated_at=:updated_at, email=:email, email_verified=:email_verified, scope=:scope, affiliation=:affiliation, profession=:profession, idp_country=:idp_country, idp_name=:idp_name, idp_identifier=:idp_identifier, idp_origin=:idp_origin, home_town=:home_town, auth_time=:auth_time', $this->config['claims_table']));
        else
        	$stmt = $this->db->prepare($sql = sprintf('INSERT INTO %s (username, subject_id, name, family_name, given_name, middle_name, nickname, preferred_username, profile, picture, website, gender, age, birthdate, zoneinfo, locale, updated_at, email, email_verified, scope, affiliation, profession, idp_country, idp_name, idp_identifier, idp_origin, home_town, auth_time) VALUES (:username, :subject_id, :name, :family_name, :given_name, :middle_name, :nickname, :preferred_username, :profile, :picture, :website, :gender, :age, :birthdate, :zoneinfo, :locale, :updated_at, :email, :email_verified, :scope, :affiliation, :profession, :idp_country, :idp_name, :idp_identifier, :idp_origin, :home_town, :auth_time) ON DUPLICATE KEY UPDATE username=:username, subject_id=:subject_id, name=:name, family_name=:family_name, given_name=:given_name, middle_name=:middle_name, nickname=:nickname, preferred_username=:preferred_username, profile=:profile, picture=:picture, website=:website, gender=:gender, age=:age, birthdate=:birthdate, zoneinfo=:zoneinfo, locale=:locale, updated_at=:updated_at, email=:email, email_verified=:email_verified, scope=:scope, affiliation=:affiliation, profession=:profession, idp_country=:idp_country, idp_name=:idp_name, idp_identifier=:idp_identifier, idp_origin=:idp_origin, home_town=:home_town, auth_time=:auth_time', $this->config['claims_table']));

        $values = array_merge(array('username' => $username), $claims);

        $stmt->execute($values);

        return true;
    }

    /** 
     * User Consent functions
     *
    */
    public function checkUserConsent($username, $client_id)
    {
        $stmt = $this->db->prepare(sprintf("SELECT * FROM %s WHERE username = :username AND client_id = :client_id AND revoked = 'false'", $this->config['consent_table']));
        $stmt->execute(compact('username', 'client_id'));

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function setUserConsent($username, $client_id, $claims)
    {
	$claims = serialize($claims);
        if (!$this->checkUserConsent($username, $client_id))
        {
                $stmt = $this->db->prepare($sql = sprintf('INSERT INTO %s (username, client_id, claims) VALUES (:username, :client_id, :claims)', $this->config['consent_table']));

                $stmt->execute(compact('username', 'client_id', 'claims'));

                return true;
        }
        return false;
    }

    public function revokeUserConsent($username, $client_id)
    {
        if ($this->checkUserConsent($username, $client_id))
        {
                $stmt = $this->db->prepare($sql = sprintf("UPDATE %s SET revoked='true' WHERE username= :username AND client_id= :client_id", $this->config['consent_table']));
                $stmt->execute(compact('username', 'client_id'));

                $stmt = $this->db->prepare($sql = sprintf('DELETE FROM %s WHERE user_id= :username AND client_id= :client_id', $this->config['access_token_table']));
                $stmt->execute(compact('username', 'client_id'));

                return true;
        }
        return false;
    }


    public function getUserConsent($username)
    {
	$sql_string = sprintf("SELECT %s.client_id, %s.client_name, %s.software_version, %s.operator_name, %s.scope, consent_date, claims FROM %s, %s WHERE %s.client_id = %s.client_id AND %s.username= :username AND %s.revoked = 'false'", $this->config['client_table'], $this->config['client_table'], $this->config['client_table'], $this->config['client_table'], $this->config['client_table'], $this->config['client_table'], $this->config['consent_table'], $this->config['client_table'], $this->config['consent_table'], $this->config['consent_table'], $this->config['consent_table']);
        $stmt = $this->db->prepare($sql_string);
        $stmt->execute(compact('username'));
        //$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	$result = [];
	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
	    $row['claims'] = unserialize($row['claims']);
    	    array_push($result,$row);
	}
        return $result;
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null, $details = null)
    {

        $client_name = null;
        $software_version = null;
        $logo_uri = null;
        $tos_uri = null;
        $policy_uri = null;
        $user_name = null;
        $user_email = null;
        $created = date('Y-m-d H:i:s');

        if (isset($details)) {
                $client_name = $details['client_name'];
                $software_version = $details['software_version'];
                $logo_uri = (isset($details['logo_uri'])) ? $details['logo_uri'] : null;
                $tos_uri = $details['tos_uri'];
                $policy_uri = $details['policy_uri'];

                $operator_name = $details['operator_name'];
                $operator_uri = $details['operator_uri'];
                $operator_address = $details['operator_address'];
                $operator_country = $details['operator_country'];
                $operator_privacy = $details['agree_privacy'];
                $user_name = (isset($details['user_name'])) ? $details['user_name'] : null;
                $user_email = (isset($details['contacts'])) ? $details['contacts'] : null;
        }

        // if it exists, update it.
	if ($this->getClientDetails($client_id)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_secret=:client_secret, redirect_uri=:redirect_uri, grant_types=:grant_types, scope=:scope, user_id=:user_id, client_name=:client_name, software_version=:software_version, logo_uri=:logo_uri, tos_uri=:tos_uri, policy_uri=:policy_uri, user_name=:user_name, user_email =:user_email, operator_name =:operator_name, operator_address =:operator_address, operator_country =:operator_country, operator_uri =:operator_uri, operator_privacy =:operator_privacy, created =:created WHERE client_id=:client_id', $this->config['client_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (client_id, client_secret, redirect_uri, grant_types, scope, user_id, client_name, software_version, logo_uri, tos_uri, policy_uri, user_name, user_email, operator_name, operator_address, operator_country, operator_uri, operator_privacy, created) VALUES (:client_id, :client_secret, :redirect_uri, :grant_types, :scope, :user_id, :client_name, :software_version, :logo_uri, :tos_uri, :policy_uri, :user_name, :user_email, :operator_name, :operator_address, :operator_country, :operator_uri, :operator_privacy, :created)', $this->config['client_table']));
        }

        return $stmt->execute(compact('client_id', 'client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id', 'client_name', 'software_version', 'logo_uri', 'tos_uri', 'policy_uri', 'user_name', 'user_email', 'operator_name', 'operator_address', 'operator_country', 'operator_uri', 'operator_privacy', 'created'));
    }

    public function getClientOperators()
    {

        // select distinct operator_name, client_name as application_name, software_version as application_version, created as registration_date from client_details ORDER BY operator_name ASC
        $sql_string = sprintf('select distinct operator_name, operator_uri, client_name as application_name, software_version as application_version, created as registration_date, tos_uri, policy_uri FROM %s ORDER BY operator_name ASC', $this->config['client_table']);
        $stmt = $this->db->prepare($sql_string);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getRefreshTokens($username, $client_id)
    {
        $sql_string = sprintf('select refresh_token FROM %s WHERE user_id = :username AND client_id = :client_id', $this->config['refresh_token_table']);
        $stmt = $this->db->prepare($sql_string);
        $stmt->execute(compact('username', 'client_id'));
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $result;

    }

    /**
     * @param string $refresh_token
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param string $expires
     * @param string $scope
     * @return bool
     */
    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null, $auth_id = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        $stmt = $this->db->prepare(sprintf('INSERT INTO %s (refresh_token, client_id, user_id, auth_id, expires, scope) VALUES (:refresh_token, :client_id, :user_id, :auth_id, :expires, :scope)', $this->config['refresh_token_table']));

        return $stmt->execute(compact('refresh_token', 'client_id', 'user_id', 'auth_id', 'expires', 'scope'));
    }

    /**
     * @param string $refresh_token
     * @return bool
     */
    public function unsetRefreshToken($refresh_token)
    {
	// This Authorization Server supports the deletion of Access Tokens. As per RFC 7009 #section 2.1 all associated access tokens must be deleted
	// As a Refresh Token is associated to a user_id and client_id, we delete all Access Tokens with the same user_id, client_id associated.

	if ($this->driver_name == 'pgsql')
	    $stmt = $this->db->prepare(sprintf('DELETE FROM %s a USING %s r WHERE a.client_id = r.client_id AND a.user_id = r.user_id AND r.refresh_token =:refresh_token', $this->config['access_token_table'], $this->config['refresh_token_table']));
	else
	    $stmt = $this->db->prepare(sprintf('DELETE a.* FROM %s a INNER JOIN %s r ON a.client_id = r.client_id AND a.user_id = r.user_id WHERE r.refresh_token =:refresh_token', $this->config['access_token_table'], $this->config['refresh_token_table']));
	$stmt->execute(compact('refresh_token'));

        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE refresh_token =:refresh_token', $this->config['refresh_token_table']));

        $stmt->execute(compact('refresh_token'));

        return $stmt->rowCount() > 0;
    }

    /**
     * Get the UUID value.
     *
     * @param array $state The state array.
     * @return string|null The UUID value.
     */
    public function getUUID($name)
    {
        
        // Get hexadecimal components of UUID secret
        $nhex = str_replace(array('-','{','}'), '', $this->config['secret']);
        
        // Binary Value
        $nstr = '';
        
        // Convert Namespace UUID to bits
        for($i = 0; $i < strlen($nhex); $i+=2) {
          $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }
        
        // Calculate hash value
        $hash = md5($nstr . $name);
        
        $uuid = sprintf('%08s-%04s-%04x-%04x-%12s',
          
          // 32 bits for "time_low"
          substr($hash, 0, 8),
          
          // 16 bits for "time_mid"
          substr($hash, 8, 4),
          
          // 16 bits for "time_hi_and_version",
          // four most significant bits holds version number 3
          (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
          
          // 16 bits, 8 bits for "clk_seq_hi_res",
          // 8 bits for "clk_seq_low",
          // two most significant bits holds zero and one for variant DCE1.1
          (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
          
          // 48 bits for "node"
          substr($hash, 20, 12)
        );
        return $uuid;
    
    }

    /**
     * @param string $username
     * @return array|bool
     */
    public function getUser($username)
    {
        $stmt = $this->db->prepare($sql = sprintf('SELECT * from %s where username=:username', $this->config['claims_table']));
        $stmt->execute(array('username' => $username));

        if (!$userInfo = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        // the default behavior is to use "username" as the user_id
        return array_merge(array(
            'user_id' => $username
        ), $userInfo);
    }

    /**
     * @param string $code
     * @param mixed  $client_id
     * @param mixed  $user_id
     * @param string $redirect_uri
     * @param string $expires
     * @param string $scope
     * @param string $id_token
     * @param string auth_id$
     * @return bool
     */
    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null, $auth_id = null)
    {
        // convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);

        // if it exists, update it.
        if ($this->getAuthorizationCode($code)) {
            $stmt = $this->db->prepare($sql = sprintf('UPDATE %s SET client_id=:client_id, user_id=:user_id, redirect_uri=:redirect_uri, expires=:expires, scope=:scope, id_token =:id_token, auth_id =:auth_id where authorization_code=:code', $this->config['code_table']));
        } else {
            $stmt = $this->db->prepare(sprintf('INSERT INTO %s (authorization_code, client_id, user_id, redirect_uri, expires, scope, id_token, auth_id) VALUES (:code, :client_id, :user_id, :redirect_uri, :expires, :scope, :id_token, :auth_id)', $this->config['code_table']));
        }

        return $stmt->execute(compact('code', 'client_id', 'user_id', 'redirect_uri', 'expires', 'scope', 'id_token', 'auth_id'));
    }


    private function initPGSQL($config)
    {
	$sql_stmts = array();
	array_push($sql_stmts, "CREATE TABLE {$this->config['client_table']} ( client_id varchar(80) NOT NULL, client_secret varchar(80) DEFAULT NULL, redirect_uri varchar(2048) DEFAULT NULL, grant_types varchar(80) DEFAULT NULL, scope varchar(4000) DEFAULT NULL, user_id varchar(560) DEFAULT NULL, client_name varchar(80) DEFAULT NULL, software_version varchar(20) DEFAULT NULL, tos_uri varchar(2048) DEFAULT NULL, policy_uri varchar(2048) DEFAULT NULL, logo_uri varchar(2048) DEFAULT NULL, operator_name varchar(80) DEFAULT NULL, operator_uri varchar(2048) DEFAULT NULL, operator_address varchar(2000) DEFAULT NULL, operator_country varchar(2) DEFAULT NULL, operator_privacy varchar(10) DEFAULT NULL, user_name varchar(2000) DEFAULT NULL, user_email varchar(80) DEFAULT NULL, created timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (client_id))");
	array_push($sql_stmts, "CREATE TABLE {$this->config['code_table']} ( authorization_code varchar(40) NOT NULL, client_id varchar(80) NOT NULL, user_id varchar(560) DEFAULT NULL, auth_id varchar(200) DEFAULT NULL, redirect_uri varchar(2048) DEFAULT NULL, expires timestamp NOT NULL, scope varchar(4000) DEFAULT NULL, id_token varchar(4000) DEFAULT NULL, PRIMARY KEY (authorization_code))");
	array_push($sql_stmts, "CREATE TABLE {$this->config['access_token_table']} ( access_token varchar(40) NOT NULL, client_id varchar(80) NOT NULL, user_id varchar(560) DEFAULT NULL, auth_id varchar(200) DEFAULT '', expires timestamp NOT NULL, scope varchar(4000) DEFAULT NULL, PRIMARY KEY (access_token))");
	array_push($sql_stmts, "CREATE TABLE {$this->config['refresh_token_table']} ( refresh_token varchar(80) NOT NULL DEFAULT '', client_id varchar(80) NOT NULL, user_id varchar(560) DEFAULT NULL, auth_id varchar(200) DEFAULT '', expires timestamp NOT NULL, scope varchar(4000) DEFAULT NULL, PRIMARY KEY (refresh_token))");
	array_push($sql_stmts, "CREATE TABLE {$this->config['claims_table']} ( username varchar(560) NOT NULL DEFAULT '', subject_id varchar(255) DEFAULT NULL, password varchar(80) DEFAULT NULL, first_name varchar(80) DEFAULT NULL, last_name varchar(80) DEFAULT NULL, name varchar(80) DEFAULT NULL, family_name varchar(80) DEFAULT NULL, given_name varchar(80) DEFAULT NULL, middle_name varchar(80) DEFAULT NULL, nickname varchar(80) DEFAULT NULL, preferred_username varchar(80) DEFAULT NULL, profile varchar(2048) DEFAULT NULL, picture varchar(2048) DEFAULT NULL, website varchar(2048) DEFAULT NULL, gender varchar(20) DEFAULT NULL, age varchar(20) DEFAULT NULL, birthdate timestamp NULL DEFAULT NULL, zoneinfo varchar(20) DEFAULT NULL, locale varchar(20) DEFAULT NULL, updated_at varchar(80) DEFAULT NULL, email varchar(80) DEFAULT NULL, email_verified int DEFAULT NULL, scope varchar(4000) DEFAULT NULL, profession varchar(200) DEFAULT NULL, affiliation varchar(200) DEFAULT NULL, idp_country varchar(200) DEFAULT NULL, idp_name varchar(200) DEFAULT NULL, idp_identifier varchar(200) DEFAULT NULL, idp_origin varchar(200) DEFAULT NULL, home_town varchar(200) DEFAULT NULL, auth_time int NOT NULL, PRIMARY KEY (username))");
	array_push($sql_stmts, "CREATE TABLE {$this->config['consent_table']} ( id SERIAL, username varchar(560)  NOT NULL DEFAULT '', client_id varchar(200) NOT NULL, consent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP, claims text NULL, revoked varchar(20) NOT NULL DEFAULT 'false', PRIMARY KEY (id))");
	foreach ($sql_stmts as $stmt)
	{
	    $this->db->exec($stmt);
	}
	if ($config['create_test_clients'] == true)
	    $this->initTestClients($this->config['client_table']);
    }

    private function initSQL($config)
    {
        $sql_stmts = array();
        array_push($sql_stmts, "CREATE TABLE {$this->config['client_table']} ( `client_id` varchar(80) NOT NULL, `client_secret` varchar(80) DEFAULT NULL, `redirect_uri` varchar(2048) DEFAULT NULL, `grant_types` varchar(80) DEFAULT NULL, `scope` varchar(4000) DEFAULT NULL, `user_id` varchar(560) DEFAULT NULL, `client_name` varchar(80) DEFAULT NULL, `software_version` varchar(20) DEFAULT NULL, `tos_uri` varchar(2048) DEFAULT NULL, `policy_uri` varchar(2048) DEFAULT NULL, `logo_uri` varchar(2048) DEFAULT NULL, `operator_name` varchar(80) DEFAULT NULL, `operator_uri` varchar(2048) DEFAULT NULL, `operator_address` varchar(2000) DEFAULT NULL, `operator_country` varchar(2) DEFAULT NULL, `operator_privacy` varchar(10) DEFAULT NULL, `user_name` varchar(2000) DEFAULT NULL, `user_email` varchar(80) DEFAULT NULL, `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`client_id`))");
        array_push($sql_stmts, "CREATE TABLE {$this->config['code_table']} ( `authorization_code` varchar(40) NOT NULL, `client_id` varchar(80) NOT NULL, `user_id` varchar(560) DEFAULT NULL, `auth_id` varchar(200) DEFAULT NULL, `redirect_uri` varchar(2048) DEFAULT NULL, `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `scope` varchar(4000) DEFAULT NULL, `id_token` varchar(4000) DEFAULT NULL, PRIMARY KEY (`authorization_code`))");
        array_push($sql_stmts, "CREATE TABLE {$this->config['access_token_table']} ( `access_token` varchar(40) NOT NULL, `client_id` varchar(80) NOT NULL, `user_id` varchar(560) DEFAULT NULL, `auth_id` varchar(200) DEFAULT '', `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `scope` varchar(4000) DEFAULT NULL, PRIMARY KEY (`access_token`))");
        array_push($sql_stmts, "CREATE TABLE {$this->config['refresh_token_table']} ( `refresh_token` varchar(80) NOT NULL DEFAULT '', `client_id` varchar(80) NOT NULL, `user_id` varchar(560) DEFAULT NULL, `auth_id` varchar(200) DEFAULT '', `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, `scope` varchar(4000) DEFAULT NULL, PRIMARY KEY (`refresh_token`))");
        array_push($sql_stmts, "CREATE TABLE {$this->config['claims_table']} ( `username` varchar(560) NOT NULL DEFAULT '', `subject_id` varchar(255) DEFAULT NULL, `password` varchar(80) DEFAULT NULL, `first_name` varchar(80) DEFAULT NULL, `last_name` varchar(80) DEFAULT NULL, `name` varchar(80) DEFAULT NULL, `family_name` varchar(80) DEFAULT NULL, `given_name` varchar(80) DEFAULT NULL, `middle_name` varchar(80) DEFAULT NULL, `nickname` varchar(80) DEFAULT NULL, `preferred_username` varchar(80) DEFAULT NULL, `profile` varchar(2048) DEFAULT NULL, `picture` varchar(2048) DEFAULT NULL, `website` varchar(2048) DEFAULT NULL, `gender` varchar(20) DEFAULT NULL, `age` varchar(20) DEFAULT NULL, `birthdate` timestamp NULL DEFAULT NULL, `zoneinfo` varchar(20) DEFAULT NULL, `locale` varchar(20) DEFAULT NULL, `updated_at` varchar(80) DEFAULT NULL, `email` varchar(80) DEFAULT NULL, `email_verified` tinyint(1) DEFAULT NULL, `scope` varchar(4000) DEFAULT NULL, `profession` varchar(200) DEFAULT NULL, `affiliation` varchar(200) DEFAULT NULL, `idp_country` varchar(200) DEFAULT NULL, `idp_name` varchar(200) DEFAULT NULL, `idp_identifier` varchar(200) DEFAULT NULL, `idp_origin` varchar(200) DEFAULT NULL, `home_town` varchar(200) DEFAULT NULL, `auth_time` int(11) unsigned NOT NULL, PRIMARY KEY (`username`))");
        array_push($sql_stmts, "CREATE TABLE {$this->config['consent_table']} ( `id` int NOT NULL AUTO_INCREMENT, `username` varchar(560)  NOT NULL DEFAULT '', `client_id` varchar(200) NOT NULL, `consent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `claims` text, `revoked` enum('false','true') NOT NULL DEFAULT 'false', PRIMARY KEY (`id`))");
	array_push($sql_stmts, "CREATE EVENT deleteExpiredAccessTokens ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE DO DELETE FROM {$this->config['access_token_table']} WHERE expires < NOW()");
	array_push($sql_stmts, "CREATE EVENT deleteExpiredCodes ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE DO DELETE FROM {$this->config['code_table']} WHERE expires < NOW()");
	array_push($sql_stmts, "CREATE EVENT deleteExpiredRefreshTokens ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE DO DELETE FROM {$this->config['refresh_token_table']} WHERE expires < NOW()");
	array_push($sql_stmts, "CREATE EVENT deleteSelfRegisteredClients ON SCHEDULE EVERY 1 DAY STARTS NOW() ENABLE DO DELETE FROM {$this->config['client_table']} WHERE user_name = NULL AND created < TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day))");

        foreach ($sql_stmts as $stmt)
            $this->db->exec($stmt);

        if ($config['create_test_clients'] == true)
            $this->initTestClients($this->config['client_table']);
    }

    
    private function initTestClients($client_table)
    {
	$stmt = "INSERT INTO {$client_table} VALUES ('0a0036ff-52fa-a2ad-c884-af80ae377730','3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc','http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/','authorization_code refresh_token','openid profile email saml offline_access','info@secure-dimensions.de','GDPR Test MobileApp - Level Email/Profile','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-profile.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:05'),('1585cd2b-c8aa-1179-a6aa-b55fb9024802','34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61','http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/','authorization_code refresh_token','openid email saml offline_access','info@secure-dimensions.de','GDPR Test MobileApp - Level Email','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-email.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:00'),('1b8114a5-123e-084d-2557-3792cc585783',NULL,'http://127.0.0.1:4711/web-app/','implicit','openid email saml','info@secure-dimensions.de','GDPR Test App - Level Email','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-email.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:25'),('1dbd9518-7624-950e-5f0b-cdb61a66f9fc','8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7','','client_credentials','openid saml','info@secure-dimensions.de','GDPR Test ServiceApp - Level Cryptoname','1','http://127.0.0.1:4711/TermsOfUse.php','','http://127.0.0.1:4711/images/icon-id.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:16'),('2bd0defa-9919-945c-18f1-a16a37fa2881','c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3','http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/  http://127.0.0.1:4711/refresh-app/','authorization_code refresh_token',' saml offline_access','info@secure-dimensions.de','GDPR Test MobileApp - Level Auth','1','http://127.0.0.1:4711/TermsOfUse.php','','http://127.0.0.1:4711/images/icon-auth.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:43'),('2dbbfbf0-cfda-2860-99ff-552278db2e71',NULL,'http://127.0.0.1:4711/web-app/ http://127.0.0.1:4711/logout-webapp/','implicit','openid saml','info@secure-dimensions.de','GDPR Test App - Level Cryptoname','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-id.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:09'),('2f5d0a34-5f76-c8c6-b262-0d38cd9e4185','fa458a15d01112826963f6261cafed88367a89922a25c3ef90e400ae3224266a','http://127.0.0.1:4711/revocation-app/','authorization_code refresh_token',' saml offline_access','info@secure-dimensions.de','Revocation Test MobileApp - Level Auth','1','http://127.0.0.1:4711/TermsOfUse.php','','http://127.0.0.1:4711/images/icon-auth.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-24 14:01:55'),('4ce41c30-7f58-6b87-107a-575fa0d114d0','a17912e39c2269e4b6dce3130c5e4cff0ad2660eed3917518b704e0768f4713a','','client_credentials','openid email saml','info@secure-dimensions.de','GDPR Test ServiceApp - Level Email','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-email.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:27'),('8d86fcb1-f720-3a95-1070-0a4eb8d050ab','210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc','http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/','authorization_code refresh_token','openid profile saml offline_access','info@secure-dimensions.de','GDPR Test MobileApp - Level Profile','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-profile.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:54'),('92cfde1b-09b2-5220-a447-10d4f555a083','49143db5a04b0c5394b4228d9015113d761f95f245b5445a7dcc5a42ec2e433c','','client_credentials','openid profile email saml','info@secure-dimensions.de','GDPR Test ServiceApp - Level Email/Profile','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-openid.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:33'),('952cf7e3-be59-d56e-1177-7cde1233e920',NULL,'http://127.0.0.1:4711/web-app/ http://127.0.0.1:4711/logout-webapp/','implicit','openid profile email saml','info@secure-dimensions.de','GDPR Test App - Level Email/Profile','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-openid.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:30'),('9aa391cd-ea50-8a70-545a-1c51879a1dd5','9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5','http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/','authorization_code refresh_token','openid saml','info@secure-dimensions.de','GDPR Test MobileApp - Level Cryptoname','1','http://127.0.0.1:4711/TermsOfUse.php','','http://127.0.0.1:4711/images/icon-id.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:49'),('a13f8fbf-e015-d76b-8057-71bd1cc089a8','8e69239aef414ad196b3a5ede48782a0346bae8a411e56156944f3a578591b77','','client_credentials','openid profile saml','info@secure-dimensions.de','GDPR Test ServiceApp - Level Profile','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-profile.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:21'),('e730605f-ec6b-fd72-1ff9-d10729e8bed6','f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6','','client_credentials',' saml','info@secure-dimensions.de','GDPR Test ServiceApp - Level Auth','1','http://127.0.0.1:4711/TermsOfUse.php','','http://127.0.0.1:4711/images/icon-auth.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:38:12'),('f689fc48-5934-8853-72e9-190ee6748795',NULL,'http://127.0.0.1:4711/web-app/','implicit','openid profile saml','info@secure-dimensions.de','GDPR Test App - Level Profile','1','http://127.0.0.1:4711/TermsOfUse.php','http://127.0.0.1:4711/PrivacyStatement.php','http://127.0.0.1:4711/images/icon-profile.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:37:19'),('f8910358-fed1-3d49-8183-913ddede237e',NULL,'http://127.0.0.1:4711/web-app/ http://127.0.0.1:4711/logout-webapp/','implicit',' saml','info@secure-dimensions.de','GDPR Test App - Level Auth','1','http://127.0.0.1:4711/TermsOfUse.php','','http://127.0.0.1:4711/images/icon-auth.png','Secure Dimensions GmbH','https://www.secure-dimensions.de','Munich, Germany','DE',NULL,'TEST','info@secure-dimensions.de','2019-10-17 10:36:47')";
	$this->db->exec($stmt);
    }
	
}
