# Resource Server
The protection of an API hosted on an Apache Web Server can be enforced by enabling the API endpoint to act as an OAuth2 Resource Server. An OAuth2 Resource Server as defined in [RFC6750](https://tools.ietf.org/html/rfc6750) can be enabled by deploying the `OAuthnBearerHandler` as an Apache Web Server [PerlAuthnHandler](https://perl.apache.org/docs/2.0/user/handlers/http.html) for the endpoint path.

The `OAuthnBearerHandler` handler introspects the intercepted Apache HTTP request and checks if an OAuth2 Bearer access token exists either in the HTTP Header, query string or body. In case an access token is found, the handler uses the [Token Introspection](https://tools.ietf.org/html/rfc7662) endpoint from the configured OAuth2 Authorization Server to test for the validity of the token.

This version of the handler does permit the intercepted request if the introspected access token is valid (active). Any other error response received from the Introspection endpoint is forwarded to the client.

## Installation
The deployment of the `OAuthnBearerHandler` hanlder requires to install `mod_perl` and dependency packages: 

````
yum -y install mod_perl perl-libapreq2 perl-libwww-perl perl-JSON perl-LWP-Protocol-https perl-Crypt-SSLeay
````

Once that installation completed, deploy the `OAuthnBearerHandler` handler. The handler use the namespace `SD` and must therefore be put into a directory named `SD`. The `SD` directory must be created somewhere in the INC-path used by the perl installation. For CENTOS 7, the following command creates the directory for the handler:

````
mkdir -p /usr/local/lib64/perl5/SD
````

The final stept is to copy (scp) the `OAuthnBearerHandler.pm` file into the created directory.

## Configuration
The activation of the `OAuthnBearerHandler` and its configuration can take place in the Apache configuration file. The following example illustrates that:

````
AuthType Bearer
AuthName "SSDI Security Demonstrator"
Require valid-user
PerlAuthenHandler SD::OAuthnBearerHandler
PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
PerlSetVar ClientId <please paste the client_id here>
PerlSetVar ClientSecret <please paste the client_secret here>
PerlSetVar ValidateURL https://<your domain name for the AS>/oauth/tokeninfo
````

**Note:** *It is important that the `OAuthnBearerHandler` handler uses a valid `client_id` and `client_secret` resulting from a former registration with the Authorization Server as a Service Application.*
