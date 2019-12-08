<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  
<head>
    <title>Application Listing</title>
    <?php include(__DIR__ . '/meta.html')?>
</head>
<body>
  
<?php include(__DIR__ . '/header.php')?>

<div class="content">
  <div class="container" id="content">
    <h1>Application Listing</h1>
    <p> This page allows you to see all of your applications that are registered with the Authorization Server.</p>
	<table>
  	<tr>
    	  <th align="left">application name</th>
    	  <th align="left">appliction version</th>
    	  <th align="left">client_id</th>
    	  <th align="left">client_secret</th>
    	  <th align="left">redirect_uri</th>
    	  <th align="left">grant_types</th>
    	  <th align="left">scopes</th>
  	</tr>
	<?php if ($client_details) 
		{
		    foreach ($client_details as $client) {
  	  		print '<tr>';
    	  		print '<td>' . $client['client_name'] . '</td>';
    	  		print '<td>' . $client['software_version'] . '</td>';
    	  		print '<td>' . $client['client_id'] . '</td>';
    	  		print '<td>' . $client['client_secret'] . '</td>';
    	  		print '<td>' . $client['redirect_uri'] . '</td>';
    	  		print '<td>' . $client['grant_types'] . '</td>';
    	  		print '<td>' . $client['scope'] . '</td>';
          		print '</tr>';
		    }
		} ?>
	</table>
    </div>
</div>

<?php include(__DIR__ . '/footer.php')?>

</body>

</html>
