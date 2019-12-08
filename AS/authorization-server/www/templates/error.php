<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
    <title><?php print $payload['title']?></title>
    <?php include(__DIR__ . '/meta.html')?>
</head>

<body>

<?php include(__DIR__ . '/header.php')?>

<div class="content">
  <div class="container" id="content">
    <font color="red"><img src="/images/error.png"> <b><?php print $payload['error_message']?></b></font>
  </div>
</div>

<?php include(__DIR__ . '/footer.php')?>

</body>
</html>
