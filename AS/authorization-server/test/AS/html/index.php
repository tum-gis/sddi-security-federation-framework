<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

    require '../vendor/autoload.php';
    use Michelf\MarkdownExtra as Markdown; 

    header('Access-Control-Allow-Origin: *');

    if(!isset($_ENV['OPENID_CONFIGURATION']))
    {
      echo '<font color="red">USAGE: Missing ENV Variable <b>OPENID_CONFIGURATION</b>. Please use <b>export OPENID_CONFIGURATION=https://DOMAINNAME/.well-known/openid-configuration</b> of the Authorization Server under test to enable automatic configuration of the endpoints to be tested.</font>';
      return;
    }

    $openid_configuration_url = $_ENV['OPENID_CONFIGURATION'];
    // configure the endpoints to be tested
    $openid_configuration = doGET($openid_configuration_url);
    if (isset($openid_configuration['message']))
    {
      $message = $openid_configuration['message'];
      echo '<font color="red">Error retrieving OpenID Configuration: <b>' . $message . '</b></font>';
      return;
    }
    
    $issuer = $openid_configuration['issuer'];
  
    $authorize_endpoint = $openid_configuration['authorization_endpoint'];
    $token_endpoint = $openid_configuration['token_endpoint'];
    $revoke_endpoint = $openid_configuration['revoke_endpoint'];
    $userinfo_endpoint = $openid_configuration['userinfo_endpoint'];
    $introspect_endpoint = $openid_configuration['introspect_endpoint'];

?>
<html>
<head>
    <link rel="shortcut icon" href="https://www.secure-dimensions.de/images/SD.ico" type="image/ico">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" integrity="sha384-9gVQ4dYFwwWSjIDZnLEWnxCjeSWFphJiwGPXr1jddIhOegiu1FwO5qRGvFXOdJZ4"
        crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: 'Open Sans', sans-serif;
        }

        .header {
            padding-top: 2rem;
            padding-bottom: 2rem;
            background-color: white;
            font-weight: 600;
        }

        .header img {
            height: 50px;
        }

        .content {
            background-color: #eee;
            padding-top: 2rem;
            padding-bottom: 2rem;
            border-top: 1px solid silver;
            border-bottom: 1px solid silver;
        }

        .content .titlerow {
            padding-bottom: 2rem;
        }

        .content .attributes {
            font-weight: 300;
            font-size: small;
        }

        .content .application-logo {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

	.content .application-name {
    	    font-weight: 600;
    	    font-size: large;
    	    padding-top: 1rem;
    	    padding-bottom: 1rem;
	}

        form .custom-control-label {
            padding-left: 1.2rem;
	}
        form .custom-control-label:before {
            background-color: lightskyblue;
            width: 2rem;
            height: 2rem;
        }

        form .custom-control-label::after {
            width: 2rem;
            height: 2rem;
        }

        form #approval-buttons {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        form #approval-buttons input {
            display: inline-block;
            margin-right: 2rem;
            width: 90px;
            height: 60px;
        }

        .footer {
            padding-top: 2rem;
            padding-bottom: 2rem;
            background-color: #f5f5f5;
            font-weight: 300;
            font-size: small;
        }

table {
    border-collapse: separate;
    border-spacing: 2px;
}
table, th, td {
  padding: 8px 2px;
  border: 1px solid black;
  table-layout: fixed;
  width:100%;
  word-break: break-all;
}
</style>
  <script type="text/javascript">
    function getFragmentParameter(name) {
        name = name.replace(/[\[\]]/g, '\\$&');
        // parsing fragment
        var regex = new RegExp('[#?&]' + name + '(=([^&]*)|&|$)'),
            results = regex.exec(window.location.hash);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, ' '));
    };

    function parseJWT (token) {
        var base64Url = token.split('.')[1];
        var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        var jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));

        return JSON.parse(jsonPayload);
    };

    function processFragment() {
        document.getElementById("access_token").innerHTML = getFragmentParameter('access_token');
        document.getElementById("expires_in").innerHTML = getFragmentParameter('expires_in');
        document.getElementById("token_type").innerHTML = getFragmentParameter('token_type');
        document.getElementById("scope").innerHTML = getFragmentParameter('scope');
        document.getElementById("state").innerHTML = getFragmentParameter('state');
        document.getElementById("id_token").innerHTML = getFragmentParameter('id_token');

        var issuer;
	var id_token = getFragmentParameter('id_token');
	var jwt;
        if (id_token != null)
        {
          jwt = parseJWT(id_token);   
          var tblBody = document.getElementById("id_token_table_body");
          Object.keys(jwt).forEach(function(key) {
	    if (key == 'iss')
	    {
		issuer = jwt[key];
	    }
            var row = document.createElement("tr");

            var cell = document.createElement("td");
            var cellText = document.createTextNode(key);
            cell.appendChild(cellText);
            row.appendChild(cell);

            var cell = document.createElement("td");
            var cellText = document.createTextNode(jwt[key]);
            cell.appendChild(cellText);
            row.appendChild(cell);

            tblBody.appendChild(row);
          });
        }

	if (getFragmentParameter('state') == '952cf7e3-be59-d56e-1177-7cde1233e920')
	{
	    // session with SP openid active
	    document.getElementById("logout").href = issuer + '/saml/logout?auth_id=oidc-profile';
	    document.getElementById("logout").innerHTML = "logout with single session via auth_id=openid";
	}
	else if (getFragmentParameter('state') == '2dbbfbf0-cfda-2860-99ff-552278db2e71')
        {
	    // one session with SP active
	    document.getElementById("logout").href = issuer + '/saml/logout';
	    document.getElementById("logout").innerHTML = "logout with single session via cookies";
        }
	else if (getFragmentParameter('state') == 'multi')
	{
            // one session already active, initiate second session
            document.getElementById("logout").href = issuer + '/saml/logout';
            document.getElementById("logout").innerHTML = "logout with multiple sessio via cookies";
        }
        else if (getFragmentParameter('state') == 'revoke_own')
        {
	    // initiate token revocation authenticating with own client_id 
 	    document.getElementById("revokeH2").innerHTML = 'Revoke Response';
	    var xhr = new XMLHttpRequest();
            var url = issuer + '/oauth/tokenrevoke';
	    var body = 'token_type_hint=access_token&token=' + getFragmentParameter('access_token') + '&client_id=' + jwt['aud'];
	    console.log('body: ' + body);
	    xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
    		if (xhr.readyState == 4) {
		    if (xhr.status == 200)
        		document.getElementById("revoke").innerHTML = JSON.parse(xhr.responseText)['revoked'];
		    else
			document.getElementById("revoke").innerHTML = 'unauthorized';
    		}
	    }
            xhr.send(body);
	}
        else if (getFragmentParameter('state') == 'revoke_none')
        {
            // initiate token revocation authenticating with NO client_id 
 	    document.getElementById("revokeH2").innerHTML = 'Revoke Response';
            var xhr = new XMLHttpRequest();
            var url = issuer + '/oauth/tokenrevoke';
            var body = 'token_type_hint=access_token&token=' + getFragmentParameter('access_token');
            console.log('body: ' + body);
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
		    if (xhr.status == 200)
                        document.getElementById("revoke").innerHTML = JSON.parse(xhr.responseText)['revoked'];
		    else
			document.getElementById("revoke").innerHTML = 'unauthorized';
                }
            }
            xhr.send(body);
        }
 	else
	{
	    // session with SP oauth and openid active
	    document.getElementById("logout").href = getFragmentParameter('state');
	    document.getElementById("logout").innerHTML = "initiate second session";
	}
    };
  </script>
</head>
<body>
<div class="header">
    <div class="container">
       <div class="row">
           <div class="col-3">
               <a href="https://www.secure-dimensions.de"><img src="https://www.secure-dimensions.de/images/SD.gif" class="float-left"/></a>
           </div>
           <div class="col-7">
               <font face="Britannic Bold" color="#008000" size="6"><b>Secure Dimensions GmbH</b></font><br>
               <font face="Britannic Bold" color="#008000" size="3"><b>Holistic Geosecurity</b></font>
           </div>
        </div>
    </div>
</div>
<div class="content">
  <div class="container" id="content">

<?php

  function doPOST($url, $postdata, $authorization = null)
  {

    if ($authorization == null)
      $opts = array(
        'http' => [   
            'method'  => 'POST',
            'header'  => array("Content-Type: application/x-www-form-urlencoded","Content-Length: " . strlen($postdata)),
            'ignore_errors' => true,
            'content' => $postdata
        ],
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
            'verify_depth'      => 0
        ]
      );
    else
      $opts = array(
        'http' => [
            'method'  => 'POST',
            'header'  => array("Content-Type: application/x-www-form-urlencoded","Content-Length: " . strlen($postdata), $authorization),
            'ignore_errors' => true,
            'content' => $postdata
        ],
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
            'verify_depth'      => 0
        ]
      );


    $context  = stream_context_create($opts);
    $response = file_get_contents($url, false, $context);
    return json_decode($response, TRUE);
  }

  function doGET($url, $authorization = null)
  {

    if ($authorization == null)
      $opts = array(
        'http' => [
            'method'  => 'GET',
            'header'  => array("Accept: application/json"),
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
            'verify_depth'      => 0
        ]
      );
    else
      $opts = array(
        'http' => [
            'method'  => 'GET',
            'header'  => array("Accept: application/json"),
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
            'verify_depth'      => 0
        ]
      );


    $context  = stream_context_create($opts);
    return json_decode(file_get_contents($url, false, $context), TRUE);
  }

  function array2table($response)
  {
    echo '<table><thead><tr><th>key</th><th>value</th><tbody>';

    if (is_array($response))
        foreach($response as $key => $value)
        {
	    if (is_array($value))
		$value = var_export($value,1);

	    if (is_bool($value))
                $value = ($value === true) ? 'true' : 'false';

            echo '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
        }
    echo '</tbody></table>';

  }

  if (substr($_SERVER['REQUEST_URI'], 0, strlen('/web-app/')) === '/web-app/')
  {
    // Testing Implicit Flow
  ?>
<h1>Authorization Server Response</h1>

<!-- http://127.0.0.1:4711/web-app/#access_token=becd90747d057ea4ac52d5777c418bcb330f3ee7&expires_in=1800&token_type=bearer&scope=saml&state=f8910358-fed1-3d49-8183-913ddede237e -->
<h2>Result from the /oauth/authorize endpoint</h2>
  <table>
    <thead><tr><th>key</th><th>value</th></tr></thead>
    <tbody>
      <tr><td>access_token</td><td><span id="access_token"/></td></tr>
      <tr><td>expires_in</td><td><span id="expires_in"/></td></tr>
      <tr><td>token_type</td><td><span id="token_type"/></td></tr>
      <tr><td>scope</td><td><span id="scope"/></td></tr>
      <tr><td>state</td><td><span id="state"/></td></tr>
      <tr><td>id_token</td><td><span id="id_token"/></td></tr>
    </tbody>
  </table>

<h2>ID Token</h2>
  <table>
        <thead><tr><th>key</th><th>value</th></tr></thead>
        <tbody id="id_token_table_body">
        </tbody>
  </table>

<h2 id="revokeH2"></h2>
  <div id="revoke"/>

  <script type="text/javascript">
    window.onload = processFragment;
  </script>
  <?php
  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/introspect/')) === '/introspect/')
  {
    // Testing Client Credentials Flow

    parse_str($_SERVER['QUERY_STRING'], $params);

    echo '<h2>Query String Parameters</h2>';
    array2table($params);

    $client_id = $params['client_id'];
    $client_secret = $params['client_secret'];
    $scope = $params['scope'];

    // get an access token with client_id and client_secret from query string
    $postdata = http_build_query(
      array(
        'grant_type' => 'client_credentials',
        'response_type' => 'token',
        'scope' => $scope,
        'client_id' => $client_id,
        'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata);

    echo '<h3>Response from Token endpoint requesting an Access Token for client credentials ' . $client_id .':' .$client_secret . '</h3>';
    array2table($response);

    $access_token = $response['access_token'];

    // prepare POST data for tokeninfo request with access token
    $postdata = http_build_query(
      array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );
 

    $test = $params['test'];
    if ($test == 'client_credentials')
    {
	// Use client credentials for identification
        $authorization = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);
        $response = doPOST($introspect_endpoint, $postdata, $authorization);
        echo '<h3>Response from Token Introspection endpoint about Access Token leveraging client credentials for identification</h3>';
        array2table($response);
    }
    elseif ($test == 'bearer')
    {
	// Use same access token for identification and introspection
        $authorization = "Authorization: Bearer " . $accss_token;
        $response = doPOST($introspect_endpoint, $postdata, $authorization);
        echo '<h3>Response from Token Introspection endpoint about Access Token using <b>same</b> access token for identification and introspection</h3>';
        array2table($response);

	// Request and access token using the Service App - Level Cryptoname 
	$data = http_build_query(
          array(
            'grant_type' => 'client_credentials',
            'response_type' => 'token',
            'client_id' => '1dbd9518-7624-950e-5f0b-cdb61a66f9fc',
            'client_secret' => '8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7'
          )
        );

        $response = doPOST($token_endpoint, $data);
  
        $authorization = "Authorization: Bearer " . $response['access_token'];
        $response = doPOST($introspect_endpoint, $postdata, $authorization);
  
        echo '<h3>Response from Token Introspection endpoint about Access Token using <b>different</b> access token for identification and introspection</h3>';
        array2table($response);

    }
    else
    {
        echo '<font color="red">Invalid test specified</font>';
	return;
    }
  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/refresh-app/')) === '/refresh-app/')
  {
    // Testing Authorization Code Flow and Refresh Token Grant

    // fetch the parameter from the query string
    parse_str($_SERVER['QUERY_STRING'], $params);

    $scope = $params['scope'];

    $client_id = $params['state'];
    if ($client_id == '2bd0defa-9919-945c-18f1-a16a37fa2881')
        $client_secret = 'c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3';
    elseif ($client_id == '9aa391cd-ea50-8a70-545a-1c51879a1dd5')
        $client_secret = '9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5';
    elseif ($client_id == '8d86fcb1-f720-3a95-1070-0a4eb8d050ab')
        $client_secret = '210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc';
    elseif ($client_id == '1585cd2b-c8aa-1179-a6aa-b55fb9024802')
        $client_secret = '34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61';
    elseif ($client_id == '0a0036ff-52fa-a2ad-c884-af80ae377730')
        $client_secret = '3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc';
    else
    { 
      echo '<font color="red">Error running the tests for unknown client_id</font>';
      return;
    }

    echo '<h2>Result from the /oauth/authorize endpoint</h2>';
    echo '<h3>Query String Parameters</h3>';
    array2table($params);

    if (isset($params['id_token']))
    { 
      echo '<h3>ID Token</h3>';
      list($header, $payload, $signature) = explode('.', $params['id_token']);
      $payload = json_decode(base64_decode($payload),true);
      array2table($payload);
    }
    // set the Authorization Header for subsequent requests
    $authorization = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);

    // get an access token for the authorization code
    $postdata = http_build_query(
      array(
        'code' => $params['code'],
        'grant_type' => 'authorization_code',
        'response_type' => 'token',
        'redirect_uri' => 'http://127.0.0.1:4711/refresh-app/',
        'client_id' => $params['state'],
        'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token endpoint requesting an Access Token for Authorization Code ' . $params["code"] . '</h3>';
    array2table($response);

    $access_token = $response['access_token'];
    $refresh_token = $response['refresh_token'];

    // get tokeninfo for access token
    $postdata = http_build_query(
      array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

    // get tokeninfo for refresh token
    $postdata = http_build_query(
      array(
        'token' => $refresh_token,
        'token_type_hint' => 'refresh_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Refresh Token ' . $refresh_token . '</h3>';
    array2table($response);

    // go for the token endpoint
    $postdata = http_build_query(
      array(
        'refresh_token' => $refresh_token,
        'grant_type' => 'refresh_token',
        'scope' => ($client_id == '2bd0defa-9919-945c-18f1-a16a37fa2881') ? 'saml' : 'openid profile'
      )
    );

    $response = doPOST($token_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token endpoint requesting an Access Token for Refresh Token ' . $refresh_token .'</h3>';
    array2table($response);

    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/mobile-app/')) === '/mobile-app/')
  {
    // Testing Authorization Code Flow

    // fetch the parameter from the query string
    parse_str($_SERVER['QUERY_STRING'], $params);

    $client_id = $params['state'];
    if ($client_id == '2bd0defa-9919-945c-18f1-a16a37fa2881')
        $client_secret = 'c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3';
    elseif ($client_id == '9aa391cd-ea50-8a70-545a-1c51879a1dd5')
        $client_secret = '9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5';
    elseif ($client_id == '8d86fcb1-f720-3a95-1070-0a4eb8d050ab')
        $client_secret = '210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc';
    elseif ($client_id == '1585cd2b-c8aa-1179-a6aa-b55fb9024802')
        $client_secret = '34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61';
    elseif ($client_id == '0a0036ff-52fa-a2ad-c884-af80ae377730')
        $client_secret = '3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc';
    elseif ($client_id == 'display')
    {
	echo '<h2>Result from the /oauth/authorize endpoint</h2>';
        echo '<h3>Query String Parameters</h3>';
        array2table($params);

        if (isset($params['id_token']))
        {
          echo '<h3>ID Token</h3>';
          list($header, $payload, $signature) = explode('.', $params['id_token']);
          $payload = json_decode(base64_decode($payload),true);
          array2table($payload);
        }
	return;
    }
    else
    {
      echo '<font color="red">Error running the tests for unknown client_id</font>';
      return;
    }

    echo '<h2>Result from the /oauth/authorize endpoint</h2>';
    echo '<h3>Query String Parameters</h3>';
    array2table($params);

    if (isset($params['id_token']))
    {
      echo '<h3>ID Token</h3>';
      list($header, $payload, $signature) = explode('.', $params['id_token']);
      $payload = json_decode(base64_decode($payload),true);
      array2table($payload);
    }

    // set the Authorization Header for subsequent requests
    $authorization = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);

    // get an access token for the authorization code
    $postdata = http_build_query(
      array(
        'code' => $params['code'],
        'grant_type' => 'authorization_code',
        'response_type' => 'token',
        'redirect_uri' => 'http://127.0.0.1:4711/mobile-app/',
        'client_id' => $params['state'],
        'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token endpoint requesting an Access Token for Authorization Code ' . $params["code"] . '</h3>';
    array2table($response);

    $access_token = $response['access_token'];
    $refresh_token = $response['refresh_token'];

    // get tokeninfo for access token
    $postdata = http_build_query(
      array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

    // get tokeninfo for refresh token
    $postdata = http_build_query(
      array(
        'token' => $refresh_token,
        'token_type_hint' => 'refresh_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Refresh Token ' . $refresh_token . '</h3>';
    array2table($response);

    // go for the userinfo endpoint
    $postdata = http_build_query(
      array(
        'client_id' => $client_id,
        'client_secret' => $client_secret
      )
    );

    $authorization = "Authorization: Bearer " . $access_token;
    $response = doPOST($userinfo_endpoint, $postdata, $authorization);

    echo '<h3>Response from UserInfo endpoint using Access Token ' . $access_token . '</h3>';
    array2table($response);

  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/service-app/')) === '/service-app/')
  {
    // Test cases for Client Credentials
    parse_str($_SERVER['QUERY_STRING'], $params);

    echo '<h2>Query String Parameters</h2>';
    array2table($params);

    $client_id = $params['client_id'];
    $client_secret = $params['client_secret'];
    $scope = $params['scope'];

    // get an access token with client_id and client_secret from query string
    $postdata = http_build_query(
      array(
        'grant_type' => 'client_credentials',
	'scope' => $scope,
        'client_id' => $client_id,
        'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata);

    echo '<h3>Response from Token endpoint requesting an Access Token for client credentials ' . $client_id .':' .$client_secret . '</h3>';
    array2table($response);

    $access_token = $response['access_token'];

    // prepare POST data for tokeninfo request with access token
    $postdata = http_build_query(
      array(
        'token' => $access_token,
        'token_type_hint' => 'access_token'
      )
    );

    $authorization = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);
    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

    // go for the userinfo endpoint
    $postdata = http_build_query(
      array(
        'client_id' => $client_id,
        'client_secret' => $client_secret
      )
    );

    $authorization = "Authorization: Bearer " . $access_token;
    $response = doPOST($userinfo_endpoint, $postdata, $authorization);

    echo '<h3>Response from UserInfo endpoint using Access Token ' . $access_token . '</h3>';
    array2table($response);

  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/gdpr-app/')) == '/gdpr-app/')
  {

    // Test cases for GDPR

    // fetch the parameter from the query string
    parse_str($_SERVER['QUERY_STRING'], $params);

    $client_id = $params['state'];
    if ($client_id == '2bd0defa-9919-945c-18f1-a16a37fa2881')
        $client_secret = 'c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3';
    elseif ($client_id == '9aa391cd-ea50-8a70-545a-1c51879a1dd5')
        $client_secret = '9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5';
    elseif ($client_id == '8d86fcb1-f720-3a95-1070-0a4eb8d050ab')
        $client_secret = '210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc';
    elseif ($client_id == '1585cd2b-c8aa-1179-a6aa-b55fb9024802')
        $client_secret = '34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61';
    elseif ($client_id == '0a0036ff-52fa-a2ad-c884-af80ae377730')
        $client_secret = '3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc';
    else
    {
      echo '<font color="red">Error running the tests for unknown client_id</font>';
      return;
    }

    echo '<h3>Query String Parameters</h3>';
    array2table($params);

    if (isset($params['id_token']))
    {
      echo '<h3>ID Token</h3>';
      list($header, $payload, $signature) = explode('.', $params['id_token']);
      $payload = json_decode(base64_decode($payload),true);
      array2table($payload);
    }

    // go for the token endpoint
    $postdata = http_build_query(
      array(
        'code' => $params['code'],
        'grant_type' => 'authorization_code',
	'response_type' => 'token',
	'redirect_uri' => 'http://127.0.0.1:4711/gdpr-app/',
	'client_id' => $client_id,
	'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata);

    $access_token = $response['access_token'];

    // go for the userinfo endpoint for each service application with different scope
    $service_apps = array (
	array('client_id' => '2bd0defa-9919-945c-18f1-a16a37fa2881', 'client_secret' => 'c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3', 'scope' => 'saml'), // scope saml
	array('client_id' => '9aa391cd-ea50-8a70-545a-1c51879a1dd5', 'client_secret' => '9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5', 'scope' => 'saml openid'), // scope openid
	array('client_id' => '8d86fcb1-f720-3a95-1070-0a4eb8d050ab', 'client_secret' => '210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc', 'scope' => 'openid profile'), // scope openid profile
	array('client_id' => '1585cd2b-c8aa-1179-a6aa-b55fb9024802', 'client_secret' => '34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61', 'scope' => 'openid email'), // scope openid email
	array('client_id' => '0a0036ff-52fa-a2ad-c884-af80ae377730', 'client_secret' => '3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc', 'scope' => 'openid email profile'), // scope openid email profile
    );

    foreach($service_apps as $service)
    {
      $client_id = $service['client_id'];
      $client_secret = $service['client_secret'];

      $authorization = 'Authorization: Bearer ' . $access_token;
      $postdata = http_build_query(
        array(
          'client_id' => $client_id,
          'client_secret' => $client_secret
        )
      );

      $response = doPOST($userinfo_endpoint, $postdata, $authorization);
      echo '<h2>Result from the /openid/userinfo endpoint for Service-Application with scope='.$service['scope'].'</h2>';
      array2table($response);
    }

  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/revocation-app/')) === '/revocation-app/')
  {
    // Test cases for the Token Revocation
    parse_str($_SERVER['QUERY_STRING'], $params);
  
    echo '<h2>Result from the /oauth/authorize endpoint</h2>';
    echo '<h3>Query String Parameters</h3>';
    array2table($params);
  
    $client_id = '2f5d0a34-5f76-c8c6-b262-0d38cd9e4185';
    $client_secret = 'fa458a15d01112826963f6261cafed88367a89922a25c3ef90e400ae3224266a';
    $authorization = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);

    echo '<h2>Requesting an Access Token from the Authorization Code and then revoke it<h2>';

    // go for the token endpoint
    $postdata = http_build_query(
      array(
        'code' => $params['code'],
        'grant_type' => 'authorization_code',
	'response_type' => 'token',
	'redirect_uri' => 'http://127.0.0.1:4711/revocation-app/',
	'client_id' => $client_id,
	'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token endpoint requesting an Access Token for Authorization Code' . $params["code"] . '</h3>';
    array2table($response);

    $access_token = $response['access_token'];
    $refresh_token = $response['refresh_token'];

    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);


    // go for the token revocation endpoint
    $postdata = http_build_query(
    array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($revoke_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Revocation endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(
        'token' => $access_token,
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

    
    echo '<h2>Requesting an Access Token from the Refresh Token and then revoke the Refresh Token<h2>';

    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(
        'token' => $refresh_token,
        'token_type_hint' => 'refresh_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Refresh Token ' . $refresh_token . '</h3>';
    array2table($response);


    // go for the token endpoint
    $postdata = http_build_query(
      array(
        'refresh_token' => $refresh_token,
        'grant_type' => 'refresh_token',
	'scope' => $response['scope']
      )
    );

    $response = doPOST($token_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token endpoint requesting an Access Token for Refresh Token ' . $refresh_token .'</h3>';
    array2table($response);

    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(
        'token' => $response['access_token'],
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);

    $postdata = http_build_query(
      array(
        'token' => $refresh_token,
        'token_type_hint' => 'refresh_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Refresh Token ' . $refresh_token . '</h3>';
    array2table($response);


    // go for the token revocation endpoint
    $postdata = http_build_query(
    array(
        'token' => $refresh_token,
        'token_type_hint' => 'refresh_token'
      )
    );

    $response = doPOST($revoke_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Revocation endpoint about Refresh Token ' . $refresh_token . '</h3>';
    array2table($response);


    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(
        'token_type_hint' => 'refresh_token',
        'token' => $refresh_token
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Refresh Token ' . $refresh_token . '</h3>';
    array2table($response);

    // go for the tokeninfo endpoint
    $postdata = http_build_query(
      array(    
        'token' => $access_token,
        'token_type_hint' => 'access_token'
      )
    );

    $response = doPOST($introspect_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token Introspection endpoint about Access Token ' . $access_token . '</h3>';
    array2table($response);


  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/logout-webapp/')) === '/logout-webapp/')
  {
  ?>
<h1>Authorization Server Response</h1>

<!-- http://127.0.0.1:4711/web-app/#access_token=becd90747d057ea4ac52d5777c418bcb330f3ee7&expires_in=1800&token_type=bearer&scope=saml&state=f8910358-fed1-3d49-8183-913ddede237e -->
<h2>Result from the /oauth/authorize endpoint</h2>
  <table>
    <thead><tr><th>key</th><th>value</th></tr></thead>
    <tbody>
      <tr><td>access_token</td><td><span id="access_token"/></td></tr>
      <tr><td>expires_in</td><td><span id="expires_in"/></td></tr>
      <tr><td>token_type</td><td><span id="token_type"/></td></tr>
      <tr><td>scope</td><td><span id="scope"/></td></tr>
      <tr><td>state</td><td><span id="state"/></td></tr>
      <tr><td>id_token</td><td><span id="id_token"/></td></tr>
    </tbody>
  </table>

<h2>ID Token</h2>
  <table>
        <thead><tr><th>key</th><th>value</th></tr></thead>
        <tbody id="id_token_table_body">
        </tbody>
  </table>

  <a id="logout"/>

  <script type="text/javascript">
    window.onload = processFragment;
  </script>
  <?php
  }
  elseif (substr($_SERVER['REQUEST_URI'], 0, strlen('/logout-app/')) === '/logout-app/')
  {
    // Testing Logout based on the Authorization Code Flow

    // fetch the parameter from the query string
    parse_str($_SERVER['QUERY_STRING'], $params);

    $client_id = $params['state'];
    if ($client_id == '2bd0defa-9919-945c-18f1-a16a37fa2881')
        $client_secret = 'c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3';
    elseif ($client_id == '9aa391cd-ea50-8a70-545a-1c51879a1dd5')
        $client_secret = '9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5';
    elseif ($client_id == '8d86fcb1-f720-3a95-1070-0a4eb8d050ab')
        $client_secret = '210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc';
    elseif ($client_id == '1585cd2b-c8aa-1179-a6aa-b55fb9024802')
        $client_secret = '34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61';
    elseif ($client_id == '0a0036ff-52fa-a2ad-c884-af80ae377730')
        $client_secret = '3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc';
    elseif ($client_id == 'false')
    {
	echo '<a href="' . $issuer . '/oauth/logout?token=null">logout with inactive access token</a>';	
	return;
    }
    else
    {
      echo '<font color="red">Error running the tests for unknown client_id</font>';
      return;
    }

    // Test the logout with code before the code is deleted. This happens once the code is exchanged for an access_token
    if ($client_id == '8d86fcb1-f720-3a95-1070-0a4eb8d050ab')
    {
  	echo '<a href="' . $issuer . '/oauth/logout?code=' . $params["code"] . '">logout with code</a>';
	return;
    }

    echo '<h2>Result from the /oauth/authorize endpoint</h2>';
    echo '<h3>Query String Parameters</h3>';
    array2table($params);

    if (isset($params['id_token']))
    {
      echo '<h3>ID Token</h3>';
      list($header, $payload, $signature) = explode('.', $params['id_token']);
      $payload = json_decode(base64_decode($payload),true);
      array2table($payload);
    }

    // set the Authorization Header for subsequent requests
    $authorization = "Authorization: Basic " . base64_encode($client_id . ":" . $client_secret);

    // get an access token for the authorization code
    $postdata = http_build_query(
      array(
        'code' => $params['code'],
        'grant_type' => 'authorization_code',
        'response_type' => 'token',
        'redirect_uri' => 'http://127.0.0.1:4711/logout-app/',
        'client_id' => $params['state'],
        'client_secret' => $client_secret
      )
    );

    $response = doPOST($token_endpoint, $postdata, $authorization);

    echo '<h3>Response from Token endpoint requesting an Access Token for Authorization Code ' . $params["code"] . '</h3>';
    array2table($response);

    $access_token = $response['access_token'];
    $refresh_token = $response['refresh_token'];

    if ($client_id == '9aa391cd-ea50-8a70-545a-1c51879a1dd5') 
    {
        echo '<a href="' . $issuer . '/oauth/logout?token=' . $access_token . '">logout with access_token</a>';
    }
    elseif($client_id == '1585cd2b-c8aa-1179-a6aa-b55fb9024802')
    {
	echo '<a href="' . $issuer . '/oauth/logout?token_type_hint=refresh_token&token=' . $refresh_token . '">logout with refresh_token</a>';
    }
    elseif($client_id == '0a0036ff-52fa-a2ad-c884-af80ae377730')
    {
        // go for the token endpoint
        $postdata = http_build_query(
          array(
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
          )
        );

        $response = doPOST($token_endpoint, $postdata, $authorization);

        echo '<h3>Response from Token endpoint requesting an Access Token for Refresh Token ' . $refresh_token . '</h3>';
        array2table($response);
	
        $access_token = $response['access_token'];
	echo '<a href="' . $issuer . '/oauth/logout?token=' . $access_token . '">logout with refreshed access_token</a>';
    }
  }
  else
  {

    //echo '<p>The following configuration - loaded from <a href="' . $openid_configuration_url . '">OpenID Configuration</a> - is used for configuring the tests:</p>';
    //array2table($openid_configuration);

    $markdown = file_get_contents('../TEST.md');

    $markdown = (strpos($markdown, 'ISSUER') === FALSE) ? $markdown : str_replace("ISSUER", $issuer, $markdown);
    $markdown = (strpos($markdown, 'AUTHORIZATION_ENDPOINT') === FALSE) ? $markdown : str_replace("AUTHORIZATION_ENDPOINT", $authorize_endpoint, $markdown);
    $markdown = (strpos($markdown, 'TOKEN_ENDPOINT') === FALSE) ? $markdown : str_replace("TOKEN_ENDPOINT", $token_endpoint, $markdown);
    $markdown = (strpos($markdown, 'REVOKE_ENDPOINT') === FALSE) ? $markdown : str_replace("REVOKE_ENDPOINT", $revoke_endpoint, $markdown);
    $markdown = (strpos($markdown, 'TOKENINFO_ENDPOINT')=== FALSE) ? $markdown : str_replace("TOKENINFO_ENDPOINT", $introspect_endpoint, $markdown);
    $markdown = (strpos($markdown, 'USERINFO_ENDPOINT') === FALSE) ? $markdown : str_replace("USERINFO_ENDPOINT", $userinfo_endpoint, $markdown);

    $testpage = Markdown::defaultTransform($markdown);
    
    echo $testpage;
    return;
  }
?>
   </div>
</div>
    <footer class="footer">
        <div class="container">
            <div class="row text-center">
                <div class="col-sm-6 offset-sm-3 col-md-3 offset-md-0">
                    <small>&copy <?php print date('Y')?> <a href="https://www.secure-dimensions.de" target="_SD">Secure Dimensions GmbH</a></small>
                </div>
                <div class="col-sm-6 offset-sm-3 col-md-3 offset-md-0">
                    <small>Last updated 31.10.2019</small>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
