<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

use \Psr\Http\Message\RequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Namshi\JOSE\SimpleJWS;
use Slim\Views\PhpRenderer;

use phpseclib\Crypt\Random;
use phpseclib\Crypt\AES;

use SimpleSAML\Session;
use \SAML2\XML\saml\NameIDType;

//use OAuth2\Controller\TokenControllerInterface;
//use OAuth2\Controller\TokenController;

use SAML2\Controller\TokenControllerInterface;
use SAML2\Controller\TokenController;

use SAML2\Server;
use SAML2\ResponseType\Code as CodeResponseType;
use SAML2\ResponseType\CodeIdToken as CodeIdTokenResponseType;
use SAML2\ResponseType\IdToken as IdTokenResponseType;
use SAML2\ResponseType\AccessToken as AccessTokenResponseType;
use SAML2\ResponseType\IdTokenToken as IdTokenTokenResponseType;
use SAML2\Controller\AuthorizeControllerInterface;
use SAML2\Controller\AuthorizeController;
use SAML2\GrantType\AuthorizationCode;
use SAML2\GrantType\RefreshToken;
use SAML2\GrantType\AccessToken;

use OAuth2\ClientAssertionType\HttpBasic;

require '../vendor/autoload.php';
require '../lib/SAML2/ResponseType/Code.php';
require '../lib/SAML2/ResponseType/IdToken.php';
require '../lib/SAML2/ResponseType/CodeIdToken.php';
require '../lib/SAML2/ResponseType/IdTokenToken.php';
require '../lib/SAML2/ResponseType/AccessToken.php';
require '../lib/SAML2/Server.php';
require '../lib/SAML2/Storage/Pdo.php';

include '../config/config.php';

// create storage for the keys used to sign id_token and JWT access token
$keyStorage = new OAuth2\Storage\Memory(array('keys' => array(
    'public_key'  => file_get_contents($config['public_key']),
    'private_key' => file_get_contents($config['private_key'])
)));


$app = new \Slim\App(["settings" => array('displayErrorDetails' => true), "as" => $config]);
$container = $app->getContainer();

// set the templates directory
$container['renderer'] = new PhpRenderer($config['templates_dir']);

// configure the logger
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('LS');
    $file_handler = new \Monolog\Handler\StreamHandler($c->get('as')['logfile']);
    $logger->pushHandler($file_handler);
    return $logger;
};

// construct the storage (only PDO!)
$dbStorage = new SAML2\Storage\Pdo($config['PDO'], $config);

// construct the AS
$server = new Server($dbStorage, $config);
$server->addStorage($keyStorage, 'public_key');

// add response types
$code = new CodeResponseType($server->getStorage('authorization_code'), $config);
$idToken = new IdTokenResponseType($server->getStorage('user_claims'), $server->getStorage('public_key'), $config);
$codeIdToken = new CodeIdTokenResponseType($code, $idToken, $config);
$accessToken = new AccessTokenResponseType($server->getStorage('access_token'), $server->getStorage('refresh_token'), $config);
$idTokenToken = new IdTokenTokenResponseType($accessToken, $idToken, $server->getStorage('access_token'), $server->getStorage('public_key'), null, $config);

$responseTypes = array(
	'code' => $code,
	'code id_token' => $codeIdToken,
	'token' => $accessToken,
	'id_token' => $idToken,
	'id_token token' => $idTokenToken
	);

// configure default and supported scopes
$defaultScope = '';
$supportedScopes = array(
  '',
  'openid',
  'profile',
  'email',
  'saml',
  'offline_access'
);

// configure the scope storage
$memory = new OAuth2\Storage\Memory(array(
  'default_scope' => $defaultScope,
  'supported_scopes' => $supportedScopes
));
$scopeUtil = new OAuth2\Scope($memory);

// create the SAML2 aware Authorize Controller
$server->setAuthorizeController(new AuthorizeController($server->getStorage('client'), $responseTypes, $config, $scopeUtil));

// create the SAML2 aware Token Controller
$grantTypes = array(new OAuth2\GrantType\ClientCredentials($dbStorage), new SAML2\GrantType\AuthorizationCode($dbStorage, $config), new SAML2\GrantType\RefreshToken($dbStorage, $config));
$clientAssertionType = new HttpBasic($server->getStorage('client'), $config);
$tokenController = new SAML2\Controller\TokenController($accessToken, $server->getStorage('client'), $grantTypes, $clientAssertionType, $scopeUtil);
$server->setTokenController($tokenController);

// convenience function
function attributes2claims($attributes)
{
  $email = NULL;
  if (isset($attributes['mail']))
    $email = $attributes['mail'][0];
  else if (isset($attributes['email']))
    $email = $attributes['email'][0];
  else
    $email = NULL;

  $claims = array();
  $claims['subject_id'] = (isset($attributes['subject-id'])? $attributes['subject-id'][0] : null);
  $claims['name'] = (isset($attributes['cn']) ? $attributes['cn'][0] : null);
  $claims['family_name'] = (isset($attributes['sn']) ? $attributes['sn'][0] : null);
  $claims['given_name'] = (isset($attributes['givenName']) ? $attributes['givenName'][0] : null);
  $claims['middle_name'] = (isset($attributes['middleName']) ? $attributes['middleName'][0] : null);
  $claims['nickname'] = (isset($attributes['nickname']) ? $attributes['nickname'][0] : null);
  $claims['preferred_username'] = (isset($attributes['displayName']) ? $attributes['displayName'][0] : null);
  $claims['profile'] = (isset($attributes['profile']) ? $attributes['profile'][0] : null);
  $claims['picture'] = (isset($attributes['picture']) ? $attributes['picture'][0] : null);
  $claims['website'] = (isset($attributes['website']) ? $attributes['website'][0] : null);
  $claims['gender'] = (isset($attributes['gender']) ? $attributes['gender'][0] : null);
  $claims['age'] = (isset($attributes['age']) ? $attributes['age'][0] : null);
  $claims['birthdate'] = (isset($attributes['birthdate']) ? $attributes['birthdate'][0] : null);
  $claims['zoneinfo'] = (isset($attributes['zoneinfo']) ? $attributes['zoneinfo'][0] : null);
  $claims['locale'] = (isset($attributes['locale']) ? $attributes['locale'][0] : null);
  $claims['updated_at'] = (isset($attributes['updatedAt']) ? $attributes['updatedAt'][0] : null);
  $claims['email'] = $email;
  $claims['email_verified'] = (isset($attributes['emailVerified']) ? $attributes['emailVerified'][0] : null);
  $claims['affiliation'] = (isset($attributes['affiliation']) ? $attributes['affiliation'][0] : null);
  $claims['profession'] = (isset($attributes['profession']) ? $attributes['profession'][0] : null);
  $claims['idp_country'] = (isset($attributes['c']) ? $attributes['c'][0] : null);
  $claims['idp_country'] = (isset($attributes['idpCountry']) ? $attributes['idpCountry'][0] : null);
  $claims['idp_name'] = (isset($attributes['schacHomeOrganization']) ? $attributes['schacHomeOrganization'][0] : null);
  $claims['idp_name'] = (isset($attributes['idpName']) ? $attributes['idpName'][0] : null);
  $claims['idp_origin'] = (isset($attributes['businessCategory']) ? $attributes['businessCategory'][0] : null);
  $claims['idp_origin'] = (isset($attributes['idpOrigin']) ? $attributes['idpOrigin'][0] : null);
  $claims['home_town'] = (isset($attributes['homeTown']) ? $attributes['homeTown'][0] : null);

  return $claims;
}

// convenience function
function getUsername($as)
{
        $attributes = $as->getAttributes();
        if (isset($attributes['eduPersonTargetedID']))
        {
            $doc = new DOMDocument();
            $doc->loadXML($attributes['eduPersonTargetedID'][0]);
            $nameID = $doc->getElementsByTagName('NameID');
            foreach ($nameID as $node)
		$username = $as->getAuthData('saml:sp:IdP') . '!' . $node->nodeValue;

        }
        elseif(isset($attributes['subject-id']))
            $username = $attributes['subject-id'][0];
        else
            $username = $as->getAuthData('saml:sp:IdP') . '!anonymous';

	return $username;
}

/*
* Authorization Server App
*/

/**
 * OAuth2 Authorize Endpoint - RFC 6749
**/

$app->GET('/oauth/authorize', function($request, $psr_response, $args=null) use ($server, $app) {
    $this->logger->addDebug('GET /oauth/authorize');

    $request = OAuth2\Request::createFromGlobals();

    $auth_id = '';

    $scope = ($request->query('scope') != null) ? $request->query('scope') : '';
    // Scope is either '' or a string
    $scopes = explode(' ', $scope);
    if (!in_array('openid',$scopes) && !in_array('profile',$scopes) && !in_array('email',$scopes))
        $auth_id = 'oauth';
    else
        $auth_id = 'oidc-profile';

    $this->logger->addDebug("Authenticating with " . $auth_id);
    
    $as = new \SimpleSAML\Auth\Simple($auth_id);
    $login_hint = $request->query('login_hint');
    if ($login_hint)
        $as->requireAuth(array('saml:idp' => $request->query('login_hint')));
    else
        $as->requireAuth();


    $attributes = $as->getAttributes();
    $idp = $as->getAuthData('saml:sp:IdP');
    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);

    $response = new OAuth2\Response();

    if (!$server->validateAuthorizeRequest($request, $response)) {
	$parameters = $response->getParameters();
        $payload = array();
        $payload['title'] = "Application Authorization Error";
        $payload['error_message'] = $parameters['error_description'];
        return $this->renderer->render($psr_response, "/error.php", array('payload' => $payload));
    }

  $this->logger->addDebug("processing /authorize for username: " . $username);
  $client_id = $request->query('client_id');
  $scope = (null != $request->query('scope') ? $request->query('scope') : '');

  $this->logger->addDebug("attributes: " . var_export($attributes,1));

  $claims = attributes2claims($attributes);
  $claims['idp_identifier'] = $idp;
  $claims['scope'] = $scope;
  $claims['auth_time'] = $as->getAuthData('saml:AuthnInstant');

  $this->logger->addDebug("storing claims for username (" . $username . "): " . var_export($claims,1));

  $server->getStorage('user_claims')->setUserClaims($username, $claims);

  if ((strstr($scope, 'profile') === FALSE) and (strstr($scope, 'email') === FALSE))
  {
	// The application has no personal data scopes, so the user must no approve the processing of personal information
        $server->handleAuthorizeRequest($request, $response, true, $username, $auth_id);
        return $psr_response->withRedirect($response->getHttpHeader('Location'));

  }
  else if ($server->getStorage('user_credentials')->checkUserConsent($username, $client_id))
  {
        // The user has previously authorized the application
        $server->handleAuthorizeRequest($request, $response, true, $username, $auth_id);
	return $psr_response->withRedirect($response->getHttpHeader('Location'));
  }
  else
  {
  	// The user must approve the application
  	$client_details = $server->getStorage('client')->getClientDetails($client_id);
  	$payload = $client_details;
  	$payload['scope'] = $scope;
  	$payload['client_id'] = $client_id;
        $payload['csrf'] = md5($client_id.':'.$idp.':'.$username);
	$payload['login_hint'] = $login_hint;

	$personal_data_scopes = array_intersect(explode(' ',$scope), explode(' ', "email profile"));
	$personal_data_claims = "";
	foreach ($personal_data_scopes as $personal_data_scope)
		$personal_data_claims .= ' ' . constant(sprintf('SAML2\Storage\UserClaimsInterface::%s_CLAIM_VALUES', strtoupper($personal_data_scope)));

	$this->logger->addDebug("claims: " . var_export($claims, 1));
	$final_claims = array();
	foreach (explode(' ' ,$personal_data_claims) as $key)
		if (isset($claims[$key]))
			$final_claims[$key] = $claims[$key];

	$this->logger->addDebug("personal_data_claims: " . var_export($final_claims, 1));

  	return $this->renderer->render($psr_response, "/authorize.php", array('payload' => $payload, 'claims' => $final_claims));
  }
});

$app->POST('/oauth/authorize', function($request, $psr_response, $args = null) use ($server, $app) {
    $this->logger->addDebug("POST /oauth/authorize");

    $request = OAuth2\Request::createFromGlobals();

    $auth_id = '';

    $scope = ($request->request('scope') != null) ? $request->request('scope') : '';
    $scopes = explode(' ', $scope);
    if (!in_array('openid',$scopes) && !in_array('profile',$scopes) && !in_array('email',$scopes))
        $auth_id = 'oauth';
    else
        $auth_id = 'oidc-profile';

    $this->logger->addDebug("Authenticating with " . $auth_id);

    $as = new \SimpleSAML\Auth\Simple($auth_id);
    $login_hint = $request->request('login_hint');
    if ($login_hint)
        $as->requireAuth(array('saml:idp' => $login_hint));
    else
        $as->requireAuth();

    $idp = $as->getAuthData('saml:sp:IdP');
    $attributes = $as->getAttributes();

    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);
    $this->logger->addDebug("username: " . $username);

    $client_id = $request->request('client_id');

    if ($request->request('csrf') != md5($client_id.':'.$idp.':'.$username))
    {
        $this->logger->addError("CSRF from request: " . $request->request('csrf'));
        $this->logger->addError("CSRF expected: " . md5($client_id.':'.$idp.':'.$username));
        $payload = array();
        $payload['title'] = "Application Authorization Error";
        $payload['error_message'] = 'This request has invalid CSRF token!';
        return $this->renderer->render($psr_response, "/error.php", array('payload' => $payload));
    }

    $response = new OAuth2\Response();

    if (!$server->validateAuthorizeRequest($request, $response)) {
        $payload = array();
	$payload['title'] = "Application Authorization Error";
        $payload['error_message'] = 'Request to authorize the application failed!';
        return $this->renderer->render($psr_response, "/error.php", array('payload' => $payload));
    }

    $is_authorized = ($request->request('authorized') === 'Yes');
    $agree_privacy = ($request->request('agree_privacy') === 'Yes'); 
    if ($is_authorized && !$agree_privacy)
    {
	$payload = $server->getStorage('client')->getClientDetails($client_id);
        $payload['agree_privacy_message'] = 'Please select!';
        return $this->renderer->render($psr_response, "/authorize.php", array('payload' => $payload));
    }

    if ($is_authorized && isset($username)) {
	$storage = $server->getStorage('user_credentials');

	$client_id =  $request->request('client_id');
        if ( !$server->getStorage('user_credentials')->checkUserConsent($username, $client_id))
        {
    	    $claims = attributes2claims($attributes);
    	    $claims['idp_identifier'] = $idp;
    	    $claims['scope'] = $scope;
            $personal_data_scopes = array_intersect(explode(' ',$scope), explode(' ', "email profile"));
            $personal_data_claims = "";
            foreach ($personal_data_scopes as $personal_data_scope)
                $personal_data_claims .= ' ' . constant(sprintf('SAML2\Storage\UserClaimsInterface::%s_CLAIM_VALUES', strtoupper($personal_data_scope)));
        
            $this->logger->addDebug("claims: " . var_export($claims, 1));
            $final_claims = array();
            foreach (explode(' ' ,$personal_data_claims) as $key)
                if (isset($claims[$key]))
                        $final_claims[$key] = $claims[$key];
        
            $this->logger->addDebug("personal_data_claims: " . var_export($final_claims, 1));
	    $storage->setUserConsent($username, $client_id, $final_claims);
	}

        $server->handleAuthorizeRequest($request, $response, true, $username, $auth_id);
	return $psr_response->withRedirect($response->getHttpHeader('Location'));
    }
    else
    {
        $payload = array();
	$payload['title'] = "Application Authorization Error";
        $payload['error_message'] = 'You did not authorize the application!';
        return $this->renderer->render($psr_response, "/error.php", array('payload' => $payload));
    }
});

/**
 * OAuth2 Token Endpoint - RFC 6749
**/

$app->GET('/oauth/token', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addError("GET /token");
    $origin = $request->getHeader('Origin');
    if ($origin)
    {
	$response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
	$response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    }
    return $response->withStatus(405)->withHeader('Content-Type', 'text/plain')->write('The request method must be POST when requesting an access token as defined in RFC 6749, section-3.2');
});

$app->POST('/oauth/token', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("POST /token");
    $origin = $request->getHeader('Origin');

    $this->logger->addDebug("request: " . var_export(OAuth2\Request::createFromGlobals()->request,1));
    if ($origin)
    {   
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    }

    $data = $server->handleTokenRequest(OAuth2\Request::createFromGlobals())->getParameters();
    $this->logger->addDebug("data: " . var_export($data,1));
    if (! isset($data['error'])) {
      return $response->withJson($data, 200);
    }
    return $response->withJson($data, 400);
});

$app->OPTIONS('/oauth/token', function(Request $request, Response $response) {
    $this->logger->addDebug("OPTIONS /token");
    $origin = $request->getHeader('Origin');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
    $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    $response = $response->withAddedHeader('Access-Control-Allow-Headers', "Authorization");
    $response = $response->withAddedHeader('Access-Control-Max-Age', '86400');

    return $response->withStatus(204);
});

/**
 * Token Revocation Endpoint - RFC 7009
**/

$app->GET('/oauth/tokenrevoke', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addError("GET /tokenrevoke");
    $origin = $request->getHeader('Origin');
    if ($origin)
    {   
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    }
    return $response->withStatus(405)->withHeader('Content-Type', 'text/plain')->write('Please use POST');
});

$app->POST('/oauth/tokenrevoke', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("POST /tokenrevoke");

    $origin = $request->getHeader('Origin');
    if ($origin)
    {   
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    }

    $request = OAuth2\Request::createFromGlobals();
    $oauth_response = new OAuth2\Response();
    $server->handleRevokeRequest($request,$oauth_response);
    if ($oauth_response->getStatusCode() != 200)
    {
	return $response->withJson($oauth_response->getParameters(), $oauth_response->getStatusCode());
    }

    $callback = $request->request('callback');
    $this->logger->addDebug("callback: " . $callback);
    if ($callback != null)
    {
	return $response->withAddedHeader('Content-Type', 'text/plain')->withStatus(200)->write($callback);
    }
    else
    {
    	return $response->withJson($oauth_response->getParameters(), 200);
    }
});

$app->OPTIONS('/oauth/tokenrevoke', function(Request $request, Response $response) {
    $this->logger->addDebug("OPTIONS /tokenrevoke");
    $origin = $request->getHeader('Origin');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
    $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    $response = $response->withAddedHeader('Access-Control-Allow-Headers', "Authorization, Content-Type");
    $response = $response->withAddedHeader('Access-Control-Max-Age', '86400');

    return $response->withStatus(204);
});

/**
 * Token Introspection - RFC 7662
**/


$app->GET('/oauth/tokeninfo', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /tokeninfo");

    $origin = $request->getHeader('Origin');
    if ($origin)
    {
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    }
    return $response->withStatus(405)->withHeader('Content-Type', 'text/plain')->write('Please use POST');

});

$app->POST('/oauth/tokeninfo', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("POST /tokeninfo");

    $origin = $request->getHeader('Origin');
    if ($origin)
    {   
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    }

    $request = OAuth2\Request::createFromGlobals();
    $this->logger->addDebug("request: " . var_export($request,1));
    list($method, $credentials) = explode(' ', $request->headers('Authorization'));
    if (($method != 'Basic') && ($method != 'Bearer'))
    {
      $data['error'] = 'invalid_client';
      $data['error_description'] = 'Client credentials were not found in the headers';
      return $response->withJson($data, 401);
    }

    if ($method == 'Basic')
    {
        list($client_id, $client_secret) = explode(':', base64_decode($credentials));
        $storage = $server->getStorage('client_credentials');
        if (false == $storage->checkClientCredentials($client_id, $client_secret))
        {
          $data['error'] = 'invalid_client';
          $data['error_description'] = 'Client credentials are invalid';
          return $response->withJson($data, 401);
        }
    }
    elseif ($method == 'Bearer')
    {
	$access_token = $server->getStorage('access_token')->getAccessToken($credentials);
	if (!isset($access_token) || (time() > $access_token['expires']))
	{
	  $data = array();
	  $data['error'] = 'invalid_token';
          $data['error_description'] = 'The provided access token in the HTTP header Authorization is invalid';
          return $response->withJson($data, 401);
	}

	if ($credentials == $request->request('token'))
	{
	  $data = array();
	  $data['error'] = 'invalid_token';
          $data['error_description'] = 'The provided access token and the HTTP Authorization token are identical';
          return $response->withJson($data, 400);
	}
    }

    $token = $request->request('token');
    $token_type_hint = $request->request('token_type_hint');
    $data = null;
    if (isset($token_type_hint) && $token_type_hint == 'refresh_token')
    {
        $data = $server->getStorage('refresh_token')->getRefreshToken($token);
    }
    else
    {
        $data = $server->getStorage('access_token')->getAccessToken($token);
        if ($data === false)
        {
            $data = $server->getStorage('refresh_token')->getRefreshToken($token);
        }
    }
   
    if ($data && isset($data['user_id']))
    {
	$data['username'] = $server->getStorage('user_claims')->getUUID($data['user_id']);
        unset($data['user_id']);
    } 
    else
    {
        // we must remove the 'user_id' from the response as it keeps the SAML identifier which is irrelevant / not to be released for OAuth2/OpenID 
        unset($data['user_id']);
    } 

    // remove SAML authentication internal information
    unset($data['auth_id']);
    $data['active'] = ($data && ($data['expires'] > time())) ? true : false;
    return $response->withJson($data, 200);
});

$app->OPTIONS('/oauth/tokeninfo', function(Request $request, Response $response) {
    $this->logger->addDebug("OPTIONS /tonkeninfo");
    $origin = $request->getHeader('Origin');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
    $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    $response = $response->withAddedHeader('Access-Control-Allow-Headers', "Authorization");
    $response = $response->withAddedHeader('Access-Control-Max-Age', '86400');

    return $response->withStatus(204);
});


/**
 * SAML2 specific API
*/

// Obtain attribues released from IdP for current session, stored in SAML2 cookie(s)
$app->GET('/saml/sessioninfo', function(Request $request, Response $response) use ($app) {
    $this->logger->addDebug('GET /saml/sessioninfo');

    $sessioninfo = array();

    /* First we collect attributes for auth source 'auth' */
    $as = new \SimpleSAML\Auth\Simple('oauth');
    if ($as->isAuthenticated())
    {
	$attributes = $as->getAttributes();
	$attributes['entityId'] = $as->getAuthData('saml:sp:IdP');
	$attributes['auth_id'] = 'oauth';
	array_push($sessioninfo, $attributes);
    }

    /* Second we collect attributes from the auth source 'openid' */ 
    $as = new \SimpleSAML\Auth\Simple('oidc-profile');
    if ($as->isAuthenticated())
    {
        $attributes = $as->getAttributes();

        $attributes['username'] = getUsername($as);
        $attributes['entityId'] = $as->getAuthData('saml:sp:IdP');
	$attributes['auth_id'] = 'oidc-profile';
        array_push($sessioninfo, $attributes);
    }

    return $this->renderer->render($response, "/sessioninfo.php", array('payload' => $sessioninfo));
});

// SAML login to support testing the authorization code flow
$app->GET('/saml/login', function(Request $request, Response $response) {
    $this->logger->addDebug("GET /saml/login");

    $as = new \SimpleSAML\Auth\Simple('oauth');
    $as->requireAuth();

    return $response->withRedirect('/api/');
});

// SAML logout does only make sense if executed in a Web Browser with an active user (of whom the session might have already be expired)
$app->GET('/saml/logout', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /saml/logout");

    $request = OAuth2\Request::createFromGlobals();

    $auth_id = $request->query('authid');

    $returnTo = $request->query('return');
    if ($returnTo == null)
    {
	$protocol = ($_SERVER['HTTPS']) ? 'https://' : 'http://';
        $returnTo = $protocol.$_SERVER['SERVER_NAME'].'/saml/sessioninfo';
    }

    $params = array(
                        'ReturnTo' => $returnTo,
                        'ReturnStateParam' => 'LogoutState',
                        'ReturnStateStage' => 'ASLogoutState',
                );
    if ($auth_id)
    {
	$as = new \SimpleSAML\Auth\Simple($auth_id);
	if (!$as->isAuthenticated()) {
            // no active session but we don't care! It is like a successful logout!
            return $response->withRedirect($returnTo);
        }

	$source = $as->getAuthSource();
	$idp = $as->getAuthData('saml:sp:IdP');
        $idpMetadata = $source->getIdPMetadata($idp);
            
        // check for SingleLogoutService
        $endpoint = $idpMetadata->getEndpointPrioritizedByBinding('SingleLogoutService', [ \SAML2\Constants::BINDING_HTTP_REDIRECT, \SAML2\Constants::BINDING_HTTP_POST], false);
        if ($endpoint === false) {
            $payload = array();
            $payload['title'] = "Logout not possible";
            $payload['error_message'] = 'It is NOT possible to log you out from the Identity Provider becaue Logout is not supported!i. You MUST close the Browser to invalide the login!';
            return $this->renderer->render($response, "/error.php", array('payload' => $payload));
        }   

        $as->logout($params);

    }
    else
    {

	// A user can have one or two sessions with the IdP
	$oauth = new \SimpleSAML\Auth\Simple('oauth');
	$openid = new \SimpleSAML\Auth\Simple('oidc-profile');

	if ($oauth->isAuthenticated() && !$openid->isAuthenticated()){
	    // Only a session with "oauth" exists
	    $oauth->logout($params);
	}
	else if (!$oauth->isAuthenticated() && $openid->isAuthenticated()){
	    // Only a session with "openid" exists
	    $openid->logout($params);
	}
	else if (!$oauth->isAuthenticated() && !$openid->isAuthenticated()){
	    // No active session exists
	    return $response->withRedirect($returnTo);
	}
	else {
	    // For the moment not implemented...
            $payload = array();
            $payload['title'] = "Logout problem";
            $payload['error_message'] = 'You have multiple SAML sessions. Logout for multiple SAML sessions is currently not supported. Please close the Browser to remove the session cookies. The active logout sessions cannot be used without the cookies.';
            return $this->renderer->render($response, "/error.php", array('payload' => $payload));
	}
    }

 
});

/**
 * OAuth2 specific API to initiate a SAML2 logout
**/

// Logout from SAML sessions based on an access token
$app->GET('/oauth/logout', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /oauth/logout");

    $request = OAuth2\Request::createFromGlobals();
    $this->logger->addDebug("Request: " . var_export($request,1));

    $token_type_hint = $request->query('token_type_hint');
    $token = $request->query('token');
    $code = $request->query('code');
    if(!$token && !$code)
    {
        $payload = array();
        $payload['title'] = 'Parameter code and token missing';
        $payload['error_message'] = "OAuth2 based logout requires a code or access token parameter in request";
        return $this->renderer->render($response, "/error.php", array('payload' => $payload));
    }

    $redirect = $request->query('return');
    if ($redirect == null)
    {
        $config = $app->getContainer()->get('as');
        $redirect = $config['issuer'].'/logoutcomplete';
    }
  
    $params = array(
    			'ReturnTo' => $redirect,
    			'ReturnStateParam' => 'LogoutState',
    			'ReturnStateStage' => 'ASLogoutState',
		);

    if ($code)
    {
	$this->logger->addDebug("logout with code");
        $data = $server->getStorage('authorization_code')->getAuthorizationCode($code);
	if ($data !== false)
	    $server->getStorage('authorization_code')->expireAuthorizationCode($code);
    }
    elseif ($token_type_hint && $token_type_hint == 'access_token')
    {
	$this->logger->addDebug("logout with access_token");	
	$data = $server->getStorage('access_token')->getAccessToken($token);
	if ($data !== false)
	    $server->getStorage('access_token')->unsetAccessToken($token);
    }
    elseif ($token_type_hint && $token_type_hint == 'refresh_token')
    {
	$this->logger->addDebug("logout with refresh_token");	
        $data = $server->getStorage('refresh_token')->getRefreshToken($token);
	if ($data !== false)
	    $server->getStorage('refresh_token')->unsetRefreshToken($token);
    }
    else
    {
	$this->logger->addDebug("logout with access_token");	
        $data = $server->getStorage('access_token')->getAccessToken($token);
	if ($data !== false)
	    $server->getStorage('access_token')->unsetAccessToken($token);
    }

    $this->logger->addDebug("data: " . var_export($data,1));
    if ($data === false)
    {
        $payload = array();
        $payload['title'] = "Warning: Logout attempt with invalid access token";
        $payload['error_message'] = 'Logout was initiated with an invalid access token! Logout cannot proceed. You must close the application or Web-Browser to complete the logout!';
        return $this->renderer->render($response, "/error.php", array('payload' => $payload));
    }
    else if ($data['auth_id'] == null)
    {
        // This access token was generated from a client credentials grant => No SAML Auth source associated.
	// It is not possible to initiate a ogout based on such an access token
        $payload = array();
        $payload['title'] = "Warning: Logout attempt with invalid access token";
        $payload['error_message'] = 'Logout was initiated with an access token created via the client_credentials grant! Logout not possible!';
        return $this->renderer->render($response, "/error.php", array('payload' => $payload));
    }
    else
    {
        // The access token was generated via the implicit flow => The user might still be online
        // So we need to initiate a proper logout in case the session is still valid
        // here we set the auth source based on the id stored with the access token
        $as = new \SimpleSAML\Auth\Simple($data['auth_id']);
    }

    if (!$as->isAuthenticated()) {
	// no active session but we don't care! It is like a successful logout!
	return $response->withRedirect($redirect);
    }

    $as->logout($params);

});

$app->GET('/logoutcomplete', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /logoutcomplete");

    $request = OAuth2\Request::createFromGlobals();
    $state = $request->query('LogoutState');
    
    $payload = array();
    if ($state)
    {
        $saml_state = SimpleSAML_Auth_State::loadState($state, 'ASLogoutState');
        $ls = $saml_state['saml:sp:LogoutStatus']; /* Only works for SAML SP */
        if ($ls['Code'] === 'urn:oasis:names:tc:SAML:2.0:status:Success' && !isset($ls['SubCode'])) {
            /* Successful logout. */
            $payload['title'] = "Logout complete";
            $payload['message'] = 'You have been logged out.';
            return $this->renderer->render($response, "/info.php", array('payload' => $payload));
        } else {
            /* Logout failed. Tell the user to close the browser. */
            $payload['title'] = "Logout failure";
            $payload['error_message'] = 'We were unable to log you out of all your sessions. To be completely sure that you are logged out, you need to close your web browser.';
            return $this->renderer->render($response, "/error.php", array('payload' => $payload));
        }
    }
    else
    {
        $payload['title'] = "Logout uncertain";
        $payload['message'] = 'We could not find a session to logout from.';
        return $this->renderer->render($response, "/info.php", array('payload' => $payload));
    }
});


/**
 * Authorization Server convenience API for application management
**/


$app->GET('/registerapps', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /registerapps");
    return $this->renderer->render($response, "/registerapps.php", array('payload' => null));

});

$app->POST('/registerapps', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /registerapps");

    // The user must have a unique identifier to manually register an application. => We must use the 'oidc-profile' auth source
    $as = new \SimpleSAML\Auth\Simple('oidc-profile');
    $as->requireAuth();
    $attributes = $as->getAttributes();
    $idp = $as->getAuthData('saml:sp:IdP');
    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);
    if (!isset($username) || strpos($username, 'anonymous') > 0)
    {
        $this->logger->addError("Failed attempt to register with username: " . $username);
        $payload = array();
        $payload['title'] = "Application Registration Error";
        $payload['error_message'] = "You cannot register an appliction because your Home Organization did not identify you. We appologize for the inconvenience.";
        return $this->renderer->render($response, "/error.php", array('payload' => $payload));
    }

    $request = OAuth2\Request::createFromGlobals();

    $software_statement = $request->request('software_statement');
    if (isset( $software_statement))
    {
	// no support to register a client application via a software statement on this endpoint
        $this->logger->addError("No support to register a client application via a software statement on this endpoint");
        $payload = array();
        $payload['title'] = "Application Registration Error";
        $payload['error_message'] = "You cannot register an appliction via a software statement on this endpoint!";
        return $this->renderer->render($response, "/error.php", array('payload' => $payload));
    }

    // we hope to have a regular manual registration form to process
    $payload = $request->request;

    $contacts = $payload['contacts'];
    $iss = $server->getConfig('iss');
    $redirect_uris = $payload['redirect_uris'];
    $grant_types = $payload['grant_types'];
    $client_name = $payload['client_name'];
    $software_version = $payload['software_version'];
    $logo_uri = $payload['logo_uri'];
    $tos_uri = $payload['tos_uri'];
    $policy_uri = $payload['policy_uri'];
    $license_uri = $payload['license_uri'];

    $scope_openid = (isset($payload['openid'])) ? $payload['openid'] : '';
    $payload['openid'] = $scope_openid;
    $scope_profile = (isset($payload['profile'])) ? $payload['profile'] : '';
    $payload['profile'] = $scope_profile;
    $scope_email = (isset($payload['email'])) ? $payload['email'] : '';
    $payload['email'] = $scope_email;
    $scope_saml = (isset($payload['saml'])) ? $payload['saml'] : '';
    $payload['saml'] = $scope_saml;
    $scope_offline = (isset($payload['offline_access'])) ? $payload['offline_access'] : '';
    $payload['offline_access'] = $scope_offline;

    if (($scope_profile != '')||($scope_email != '')) {
        if (($payload['agree_tos'] != "checked") && (!isset($payload['agree_privacy'])))
        {
	    // The user has not clicked one of the two or both options agree_tos and/or agree_privacy
	    return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => 'you must agree to the Terms of Use and the Privacy Statement of this Service'));
        }
    } else {
	if ($payload['agree_tos'] != "checked")
        {
            // The user has not clicked agree_tos 
            return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => 'you must agree to the Terms of Use of this Service'));
        }
    }
	
    $software_id = md5($client_name . '+' . $software_version . '@' . $server->getConfig('iss'));
    $client_id = substr($software_id, 0, 8 ) .'-'.  substr($software_id, 8, 4) .'-'.  substr($software_id, 12, 4) .'-'.  substr($software_id, 16, 4) .'-'.  substr($software_id, 20);

    $scope = '';
    if ($scope_openid != '' || $scope_profile != '' || $scope_email != '') {
	$scope = 'openid';
        if ($scope_profile != '')
	  $scope .= ' ' .$scope_profile; 
	if ($scope_email != '')
	  $scope .= ' ' .$scope_email; 
	if ($scope_offline != '')
	  $scope .= ' ' .$scope_offline; 
    }
    if ($scope_saml != '')
	$scope .= ' ' .$scope_saml;

    if ($scope_offline != '')
	$scope .= ' ' .$scope_offline;
    
    $storage = $server->getStorage('client_credentials');

    $client_details = $storage->getClientIds();
    if (isset($client_details) && array_search($client_id, array_column($client_details,'client_id'))) {
	// this application is already registered. User must provide a different name or version for registration
        $global_error_message = "This application identified by its name and version is already registered. You cannot update that registration.";
        $client_name_message = "Please choose another name or provide a new version number";
        $software_version_message = "Please choose another version number or provide a new name";

	return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'client_name_message' => $client_name_message, 'software_version_message' => $software_version_message));
    }

    // check that tos_uri, poliy_uri exist
    $host = parse_url($tos_uri, PHP_URL_HOST);
    if (checkdnsrr($host, "A") === false) {
      // The domain does not exist - probably handle this as if it were a 404
	$global_error_message = "This application's Terms of Use could not be resolved.";
        $tos_uri_message = "The provided hostname does not seem to exit";
        return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'tos_uri_message' => $tos_uri_message));
    } else {
        $context = stream_context_create( array( 'http' => array( 'method' => 'HEAD', 'timeout' => 1, 'header' => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\nAccept-Encoding: gzip,deflate\r\nAccept-Charset:UTF-8,*;q=0.5\r\nUser-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:19.0) Gecko/20100101 Firefox/19.0 FirePHP/0.4\r\n')));
        $content = @file_get_contents($tos_uri, false, $context);

        if (in_array('200 OK', $http_response_header)) {
            $global_error_message = "This application's Terms of Use could not be resolved.";
	    $tos_uri_message = "The provided URL does not return an HTTP status of 200. Status code was '" . $http_response_header[0] . "'. Please provide a valid URL that resolves directly to the Terms of Use";
	    return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'tos_uri_message' => $tos_uri_message));
        }
    }

    if (($scope_profile != '') || ($scope_email != ''))
    {
      $host = parse_url($policy_uri, PHP_URL_HOST);
      if (checkdnsrr($host, "A") === false) {
      // The domain does not exist - probably handle this as if it were a 404
        $global_error_message = "This application's Privacy Statement could not be resolved.";
        $policy_uri_message = "The provided hostname does not seem to exit";
        return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'policy_uri_message' => $policy_uri_message));
      } else {
        $context = stream_context_create( array( 'http' => array( 'method' => 'HEAD', 'timeout' => 1, 'header' => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\nAccept-Encoding: gzip,deflate\r\nAccept-Charset:UTF-8,*;q=0.5\r\nUser-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:19.0) Gecko/20100101 Firefox/19.0 FirePHP/0.4\r\n')));
        $content = @file_get_contents($policy_uri, false, $context);

        if (in_array('200 OK', $http_response_header)) {
            $global_error_message = "This application's Privacy Statemeent could not be resolved.";
            $policy_uri_message = "The provided URL does not return an HTTP status of 200. Status code was '" . $http_response_header[0] . "'. Please provide a valid URL that resolves directly to the Privacy Statement";
            return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'policy_uri_message' => $policy_uri_message));
        }
      }
    }

    $host = parse_url($license_uri, PHP_URL_HOST);
    if ($host != "creativecommons.org") {
      // The domain does not exist - probably handle this as if it were a 404
        $global_error_message = "This application's License is not hosted at 'creativecommons.org'.";
        $license_uri_message = "The provided hostname must be 'creativecommons.org'";
        return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'license_uri_message' => $license_uri_message));
    } else {
        $context = stream_context_create( array( 'http' => array( 'method' => 'HEAD', 'timeout' => 1, 'header' => 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\nAccept-Encoding: gzip,deflate\r\nAccept-Charset:UTF-8,*;q=0.5\r\nUser-Agent: Mozilla/5.0 (X11; Linux x86_64; rv:19.0) Gecko/20100101 Firefox/19.0 FirePHP/0.4\r\n')));
        $content = @file_get_contents($license_uri, false, $context);

        if (in_array('200 OK', $http_response_header)) {
            $global_error_message = "This application's License could not be resolved.";
            $license_uri_message = "The provided URL does not return an HTTP status of 200. Status code was '" . $http_response_header[0] . "'. Please provide a valid URL that resolves directly to the Privacy Statement";
            return $this->renderer->render($response, "/registerapps.php", array('payload' => $payload, 'global_error_message' => $global_error_message, 'license_uri_message' => $license_uri_message));
        }
    }

    if (strpos($grant_types, 'implicit') !== false) {
    	$clientDetails = $storage->setClientDetails($client_id, null, $redirect_uris, $grant_types, $scope, $username, $payload);
    	// we must not disclose the client_secret as this is a Web-Application and therefore a public client!
	return $this->renderer->render($response, "/listapp.php", array('client_id' => $client_id, 'redirect_uris' => $redirect_uris, 'grant_types' => $grant_types, 'scopes' => $scope));
    }
    else {
    	$client_secret = bin2hex(openssl_random_pseudo_bytes(32));
    	$clientDetails = $storage->setClientDetails($client_id, $client_secret, $redirect_uris, $grant_types, $scope, $username, $payload);
        // this is a confidential client that is capable of securely storing the client_secret!
	return $this->renderer->render($response, "/listapp.php", array('client_id' => $client_id, 'client_secret' => $client_secret, 'redirect_uris' => $redirect_uris, 'grant_types' => $grant_types, 'scopes' => $scope));
    }

});

$app->GET('/listapps', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /listapps");

    // The user must have a unique identifier to list his application(s). => We must use the 'oidc-profile' auth source
    $as = new \SimpleSAML\Auth\Simple('oidc-profile');
    $as->requireAuth();
    $attributes = $as->getAttributes();
    $idp = $as->getAuthData('saml:sp:IdP');
    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);
    $this->logger->addDebug("username: " . $username);

    $this->logger->addDebug("GET /listapps");
    $storage = $server->getStorage('client_credentials');

    $client_details = $storage->getUserClientsDetails($username);

    return $this->renderer->render($response, "/listapps.php", array('secret' => $server->getConfig('secret'), 'client_details' => $client_details));

});

$app->GET('/authorizedapps', function(Request $request, Response $response) use ($server, $app) {
    $this->logger->addDebug("GET /authorizedapps");

    // The user must have a unique identifier to list authorized application(s). => We must use the 'oidc-profile' auth source
    $as = new \SimpleSAML\Auth\Simple('oidc-profile');
    $as->requireAuth();
    $attributes = $as->getAttributes();
    $idp = $as->getAuthData('saml:sp:IdP');
    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);
    $this->logger->addDebug("username: " . $username);

    $storage = $server->getStorage('user_credentials');
    $consent_details = $storage->getUserConsent($username);

    return $this->renderer->render($response, "/authorizedapps.php", array('consent_details' => $consent_details));

});

$app->DELETE('/authorizedapps/{client_id}', function(Request $request, Response $response, $args) use ($server, $app) {
    $this->logger->addDebug("DELETE /authorizedapps/".$args['client_id']);

    if ($args == null)
    {
	$payload = array();
       	$payload['title'] = "Parameter mising";
       	$payload['error_message'] = "a required parameter was missing";
       	return $this->renderer->render($psr_response, "/error.php", array('payload' => $payload));
    }

    // The user must have a unique identifier to delete authorization for application(s). => We must use the 'oidc-profile' auth source
    $as = new \SimpleSAML\Auth\Simple('oidc-profile');
    $as->requireAuth();
    $attributes = $as->getAttributes();
    $idp = $as->getAuthData('saml:sp:IdP');
    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);
    $this->logger->addDebug("username: " . $username);

    $storage = $server->getStorage('user_credentials');

    $storage->revokeUserConsent($username, $args['client_id']);

    $consent_details = $storage->getUserConsent($username);

    return $this->renderer->render($response, "/authorizedapps.php", array('consent_details' => $consent_details));
});

$app->DELETE('/logoutapps/{client_id}', function(Request $request, Response $response, $args) use ($server, $app) {
    $this->logger->addDebug("DELETE /logoutapps/".$args['client_id']);

    if ($args == null)
    {
        $payload = array();
        $payload['title'] = "Parameter mising";
        $payload['error_message'] = "a required parameter was missing";
        return $this->renderer->render($psr_response, "/error.php", array('payload' => $payload));
    }

    // The user must have a unique identifier to logout from a device running an application(s). => We must use the 'oidc-profile' auth source
    $as = new \SimpleSAML\Auth\Simple('oidc-profile');
    $as->requireAuth();
    $attributes = $as->getAttributes();
    $idp = $as->getAuthData('saml:sp:IdP');
    $this->logger->addDebug("entityId: " . $idp);
    $attributes['idpIdentifier'] = array($idp);

    $username = getUsername($as);
    $this->logger->addDebug("username: " . $username);

    $storage = $server->getStorage('refresh_token');

    $this->logger->addDebug("username: " . $username);
    $this->logger->addDebug("client_id: " . $args['client_id']);
    $refresh_tokens = $storage->getRefreshTokens($username, $args['client_id']);
    $number_of_tokens = count($refresh_tokens);
    $this->logger->addDebug("number_of_tokens: " . $number_of_tokens);
    if ($number_of_tokens > 0)
    {
    $this->logger->addDebug("refresh_tokens: " . var_export($refresh_tokens, 1));
	foreach ($refresh_tokens as $refresh_token)
		foreach ($refresh_token as $key => $token)
		{
			$this->logger->addDebug("unsetting refresh token: " . $token);
			$storage->unsetRefreshToken($token);
		}
	}

        $consent_details = $storage->getUserConsent($username);

        return $this->renderer->render($response, "/authorizedapps.php", array('consent_details' => $consent_details));
});


$app->GET('/listoperators', function(Request $request, Response $response) use ($server, $app) {
        $this->logger->addDebug("GET /listoperators");

        $storage = $server->getStorage('client_credentials');
        $operator_list = $storage->getClientOperators();

        return $this->renderer->render($response, "/listoperators.php", array('payload' => $operator_list));
});

/**
 * OpenID Connect UserInfo
**/
$app->MAP(['GET', 'POST'], '/openid/userinfo', function(Request $request, Response $response) use ($server, $app) {
    $origin = $request->getHeader('Origin');
    if ($origin)
    {
        $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
        $response = $response->withAddedHeader('Access-Control-Allow-Methods', "GET, POST, OPTIONS");
    }

    $oauth_request = OAuth2\Request::createFromGlobals();
    if($request->isGet()) {
	$this->logger->addDebug("GET /openid/userinfo");
        $client_id = $oauth_request->query('client_id');
        $client_secret = $oauth_request->query('client_secret');
    }

    if($request->isPost()) { 
        $this->logger->addDebug("POST /openid/userinfo");
        $client_id = $oauth_request->request('client_id');
        $client_secret = $oauth_request->request('client_secret');
    }
  
    $oauth_response = new OAuth2\Response();
    $token_data = $server->getAccessTokenData($oauth_request, $oauth_response);
    if($token_data == NULL)
    {
        $headers = $oauth_response->getHttpHeaders();
        foreach ($headers as $key => $value)
            $response = $response->withAddedHeader($key, $value);

        return $response->withStatus($oauth_response->getStatusCode())->withAddedHeader('Content-Type', 'text/plain')->write($oauth_response->getStatusText());
    }

    $storage = $server->getStorage('client_credentials');
    // if the access token was created via the client_credentials grant, there is no active user associated with the access token
    if($storage->checkRestrictedGrantType($token_data['client_id'], 'client_credentials'))
    {
	$data = array('error' => 'invalid_token', 'error_description' => 'The access token was created via the Client Credentials Grant and therefore cannot be used to request user claims');
	return $response->withStatus(401)->withHeader('Content-Type', 'application/json')->write(json_encode($data));
    }

    $data = $server->handleUserInfoRequest($oauth_request)->getParameters();
    if (isset($data['error']))
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json')->write(json_encode($data));


    // reduce the scopes to the intersection between the registered scopes for the client_id and the 
    $token_scopes = explode(' ', $token_data['scope']);
    if ($client_id)
        $registered_scopes = $scopes = explode(' ',$storage->getClientScope($client_id));
    else
	$registered_scopes = $token_scopes;

    $remaining_scopes = array_intersect($token_scopes, $registered_scopes);
    
    if ($client_id && !in_array('openid', $registered_scopes))
    {   
        $data = array('error' => 'insufficient_scope', 'error_description' => 'The request requires higher privileges than provided by the requesting application');
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode($data));
    }
    elseif (!in_array('openid', $token_scopes))
    {   
        $data = array('error' => 'insufficient_scope', 'error_description' => 'The request requires higher privileges than provided by the access token');
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json')->write(json_encode($data));
    }

    $remaining_claims = '';
    foreach ($remaining_scopes as $scope)
    {
        $remaining_claims .= ' ' . constant(sprintf('SAML2\Storage\UserClaimsInterface::%s_CLAIM_VALUES', strtoupper($scope)));
    }
    $remaining_claims = explode(' ', $remaining_claims);

    foreach ($data as $key => $value)
    {
        // create effective list of claims
        if (!in_array($key, $remaining_claims))
            unset($data[$key]);

        // remove all claims that have no value
        if (!isset($value))
            unset($data[$key]);
    }

    if (true == $storage->checkClientCredentials($client_id, $client_secret))
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write(json_encode($data));           

    $jwt = new JOSE_JWT($data);
    $jws = $jwt->sign($server->getStorage('public_key')->getPrivateKey(), 'RS256');
    $jwe = new JOSE_JWE($jws->toString());
    $jwe = $jwe->encrypt($server->getConfig('secret'),'dir');

    return $response->withStatus(200)->withHeader('Content-Type', 'application/jwt')->write($jwe->toString());
});

$app->OPTIONS('/openid/userinfo', function(Request $request, Response $response) {
    $this->logger->addDebug("OPTIONS /userinfo");
    $origin = $request->getHeader('Origin');
    $response = $response->withAddedHeader('Access-Control-Allow-Origin', $origin);
    $response = $response->withAddedHeader('Access-Control-Allow-Methods', "POST, OPTIONS");
    $response = $response->withAddedHeader('Access-Control-Allow-Headers', "Authorization");
    $response = $response->withAddedHeader('Access-Control-Max-Age', '86400');

    return $response->withStatus(204);
});


/**
 * OpenID Connect Discovery
**/
$app->GET('/.well-known/openid-configuration', function(Request $request, Response $response) use ($server, $app) {
        $this->logger->addDebug("GET /.well-known/openid-configuration");

	$config = $app->getContainer()->get('as');
	$issuer = $config['issuer'];

        $json = str_replace("ISSUER", $issuer, file_get_contents($config['openid_configuration_file']));
	
	$origin = $request->getHeader('Origin');
 	if ($origin)
	    return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withAddedHeader('Access-Control-Allow-Origin', $origin)->write($json);
	else
	    return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write($json);
});

$app->GET('/.well-known/jwks.json', function(Request $request, Response $response) use ($server, $app) {

        $this->logger->addDebug("GET /.well-known/jwks.json");

        $config = $app->getContainer()->get('as');
        $issuer = $config['issuer'];

	$json = file_get_contents($config['jwks_file']);
        $origin = $request->getHeader('Origin');
        if ($origin)
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withAddedHeader('Access-Control-Allow-Origin', $origin)->write($json);
        else
            return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->write($json);
});


/**
 * GDPR convenience API
**/
$app->GET('/NoPrivacyStatement', function(Request $request, Response $response) use ($server, $app) {

        $this->logger->addDebug("GET /NoPrivacyStatement");

        return $this->renderer->render($response, "/no_privacy_statement.php");

});

$app->GET('/PrivacyStatement', function(Request $request, Response $response) {
        $this->logger->addDebug("GET /PrivacyStatement");

        return $this->renderer->render($response, "/privacy_statement.php");

});

$app->GET('/CookieStatement', function(Request $request, Response $response) {
        $this->logger->addDebug("GET /CookieStatement");

        return $this->renderer->render($response, "/cookie_statement.php");

});

$app->GET('/TermsOfUse', function(Request $request, Response $response) {
        $this->logger->addDebug("GET /TermsOfUse");

        return $this->renderer->render($response, "/terms_of_use.php");

});

$app->GET('/IdPs', function(Request $request, Response $response) {
        $this->logger->addDebug("GET /IdPs");

        return $this->renderer->render($response, "/idps.php");

});

$app->GET('/Operators', function(Request $request, Response $response) use ($server) {
        $this->logger->addDebug("GET /Operators");

	$storage = $server->getStorage('client_credentials');
	$operators = $storage->getClientOperators();

        return $this->renderer->render($response, "/listoperators.php", array('payload' => $operators));

});

$app->GET('/DiscoveryService', function(Request $request, Response $response) use ($app) {
        $this->logger->addDebug("GET /DiscoveryService");

        $config = $app->getContainer()->get('as');
        $this->logger->addDebug("redirect: " . $config['ds_url']);
        if (empty($config['ds_url']))
            return $response->withStatus(500)->withHeader('Content-Type', 'text/plain')->write('Server misconfiguration');

        return $response->withRedirect($config['ds_url']);
});

$app->run();
