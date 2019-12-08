<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  
<head>
    <title>Application Registration Result</title>
    <?php include(__DIR__ . '/meta.html')?>
</head>
<body>

<?php include(__DIR__ . '/header.php')?>
      
<div class="content">
  <div class="container" id="content">
    <h1>Application Registration Complete</h1>
    <p>
    </p>
	Your application was successfully registered with the Authorization Server.
    <h2>Registration Success</h2>
        Your application must use the following OAuth2 parameters to request access tokens and id tokens. <br>
	<table cellspacing="10px">
  	<tr>
    	  <th align="left">Parameter</th>
    	  <th align="left">Value</th>
  	</tr>
  	<tr>
    	  <td>client_id</td>
    	  <td><?php print $client_id ?></td>
  	</tr>
  	<tr>
    	  <td>client_secret</td>
    	  <td><?php (isset($client_secret)) ? print $client_secret : print 'this application type has no client_secret' ?></td>
  	</tr>
        <tr>
          <td>grant_type</td>
          <td><?php print $grant_types ?></td>
        </tr>
        <tr>
          <td>scopes</td>
          <td><?php print $scopes ?></td>
        </tr>
        <tr>
          <td>redirect_uri</td>
          <td><?php print $redirect_uris ?></td>
        </tr>
	</table>
      
    <h2>List your registered applications</h2>
	You can list your applications by following <a href="/listapps">this</a> link.
          
    <p/>
    <h2>Authorization Server API</h2>
	You can find the well-known description for this OpenID Connect compliant Authorization Server <a href="/.well-known/openid-configuration" target="WELL-KNOWN">here</a>
    </div>
</div>

<?php include(__DIR__ . '/footer.php')?>

</body>

</html>
