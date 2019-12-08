<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  
<head>
    <title>Authorization Server - Cookie Statement</title>
    <?php include(__DIR__ . '/meta.html')?>
</head>
  
<body>

<?php include(__DIR__ . '/header.php')?>

<div class="content">
  <div class="container" id="content">
      <h1>Authorization Server - Cookie Statement</h1>
      <h2>Name of the service</h2>
      Authorization Server - <a href="/"><?php print $_SERVER['SERVER_NAME'];?></a>
      <h2>Purpose of using cookies</h2>
      The <a href="/" target="_AS">Authorization Server</a> - this service - stores session information in cookies to be able to provide user information as described in the <a href="/PrivacyStatement" target="_PS">Privacy Statement</a>. This service does not leverage persistent cookies.
      <h2>Session Cookies</h2>
        <table>
          <tr>
            <th>Cookie name</th><th>Purpose</th><th>Session/Persistent cookie?</th>
          </tr>
          <tr>
            <td>cookieconsent_status</td><td>stores your acceptance of the Cookie discalimer</td><td>Persistent</td>
          </tr>
          <tr>
            <td>ASSessionID</td><td>stores information about the currently active session</td><td>Session</td>
          </tr>
	  <tr>
            <td>ASAuthToken</td><td>stores the information regarding the authentication for the current session</td><td>Session</td>
	  </tr>
	</table>
    </div>
</div>    

<?php include(__DIR__ . '/footer.php')?>

</body>
  
</html>
