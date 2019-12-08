<html>
  
  <head>
    <title>Authorization Server</title>
    <?php include(__DIR__ . '/templates/meta.html')?>

    <script src="//cdnjs.cloudflare.com/ajax/libs/cookieconsent2/3.0.3/cookieconsent.min.js"></script>
    <script>
        window.addEventListener("load", function(){
        window.cookieconsent.initialise({
          "palette": {
		"popup": {
      		"background": "#216942",
      		"text": "#b2d192"
    		},
    		"button": {
      		"background": "#afed71"
    		}
          },
	"content": {
    		"href": "/CookieStatement"
  	}
        })});
    </script>
   </head>
  
  <body>

    <?php include(__DIR__ . '/templates/header.php')?>

<div class="content">
  <div class="container" id="content">
  <h1>Authorization Server</h1>
  <p>This service - the <a href="<?php $_SERVER['HTTP_HOST']?>" target="_AS">Authorization Server</a> - allows registered applications to access protected services and resources.</p>   
  <h2>Login via SAML Federation</h2>
  <p>This service allows that you login using federated Identity Management. In practise, you will get asekd to select for the login organization that you wish to use each time you login. You can preset / and unset your choice at the <a href="/DiscoveryService" target="_DS">Discovery Service</a>.</p>   

    <h2>User Views</h2>
    <div class="container">
    <div class="col-9">
      <div class="row">
        <div class="card" style="width: 18rem;">
          <img class="card-img-top" src="/images/icon-authorized-apps.png" alt="authorized apps">
          <div class="card-body">
            <h5 class="card-title">My Authorized Applications</h5>
            <p class="card-text">Check which applications you trust...</p>
            <a href="/authorizedapps" target="_AUTHZAPPS" class="btn btn-success">Check Authorized Applications</a>
          </div>
        </div>
        <div class="card" style="width: 18rem;">
          <img class="card-img-top" src="/images/icon-personal-data.png" alt="personal data">
          <div class="card-body">
            <h5 class="card-title">Check Your Personal Data</h5>
            <p class="card-text">Check which personal data we collected...</p>
            <a href="/saml/sessioninfo" target="_PERSONALDATA" class="btn btn-success">Check Personal Data</a>
          </div>
        </div>
      </div>
    </div>
    </div>

    <h2>Developer Views</h2>
    <div class="container">
    <div class="col-9">
      <div class="row">
        <div class="card" style="width: 18rem;">
        <img class="card-img-top" src="/images/icon-register-apps.png" alt="register apps">
        <div class="card-body">
          <h5 class="card-title">Application Registration</h5>
          <p class="card-text">You have a Web-based, mobile or server-side application or a service and would like to register it?</p>
          <a href="/registerapps" target="_APPS" class="btn btn-success">Application Registration</a>
	  <p/>
        </div>
      </div>
      <div class="card" style="width: 18rem;">
        <img class="card-img-top" src="/images/icon-register-services.png" alt="register services">
        <div class="card-body">
          <h5 class="card-title">Application Listing</h5>
          <p class="card-text">You have previously registerd your application and would like to see the details?</p>
          <a href="/listapps" target="_APPS" class="btn btn-success">Application Listing</a>
        </div>
      </div>
    </div>
    </div>

  </div>
</div>      
    <?php include(__DIR__ . '/templates/footer.php')?>
  </body>
  
</html>
