<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$config = array(
    'iss' => $_SERVER['SERVER_NAME'],
    'issuer' => 'https://' . $_SERVER['SERVER_NAME'],
    'ds_url' => 'https://ds.sddi.secure-dimensions.de/WAYF',
    'allow_implicit' => true,
    'access_lifetime' => 1800, // 30 minutes
    'refresh_token_lifetime' => 2592000, // 30 days
    'always_issue_new_refresh_token' => true, 
    'unset_refresh_token_after_use' => false,
    'use_openid_connect' => true,
    'use_jwt_access_tokens' => false,
    'store_encrypted_token_string' => false,
    'claims_table' => 'claims',
    'code_table' => 'authorization_codes',
    'client_table' => 'clients',
    'access_token_table' => 'access_tokens',
    'refresh_token_table' => 'refresh_tokens',
    'consent_table' => 'consents',
    'require_exact_redirect_uri' => true,
    'www_realm' => 'Authorization Server',
    'secret' => 'r8922jv0rumr8ruc20nruv0r', // used to generate the cryptoname
    'private_key' => '../pki/AS_Private_Key.pem',
    'public_key' => '../pki/AS_Public_Key.pem',
    'jwks_file' => '../pki/jwks.json',
    'templates_dir' => './templates',
    'logfile' => '../log/as.log',
    'openid_configuration_file' => '../config/openid_configuration.json',
    'PDO' => array('dsn' => 'mysql:dbname=samlas;host=127.0.0.1', 'username' => 'php', 'password' => 'password'),
    //'PDO' => array('dsn' => 'pgsql:dbname=samlas;host=127.0.0.1;port=5432', 'username' => 'php', 'password' => 'password'),
    'create_db' => true,
    'create_test_clients' => true,
); 

?>
