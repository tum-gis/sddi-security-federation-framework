<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  
<head>
    <title>Personal Data for current Session</title>
    <?php include(__DIR__ . '/meta.html')?>
</head>

<body>
  
<?php include(__DIR__ . '/header.php')?>

<div class="content"> 
  <div class="container" id="content"> 
    <h1>Personal Data for current Session</h1>
    <p> This page lists the personal information that was collected from the IdP that you've used for login.</p>
      <h2>Personal Data</h2>
	<?php
	if (empty($payload))
	    echo "Do session available";
	else
	{
	    $size = count($payload);
	    for ($idx = 0; $idx < $size; $idx++)
	    {
		$auth_id = null;

		echo "<h3>Session no.".strval($idx+1)."</h3>";
		echo "<table>";
		echo "<tr><td>SAML2 Attribute Name</td><td>Value</td></tr>";
		foreach($payload[$idx] as $key=>$val)
		{
		    if ($key != 'auth_id')
		    {
       	                if (is_array($val))
       	                    echo "<tr><td>".$key."</td><td>" .join(', ', $val)."</td></tr>";
	                else
		            echo "<tr><td>".$key."</td><td>" . $val ."</td></tr>";
    	   	    } 
		    else
		    {
			$auth_id = $val;
		    }
		}
		$protocol = ($_SERVER['HTTPS']) ? 'https://' : 'http://';
		echo "<tr><td><a href=\"/saml/logout?authid=".$auth_id."&return=".$protocol.$_SERVER['SERVER_NAME']."/logoutcomplete\">logout</a>";
		echo "</table>";
	    }
	}
	?>
   </div>
</div>

<?php include(__DIR__ . '/footer.php')?>

</body>

</html>
