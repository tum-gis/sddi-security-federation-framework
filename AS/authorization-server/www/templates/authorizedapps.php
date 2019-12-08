<!DOCTYPE html>
<html lang="en">

<head>
    <title>Authorized Application Management</title>
    <?php include(__DIR__ . '/meta.html')?>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.17.0/jquery.validate.min.js" integrity="sha384-rZfj/ogBloos6wzLGpPkkOr/gpkBNLZ6b6yLy4o+ok+t/SAKlL5mvXLr0OXNi1Hp" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.0/umd/popper.min.js" integrity="sha384-cs/chFZiN24E4KMATLdqdvsezGxaGsi4hLGOzlXwp5UZB1LY//20VyM2taTB4QvJ" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous"></script>

    <script type="text/javascript">
        function revokeConsent(client_id)
        {
            if (confirm("Are you sure?"))
	    {
                var url = "/authorizedapps/";
                var xhr = new XMLHttpRequest();
                xhr.open("DELETE", url+client_id, true);
                xhr.onload = function () {
                    if (xhr.readyState == 4 && xhr.status == "200") {
                        location.reload();
                    } else {
                        alert("Error");
                    }
                }
	    }
            xhr.send(null);
        }
	function logoutFromDevice(client_id)
        {
            if (confirm("Are you sure?"))
            {
                var url = "/logoutapps/";
                var xhr = new XMLHttpRequest();
                xhr.open("DELETE", url+client_id, true);
                xhr.onload = function () {
                    if (xhr.readyState == 4 && xhr.status == "200") {
	                var url2 = "/authorizedapps/";
                	var xhr2 = new XMLHttpRequest();
                	xhr2.open("DELETE", url2+client_id, true);
                	xhr2.onload = function () {
                    	  if (xhr2.readyState == 4 && xhr2.status == "200")
                            location.reload();
                          else
                            alert("Error");
                        }
			xhr2.send(null);
                    } else {
                        alert("Error");
                    }
                }
            }
            xhr.send(null);
        }
    </script>

  </head>
  <body>
  
<?php include(__DIR__ . '/header.php')?>

<div class="content"> 
  <div class="container" id="content"> 
    <h1>Authorized Application Management</h1>
    <p> This page allows you to see all of your authorized applications with the  Authorization Server.</p>
      <h2>Application Listing</h2>
	<p>Please click on the "revoke consent" button to remove the authorization for an application to access your personal data. Please note: Once you have revoked the authorization for an application, you will be asked to authorize the application next time you are using it!</p>
	<table class="table">
	  <thead>
  	  <tr>
    	    <th align="left">application name</th>
    	    <th align="left">application version</th>
    	    <th align="left">operator name</th>
    	    <th align="left">personal data</th>
    	    <th align="left">authorization date</th>
  	  </tr>
	  </thead>
	  <tbody>
	<?php $ix=0;
	  foreach ($consent_details as $consent) {
  	  print '<tr>';
    	  print '<td>' . $consent['client_name'] . '</td>';
    	  print '<td>' . $consent['software_version'] . '</td>';
    	  print '<td>' . $consent['operator_name'] . '</td>';
    	  echo '<td>'; 
		foreach($consent["claims"] as $key => $value)
                {
                    if($key == 'email_verified')
                        $value = $value ? 'true' : 'false';
                
		    if (!empty($value))
                        echo $key . ' : ' . $value . '<br />';
                }
	  echo '</td>';
	  echo '<td><span id="consent_date"><script>document.currentScript.parentNode.textContent = "' . $consent['consent_date'] . '";</script>';
	  echo '<td><button class="btn btn-danger" onclick="javascript:revokeConsent(' . "'" . $consent['client_id'] . "'" . ');">revoke consent</button></td>';
	  if(in_array('offline_access', explode(' ', $consent["scope"])))
		echo '<td><button class="btn btn-danger" onclick="javascript:logoutFromDevice(' . "'" . $consent['client_id'] . "'" . ');">logout from device</button></td>';
	  echo '</tr>';
	  }
	?>
	</tbody>
	</table>
    </div>
    </div>

    <?php include(__DIR__ . '/footer.php')?>

  </body>

</html>
