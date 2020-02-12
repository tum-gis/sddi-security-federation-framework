# Protecting the Service Provider
In the current deployment of the SDDi Security Demonstrator, the SOS1 and SOS2 are protected via two different methods:

* SAML (Shibboleth) sessions based on HTTP cookies
* OAuth2 Bearer Access Tokens released from the Authorization Server

**Note:** *In the current configuration, the endpoints do not use Bearer access tokens released by the Authorization Server!*

## Installation
The setup for protecting the SOS endpoints to use HTTP Cookies is based on the [Shibboleth Service Provider installation](https://www.switch.ch/aai/guides/sp/installation/).

The setup for protecting the SOS endpoints to use Bearer tokens is described in the README.md located in the `RS` directory.

### Configuration
The v2 of the Shibboleth Service Provider is used. The  actual configuration can be found on the machines hosting the SOS1 and SOS2.

* /etc/shibboleth: This is the main configuration directory for the Shibboleth Service Provider
* /etc/httpd/conf.d/sos.conf: This file contains the main configuration and access control part.

#### SOS1 Apache Web Server Configuration
The following snippet illustrates the dual protection applied to the `/weather-sensors-sos-webapp/service` endpoint.

The `<If "%{HTTP:Authorization} =~ /Bearer/ || %{QUERY_STRING} =~ /access_token/">` section defines the Bearer token protection. 

The `<Elseif "%{ENV:REDIRECT_REWRITTEN} =~ /1/">` is required to ensure non-redirct looping for Bearer token based access.

The `<Else>` section defines the HTTP Cookie (Shibboleth) Session setup.

````
<Location /weather-sensors-sos-webapp/service>
        Header always set Cache-Control "private, no-cache, no-store, proxy-revalidate, no-transform
        Header always set Pragma "no-cache"
        <If "%{HTTP:Authorization} =~ /Bearer/ || %{QUERY_STRING} =~ /access_token/">
            AuthType Bearer
            AuthName "SSD Security Demonstrator"
            Require valid-user
            PerlAuthenHandler SD::OAuthnBearerHandler
            PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
            PerlSetVar ClientId 5a00af9a-ba8e-f5bd-cb5e-54d3aff6b8ff@asdev.sddi.secure-dimensions.de
            PerlSetVar ClientSecret 1587f03298f08df8f13e616c6652c2c1b141f0f34f634ce58db4bcdaa13fbcf9
            PerlSetVar ValidateURL https://as.sddi.secure-dimensions.de/oauth/tokeninfo

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

#### SOS2 Apache Web Server Configuration
The SOS2 configuration is basically identical to the SOS1 with one important difference: The access control condition requires TUM users!

````
    <Location /smart-meters-sos-webapp/service>
        Header always set Cache-Control "private, no-cache, no-store, proxy-revalidate, no-transform
        Header always set Pragma "no-cache"
    <If "%{HTTP:Authorization} =~ /Bearer/ || %{QUERY_STRING} =~ /access_token/">
        AuthType Bearer
        AuthName "SSD Security Demonstrator"
        Require valid-user
        PerlAuthenHandler SD::OAuthzBearerHandler
        PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
        PerlSetVar ClientId 5a00af9a-ba8e-f5bd-cb5e-54d3aff6b8ff@asdev.sddi.secure-dimensions.de
        PerlSetVar ClientSecret 1587f03298f08df8f13e616c6652c2c1b141f0f34f634ce58db4bcdaa13fbcf9
        PerlSetVar ValidateURL https://as.sddi.secure-dimensions.de/oauth/tokeninfo
        PerlSetVar UserInfoURL https://as.sddi.secure-dimensions.de/oauth/userinfo

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

The actual enforcement of the access condition that only TUM users can access the SOS2 is implemented in the `<Else>` section via a [PerlAuthzHandler](https://perl.apache.org/docs/2.0/user/handlers/http.html): `PerlAuthzHandler SD::ShibAuthzHandler`.

## ShibAuthzHandler
The file `ShibAuthzHandler.pm` can be found in the `SP` directory.