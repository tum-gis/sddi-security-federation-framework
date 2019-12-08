<html>
    
    <head>
        <title>Authorization Server for OAuth2 - Privacy Statement</title>
        <?php include(__DIR__ . '/meta.html')?>
    </head>
    
    <body>
        
	<?php include(__DIR__ . '/header.php')?>


<div class="content">
  <div class="container" id="content">
            <h1>Authorization Server Oauth2 - Privacy Statement</h1>
            <h2>Name of the service</h2>
            Authorization Server for OAuth2 - <a href="/"><?php print $_SERVER['SERVER_NAME'];?></a>, in the following referred to as the <b>"Service"</b>. 
	    <h2>Simplified Overall Description of the Service</h2>
	    <p>
	    The <b>Service</b> does not have user accounts. It collects user authentication information from a trusted Identity Provider where the user has an account at upon the user&apos;s consent. A list of such operators (the <b>"Identity Providers"</b>) is available <a href="/IdPs" target="_IDP">here</a>. The user's authentication statement is in the following referred to as the <b>"Data"</b>). The Service is to make such Data available to the operators of applications and services registered with the Engagement Platform (the "Operators"). A list of such operators is available at <a href="/Operators" target="OP"><b>"Operators"</b></a>. 
	    </p>
	    <p>
	    Please note that the above is a simplified explanation only. Below please find a more detailed description, including in particular explanations in regard to the Purpose and the Determined Use, the implemented security measures and the duration of storage.
	    </p>
            <h2>Description of the Service</h2>
            <p>
            This OAuth2 version of the Service - the <a href="/" target="_AS">Authorization Server</a> is part of the <a href="https://www.tum.de" target="_TUM">Platform</a> (the <b>"Platform"</b>) and acts as a broker of user authentication between the Identity Providers and the Operators, while the Identity Providers provide the authentication information of the user. Each registered Operator can obtain an authentication statement (the Data), individual for each application and service, for the Purpose as determined below. Any use exceeding the or deviating from the Purpose and/or the Determined Use of Data is described in the Terms of Use and Privacy Statement of each Operator and requires prior separate consent of the user.
            <p/>
            <p>
            This Service does not provision any Personal Data to Operators for their registered application or service that was registered with this Service. It is therefore not possible that an Operator obtains Personal Data via the OpenID Connect UserInfo endpoint, provided also by this service.
            <p/>
            <p>
            In order to provide the Data to the registered Operators, this Service must first collect the Data from the Identity Provider used for login. Each Identity Provider may get user consent to release an authentication statement to this Service. By using this Service, you agree that the collected Data is processed for the purpose of making it available to the registered Operators upon request.
            <p/>
            <p>
            Any registered Operator requires a valid access token to obtain Data. Each access token has a validity period that limits the time where it can be used to fetch Data. This Service allows you to see the amount of Data that is collected for the current lifetime of an access token.
            <p/>
            <p>
            This Service does not collect any more personal information as received from an Identity Provider as previously authorized by the user at login with the Identity Provider.
            <h2>Controller of the personal data file and a contact person</h2>
            Secure Dimensions GmbH<br/>
            Waxensteinstr. 28, 81377 Munich, Germany<br/>
            Tel. +49 89 38151813<br/>
            Andreas Matheus<br/>
            support &lt;at&gt; secure-dimensions.de
            <h2>Jurisdiction</h2>
            Germany - Bavaria (DE-BY)
            <h2>Collected Data</h2>
            The Data is collected from the Identity Provider upon login. 
            <h2>Processed Data</h2>
            The Data, collected from an Identity Provider is temporarily stored for the Purpose to make it available to registered Operators upon request. The Data is not processed for any other Purpose.
            <h2>The Purpose of the processing of the Data</h2>
            <p>
            The Purpose of this Service is to fulfil the objective of brokering Data to Operators’ registered applications and services by presenting a valid access token. It is a technical requirement that the brokered Data is stored for the validity of the access token. The lifetime of an access token begins when the user starting the registered application or service and ends after a predefined time. The lifetime ends before the expiration time with the user’s logoff.
            </p>
            <p>
            The Operators are contractually bound to use the Data solely as determined in the Terms of Use and Privacy Statement (the <b>"Determined Use"</b>). An Operator must provide a URL to the Terms of Use and Privacy Statement when registering an application or service with this Service. It is the duty of the Operator to further obtain the user’s specific and explicit consent to any use of the Data exceeding the or deviating from the Purpose and/or the Determined Use.
            </p>
            <h2>A description of the Data being processed</h2>
            The Data that can be requested by registered Operators is controlled via the concept of scopes. By default, no scope is set when registering an application or service. This enables the Authorization Server to release an access token to a registered application or service. <br>
	    In addition, the scope 'saml' can be selected which enables to fetch the authentication information.
            <h3>Scope saml</h3>
	    This scope is project specific and contains the following Data: <br>
	    idp_country (the country code of the Identity Provider), idp_name (the common name of the Identity Provider), idp_origin (either eduGain for any Identity Provider connected via eduGAIN, social for the Facebook and Google Identity Providers) 
            <h2>Retention of the Data</h2>
            The Data is stored for the duration of the lifetime of active sessions determined by the lifetime of access tokens.
            <h2>Principles of protecting personal data</h2>
            This Service enforces all communication to be HTTP over TLS (HTTPS). For the storage of the Data at this Service, standard security procedures to ensure a secure data storage are applied.
            <h2>Regular disclose of the Data to third parties</h2>
            This service provides the Data as OpenID Connect User Claims to registered Operators using a valid access token. The amount of Data depends on the scopes bound to the access token.
            <h2>Transfer of the Data outside the EU or EEA</h2>
            This Service allows non-managed, self-registration of applications and services by Operators. This Service does not limit the Operators to be legal entities inside the EU or EEA. Therefore, the user shall read the Terms of Use and the Privacy Statement of the Operator&apos;s application to understand the further processing of the Data and to determine the transfer of the Data outside the EU or EEA before authorizing the application. If in doubt, the user shall not authorize an application to prevent the transfer of the Data outside the EU or EEA.
	    <p>
	    Operators are contractually bound to comply with GDPR standards or even higher standards also in case the Operators have their seats outside of the EU or EEA.
	    <p>
	    In case an Operator is seated outside of the EU or EEA, the Service will point this out to the user explicitly in the course of the user’s registration with such Operator’s application or service. 
            <h2>Right of access personal data on him/her</h2>
            Which Data has been collected by this Service for the outlined Purpose can be observed by consulting the following service via this <a href="/saml/sessioninfo" target="_sessioninfo">URL</a>.
            <h2>Rectification</h2>
            Contact your Identity Provider to correct the Data that is collected from there.
            <h2>Data protection Code of Conduct</h2>
            The Data processed by this service will be protected according to the <a href="http://www.geant.net/uri/dataprotection-code-of-conduct/v1" target="_CoC">Code of Conduct for Service Providers</a>,
            a common standard for the research and higher education sector to protect your privacy.
        </div>
</div>        

	<div id="footer" style="background-color: white;">
	    <table border="0" width="100%">
  	        <tr>
    	            <td width="30%" align="left"><font face="Britannic Bold" size="2">Copyright &copy; <?php print date('Y')?> Secure Dimensions GmbH</font></td>
    	            <td width="15%" align="right"><font face="Britannic Bold" size="2"><a href="/TermsOfUse" target="_S">Terms Of Use</a></font></td>
    	            <td width="15%" align="right"><font face="Britannic Bold" size="2"><a href="/NoPrivacyStatement" target="_S">Privacy Statement</a></font></td>
    	            <td width="15%" align="right"><font face="Britannic Bold" size="2"><a href="/CookieStatement" target="_S">Cookie Statement</a></font></td>
    	            <td width="25%" align="right"><font face="Britannic Bold" size="2">Last updated 30.09.2019</font></td>
  	        </tr>
	    </table>
</div>
    
    </body>

</html>
