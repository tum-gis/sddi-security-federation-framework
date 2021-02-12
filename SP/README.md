# Protecting the Service Provider
In the current deployment of the SDDI Security Demonstrator, the SOS1 and SOS2 are protected via two different methods:

* SAML (Shibboleth) sessions based on HTTP cookies
* OAuth2 Bearer Access Tokens released from the Authorization Server

**Note:** *In the current configuration, the endpoints do not use Bearer access tokens released by the Authorization Server!*

## Installation
The setup for protecting the SOS endpoints to use HTTP Cookies is based on the [Shibboleth Service Provider installation](https://www.switch.ch/aai/guides/sp/installation/).

The setup for protecting the SOS endpoints to use Bearer tokens is described in the README.md located in the `RS` directory.

***NOTE***: The following instructions are based on the operating system Ubuntu.

The v2 of the Shibboleth Service Provider is used. The  actual configuration can be found on the machines hosting the SOS1 and SOS2.

*   ``/etc/shibboleth``: This is the main configuration directory for the Shibboleth Service Provider
*   ``/etc/httpd/conf.d/sos.conf`` (CentOS) or ``/etc/apache2/sites-available/sos-https.conf`` (Ubuntu): 
    This file contains the main configuration and access control part.

### Apache Web Server Configuration

#### For SOS1
The following snippet illustrates the dual protection applied to the `/weather-sensors-sos-webapp/service` endpoint.

The `<If "%{HTTP:Authorization} =~ /Bearer/ || %{QUERY_STRING} =~ /access_token/">` section defines the Bearer token protection. 

The `<Elseif "%{ENV:REDIRECT_REWRITTEN} =~ /1/">` is required to ensure non-redirct looping for Bearer token based access.

The `<Else>` section defines the HTTP Cookie (Shibboleth) Session setup.

Please refer to the 
[web service registration](../Troubleshooting.md#createupdate-client-id-client-secret-and-redirect-url)
to get the ``<CLIENT_ID>`` and ``<CLIENT_SECRET>``.

````
<Location /weather-sensors-sos-webapp/service>
    Header always set Cache-Control "private, no-cache, no-store, proxy-revalidate, no-transform
    Header always set Pragma "no-cache"
    <If "%{HTTP:Authorization} =~ /Bearer/ || %{QUERY_STRING} =~ /access_token/">
        AuthType Bearer
        AuthName "SDDI Security Demo"
        Require valid-user
        PerlAuthenHandler SD::OAuthnBearerHandler
        PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
        PerlSetVar ClientId <CLIENT_ID>
        PerlSetVar ClientSecret <CLIENT_SECRET>
        PerlSetVar ValidateURL https://ssdas.gis.bgu.tum.de/oauth/tokeninfo

        Header unset Authorization
        RewriteEngine on
        # a
        #  ?a=foo
        #  Starts with a=, non-ampersand to the end.
        #  Suppress querystring with trailing question mark.
        RewriteCond %{QUERY_STRING} ^access_token=([^&]+)$
        RewriteRule .* /weather-sensors-sos-webapp/service [END,PT,E=REWRITTEN:1]

        # a-other
        #  ?a=foo&b=bar, ?a=foo&b=bar&c=1
        #  Starts with a=, non-ampersand, ampersand, remaining required.
        #  Escape question mark so it doesn't include entire original querystring.
        RewriteCond %{QUERY_STRING} ^access_token=([^&]+)&(.+)
        RewriteRule .* /weather-sensors-sos-webapp/service?%2 [END,PT,E=REWRITTEN:1]

        # other-a or other-a-other
        #  ?b=baz&a=qux, ?b=baz&c=1&a=qux
        #  ?c=1&a=foo&d=2&b=bar&e=3, ?z=4&c=1&a=foo&d=2&b=bar&e=3
        #  Starts with anything, ampersand, a=, non-ampersand, remaining optional.
        #  The remaining optional lets it follow with nothing, or with ampersand and more parameters.
        #  Escape question mark so it doesn't include entire original querystring.
        RewriteCond %{QUERY_STRING} ^(.+)&access_token=([^&]+)(.*)$
        RewriteRule .* /weather-sensors-sos-webapp/service?%1%3 [END,PT,E=REWRITTEN:1]
    </If>
    <Elseif "%{ENV:REDIRECT_REWRITTEN} =~ /1/">
        Require all granted
    </Elseif>
    <Else>
        AuthType shibboleth
        ShibRequestSetting requireSession 1
        Require shib-session
	    #Require all granted
    </Else>
</Location>
````

The access control condition in place requires an authentication user (`ShibRequestSetting requireSession 1`).

Create a Perl file in the Perl directory (shown in environment variables). Such as in Ubuntu, create the file ``/usr/local/share/perl/5.22.1/SD/OAuthnBearerHandler.pm`` 
with the contents provided in [OAuthnBearerHandler.pm](SOS1/OAuthnBearerHandler.pm).

#### For SOS2
The SOS2 configuration is basically identical to the SOS1 with one important difference: The access control condition requires TUM users!

````
<Location /smart-meters-sos-webapp/service>
    Header always set Cache-Control "private, no-cache, no-store, proxy-revalidate, no-transform
    Header always set Pragma "no-cache"
    <If "%{HTTP:Authorization} =~ /Bearer/ || %{QUERY_STRING} =~ /access_token/">
        AuthType Bearer
        AuthName "SDDI Security Demo"
        Require valid-user
        PerlAuthenHandler SD::OAuthzBearerHandler
        PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
        PerlSetVar ClientId <CLIENT_ID>
        PerlSetVar ClientSecret <CLIENT_SECRET>
        PerlSetVar ValidateURL https://ssdas.gis.bgu.tum.de/oauth/tokeninfo
        PerlSetVar UserInfoURL https://ssdas.gis.bgu.tum.de/oauth/userinfo

	    Header unset Authorization
	    RewriteEngine on
	    # a
	    #  ?a=foo
	    #  Starts with a=, non-ampersand to the end.
	    #  Suppress querystring with trailing question mark.
	    RewriteCond %{QUERY_STRING} ^access_token=([^&]+)$
	    RewriteRule .* /smart-meters-sos-webapp/service [END,PT,E=REWRITTEN:1]
	    
	    # a-other
	    #  ?a=foo&b=bar, ?a=foo&b=bar&c=1
	    #  Starts with a=, non-ampersand, ampersand, remaining required.
	    #  Escape question mark so it doesn't include entire original querystring.
	    RewriteCond %{QUERY_STRING} ^access_token=([^&]+)&(.+)
	    RewriteRule .* /smart-meters-sos-webapp/service?%2 [END,PT,E=REWRITTEN:1]
	    
	    # other-a or other-a-other
	    #  ?b=baz&a=qux, ?b=baz&c=1&a=qux
	    #  ?c=1&a=foo&d=2&b=bar&e=3, ?z=4&c=1&a=foo&d=2&b=bar&e=3
	    #  Starts with anything, ampersand, a=, non-ampersand, remaining optional.
	    #  The remaining optional lets it follow with nothing, or with ampersand and more parameters.
	    #  Escape question mark so it doesn't include entire original querystring.
	    RewriteCond %{QUERY_STRING} ^(.+)&access_token=([^&]+)(.*)$
	    RewriteRule .* /smart-meters-sos-webapp/service?%1%3 [END,PT,E=REWRITTEN:1]
    </If>
    <Elseif "%{ENV:REDIRECT_REWRITTEN} =~ /1/">
        Require all granted
    </Elseif>
	<Else>
        AuthType shibboleth
        ShibRequestSetting requireSession 1
        Require shib-session
	    ShibUseHeaders on
	    PerlAuthzHandler SD::ShibAuthzHandler
        PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
	</Else>
</Location>
````

Create a Perl file in the Perl directory (shown in environment variables). Such as in Ubuntu, create the file ``/usr/local/share/perl/5.22.1/SD/OAuthnBearerHandler.pm``
with the contents provided in [OAuthnBearerHandler.pm](SOS1/OAuthnBearerHandler.pm).

The actual enforcement of the access condition that only TUM users can access the SOS2 is implemented 
in the `<Else>` section via a [PerlAuthzHandler](https://perl.apache.org/docs/2.0/user/handlers/http.html): `PerlAuthzHandler SD::ShibAuthzHandler`.

Create a Perl file in the Perl directory (shown in environment variables). Such as in Ubuntu, create the file ``/usr/local/share/perl/5.22.1/SD/ShibAuthzHandler.pm``
with the contents provided in [ShibAuthzHandler.pm](SOS2/ShibAuthzHandler.pm). 
Make sure to edit line 43:
````perl5
if ($idp_identifier eq 'https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php')
````

### Manage SSL certificates

The original certificates and private keys should be stored centrally in the directory
``/etc/ssl/certs/``.
Other directories should only have symbolic links to these files.

**IMPORTANT**: The passphrase must be removed from the private key!
```bash
openssl rsa -in private_key.pem -out private_key_no_passphrase.pem
```

1.  Configure ``apache``:
    ```bash
    ...
    
    SSLCertificateFile      /etc/ssl/certs/certificate.pem
    SSLCertificateKeyFile   /etc/ssl/certs/private_key_no_passphrase.pem
    SSLCertificateChainFile /etc/ssl/certs/chain.pem
    
    ...
    ```

1.  Change the owner and group of the certificates and private keys to ``apache``:
    ```bash
    cd /etc/ssl/certs/
    chown _shibd:_shibd ./private_key_no_passphrase.pem
    chown _shibd:_shibd ./private_key.pem
    chown _shibd:_shibd ./certificate.pem
    chown _shibd:_shibd ./chain.pem
    ```

    Then change the permission to read-only for the owner of the private key
    and write-only for the owner of the certificate file:
    ```bash
    chmod 400 ./private_key_no_passphrase.pem
    chmod 400 ./private_key.pem
    chmod 644 ./certificate.pem
    chmod 644 ./chain.pem
    ```

1.  Create symbolic links the private key and certificate to the directory ``/etc/shibboleth/``:
    ```bash
    cd /etc/shibboleth/
    ln -s /etc/ssl/certs/private_key_no_passphrase.pem ./
    ln -s /etc/ssl/certs/certificate.pem ./
    ln -s /etc/ssl/certs/chain.pem ./
    ```

### Update metadata
```bash
# Directory that stores the metadata files
cd /etc/shibboleth/

# Google IdP
curl https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php -o google-idp-metadata.xml 

# Federated metadata
curl http://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml -o dfn-aai-basic-metadata.xml
curl http://www.aai.dfn.de/fileadmin/metadata/dfn-aai-metadata.xml -o dfn-aai-metadata.xml
curl https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-edugain+idp-metadata.xml -o dfn-aai-edugain+idp-metadata.xml

# Update metadata signature
curl https://www.aai.dfn.de/fileadmin/metadata/dfn-aai.g2.pem -o dfn-aai.g2.pem
```

### Update ``/etc/shibboleth/shibboleth2.xml``
```xml
<ApplicationDefaults entityID="https://ssdsos<N>.gis.bgu.tum.de/shibboleth"
                     REMOTE_USER="eppn persistent-id targeted-id">
    
<SSO discoveryProtocol="SAMLDS" discoveryURL="https://ssdds.gis.bgu.tum.de/WAYF">
    SAML2
</SSO>

<!-- SDDI Google IdP Metadata -->
<MetadataProvider type="XML" file="google-idp-metadata.xml"/>


<!-- DFN Production -->
<MetadataProvider type="XML" validate="true"
                  uri="https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml"
                  backingFilePath="dfn-aai-basic-metadata.xml" reloadInterval="3600">
    <MetadataFilter type="Signature" certificate="dfn-aai.g2.pem"/>
</MetadataProvider>
<MetadataProvider type="XML" validate="true"
                  uri="https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-metadata.xml"
                  backingFilePath="dfn-aai-metadata.xml" reloadInterval="3600">
    <MetadataFilter type="Signature" certificate="dfn-aai.g2.pem"/>
</MetadataProvider>

<!-- eduGain -->
<MetadataProvider type="XML" validate="false"
                  uri="https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-edugain+idp-metadata.xml"
                  backingFilePath="dfn-aai-edugain+idp-metadata.xml" reloadInterval="3600">
    <!--MetadataFilter type="Signature" certificate="dfn-aai.g2.pem"/-->
</MetadataProvider>

<!-- Certificates. -->
<CredentialResolver type="File" key="private_key_no_passphrase.pem" certificate="certificate.pem"/>
```
