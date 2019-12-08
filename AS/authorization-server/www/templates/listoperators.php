<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  
<head>
    <title>Authorization Server Operator and Application Listing</title>
    <?php include(__DIR__ . '/meta.html')?>

    <script>
	function localDate(timestamp)
        {
                // Split timestamp into [ Y, M, D, h, m, s ]
                var t = timestamp.split(/[- :]/);

                // Apply each element to the Date function
                var d = new Date(Date.UTC(t[0], t[1]-1, t[2], t[3], t[4], t[5]));

                return d;
                // -> Wed Jun 09 2010 14:12:01 GMT+0100 (BST)
        }
    </script>
</head>
<body>
  
<?php include(__DIR__ . '/header.php')?>

<div class="content">
  <div class="container" id="content">
    <h1>Authorization Server Operator and Application Listing</h1>
    <p> This page lists all operators and their registered applications with the Authorization Server.</p>
      <h2>Operator and their Application Listing</h2>
	<table cellspacing="10px">
  	<tr>
    	  <th align="left">Operator Name</th>
    	  <th align="left">Application Name</th>
    	  <th align="left">Application Terms of Use</th>
    	  <th align="left">Application Privacy Statement</th>
    	  <th align="left">Application Version</th>
    	  <th align="left">Registation Date</th>
  	</tr>
	<?php foreach ($payload as $item) {
  	  print '<tr>';
    	  (isset($item['operator_uri'])) ? print '<td><a href="' . $item['operator_uri'] . '" target="_OP">' . $item['operator_name'] . '</a></td>' : print '<td>' . $item['operator_name'] . '</td>';
    	  print '<td>' . $item['application_name'] . '</td>';
    	  print '<td><a href="' . $item['tos_uri'] . '" target="_ToU">terms of use</a></td>';
    	  print '<td><a href="' . $item['policy_uri'] . '" target="_PS">privacy statement</a></td>';
    	  print '<td>' . $item['application_version'] . '</td>';?>
	  <td><span id="registration_date"><script>document.currentScript.parentNode.textContent = localDate("<?php print $item['registration_date']?>");</script></span></td>
          </tr>
	<?php } ?>
	</table>
    </div>
</div>

<?php include(__DIR__ . '/footer.php')?>

</body>

</html>
