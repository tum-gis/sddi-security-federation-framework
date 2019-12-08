<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Strict//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
  
<head>
    <title>Application Registration</title>
    <?php include(__DIR__ . '/meta.html')?>
</head>

<body>

<?php include(__DIR__ . '/header.php')?>
      
<div class="content">
  <div class="container" id="content">
    <h1>Application Registration</h1>
    <p>
    <span id="global_error_message_container" class="message_container" <?php (isset($global_error_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
      <img src="/images/error.png" alt="Error">
      <span id="global_error_message"><?php (isset($global_error_message)) ? print $global_error_message : print ''?></span>
    </span>
    </p>
      This page allows to register an OpenID Connect enabled application to be registered.
    <p>You will ne asked to login when clicking the <b>Register</b> button.</p>
      
    <h2>Please provide details about the operator of the application</h2>
      <p>
	The operator is the legal entity responsible for the application. It can either be an organization or an individual natural person.
      </p> 	
      <p>
      In case that you register the application as an idividual, please provide your contact details.
      </p>
      * indicates required input.
      <form id="RegisterForm" action="/registerapps" method="POST">
          <p>
            Operator Name<a href=" " title="The name of the operator for the application that will be displayed to users when approving (trusting) this application or list authorized applications. If you register the application as an individual, please provide your name." style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "operator_name" id = "operator_name" value="<?php (isset($payload['operator_name'])) ? print $payload['operator_name'] : print ''?>" size="60"/>*<br/>
            <span id="operator_name_message_container" class="message_container" <?php (isset($operator_name_message)) ? print 'style="display: block;"' : print 'style="display: none";'?>>

                <img src="/images/error.png" alt="Error">
                <span id="operator_name_message"><?php (isset($operator_name_message)) ? print $operator_name_message : print ''?></span>
            </span>

          </p>
          <p>
            Operator Homepage URL<a href=" " title="If you want that the link to the homepage of the operator is displayed with the listing of all operators, please provide a URL." style="text-decoration:none"> ?</a><br>
            <input type = "url" name = "operator_uri" id = "operator_uri" value="<?php (isset($payload['operator_uri'])) ? print $payload['operator_uri'] : print ''?>" size="60"/><br/>
            <span id="operator_uri_message_container" class="message_container" <?php (isset($operator_uri_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="operator_uri_message"><?php (isset($operator_uri_message)) ? print $operator_uri_message : print ''?></span>
            </span>

          </p>
          <p>
            Operator Postal Address<a href=" " title="The registereed address of the operator for the application that will be displayed to users when approving (trusting) this application or list authorized applications. If you register the application as an individual, please provide your address." style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "operator_address" id = "operator_address" value="<?php (isset($payload['operator_address'])) ? print $payload['operator_address'] : print ''?>" size="120"/>*<br/>
            <span id="operator_address_message_container" class="message_container" <?php (isset($operator_address_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="operator_address_message"><?php (isset($operator_address_message)) ? print $operator_address_message : print ''?></span>
            </span>

          </p>
          <p>
            Operator Contact Name<a href=" " title="The person's name that functions as the point of contact for the application that will be displayed to users when listing authorized applications. If you register the application as an individual, please provide your name." style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "operator_contact" id = "operator_contact" value="<?php (isset($payload['operator_contact'])) ? print $payload['operator_contact'] : print ''?>" size="120"/>*<br/>
            <span id="operator_contact_message_container" class="message_container" <?php (isset($operator_contact_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="operator_contact_message"><?php (isset($operator_contact_message)) ? print $operator_contact_message : print ''?></span>
            </span>

          </p>
          <p>
            Operator Contact Email<a href=" " title="It is mandatory to provide the email address of the contact person so that we can contact you in case there are any problems with the application. Please provide your email address in case you are the contact person. If the email turns out to be invalid, your application will be removed." style="text-decoration:none"> ?</a><br>
            <input type = "email" name = "contacts" id = "contacts" value="<?php (isset($payload['contacts'])) ? print $payload['contacts'] : print ''?>" size="40"/>*<br/>
            <span id="contacts_message_container" class="message_container" <?php (isset($contacts_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="contacts_message"><?php (isset($contacts_message)) ? print $contacts_message : print ''?></span>
            </span>
          </p>
          <p>
            Operator Country<a href=" " title="The country of operators headquaters. If you register the application as an individual, please select the country of residency." style="text-decoration:none"> ?</a><br>
<select name="operator_country" id="operator_country">
	<option value="">Please select ...</option>
	    <?php
                $country_codes = new PeterColes\Countries\Maker();
                $countries = $country_codes->lookup('en')->toArray();
		foreach ($countries as $key => $name)
		    print '<option value="' . $key . '">' . $name . '</option>' . PHP_EOL;
	    ?>
</select>*<br/>
            <span id="operator_country_message_container" class="message_container" <?php (isset($operator_country_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="operator_country_message"><?php (isset($operator_country_message)) ? print $operator_country_message : print ''?></span>
            </span>
	<input type="hidden" name="operator_tos_uri" id="operator_tos_uri"/>
	<input type="hidden" name="operator_policy_uri" id="operator_policy_uri"/>
          </p>


    <h2>Which type of application do you like to register?</h2>
        The Authorization Server supports different types of applications. Depending on which type you select, particular conditions are applied.<br>
        <ul>
        <li>A client-side Web-Application runs inside the Web-Browser and must use the OAuth2 Implicit Grant</li>
        <li>A server-side Web-Application runs on a Web Server and must use the OAuth2 Authorization Code Grant</li>
        <li>A Mobile application must use the Authorization Code Grant and a redirect URI with application specific scheme, <b>not</b> http: or https:</li>
        <li>A Desktop application must use the Authorization Code Grant with a redirect URI specific for the application.</li>
        <li>A Service application must use the Client Credentials Grant and therefore has no redirect URI.</li>
        </ul>
        <p>
            <select name="appType" id="appType" onchange="appendInput(this.selectedIndex);">
              <option value="NONE" <?php (isset($payload['app_type'])) ? print '' : print 'selected'?>>Please select ...</option>
              <option value="WebApp" <?php (isset($payload['app_type']) && $payload['app_type'] == 1) ? print 'selected' : print ''?>>Client-side Web-Application</option>
              <option value="ServerApp" <?php (isset($payload['app_type']) && $payload['app_type'] == 2) ? print 'selected' : print ''?>>Server-side Web-Application</option>
              <option value="MobileApp" <?php (isset($payload['app_type']) && $payload['app_type'] == 3) ? print 'selected' : print ''?>>Mobile Application</option>
              <option value="DesktopApp" <?php (isset($payload['app_type']) && $payload['app_type'] == 4) ? print 'selected' : print ''?>>Desktop Application</option>
              <option value="ServiceApp" <?php (isset($payload['app_type']) && $payload['app_type'] == 5) ? print 'selected' : print ''?>>Service Application</option>
            </select><br/>
            <span id="app_type_message_container" class="message_container" <?php (isset($app_type_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?> >
                <img src="/images/error.png" alt="Error">
                <span id="app_type_message"><?php (isset($app_type_message)) ? print $app_type_message : print ''?></span>
          </span>
        </p>

      
    <h2>Please provide details about your application</h2>
      <p>
      The general information is required meta information about the application. The email address is required to make sure we can contact you if it matters.
      <p/>
      <p>
      The related information that you must provide is relevant to release access tokens to your application.
      <p/>
      <p>
      In case that either the Terms of Use or the Privacy Statement changes you must register a new version of the application to reflect the use of the new version.
      </p>
         <?php isset($payload['app_type']) ? print '<input type = "hidden" name = "app_type" value="' . $payload['app_type'] . '"/><br>' : print ''?>
         <?php isset($payload['grant_types']) ? print '<input type = "hidden" name = "grant_types" value="' . $payload['grant_types'] . '"/><br>' : print ''?>
         <br>
        <h3>General information</h3>
          <p>
            Software Version<a href=" " title="The version of your application. E.g. 1.0" style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "software_version" id = "software_version" value="<?php (isset($payload['software_version'])) ? print $payload['software_version'] : print ''?>" />*<br/>
            <span id="software_version_message_container" class="message_container" <?php (isset($software_version_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="software_version_message"><?php (isset($software_version_message)) ? print $software_version_message : print ''?></span>
            </span>

          </p>
          <p>
            Application Name<a href=" " title="The name of your application that will be displayed to users when approving (trusting) this application. Please use a self-describing name." style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "client_name" id = "client_name" value="<?php (isset($payload['client_name'])) ? print $payload['client_name'] : print ''?>" size="60"/>*<br/>
            <span id="client_name_message_container" class="message_container" <?php (isset($client_name_message)) ? print 'style="display: block;"' : print 'style="display: none";'?>>
                <img src="/images/error.png" alt="Error">
                <span id="client_name_message"><?php (isset($client_name_message)) ? print $client_name_message : print ''?></span>
            </span>

          </p>
          <p>
            Application Logo URL<a href=" " title="If you want that the logo of your application is displayed during user's approval, please provide a URL to that logo." style="text-decoration:none"> ?</a><br>
            <input type = "url" name = "logo_uri" id = "logo_uri" value="<?php (isset($payload['logo_uri'])) ? print $payload['logo_uri'] : print ''?>" size="60"/><br/>
            <span id="logo_uri_message_container" class="message_container" <?php (isset($logo_uri_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="logo_uri_message"><?php (isset($logo_uri_message)) ? print $logo_uri_message : print ''?></span>
            </span>

          </p>
          <p>
            Application Terms of Use URL<a href=" " title="It is mandatory to provide the Terms of Use for your application. The user of your application may visit the Terms of Use via the URL provided to determine authorization of your application to access personal information of the user! In case your application enables the user to participate in citizen science, it is required that the license on the user contributions is CC:BY." style="text-decoration:none"> ?</a><br>
            <input type = "url" name = "tos_uri" id = "tos_uri" value="<?php (isset($payload['tos_uri'])) ? print $payload['tos_uri'] : print ''?>" size="60"/>*<br/>
            <span id="tos_uri_message_container" class="message_container" <?php (isset($tos_uri_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="tos_uri_message"><?php (isset($tos_uri_message)) ? print $tos_uri_message : print ''?></span>
            </span>

          </p>
          
        <h3>Personal Data related settings</h3>
        <p>
	    The default setting (no scopes selected) means that the user of the application must successfully login but after that the user acts anonymously.<br>
	    By selecting the scope "Cryptoname" the user's identifier will be requested and used to generate the Cyrptoname. This scope does not require GDPR compliance, as no personal information is requested. And the Cryptoname is a non trackable, one way hash generated for the user that cannot be resolved to the real identity.<br>
	    The scope "SAML" does not include personal information. The information associated with this scope help to identify the login entity - the IdP.<br>
	    The scope "Profile" and "Email" result in transfer of personal information. Therefore, the application must operate under GDPR compliance.<br>
	</p>
	<p>	
            By selecting one or multiple Scopes (please select as required) the application is able to fetch personal information. Which personal information is linked with each scope is defined in the <a href="/PrivacyStatement" target="PS">Privacy Statement</a> of this service.
            <br>
            <input type="checkbox" name="openid" id="openid" value="<?php print $payload['openid']?>" <?php ($payload['openid'] == '') ? print '' : print 'checked'?>> Scope Cryptoname </input>
            <br>
            <input type="checkbox" name="profile" id="profile" value="<?php print $payload['profile']?>" <?php ($payload['profile'] == '') ? print '' : print 'checked'?>> Scope Profile </input>
            <br>
            <input type="checkbox" name="email" id="email" value="<?php print $payload['email']?>" <?php ($payload['email'] == '') ? print '' : print 'checked'?>> Scope Email </input>
            <br>
            <input type="checkbox" name="saml" id="saml" value="<?php print $payload['saml']?>" <?php ($payload['saml'] == '') ? print '' : print 'checked'?>> Scope SAML </input>
            <br>
	</p>
          <p id="policy_container" style="display:none">
            Application Privacy Statement URL<a href=" " title="It is mandatory to provide the Privacy Statement for your application. It is important that in the policy statement, you also explain the use of personal information. The user of your application may visit the Privacy Statement via the URL provided to determine authorization of your application to access personal information of the user!" style="text-decoration:none"> ?</a><br>
            <input type = "url" name = "policy_uri" id = "policy_uri" value="<?php (isset($payload['policy_uri'])) ? print $payload['policy_uri'] : print ''?>" size="60"/>*<br/>
            <span id="policy_uri_message_container" class="message_container" <?php (isset($policy_uri_message)) ? print 'style="display: block;"' : print 'style="display: none"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="policy_uri_message"><?php (isset($policy_uri_message)) ? print $policy_uri_message : print ''?></span>
            </span>

          </p>

	<p>
	    <span id="mobile_app" <?php ($payload['offline_access'] == '') ? print 'style="display: none;"' : print 'style="display: block;"'?>>
	    	Refresh Token (please select this box if you want to obtain a refresh_token along with the access_token)
 	    	<br>
		<input type="checkbox" name="offline_access" id="offline_access" value="<?php print $payload['offline_access']?>" <?php ($payload['offline_access'] == '') ? print '' : print 'checked'?>> Offline Access </input></span>
	    <br>
        </p>
	<span id="input_redirect_uris" style="display: none;">
	<h3>OAuth2 specific</h3>
          <p>
            Redirect URI(s) (please use space to seperate multiple URIs)<a href=" " title="It is mandatory to provide at least one URI where the authorization server delivers the access token. Please check the OAuth2 specification (RFC 6749) if in doubt which URI to provide." style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "redirect_uris" id = "redirect_uris" value="<?php (isset($payload['redirect_uris'])) ? print $payload['redirect_uris'] : print ''?>" size="60"/>*<br/>
            <span id="redirect_uris_message_container" class="message_container" <?php (isset($redirect_uris_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="redirect_uris_message"><?php (isset($redirect_uris_message)) ? print $redirect_uris_message : print ''?></span>
	    </span>
          </p>
        </span>

        <h3>License information</h3>
          <p>
            Creative Commons License URL<a href=" " title="It is mandatory to provide the URL to a Creative Commons license" style="text-decoration:none"> ?</a><br>
            <input type = "text" name = "license_uri" id = "license_uri" value="<?php (isset($payload['license_uri'])) ? print $payload['license_uri'] : print ''?>" size="60"/>*<br/>
            <span id="license_uri_message_container" class="message_container" <?php (isset($license_uri_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="license_uri_message"><?php (isset($license_uri_message)) ? print $license_uri_message : print ''?></span>
            </span>
          </p>

	  <p>
          <input type="checkbox" name="agree_tos" id="agree_tos" value=""><b>I agree to the <a href="/TermsOfUse" target="_TOU">Terms of Use</a> of this service and confirm that operating this application does not violate any provisions set forth in the <a href="/PrivacyStatement" target="_PS">Privacy Statement</a> of this service.</b> </input><br>
            <span id="agree_tos_message_container" class="message_container" <?php (isset($agree_tos_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="agree_tos_message"><?php (isset($agree_tos_message)) ? print $agree_tos_message : print ''?></span>
            </span>
	  </p>

	  <p id="privacy_checkbox_container" style="display:none">
	  <span id="gdpr" <?php $eea_countries = array("AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR", "DE", "GR", "HU", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PL", "PT", "RO", "SK", "SI", "ES", "SE", "UK", "IS", "LI", "NO"); (in_array($payload['operator_country'], $eea_countries)) ? print 'style="display: block;"' : print 'style="display: none;"'?>><input type="checkbox" name="agree_privacy" id="agree_gdpr_privacy" value="GDPR"><b>I guarantee that the processing of any personal data executed by my application is compliant with the EU <a href="http://eur-lex.europa.eu/legal-content/EN/TXT/?uri=uriserv:OJ.L_.2016.119.01.0001.01.ENG" target="_GDPR">General Data Protection Regulation (“GDPR”)</a>. I have implemented sufficient security measures to prevent the unlawful use of personal data or the accessibility for unauthorized third parties, in particular in accordance with Art 32 of the GDPR. </b></input><br/></span>
          <span id="non_gdpr" <?php $eea_countries = array("AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR", "DE", "GR", "HU", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PL", "PT", "RO", "SK", "SI", "ES", "SE", "UK", "IS", "LI", "NO"); (in_array($payload['operator_country'], $eea_countries)) ? print 'style="display: none;"' : print 'style="display: block;"'?>><input type="checkbox" name="agree_privacy" id="agree_non_gdpr_privacy" value="NON-GDPR"><b>I guarantee that the processing of any personal data executed by my application is compliant with data protection standards equal to or higher as under the regime of the EU <a href="http://eur-lex.europa.eu/legal-content/EN/TXT/?uri=uriserv:OJ.L_.2016.119.01.0001.01.ENG" target="_GDPR">General Data Protection Regulation (“GDPR")</a>. I have implemented sufficient security measures to prevent unlawful use of personal data or the accessibility for unauthorized third parties, in particular corresponding to Art 32 of the GDPR.</b></input><br/></span>
            <span id="agree_privacy_message_container" class="message_container" <?php (isset($agree_privacy_message)) ? print 'style="display: block;"' : print 'style="display: none;"'?>>
                <img src="/images/error.png" alt="Error">
                <span id="agree_privacy_message"><?php (isset($agree_privacy_message)) ? print $agree_privacy_message : print ''?></span>
            </span>

	  </p>
        <button id="register_button" type="button" class="btn btn-success">Register</button>
	<input type="button" onclick="window.location='/'" class="btn btn-danger" value="Cancel" text="Cancel"/>
      </form>
    </div> 
</div>

<?php include(__DIR__ . '/footer.php')?>

   <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

    <script type="text/javascript">

       $(document).ready(function() {
           
           document.getElementsByName("operator_name")[0].addEventListener('change', function(){
            var name = $("#operator_name").val();
            var operator_name_message = check_name(name);
            if ( operator_name_message != '' ) {
                data_ok = false;
                show_error_message(operator_name_message, 'operator_name');
            }
            else {
                data_ok = true;
                hide_error_message('operator_name');
            }
           });

           document.getElementsByName("operator_uri")[0].addEventListener('change', function(){
              var operator_uri = $("#operator_uri").val();
              if (operator_uri == '') {
                data_ok = true;
                hide_error_message('operator_uri');
              }
              else {
                  var operator_uri_message = check_url(operator_uri);
                  if ( operator_uri_message != '' ) {
                    data_ok = false;
                    show_error_message(operator_uri_message, 'operator_uri');
                  }
                  else {
                    data_ok = true;
                    hide_error_message('operator_uri');
                  }
              }
           });

           document.getElementsByName("operator_address")[0].addEventListener('change', function(){
            var name = $("#operator_address").val();
            var operator_address_message = check_name(name);
            if ( operator_address_message != '' ) {
                data_ok = false;
                show_error_message(operator_address_message, 'operator_address');
            }
            else {
                data_ok = true;
                hide_error_message('operator_address');
            }
           });

           document.getElementsByName("operator_contact")[0].addEventListener('change', function(){
            var name = $("#operator_contact").val();
            var operator_contact_message = check_name(name);
            if ( operator_contact_message != '' ) {
                data_ok = false;
                show_error_message(operator_contact_message, 'operator_contact');
            }
            else {
                data_ok = true;
                hide_error_message('operator_contact');
            }
           });

           document.getElementsByName("operator_country")[0].addEventListener('change', function(){
            var idx = $("#operator_country")[0].selectedIndex;
	    if ( idx == 0 ) {
                data_ok = false;
                show_error_message("Please select the country", 'operator_country');
            }
            else {
		// EU countries: Austria, Belgium, Bulgaria, Croatia, Republic of Cyprus, Czech Republic, Denmark, Estonia, Finland, France, Germany, Greece, Hungary, Ireland, Italy, Latvia, Lithuania, Luxembourg, Malta, Netherlands, Poland, Portugal, Romania, Slovakia, Slovenia, Spain, Sweden and the UK
		// EEA countries: Iceland, Liechtenstein and Norway
		var eu_eea_country = [,"AT", "BE", "BG", "HR", "CY", "CZ", "DK", "EE", "FI", "FR", "DE", "GR", "HU", "IE", "IT", "LV", "LT", "LU", "MT", "NL", "PL", "PT", "RO", "SK", "SI", "ES", "SE", "UK", "IS", "LI", "NO"];
		var country = $("#operator_country").val();
                if (eu_eea_country.indexOf(country) === -1)
		{
		    $('#gdpr').hide('medium');
		    $('#non_gdpr').show('medium');
		}
		else
		{
		    $('#gdpr').show('medium');
		    $('#non_gdpr').hide('medium');
                }
                hide_error_message('operator_country');
              }
           });

           document.getElementsByName("appType")[0].addEventListener('change', function(){
              var app_type = $("#appType")[0].selectedIndex;
	      if ( app_type == 5){
	        hideRedirectURIs();
	      }
	      else {
		showRedirectURIs();
	      }
	      if ((app_type == 2 ) || (app_type == 3 )) {
		showOfflineAccess();
   	      } 
	      else {
		hideOfflineAccess();
	      }
              if ( app_type == 0 ) {
                data_ok = false;
                show_error_message("Please select the type of your application", 'app_type');
              }
              else {
                hide_error_message('app_type');
              }
           });

           document.getElementsByName("software_version")[0].addEventListener('change', function(){
            var software_version = $("#software_version").val();
            var software_version_message = check_version_number(software_version);
            if ( software_version_message != '' ) {
                data_ok = false;
                show_error_message(software_version_message, 'software_version');
            }
            else {
                data_ok = true;
                hide_error_message('software_version');
                hide_error_message('client_name');
            }
           });
            
           document.getElementsByName("client_name")[0].addEventListener('change', function(){
            var client_name = $("#client_name").val();
            var client_name_message = check_name(client_name);
            if ( client_name_message != '' ) {
              data_ok = false;
              show_error_message(client_name_message, 'client_name');
            }
            else {
              data_ok = true;
              hide_error_message('client_name');
                hide_error_message('software_version');
            }
           });

           document.getElementsByName("logo_uri")[0].addEventListener('change', function(){
              var logo_uri = $("#logo_uri").val();
              if (logo_uri == '') {
                data_ok = true;
                hide_error_message('logo_uri');
              }
              else {
                  var logo_uri_message = check_https_url(logo_uri);
                  if ( logo_uri_message != '' ) {
                    data_ok = false;
                    show_error_message(logo_uri_message, 'logo_uri');
                  }
                  else {
                    data_ok = true;
                    hide_error_message('logo_uri');
                  }
              }
           });
                          
           document.getElementsByName("tos_uri")[0].addEventListener('change', function(){
              var tos_uri = $("#tos_uri").val();
              var tos_uri_message = check_url(tos_uri);
              if ( tos_uri_message != '' ) {
                data_ok = false;
                show_error_message(tos_uri_message, 'tos_uri');
              }
              else {
                data_ok = true;
                hide_error_message('tos_uri');
              }
           });
  
           document.getElementsByName("policy_uri")[0].addEventListener('change', function(){
              var policy_uri = $("#policy_uri").val();
              var policy_uri_message = check_url(policy_uri);
              if ( policy_uri_message != '' ) {
                data_ok = false;
                show_error_message(policy_uri_message, 'policy_uri');
              }
              else {
                data_ok = true;
                hide_error_message('policy_uri');
              }
           });
 
           document.getElementsByName("contacts")[0].addEventListener('change', function(){
              var contacts = $("#contacts").val();
              var contacts_message = check_email(contacts);
              if ( contacts_message != '' ) {
                data_ok = false;
                show_error_message(contacts_message, 'contacts');
              }
              else {
                data_ok = true;
                hide_error_message('contacts');
              }
           });
           
           document.getElementsByName("redirect_uris")[0].addEventListener('change', function(){
              var redirect_uris = $("#redirect_uris").val();
              var redirect_uris_message = check_url(redirect_uris);
              if ( redirect_uris_message != '' ) {
                data_ok = false;
                show_error_message(redirect_uris_message, 'redirect_uris');
              }
              else {
                hide_error_message('redirect_uris');
              }
           });
           
           document.getElementsByName("license_uri")[0].addEventListener('change', function(){
              var license_uri = $("#license_uri").val();
              var license_uri_message = check_url(license_uri);
              if ( license_uri_message != '' ) {
                data_ok = false;
                show_error_message(license_uri_message, 'license_uri');
              }
              else {
                hide_error_message('license_uri');
              }
           });

           document.getElementsByName("openid")[0].addEventListener('change', function(){
            var checked = $("#openid").prop("checked") ? 1 : 0;
            if (checked == 1) {
                $("#openid").val("openid");
            }
           });

           document.getElementsByName("profile")[0].addEventListener('change', function(){
            var checked = $("#profile").prop("checked") ? 1 : 0;
            if (checked == 1) {
                $("#profile").val("profile");
            }
           });

            document.getElementsByName("email")[0].addEventListener('change', function(){
            var checked = $("#email").prop("checked") ? 1 : 0;
            if (checked == 1) {
                $("#email").val("email");
	    }
            });

            document.getElementsByName("saml")[0].addEventListener('change', function(){
            var checked = $("#saml").prop("checked") ? 1 : 0;
            if (checked == 1) {
                $("#saml").val("saml");
	    }
            });

            document.getElementsByName("offline_access")[0].addEventListener('change', function(){
            var checked = $("#offline_access").prop("checked") ? 1 : 0;
            if (checked == 1) {
                $("#offline_access").val("offline_access");
	    }
            });
	
            // Check the tick box for Terms Of Use
            document.getElementsByName("agree_tos")[0].addEventListener('change', function(){
            if ($("#agree_tos").prop("checked"))
	    {
		$("#agree_tos").val("checked");
		hide_error_message('agree_tos');
	    }
            else
            {
                $("#agree_tos").val("");
            }
	    
            });

            // Check the tick box for GDPR Privacy Statement
            document.getElementById("agree_gdpr_privacy").addEventListener('change', function(){
	    if ($("#profile").prop("checked") || $("#email").prop("checked")) {
                if ($("#agree_gdpr_privacy").prop("checked"))
	        {
		    $("#agree_gdpr_privacy").val("GDPR");
		    hide_error_message('agree_privacy');
	        }
	        else
	        {
		    $("#agree_privacy").val("");
	        }
	    }
            });

            // Check the tick box for NON GDPR Privacy Statement
            document.getElementById("agree_non_gdpr_privacy").addEventListener('change', function(){
	    if ($("#profile").prop("checked") || $("#email").prop("checked")) {
                if ($("#agree_non_gdpr_privacy").prop("checked"))
                {
                    $("#agree_non_gdpr_privacy").val("NON-GDPR");
                    hide_error_message('agree_privacy');
                }
                else
                {
                    $("#agree_privacy").val("");
                }
	    }
            });

            //Check the operator country.
	    var payload_country = "";
	    payload_country = "<?php (isset($payload['operator_country'])) ? print $payload['operator_country'] : print '';?>";
	    $("#operator_country").val(payload_country);

        $("#register_button").click(function() {
          //Set flag showing everything is OK.
          var data_ok = true;
    
           //Check the operator name.
          var name = $("#operator_name").val();
          var operator_name_message = check_name(name);
          if ( operator_name_message != '' ) {
            data_ok = false;
            show_error_message(operator_name_message, 'operator_name');
          }
          else {
            hide_error_message('operator_name');
          }

           //Check the operator homepage URL.
          var operator_uri = $("#operator_uri").val();
          if (operator_uri == '') {
              hide_error_message('operator_uri');
          }
          else {  
              var operator_uri_message = check_url(operator_uri);
              if ( operator_uri_message != '' ) {
                data_ok = false;
                show_error_message(operator_uri_message, 'operator_uri');
              }
              else {
                hide_error_message('operator_uri');
              }
          }

           //Check the operator address.
          var name = $("#operator_address").val();
          var operator_address_message = check_name(name);
          if ( operator_address_message != '' ) {
            data_ok = false;
            show_error_message(operator_address_message, 'operator_address');
          }
          else {
            hide_error_message('operator_address');
          }

           //Check the operator contact.
          var name = $("#operator_contact").val();
          var operator_contact_message = check_name(name);
          if ( operator_contact_message != '' ) {
            data_ok = false;
            show_error_message(operator_contact_message, 'operator_contact');
          }
          else {
            hide_error_message('operator_contact');
          }

          var country_idx = $("#operator_country")[0].selectedIndex;
          if ( country_idx == 0 ) {
            data_ok = false;
            show_error_message("Please select the country", 'operator_country');
          }
          else {
            hide_error_message('operator_country');
          }

          //Check that app type is set.
          var app_type = $("#appType")[0].selectedIndex;
          if ( app_type == 0 ) {
            data_ok = false;
            show_error_message("Please select the type of your application", 'app_type');
          }
          else {
            hide_error_message('app_type');
          }

          //Check the software version.
          var software_version = $("#software_version").val();
          var software_version_message = check_version_number(software_version);
          if ( software_version_message != '' ) {
            data_ok = false;
            show_error_message(software_version_message, 'software_version');
          }
          else {
            hide_error_message('software_version');
          }
            
            
           //Check the application name.
          var client_name = $("#client_name").val();
          var client_name_message = check_name(client_name);
          if ( client_name_message != '' ) {
            data_ok = false;
            show_error_message(client_name_message, 'client_name');
          }
          else {
            hide_error_message('client_name');
          }
           
           //Check the application logo URL.
          var logo_uri = $("#logo_uri").val();
          if (logo_uri == '') {
              hide_error_message('logo_uri');
          }
          else {  
              var logo_uri_message = check_https_url(logo_uri);
              if ( logo_uri_message != '' ) {
                data_ok = false;
                show_error_message(logo_uri_message, 'logo_uri');
              }
              else {
                hide_error_message('logo_uri');
              }
          }

           //Check the application ToU URL.
          var tos_uri = $("#tos_uri").val();
          var tos_uri_message = check_url(tos_uri);
          if ( tos_uri_message != '' ) {
            data_ok = false;
            show_error_message(tos_uri_message, 'tos_uri');
          }
          else {
            hide_error_message('tos_uri');
          }
 
          //Check the application Privacy Statement URL.
          var policy_uri = $("#policy_uri").val();
          var policy_uri_message = check_url(policy_uri);
          if ( policy_uri_message != '' ) {
            data_ok = false;
            show_error_message(policy_uri_message, 'policy_uri');
          }
          else {
            hide_error_message('policy_uri');
          }
 
          //Check the email
          var contacts = $("#contacts").val();
          var contacts_message = check_email(contacts);
          if ( contacts_message != '' ) {
            data_ok = false;
            show_error_message(contacts_message, 'contacts');
          }
          else {
            hide_error_message('contacts');
          }

         //Check the application Redirect URL.
          var redirect_uris = $("#redirect_uris").val();
          var redirect_uris_message = check_url(redirect_uris);
          if ( redirect_uris_message != '' ) {
            data_ok = false;
            show_error_message(redirect_uris_message, 'redirect_uris');
          }
          else {
            hide_error_message('redirect_uris');
          }
            
         //Check the application License URL.
          var license_uri = $("#license_uri").val();
          var license_uri_message = check_url(license_uri);
          if ( license_uri_message != '' ) {
            data_ok = false;
            show_error_message(license_uri_message, 'license_uri');
          }
          else {
            hide_error_message('license_uri');
          }
            
         //Check the accept for Terms Of Use
          var agree_tos = $("#agree_tos").val();
          var agree_tos_message = check_name(agree_tos);
          if ( agree_tos_message != '' ) {
            data_ok = false;
            show_error_message('You must select this option!', 'agree_tos');
          }
          else {
            hide_error_message('agree_tos');
          }

        //Check the accept for GDPR Privacy Statement
	  if ($("#profile").prop("checked") || $("#email").prop("checked")) {
              var agree_gdpr_privacy = $("#agree_gdpr_privacy").val();
              var agree_non_gdpr_privacy = $("#agree_non_gdpr_privacy").val();
              if ((($("#agree_gdpr_privacy").prop("checked")) && (!$("#agree_non_gdpr_privacy").prop("checked")))  || ( (!$("#agree_gdpr_privacy").prop("checked")) && ($("#agree_non_gdpr_privacy").prop("checked")))){
                hide_error_message('agree_privacy');
              }
              else {
                data_ok = false;
                show_error_message('You must select this option!', 'agree_privacy');
              }
    
              if (data_ok) {
                hide_global_error_message();
                var str = '';
                var elem = document.getElementById('RegisterForm').elements;
                for(var i = 0; i < elem.length; i++)
                {
                    str += "Type: " + elem[i].type + " ";
                    str += "Name: " + elem[i].name + " ";
                    str += "Value: " + elem[i].value + " ";
                    str += "\n";
    
                } 
                document.getElementById('RegisterForm').submit();
              }
	  } else {
		document.getElementById('RegisterForm').submit();
	  }
	  
        });

        $('#profile, #email').change(function () {
                if ($("#profile").prop("checked") || $("#email").prop("checked")) {
                    toggleGDPRItems(true);
                }
                else {
                    toggleGDPRItems(false);
                }
        });

	if ($("#profile").prop("checked") || $("#email").prop("checked")) {
		toggleGDPRItems(true);
	}
      });

        
      //Check whether the version number is valid.
      //Input
      //  value_to_check: the value to check.
      //Return: An error message, or empty string if no error.
      function check_version_number(value_to_check) {
          var numbers=value_to_check.split(".");
          var arrayLength=numbers.length;
          
          for (var i = 0; i < arrayLength; i++) {
            if ( numbers[i] == '' ) {
                return 'Please enter a number';
            }
            if ( isNaN(numbers[i]) ) {
                return 'Please enter only digits and dots';
            }
            if ( numbers[i] < 0 ){
                return 'Please enter positive numbers only';
            }
          }
          return '';
      }
       
        
      //Check whether the application string is a valid string.
      //Input
      //  value_to_check: the value to check.
      //Return: An error message, or empty string if no error.
      function check_name(value_to_check) {
          if ( value_to_check == '' ) {
                return 'Please enter a string';
          }
          return '';
      }
        
      //Check whether a URL is valid
      //Input
      //  value_to_check: the value to check.
      //Return: An error message, or empty string if no error.
      function check_url(value_to_check) {
          var parser = undefined;
          
          try {
              parser = new URL(value_to_check);
          }
          catch (e) {
              return 'Please enter a valid URL';
          }
          
          return '';
      }

      //Check whether a URL is valid and has scheme HTTPS
      //Input
      //  value_to_check: the value to check.
      //Return: An error message, or empty string if no error.
      function check_https_url(value_to_check) {
          var parser = undefined;
          
          try {
              parser = new URL(value_to_check);
	      if (parser.protocol != "https:") {
		return 'The URL must use scheme https:';
	      }
          }
          catch (e) {
              return 'Please enter a valid URL';
          }
          
          return '';
      }

      //Check whether an email is valid
      //Input
      //  value_to_check: the value to check.
      //Return: An error message, or empty string if no error.
      function check_email(value_to_check) {
          if (/(?:[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")@(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])/.test(value_to_check)) {
            return ('');
          }
          return 'Please enter a valid email address';
          
       }

        
      function showOfflineAccess() {
	$('#mobile_app').show('medium');
	$('#offline_access').prop('checked', true);
	$("#offline_access").val("offline_access");
      }
        
      function hideOfflineAccess() {
        $('#mobile_app').hide('medium');
	$('#offline_access').prop('checked', false);
	$("#offline_access").val("");
      }
        
 
      //Show an error message.
      //Input
      //  message: The message to show.
      //  input_name: The product name the message is about.
      //Return: nothing.
      function show_error_message(message, input_name) {
        //Show the error message in the product's message area.
        $('#' + input_name + '_message').text(message);
        if ( $('#' + input_name + '_message_container').is(':hidden') ) {
          $('#' + input_name + '_message_container').show('medium');
        }
        //Show the global error message at the top of the page.
        //show_global_error_message("Sorry, but your registration cannot be processed. Please correct errors as indicated below.");
      }

      //Show a global error message. It applies to the entire
      //  page, not just one product.
      //Input
      //  message: The message to show.
      //Return: nothing.
      function show_global_error_message(message) {
        $("#global_error_message").text(message);
        if ($('#global_error_message_container' ).is(':hidden') ) {
          $('#global_error_message_container').show('medium');
        }
      }

      //Hide a product error message.
      //Input
      //  input_name: The product name the message is about.
      //Return: nothing.
      function hide_error_message(input_name) {
        $('#' + input_name + '_message_container').hide('medium');
      }

      //Hide global error message.
      //Return: nothing.
      function hide_global_error_message() {
        if ($('#global_error_message_container' ).is(':visible') ) {
          $('#global_error_message_container').hide('medium');
        }
      }
        
      function hideRedirectURIs() {
        $('#input_redirect_uris').hide('medium');
      }

      function showRedirectURIs() {
        if ($('#input_redirect_uris' ).is(':hidden') ) {
          $('#input_redirect_uris').show('medium');
        }
      }

      // insert app type specific input selectors
      function appendInput(index) {
          // Make sure the error message dissaears for a valid selection
          if ( $("#appType")[0].selectedIndex != 0 ) {
            hide_error_message('app_type');
          }
          var grant_type = null;
          if (document.getElementsByName("grant_types").length != 0) {
              grant_type = document.getElementsByName("grant_types")[0];
          }
          else {
              grant_type = document.createElement('input');
              grant_type.type = "hidden";
              grant_type.name = "grant_types";
              grant_type.appendChild(document.createElement("br"));
          }

	  var app_type = null;
          if (document.getElementsByName("app_type").length != 0) {
              app_type = document.getElementsByName("app_type")[0];
          }
          else {
              app_type = document.createElement('input');
              app_type.type = "hidden";
              app_type.name = "app_type";
              app_type.appendChild(document.createElement("br"));
          }
	  showRedirectURIs();
	  app_type.value = index; 
          document.getElementById("RegisterForm").appendChild(app_type);
          if (index == 1) { // Web-App in Browser
            grant_type.value = "implicit";
            document.getElementById("RegisterForm").appendChild(grant_type);
          }
          if (index == 2) { // Web-App on Server 
            grant_type.value = "authorization_code refresh_token";
            document.getElementById("RegisterForm").appendChild(grant_type);
          }
          if (index == 3) { // Mobile App
            grant_type.value = "authorization_code refresh_token";
            document.getElementById("RegisterForm").appendChild(grant_type);
          }
          if (index == 4) { // Desktop App
            grant_type.value = "authorization_code";
            document.getElementById("RegisterForm").appendChild(grant_type);
          }
          if (index == 5) { // Service App
	    hideRedirectURIs();
            grant_type.value = "client_credentials";
            document.getElementById("RegisterForm").appendChild(grant_type);
          }
      }

      function toggleGDPRItems(show) {
      	if (show) {
	    $("#policy_container").show();
	    $("#privacy_checkbox_container").show();
	}
	else
	{
	    $("#policy_container").hide();
	    $("#privacy_checkbox_container").hide();
	    $("#no_privacy").checked();
	}
      }

    </script>
  </body>

</html>
