# Test Cases
# OAuth2 / OpenID Connect Authorization Server with SAML2 Authentication
Testing the implementation of this [Authorization Server](ISSUER) with SAML authentiation requires a deployment of the Authorization Server plus at the minimum one Identity Provider (IdP). The configuration of the IdP must push user attributes such that at least one attribute is a available for each OpenID Connect scope: For the scope `profile` some ficticous user's `preferred username` or `display name` can be used; for the scope `email`some dummy `email address` is fine.

Assuming that such an IdP exists, the following sections test the functionality of the Authorization Server:

* Testing OAuth2 compliance focuses on testing the Authorization Flows and Grant Types on the `/oauth/authorize` and `/token` endpoints as specified in [RFC 6749](https://tools.ietf.org/html/rfc6749)
* Testing OpenID Connect UserInfo focuses on flows involving the response types `id_token` as well as interacting with the `/openid/userinfo` endpoint as specified in [OpenID Connect Core 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-core-1_0.html)
* Token Introspection focuses on the `/oauth/tokeninfo` endpoint that implements [OAuth 2.0 Token Introspection](https://tools.ietf.org/html/rfc7662)
* Token Revocation focuses on the `/oauth/revoke` endpoint as specified in [OAuth 2.0 Token Revocation](https://tools.ietf.org/html/rfc7009)
* Section GDPR compliance focuses on the collection, storage and processing of personal information based on the regulations expressed in the [General Data Protection Regulation](https://eur-lex.europa.eu/legal-content/EN/TXT/?uri=uriserv:OJ.L_.2016.119.01.0001.01.ENG) published by the European Union.

The objective of the tests is to verify an error free implementation of the SAML2 related classes that implement the brokering of SAML user attributes into OpenID claims. The basic tests of the OAuth2 library are considered as passed.

## Preperations
Before the tests can begin, the Authorization Server must 'know' test applications that are fit for purpose to execute the different tests. For testing the different flows (authorization code flow, implicit, client credentials) and the GDPR compliance you first need to register applications as the cross product of flows and scopes.

The Authorization Server supports the following scopes:

````
scopes_supported: [
"openid",
"profile",
"email",
"saml",
"offline_access"
]
````

* Register with type `Client-side Web Appliction` will result in applications that are allowed to execute the `implicit` flow. In order to test the GDPR complianc, we need to have one application with no scope selected, `saml`, `openid`, `profile`, `email` and `profile + email`.
* Register with type `Server-side Web Appliction` will result in applications that are allowed to execute the `authorization code` flow. In order to test the GDPR complianc, we need to have one application with no scope selected, `saml`, `openid`, `profile`, `email` and `profile + email`.
* Register with type `Service Application` will result in applications that are allowed to execute the `implicit` flow. In order to test the GDPR complianc, we need to have one application with no scope selected, `saml`, `openid`, `profile`, `email` and `profile + email`.

### Registering Test Applications
In order to register the test applications, simply set the `create_test_clients=true` in `.../config/config.php`. Make sure that also `create_db=true` is set.

#### Registered Test Applications
The applications that get automatically registered are the following:

````
| client_id                            | client_secret                                                    | redirect_uri                                                                                                                            | grant_types                      | scope                                    | user_id                   | client_name                                | software_version | tos_uri                              | policy_uri                                 | logo_uri                                      | operator_name          | operator_uri                     | operator_address | operator_country | operator_privacy | user_name | user_email                | created             |
+--------------------------------------+------------------------------------------------------------------+-----------------------------------------------------------------------------------------------------------------------------------------+----------------------------------+------------------------------------------+---------------------------+--------------------------------------------+------------------+--------------------------------------+--------------------------------------------+-----------------------------------------------+------------------------+----------------------------------+------------------+------------------+------------------+-----------+---------------------------+---------------------+
| 0a0036ff-52fa-a2ad-c884-af80ae377730 | 3fe895588fe4553d8f121ca5084d23dc5ecdf19341d5c861197f404a8f71dacc | http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/  | authorization_code refresh_token | openid profile email saml offline_access | info@secure-dimensions.de | GDPR Test MobileApp - Level Email/Profile  | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-profile.png | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:05 |
| 1585cd2b-c8aa-1179-a6aa-b55fb9024802 | 34be2a61e9de6f95631cd2bd32953e52df71ec5053d3e658bb1cfba8279e5e61 | http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/  | authorization_code refresh_token | openid email saml offline_access         | info@secure-dimensions.de | GDPR Test MobileApp - Level Email          | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-email.png   | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:00 |
| 1b8114a5-123e-084d-2557-3792cc585783 | NULL                                                             | http://127.0.0.1:4711/web-app/                                                                                                          | implicit                         | openid email saml                        | info@secure-dimensions.de | GDPR Test App - Level Email                | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-email.png   | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:25 |
| 1dbd9518-7624-950e-5f0b-cdb61a66f9fc | 8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7 |                                                                                                                                         | client_credentials               | openid saml                              | info@secure-dimensions.de | GDPR Test ServiceApp - Level Cryptoname    | 1                | http://127.0.0.1:4711/TermsOfUse.php |                                            | http://127.0.0.1:4711/images/icon-id.png      | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:16 |
| 2bd0defa-9919-945c-18f1-a16a37fa2881 | c73e3f736fe03cb7d09d7f871242a2c67ab16160d6b8acee0c4d691dd7663bc3 | http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/  http://127.0.0.1:4711/refresh-app/ | authorization_code refresh_token |  saml offline_access                     | info@secure-dimensions.de | GDPR Test MobileApp - Level Auth           | 1                | http://127.0.0.1:4711/TermsOfUse.php |                                            | http://127.0.0.1:4711/images/icon-auth.png    | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:43 |
| 2dbbfbf0-cfda-2860-99ff-552278db2e71 | NULL                                                             | http://127.0.0.1:4711/web-app/ http://127.0.0.1:4711/logout-webapp/                                                                     | implicit                         | openid saml                              | info@secure-dimensions.de | GDPR Test App - Level Cryptoname           | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-id.png      | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:09 |
| 2f5d0a34-5f76-c8c6-b262-0d38cd9e4185 | fa458a15d01112826963f6261cafed88367a89922a25c3ef90e400ae3224266a | http://127.0.0.1:4711/revocation-app/                                                                                                   | authorization_code refresh_token |  saml offline_access                     | info@secure-dimensions.de | Revocation Test MobileApp - Level Auth     | 1                | http://127.0.0.1:4711/TermsOfUse.php |                                            | http://127.0.0.1:4711/images/icon-auth.png    | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-24 14:01:55 |
| 4ce41c30-7f58-6b87-107a-575fa0d114d0 | a17912e39c2269e4b6dce3130c5e4cff0ad2660eed3917518b704e0768f4713a |                                                                                                                                         | client_credentials               | openid email saml                        | info@secure-dimensions.de | GDPR Test ServiceApp - Level Email         | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-email.png   | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:27 |
| 8d86fcb1-f720-3a95-1070-0a4eb8d050ab | 210bebb3007724e974dd6175861f44c6cbfc4809b052c77147c82fb1ebf891fc | http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/  | authorization_code refresh_token | openid profile saml offline_access       | info@secure-dimensions.de | GDPR Test MobileApp - Level Profile        | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-profile.png | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:54 |
| 92cfde1b-09b2-5220-a447-10d4f555a083 | 49143db5a04b0c5394b4228d9015113d761f95f245b5445a7dcc5a42ec2e433c |                                                                                                                                         | client_credentials               | openid profile email saml                | info@secure-dimensions.de | GDPR Test ServiceApp - Level Email/Profile | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-openid.png  | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:33 |
| 952cf7e3-be59-d56e-1177-7cde1233e920 | NULL                                                             | http://127.0.0.1:4711/web-app/ http://127.0.0.1:4711/logout-webapp/                                                                     | implicit                         | openid profile email saml                | info@secure-dimensions.de | GDPR Test App - Level Email/Profile        | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-openid.png  | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:30 |
| 9aa391cd-ea50-8a70-545a-1c51879a1dd5 | 9e15b6b3c5099449c82327f01feac2b90b64ef7238ab765ce0e7134cf93c7bc5 | http://127.0.0.1:4711/mobile-app/ http://127.0.0.1:4711/gdpr-app/ http://127.0.0.1:4711/logout-app/ http://127.0.0.1:4711/refresh-app/  | authorization_code refresh_token | openid saml                              | info@secure-dimensions.de | GDPR Test MobileApp - Level Cryptoname     | 1                | http://127.0.0.1:4711/TermsOfUse.php |                                            | http://127.0.0.1:4711/images/icon-id.png      | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:49 |
| a13f8fbf-e015-d76b-8057-71bd1cc089a8 | 8e69239aef414ad196b3a5ede48782a0346bae8a411e56156944f3a578591b77 |                                                                                                                                         | client_credentials               | openid profile saml                      | info@secure-dimensions.de | GDPR Test ServiceApp - Level Profile       | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-profile.png | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:21 |
| e730605f-ec6b-fd72-1ff9-d10729e8bed6 | f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6 |                                                                                                                                         | client_credentials               |  saml                                    | info@secure-dimensions.de | GDPR Test ServiceApp - Level Auth          | 1                | http://127.0.0.1:4711/TermsOfUse.php |                                            | http://127.0.0.1:4711/images/icon-auth.png    | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:38:12 |
| f689fc48-5934-8853-72e9-190ee6748795 | NULL                                                             | http://127.0.0.1:4711/web-app/                                                                                                          | implicit                         | openid profile saml                      | info@secure-dimensions.de | GDPR Test App - Level Profile              | 1                | http://127.0.0.1:4711/TermsOfUse.php | http://127.0.0.1:4711/PrivacyStatement.php | http://127.0.0.1:4711/images/icon-profile.png | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:37:19 |
| f8910358-fed1-3d49-8183-913ddede237e | NULL                                                             | http://127.0.0.1:4711/web-app/ http://127.0.0.1:4711/logout-webapp/                                                                     | implicit                         |  saml                                    | info@secure-dimensions.de | GDPR Test App - Level Auth                 | 1                | http://127.0.0.1:4711/TermsOfUse.php |                                            | http://127.0.0.1:4711/images/icon-auth.png    | Secure Dimensions GmbH | https://www.secure-dimensions.de | Munich, Germany  | DE               | NULL             | TEST      | info@secure-dimensions.de | 2019-10-17 10:36:47 |
````

### Test Preperation
Testing the different endpoints, grant types, response types as well as the token revocation, token info and user info endpoints cannot be simply achieved. For the purpose of simplifying the tests, a Test Web Server is available that participates in the test execution.

For running the tests below for the Authorization Server you deployed, it is necessary to tell the Test Web Server about the domain name of that deployment. To configure the Test Web Server, please go to the sub-directory `.../test/AS` and install the required libraries (run `composer install`) first. The requried library provides a parser that generates the test HTML page from the existing MARKDOWN page. Part of that parsing is the replacement of the placeholders for the different endpoints that will be tested.

To start the Test Web Server, you only need to export the `OPENID_CONFIGURATION` environment variable which will be used by the Test Web Server to auto-configure all endpoints.

````
c#> cd authorization-server/test/AS
c#> export OPENID_CONFIGURATION=https://<DOMAIN NAME FOR YOUR DEPLOYMENT>/.well-known/openid-configuration 
c#> php -S 127.0.0.1:4711 -t html
````

For executing the tests, please navigate your Web Browser the auto-configured test page: <http://127.0.0.1:4711>.

**Note:**
This markdown document contains incorrect URL, as the protocol and hostname are replaced with a CONSTANT identifier. To conduct the tests, a client side test web server must be exeuted (see above). This test web server uses a markdown-to-html functionality to transform the mark-down to html and to exchange the CONSTANTS with meaningful values. These values are fetched from the OpenID Connect Discovery URL which is set by the `OPENID_CONFIGURATION` environment valiable (see above).


## Testing OAuth2 Compliance
This Authorization Server implementation supports the OpenID Connect discovery as specified in [OpenID Connect Discovery 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-discovery-1_0.html). The response exposed via the [/.well-known/openid-configuration](ISSUER/.well-known/openid-configuration) endpoint lists the following supported grant and response types:

````
grant_types_supported: [
"authorization_code",
"implicit",
"client_credentials"
]
````

````
response_types_supported: [
"code",
"token",
"id_token",
"code id_token",
"token id_token"
]
````

The following sections define the tests to verify the implementation concerning the supported grant type (Authorization Code, Implicit and Client Credentials).

### Testing Authorization Code Flow
The `Authorization Code Flow` is based on a HTTP redirect from the `/oauth/authorize` endpoint to the `redirect_uri` of the application. All applications that are registered for testing the `grant_tpye=authorization_code` use a redirect to `127.0.0.1:4711`. In order to illustrate the test results, please start the PHP demo web server using the file `test_authorization_code.php`.

The `Authorization Code Flow` can be combined with different response types. The response type `code` would trigger the pure OAuth2 capabilities and any token obtained cannot fetch user claims via the `/openid/userinfo` endpoint. The respnse type `code id_token` requires that the test application is registered with a minimum of the cope `openid`. 

The main purpose of the following tests is to validate that the responses are compliant with the OAuth2 and OpenID Connect specifications and that the claims disclosed in the id_token corresponds to the scopes that the application is registered for.

#### Mobile Application with Scope Elevation
In case an application requests an access token with elevated scope, the Authorization Server must return an error "unsupported scope requested".

With the following request, uses the elevated scope `openid`: 

[AUTHORIZATION_ENDPOINT?client\_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response\_type=code&grant\_type=authorization\_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&](AUTHORIZATION_ENDPOINT?client_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response_type=code&grant_type=authorization_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&)

=> <span style="color:green">Test passed</span>

#### Mobile Application with scope `saml`
The Authorization Server must return neither an `id_token` nor return user claims via the `/openid/userinfo` endpoint.

##### Display Authorization Code via Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml&response\_type=code&grant\_type=authorization\_code&state=display&](AUTHORIZATION_ENDPOINT?client_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml&response_type=code&grant_type=authorization_code&state=display&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml&response\_type=code&grant\_type=authorization\_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&](AUTHORIZATION_ENDPOINT?client_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml&response_type=code&grant_type=authorization_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code id_token`
Result must be an error with HTTP status 403: insufficient scope.

[AUTHORIZATION_ENDPOINT?client\_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml&response\_type=code id\_token&grant\_type=authorization\_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&nonce=123&](AUTHORIZATION_ENDPOINT?client_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml&response_type=code%20id_token&grant_type=authorization_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&nonce=123&)

=> <span style="color:green">Test passed</span>

##### UserInfo Response
Result must be the following error because the application is not registered with a scope that allows to fetch user claims.

```json
{
"error": "insufficient_scope",
"error_description": "The request requires higher privileges than provided by the access token"
}
```

=> <span style="color:green">Test passed</span>

#### Mobile Application with scope `saml openid`
The Authorization Server must return an `id_token` and user claims via the `/openid/userinfo` endpoint. But, the only allowed claims are those for scope `saml`.

##### Display Authorization Code via Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response\_type=code&grant\_type=authorization\_code&state=display&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response_type=code&grant_type=authorization_code&state=display&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response\_type=code&grant\_type=authorization\_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response_type=code&grant_type=authorization_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code id_token`
Result must contain a `code` and `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response\_type=code%20id\_token&grant\_type=authorization\_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&nonce=123&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid&response_type=code%20id_token&grant_type=authorization_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&nonce=123&)

=> <span style="color:green">Test passed</span>

##### UserInfo Response
Result must contain only claims for scope `saml`.

=> <span style="color:green">Test passed</span>

#### Mobile Application with scope `saml openid profile`
The Authorization Server must return an `id_token` and user claims via the `/openid/userinfo` endpoint. But, the only allowed claims are those associated with the scope `profile`.

##### Display Authorization Code via Response Type `code`
Result must contain a `code` parameter only.

[AUTHORIZATION_ENDPOINT?client\_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20profile&response\_type=code&grant\_type=authorization\_code&state=display&](AUTHORIZATION_ENDPOINT?client_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20profile&response_type=code&grant_type=authorization_code&state=display&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code`
Result must contain a `code` parameter only.

[AUTHORIZATION_ENDPOINT?client\_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20profile&response\_type=code&grant\_type=authorization\_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&](AUTHORIZATION_ENDPOINT?client_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20profile&response_type=code&grant_type=authorization_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code id_token`
Result must contain a `code` and `id_token` parameter. The id_token must contain - as a max. - all claims listed for scope `saml` and `profile` as define in the [OpenID Connect Core 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-core-1_0.html)

[AUTHORIZATION_ENDPOINT?client\_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20profile&response\_type=code%20id\_token&grant\_type=authorization\_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&nonce=123&](AUTHORIZATION_ENDPOINT?client_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20profile&response_type=code%20id_token&grant_type=authorization_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&nonce=123&)

=> <span style="color:green">Test passed</span>

##### UserInfo Response
Result must contain claims associated with scope `saml`and `profile`.

=> <span style="color:green">Test passed</span>

#### Mobile Application with scope `saml openid email`
The Authorization Server must return an `id_token` and user claims via the `/openid/userinfo` endpoint. But, the only allowed claims are those associated with the scope `email`.

##### Display Authorization Code via Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email&response\_type=code&grant\_type=authorization\_code&state=display&
](AUTHORIZATION_ENDPOINT?client_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email&response_type=code&grant_type=authorization_code&state=display&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email&response\_type=code&grant\_type=authorization\_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&
](AUTHORIZATION_ENDPOINT?client_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email&response_type=code&grant_type=authorization_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code id_token`
Result must contain a `code` and `id_token` parameter. The id_token must contain - as a max. - all claims listed for scope `saml` and `email` as define in the [OpenID Connect Core 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-core-1_0.html)

[AUTHORIZATION_ENDPOINT?client\_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email&response\_type=code%20id\_token&grant\_type=authorization\_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&nonce=123&
](AUTHORIZATION_ENDPOINT?client_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email&response_type=code%20id_token&grant_type=authorization_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&nonce=123&)

=> <span style="color:green">Test passed</span>

##### UserInfo Response
Result must contain claims associated with scope `saml`and `email`.

=> <span style="color:green">Test passed</span>

#### Mobile Application with scope `saml openid email profile`
The Authorization Server must return an `id_token` and user claims via the `/openid/userinfo` endpoint. But, the only allowed claims are those associated with the scopes `saml`, `email` and `profile`.

##### Display Authorization Code via Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email%20profile&response\_type=code&grant\_type=authorization\_code&state=display&](AUTHORIZATION_ENDPOINT?client_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email%20profile&response_type=code&grant_type=authorization_code&state=display&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code`
Result must contain a `code` and **no** `id_token` parameter.

[AUTHORIZATION_ENDPOINT?client\_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email%20profile&response\_type=code&grant\_type=authorization\_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&](AUTHORIZATION_ENDPOINT?client_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email%20profile&response_type=code&grant_type=authorization_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&)

=> <span style="color:green">Test passed</span>

##### Testing Response Type `code id_token`
Result must contain a `code` and `id_token` parameter. The id_token must contain - as a max. - all claims listed for scope `saml`, `profile` and `email` as define in the [OpenID Connect Core 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-core-1_0.html)

[AUTHORIZATION_ENDPOINT?client\_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect\_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email%20profile&response\_type=code%20id\_token&grant\_type=authorization\_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&nonce=123&
](AUTHORIZATION_ENDPOINT?client_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect_uri=http://127.0.0.1:4711/mobile-app/&scope=saml%20openid%20email%20profile&response_type=code%20id_token&grant_type=authorization_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&nonce=123&)

=> <span style="color:green">Test passed</span>

##### UserInfo Response
Result must contain claims associated with scope `saml`, `profile` and `email`.

=> <span style="color:green">Test passed</span>

### Testing Implicit Flow
The `Implicit Flow` can be combined with different response types. The response type `token` triggers the pure OAuth2 capabilities and any access token obtained by the application cannot request an `id_token`.

An application registered with the `grant_type = implicit` should not fetch user claims via the `/openid/userinfo` endpoint. The application should use the respnse type `id_token token` to fetch an `id_token` containing the user claims. 

The Authorization Server does only return an `id_token` if the scope of the registered application is at least `openid`. 

The main purpose of the following tests is to validate that the responses are compliant with the OAuth2 and OpenID Connect specifications and that the claims disclosed in the id_token corresponds to the scopes that the application is registered for.

#### Test Preperation
The Implicit Flow is based on a HTTP redirect from the `/oauth/authorize` endpoint to the `redirect_uri` of the application. All applications that are registered for testing the `grant_tpye=implicit` use a redirect to `127.0.0.1:4711/web-app`. In order to illustrate the test results, the Test Web Server is used.

For executing a test, please copy and paste a URL from the sub-sections into your favorite Web Browser. The redirect will be processed by the test web server that you've just started with the command above.

#### Web Appplication with Scope Elevation
In case an application requests an access token with elevated scope, the Authorization Server must return an error "unsupported scope requested".

With the following request, uses the elevated scope `openid`: 

[AUTHORIZATION_ENDPOINT?client\_id=f8910358-fed1-3d49-8183-913ddede237e&response\_type=token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml%20openid&](AUTHORIZATION_ENDPOINT?client_id=f8910358-fed1-3d49-8183-913ddede237e&response_type=token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml%20openid&)

=> <span style="color:green">Test passed</span>

#### Web Application with scope `saml`
The Authorization Server must return an access token `token` in the URL fragment. It must not return an `id_token` because of insufficient scope of the application.

##### Authorize Request with Response Type `token`
The Authorization Server must return `token` only.

[AUTHORIZATION_ENDPOINT?client\_id=f8910358-fed1-3d49-8183-913ddede237e&response\_type=token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml&](AUTHORIZATION_ENDPOINT?client_id=f8910358-fed1-3d49-8183-913ddede237e&response_type=token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token`
The Authorization Server must return HTTP Status 403 with `insufficient_scope`.

[AUTHORIZATION_ENDPOINT?client\_id=f8910358-fed1-3d49-8183-913ddede237e&response\_type=id\_token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml&](AUTHORIZATION_ENDPOINT?client_id=f8910358-fed1-3d49-8183-913ddede237e&response_type=id_token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token token`
The Authorization Server returns `token` and **no** `id_token` (partial response)

[AUTHORIZATION_ENDPOINT?client\_id=f8910358-fed1-3d49-8183-913ddede237e&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml&](AUTHORIZATION_ENDPOINT?client_id=f8910358-fed1-3d49-8183-913ddede237e&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f8910358-fed1-3d49-8183-913ddede237e&scope=saml&)

=> <span style="color:green">Test passed</span>

#### Web Application with scope `saml openid`
The Authorization Server must return an access token `token` in the URL fragment. It must also return an `id_token` if requested including the claims for the scopes `saml` and `openid`.

##### Authorize Request with Response Type `token`
The Authorization Server must return `token` only.

[AUTHORIZATION_ENDPOINT?client\_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&response\_type=token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&scope=saml%20openid&](AUTHORIZATION_ENDPOINT?client_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&response_type=token&redirect_uri=http://127.0.0.1:4711/web-app/&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&scope=saml%20openid&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token`
The Authorization Server must return `id_token` only.

[AUTHORIZATION_ENDPOINT?client\_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&response\_type=id\_token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&scope=saml%20openid&](AUTHORIZATION_ENDPOINT?client_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&response_type=id_token&redirect_uri=http://127.0.0.1:4711/web-app/&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&scope=saml%20openid&)

=> <span style="color:green">Test passed</span>
##### Authorize Request with Response Type `id_token token`
The Authorization Server must return `token` and `id_token` including user claim `sub`.

[AUTHORIZATION_ENDPOINT?client\_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&scope=saml%20openid&](AUTHORIZATION_ENDPOINT?client_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&scope=saml%20openid&)

=> <span style="color:green">Test passed</span>

#### Web Application with scope `saml openid profile`
The Authorization Server must return an access token `token` in the URL fragment. It must also return an `id_token` if requested including the claims for the scopes `saml`, `openid` and `profile`.

##### Authorize Request with Response Type `token`
The Authorization Server must return `token` only.

[AUTHORIZATION_ENDPOINT?client\_id=f689fc48-5934-8853-72e9-190ee6748795&response\_type=token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f689fc48-5934-8853-72e9-190ee6748795&scope=saml%20openid%20profile&](AUTHORIZATION_ENDPOINT?client_id=f689fc48-5934-8853-72e9-190ee6748795&response_type=token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f689fc48-5934-8853-72e9-190ee6748795&scope=saml%20openid%20profile&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token`
The Authorization Server must return `id_token` only.

[AUTHORIZATION_ENDPOINT?client\_id=f689fc48-5934-8853-72e9-190ee6748795&response\_type=id\_token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f689fc48-5934-8853-72e9-190ee6748795&scope=saml%20openid%20profile&](AUTHORIZATION_ENDPOINT?client_id=f689fc48-5934-8853-72e9-190ee6748795&response_type=id_token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f689fc48-5934-8853-72e9-190ee6748795&scope=saml%20openid%20profile&)

=> <span style="color:green">Test passed</span>


##### Authorize Request with Response Type `id_token token`
The Authorization Server must return `token` and `id_token` including user claims for scopes `saml` and `profile`.

[AUTHORIZATION_ENDPOINT?client\_id=f689fc48-5934-8853-72e9-190ee6748795&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=f689fc48-5934-8853-72e9-190ee6748795&scope=saml%20openid%20profile&](AUTHORIZATION_ENDPOINT?client_id=f689fc48-5934-8853-72e9-190ee6748795&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=f689fc48-5934-8853-72e9-190ee6748795&scope=saml%20openid%20profile&)

=> <span style="color:green">Test passed</span>

#### Web Application with scope `saml openid email`
The Authorization Server must return an access token `token` in the URL fragment. It must also return an `id_token` if requested including the claims for the scopes `saml`, `openid` and `email`.

##### Authorize Request with Response Type `token`
The Authorization Server must return `token` only.

[AUTHORIZATION_ENDPOINT?client\_id=1b8114a5-123e-084d-2557-3792cc585783&response\_type=token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=1b8114a5-123e-084d-2557-3792cc585783&scope=saml%20openid%20email&](AUTHORIZATION_ENDPOINT?client_id=1b8114a5-123e-084d-2557-3792cc585783&response_type=token&redirect_uri=http://127.0.0.1:4711/web-app/&state=1b8114a5-123e-084d-2557-3792cc585783&scope=saml%20openid%20email&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token`
The Authorization Server must return `id_token` only.

[AUTHORIZATION_ENDPOINT?client\_id=1b8114a5-123e-084d-2557-3792cc585783&response\_type=id\_token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=1b8114a5-123e-084d-2557-3792cc585783&scope=saml%20openid%20email&](AUTHORIZATION_ENDPOINT?client_id=1b8114a5-123e-084d-2557-3792cc585783&response_type=id_token&redirect_uri=http://127.0.0.1:4711/web-app/&state=1b8114a5-123e-084d-2557-3792cc585783&scope=saml%20openid%20email&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token token`
The Authorization Server must return `token` and `id_token` including user claims for scopes `saml` and `email`.

[AUTHORIZATION_ENDPOINT?client\_id=1b8114a5-123e-084d-2557-3792cc585783&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=1b8114a5-123e-084d-2557-3792cc585783&scope=saml%20openid%20email&](AUTHORIZATION_ENDPOINT?client_id=1b8114a5-123e-084d-2557-3792cc585783&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=1b8114a5-123e-084d-2557-3792cc585783&scope=saml%20openid%20email&)

=> <span style="color:green">Test passed</span>

#### Web Application with scope `saml openid email profile`
The Authorization Server must return an `id_token` and return user claims via the `/openid/userinfo` endpoint for claim `sub` and claims for scopes `saml`, `email` and `profile`.

##### Authorize Request with Response Type `token`
The Authorization Server must return `token` only.

[AUTHORIZATION_ENDPOINT?client\_id=952cf7e3-be59-d56e-1177-7cde1233e920&response\_type=token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&](AUTHORIZATION_ENDPOINT?client_id=952cf7e3-be59-d56e-1177-7cde1233e920&response_type=token&redirect_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&)

=> <span style="color:green">Test passed</span>

##### Authorize Request with Response Type `id_token`
The Authorization Server must return `id_token` only.

[AUTHORIZATION_ENDPOINT?client\_id=952cf7e3-be59-d56e-1177-7cde1233e920&response\_type=id\_token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&](AUTHORIZATION_ENDPOINT?client_id=952cf7e3-be59-d56e-1177-7cde1233e920&response_type=id_token&redirect_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&)

=> <span style="color:green">Test passed</span>


##### Authorize Request with Response Type `id_token token`
The Authorization Server must return `token` and `id_token` including user claims for scopes `saml`, `email` and `profile`.

[AUTHORIZATION_ENDPOINT?client\_id=952cf7e3-be59-d56e-1177-7cde1233e920&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&](AUTHORIZATION_ENDPOINT?client_id=952cf7e3-be59-d56e-1177-7cde1233e920&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&)

=> <span style="color:green">Test passed</span>





### Testing Client Credentials Flow
The Authorization Server supports the grant_type `client_credentials` which allows that Service Applications can request access tokens based on their client_id / client_secret combination - aka the client credentials.

Such an access token can be used as an alterantive for identification as defined in [OAuth 2.0 Token Introspection](https://tools.ietf.org/html/rfc7662). It could also be used to execute the UserInfo endpoint to obtain user information as defined in [OpenID Connect Core 1.0 incorporating errata set 1](https://openid.net/specs/openid-connect-core-1_0.html). But which user claims shall the Authorization Server return for such a request? There is no active user invloved, so the access token **cannot** be linked to any user claims. However, the Authorization Server could release the user claims for the user that registerred the Service Application. 

This Authorization Server does return the error `invalid_token` if a Bearer access token obtained via the Client Credentials grant is used as a token requesting user claims via the UserInfo endpoint.

The `client_credentials` grant type does not support the parameter `reponse_type`. The default `response_type` is `token`.

It is sufficient to test the behaviour with a Service Application which has no scope that would allow to request user claims (e.g. `scope=saml`) and with another Service Application which has the `scope=openid` as that would allow to execute the UserInfo endpoint. It is not necessary to repeat the tests with applications that have additional scopes like `profile` and/or `email` as they all fall into the same category.

#### Preperation
The `/oauth/token` endpoint must be executed via HTTP POST. For convenience, you can start the test web server and then issue HTTP GET requests to the Test Web Server. The test web server transforms the query string into a POST request to the `/oauth/token` endpoint.

#### Token Request with elevated Scope `openid` and Response Type `token`
Authorization Server must return error `invalid_scope`.

[http://127.0.0.1:4711/service-app/?client\_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client\_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&scope=openid&](http://127.0.0.1:4711/service-app/?client_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&scope=openid&)

````
curl -i -L -X POST \
   -H "Content-Type:application/x-www-form-urlencoded" \
   -d "client_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6" \
   -d "scope=openid" \
   -d "client_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6" \
   -d "grant_type=client_credentials" \
 'TOKEN_ENDPOINT'
````

=> <span style="color:green">Test passed</span>

#### Token Request with Scope `saml` and Response Type `token`
Authorization Server must return an access token an a error `invalid_token` for the UserInfo request.

[http://127.0.0.1:4711/service-app/?client\_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client\_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&](http://127.0.0.1:4711/service-app/?client_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&)

````
curl -i -L -X POST \
   -H "Content-Type:application/x-www-form-urlencoded" \
   -d "client_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6" \
   -d "client_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6" \
   -d "grant_type=client_credentials" \
 'TOKEN_ENDPOINT'
````

=> <span style="color:green">Test passed</span>

#### Token Request with Scopes `saml`  and `openid` 
Authorization Server must return a valid response. Request to UserInfo endpoint must return `invalid_token`.

[http://127.0.0.1:4711/service-app/?client\_id=1dbd9518-7624-950e-5f0b-cdb61a66f9fc&client\_secret=8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7&](http://127.0.0.1:4711/service-app/?client_id=1dbd9518-7624-950e-5f0b-cdb61a66f9fc&client_secret=8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7&)

````
curl -i -L -X POST \
   -H "Content-Type:application/x-www-form-urlencoded" \
   -d "client_id=1dbd9518-7624-950e-5f0b-cdb61a66f9fc" \
   -d "client_secret=8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7" \
   -d "grant_type=client_credentials" \
   -d "scope=saml openid" \
 'TOKEN_ENDPOINT'
````

=> <span style="color:green">Test passed</span>

## Testing Refresh Token Grant Type
The Authorization Server returns a new access_token and refresh_token when using the `refresh_token` grant type.

It is important to test that scope elevation is not possible. Otherwise, an application could request more scopes which might result in access to restricted resources including personal information.

### Obtaining an Access Token for a Refresh Token
The Authorization Server returns a fresh pair of `access_token` and `refresh_token`:

[AUTHORIZATION_ENDPOINT?client\_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect\_uri=http://127.0.0.1:4711/refresh-app/&scope=offline\_access&response\_type=code&grant\_type=authorization\_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&](AUTHORIZATION_ENDPOINT?client_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect_uri=http://127.0.0.1:4711/refresh-app/&scope=offline_access&response_type=code&grant_type=authorization_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&)

=> <span style="color:green">Test passed</span>

### Obtaining an Access Token with Scope Elevation
The Authorization Server returns an error `invalid_scope` when the requested scope elevates the allowed scope. In this example, the application has the scope `saml openid` but attempts to exchange a refresh token to an access token requesting scope `profile`:

[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/refresh-app/&scope=offline\_access&response\_type=code&grant\_type=authorization\_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/refresh-app/&scope=offline_access&response_type=code&grant_type=authorization_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&)

=> <span style="color:green">Test passed</span>

## Testing Token Introspection
The Authorization Server provides a [OAuth 2.0 Token Introspection](https://tools.ietf.org/html/rfc7662) compliant endpoint for obtaining token information.

To prevent token scanning, the endpoint requires application identification to be provided with an introspection request. The Authorization Server accepts client credentials from a registered application as identification. As an alternative, it also accepts an access token from an application registered with `grant_type=client_credentials` as identification.

Example token introspection request using client credentials

````
curl -i -L -X POST \
   -H "Authorization:Basic MWRiZDk1MTgtNzYyNC05NTBlLTVmMGItY2RiNjFhNjZmOWZjOjhmNDI2MGQ5NzZkMzg4Nzc1NGY4N2ViNTQ0NjVmMDM2OTIwZTg5NzUxYzlhZDMxMzM3OGEzNmUwNjQzNDFjZjc=" \
   -H "Content-Type:application/x-www-form-urlencoded" \
   -d "token=4067cc994f03b3ffd027bda9f5885a97aa179aba" \
 'TOKENINFO_ENDPOINT'
````

Example token introspection request using a bearer access token

````
curl -i -L -X POST \
   -H "Authorization:Bearer 6cf8f18fb58a7e0195f6bcef975747f61ac673d4" \
   -H "Content-Type:application/x-www-form-urlencoded" \
   -d "token=4067cc994f03b3ffd027bda9f5885a97aa179aba" \
 'TOKENINFO_ENDPOINT'
````

### Testing Token Introspection with Client Credentials as identification
The Authorization Server must return a valid response in case that valid client credentials are provided for identification in the HTTP Authorization Basic header. The test server requests a valid access token using the Client Credentials Flow that is used for introspection.

[http://127.0.0.1:4711/introspect/?test=client\_credentials&client\_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client\_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&token\_type\_hint=access\_token&](http://127.0.0.1:4711/introspect/?test=client_credentials&client_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&token_type_hint=access_token&)

=> <span style="color:green">Test passed</span>

### Testing Token Introspection with Bearer Token as identification
The Authorization Server differenciates two cases:

* The access token used as Bearer Authentication (in the HTTP Header) is **different from** the token under intropection. In this case, the Authorization Server returns token information. For this case, the test web server requests an access token to be introspected using the client credentials of the Service App - Level Cryptoname (`client_id=1dbd9518-7624-950e-5f0b-cdb61a66f9fc client_secret=8f4260d976d3887754f87eb54465f036920e89751c9ad313378a36e064341cf7`) 
* The access token used as Bearer Authentication (in the HTTP Header) is **identical to** the token under introspection. In this case, the Authorization Server returns an HTTP status code 401 with error code `invalid_token` error (RFC 7662 - section 2.1: "... or a **separate** ... OAuth 2.0 Bearer access token").

[http://127.0.0.1:4711/introspect/?test=bearer&client\_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client\_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&](http://127.0.0.1:4711/introspect/?test=bearer&client_id=e730605f-ec6b-fd72-1ff9-d10729e8bed6&client_secret=f92c9e361b0952b1dc752785fbfbccb4ee837a1913897ce76c76f473fa15d2a6&)

=> <span style="color:green">Test passed</span>


## Testing Token Revocation
The Authorization Server provides a [OAuth 2.0 Token Revocation](https://tools.ietf.org/html/rfc7009) compliant endpoint for revoking tokens. The token kind can only be Bearer - a JWT token cannot be revoked. The Authorization Server supports the token revocation for access and refresh token. If the `token_type_hint` is not present in the request, the Authorization Server assumes `refresh_token`. For revoking access tokens, the use of `token_type_hint=access_token` is required.

In case an access token is revoked, the associated refresh token is not revoked. In case a refresh token is revoked, the Authorization Server revokes all associated access tokens.

The Token Revocation endpoint requires that the calling application uses HTTP Basic Authentication based on `client_id` and `client_secret`. Public clients may submit the `client_id` as part of the request instead of the HTTP Basic Authentication.

The token revocation is restricted to the following condition: `client_id` from the request must be identical to the `client_id` of the token. 

### Testing Token Revocation via HTTP GET
The use of a HTTP GET request must return a HTTP status code 405.
[REVOKE_ENDPOINT?token=foobar&](REVOKE_ENDPOINT?token=foobar&)

=> <span style="color:green">Test passed</span>

### Testing Token Revocation for Public Clients
Public clients cannot revoke access tokens as they cannot supply the required client credentails as per RFC 7009, section 2.1.

Two test cases can be identified for public clients to attempt to revoke an access token. 

#### Revoke with client_id set
The response has status 400 with `invalid_client`.

[AUTHORIZATION_ENDPOINT?client\_id=f689fc48-5934-8853-72e9-190ee6748795&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&scope=saml%20openid&](AUTHORIZATION_ENDPOINT?client_id=f689fc48-5934-8853-72e9-190ee6748795&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=revoke_own&scope=saml%20openid&)

=> <span style="color:green">Test passed</span>

#### Revoke with no client credentials
The response has status 400 with `invalid_client`.

[AUTHORIZATION_ENDPOINT?client\_id=f689fc48-5934-8853-72e9-190ee6748795&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&scope=saml%20openid&](AUTHORIZATION_ENDPOINT?client_id=f689fc48-5934-8853-72e9-190ee6748795&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=revoke_none&scope=saml%20openid&)

=> <span style="color:green">Test passed</span>

### Testing Token Revocation of an Access and Refresh Token
The sequence of interactions initiated by te test web server starts after the Authorization Server redirects to the `redirect_uri` of the MobileApp. Test web server ...

**Part I - Request an Access token from the Authorization Code and revoke the Access Token**

* ... requests an access token for the authorization code received in the query string of the redirect
* ... requests tokeninfo for the access token
* ... revokes the access token
* ... requests tokenino for the access token  

**Part II - Request an Access token from the Refresh Token and revoke the Refresh Token**

* ... requests tokeninfo for the refresh token received in part I
* ... requests an access token for the refresh token received in part I
* ... requests tokeninfo for the access token
* ... revokes the refresh token
* ... requests tokenino for the refresh token
* ... requests tokenino for the access token

[AUTHORIZATION_ENDPOINT?client\_id=2f5d0a34-5f76-c8c6-b262-0d38cd9e4185&redirect\_uri=http://127.0.0.1:4711/revocation-app/&scope=saml%20offline\_access&response\_type=code&grant\_type=authorization\_code&state=foobar&](AUTHORIZATION_ENDPOINT?client_id=2f5d0a34-5f76-c8c6-b262-0d38cd9e4185&redirect_uri=http://127.0.0.1:4711/revocation-app/&scope=saml%20offline_access&response_type=code&grant_type=authorization_code&state=foobar&)

=> <span style="color:green">Test passed</span>

## Testing GDPR Compliance
The Authorization Server allows operator to register an application of different types as identified in the OAuth2 specification. Each application can obtain personal information from the Authorization Server as OpenID Connect claims if the registered scope is including scope `openid`. Testing the Authorization Server towards GDPR compliance boils down to test that a registered application can only obtain the amount of personal information (i) corresponding the registered scope(s) and (ii) obtain the personal information after the user's authorization.  

When registering an application that wants to consume personal information, the operator (the person registering the application on behalf of the operator) must provide a URL that points to the Privacy Statement of the application. In the linked document, the operator explains (in simple) words to the user of the application the conditions underwhich personal information gets collected, stored, processed and relayed. From the Authorization Server's perspective, it is sufficient that the operator provides a Privacy Statement URL when registering the application. Such an application is then considred a GDPR-compliant application.

When the user executes a GDPR-comliant application for the first time, the Authorization Server requests the user's approval to make the amount of personal data available to the application as requested by the appplication. The Authorization Server displays in the "Approval Window" the amount of personal information available to the application and a hyperlink to the Provacy Statement of the application. The user must click "Approve" to make the personal information available to the application.

The objective of the tests below is to verify that the Authorization Server fulfills the requirements listed above:

* Display the approval dialog to the user when executing the application for the first time.
* The approval dialog must include the hyperlink to the Privacy Statement of the application
* The personal information displayed in the approval dialog and made available to the application, either as `id_token` or via the `/openid/userinfo` endpoint must be identical.
* The maximum amount ot personal information made available to an application via `id_token` or `/openid/userinfo` endpoint is limited by the registered scope of the application.

Validating GDPR Comliance for Mobile- and Web-Applications is included in the tests listed in the provious sections.

Any application that is registered as a Service must use the grant_type `client_credentials`. Even though such a (service) application can request access tokens from the Authorization Server, it must no be possible to obtain any personal information (user claims) via such an access token. This means that the Authorization Server must not return an `id_token` even if the `response_type = id_token token`. Also, the `/openid/userinfo` endpoint must not return user claims for such an access token.


A Service Application protects resources as defined in [The OAuth 2.0 Authorization Framework: Bearer Token Usage](https://tools.ietf.org/html/rfc6750). Therefore, an Access Token of type Bearer, as released by the Authorization Server can be used by any registered application to obtain user claims. It is therefore possible that a Service Application fetches personal information of the user associated with the access token. 

Important is the fact, that the access token might be associated to a superset of user claims compared to scopes of the Service Application. The following tests shall ensure that a Service Application can never obtain more personal information (user claims) as it corresponds to the scopes of the Service Application.

* Service-Application with no scope: `/openid/userinfo` must return `insufficient scope`
* Service-Application with scope `saml`: `/openid/userinfo` must return `insufficient scope` because the response must at least include the claim `sub`. But to release that, it requires the scope `openid`.
* Service Application with scope `openid`: `/openid/userinfo` must return claim `sub`
* Service Application with scope `saml openid`: `/openid/userinfo` must return claims for scope `saml` (including claim `sub`).
* Service Application with scope `openid profile`: `/openid/userinfo` must return claims for scope `profile` (including claim `sub`).
* Service Application with scope `openid email`: `/openid/userinfo` must return claims for scope `email` (including claim `sub`).
* * Service Application with scope `openid email profile`: `/openid/userinfo` must return claims for scope `email profile` (including claim `sub`).


### Preperation
The tests invlove a two step interaction with the Authorization Server: (i) to obtain an access token via the `/oauth/token` endpoint a HTTP POST request must be issued. This can be done via cURL. 

For testing the user claims, returned for the obtained access token, it is required to send another request to the Authorization Server where the previously obtained access token is a parameter. 

For convenience, it is possible to use the Test Web Server.

#### Testing Mobile Application with scope `saml`
[AUTHORIZATION_ENDPOINT?client\_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect\_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml&response\_type=code&grant\_type=authorization\_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&](AUTHORIZATION_ENDPOINT?client_id=2bd0defa-9919-945c-18f1-a16a37fa2881&redirect_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml&response_type=code&grant_type=authorization_code&state=2bd0defa-9919-945c-18f1-a16a37fa2881&)

=> <span style="color:green">Test passed</span>

#### Testing Mobile Application with scope `saml openid`
[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid&response\_type=code&grant\_type=authorization\_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid&response_type=code&grant_type=authorization_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&)

=> <span style="color:green">Test passed</span>

#### Testing Mobile Application with scope `saml openid profile`
[AUTHORIZATION_ENDPOINT?client\_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect\_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid%20profile&response\_type=code&grant\_type=authorization\_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&](AUTHORIZATION_ENDPOINT?client_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid%20profile&response_type=code&grant_type=authorization_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&)

=> <span style="color:green">Test passed</span>

#### Testing Mobile Application with scope `saml openid email`
[AUTHORIZATION_ENDPOINT?client\_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect\_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid%20email&response\_type=code&grant\_type=authorization\_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&
](AUTHORIZATION_ENDPOINT?client_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid%20email&response_type=code&grant_type=authorization_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&)

=> <span style="color:green">Test passed</span>

#### Testing Mobile Application with scope `saml email profile`
[AUTHORIZATION_ENDPOINT?client\_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect\_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid%20email%20profile&response\_type=code&grant\_type=authorization\_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&](AUTHORIZATION_ENDPOINT?client_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect_uri=http://127.0.0.1:4711/gdpr-app/&scope=saml%20openid%20email%20profile&response_type=code&grant_type=authorization_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&)

=> <span style="color:green">Test passed</span>

## Testing Login
The Authorization Server supports the standard OAuth2 / OpenID Connect authorization flows which - at some point - require user authentication. For this implementation, the user can choose from different login providers - aka IdPs - via the [IdP Discovery Service](ISSUER/DiscoveryService). 

The Authorization Server also supports the login via a pre-selected IdP. In order to active this functionality, the paramater `idp` must complement the standard parameters. The value of the `idp` parameter is the entityID of the IdP to use. The identifier can be found via the [IdPs](ISSUER/IdPs) endpoint.

### Testing Login with Login Hint
The Authorization Server allows to bypass the Discovery Service and directly requests login via a specific IdP.

This feature can be enabled by leveraging the `entityID` of the IdP as value to the parameter `login_hint`.

The `entityID` can be obtained from the federation metadata, supported by the Authorization Server.

````
[AUTHORIZATION_ENDPOINT?client\_id=952cf7e3-be59-d56e-1177-7cde1233e920&response\_type=id\_token%20token&redirect\_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&login\_hint=https://google-idp.sddi.secure-dimensions.de/simplesaml/saml2/idp/metadata.php&](AUTHORIZATION_ENDPOINT?client_id=952cf7e3-be59-d56e-1177-7cde1233e920&response_type=id_token%20token&redirect_uri=http://127.0.0.1:4711/web-app/&state=952cf7e3-be59-d56e-1177-7cde1233e920&scope=saml%20openid%20email%20profile&login_hint=https://google-idp.sddi.secure-dimensions.de/simplesaml/saml2/idp/metadata.php&)
````

=> <span style="color:green">Test passed</span>


## Testing Single-Logout
Single-Logout can be initiated based on an active access token or a SAML session.

The Authorization Server has two logical SAML SPs: The first SP (`oauth`) is associated for applications that are registered without personal scopes; so OAuth2 flows only. The second SP is associated with applications registered for scope(s) `profile` or `email` or both; so OpenID Connect flows.

Depending on the applications scopes that a user executes, the Authorization Server establishes one or two independent authentiction sessions. This must be taken under consideration when initiating Single-Logout.

Depending on the application type, a logout can be initiated by leveraging SAML session cookies or must be initiated based on an (active) access token: For Web-Browser based applications that use the Implicit Flow, it is possible to initiate a Logout leveraging the SAML session cookie(s). For a mobile application using the Authorization Code Flow, the logout is initiated based on the access token. This is inparticular important, when the mobile application used a device Web-Browser for the authentication, as the session cookies are stored with the Web-Browser and not with the mobile application itself.

**Note: At any time, but in particular in case that the flow stops at the IdP, please use this URL to verify active session(s) exist and the associated personal information:**

[ISSUER/saml/sessioninfo](ISSUER/saml/sessioninfo)

### Initiating Logout via an Access Token
The application must use the `/oauth/logout` endpoint and submit the access token with the request. This endpoint must not support CORS as the HTTP `Origin` header would become `null` after a redirect and invalidate further processing.

#### Inactive Access Token
In the case that the application has revoked the access token or the access token expired, it is not possible to initiate a logout. This is a dangerous situation, as an authentication session may still exist. 

The Authorization Server resturns an error page warning the user and requsting to close the user-agent, e.g. the web-Brower!

[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid&response\_type=code&grant\_type=authorization\_code&state=false&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid&response_type=code&grant_type=authorization_code&state=false&)

=> <span style="color:green">Test passed</span>

#### Active Access Token
The Authorization Server will initiate a Single-Logout with the IdP based on the logical SP associated with the access token. 

Depending on the IdP's implementation of the Single-Logout, the flow may end with the IdP. In that case, the application will not be redirected to the requested page. This is not a bug in the Authorization Server, it is caused by the Web-Browser's security sand-boxing and the use of Cookies in the context of CORS.

Logout with access token. 

[AUTHORIZATION_ENDPOINT?client\_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect\_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid&response\_type=code%20id\_token&grant\_type=authorization\_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&](AUTHORIZATION_ENDPOINT?client_id=9aa391cd-ea50-8a70-545a-1c51879a1dd5&redirect_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid&response_type=code%20id_token&grant_type=authorization_code&state=9aa391cd-ea50-8a70-545a-1c51879a1dd5&nonce=access_token&)

=> <span style="color:green">Test passed</span>

#### Authorization Code
Logout based on the authorization_code. 

[AUTHORIZATION_ENDPOINT?client\_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect\_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid%20offline\_access&response\_type=code&grant\_type=authorization\_code%20id\_token&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&](AUTHORIZATION_ENDPOINT?client_id=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&redirect_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid%20offline_access&response_type=code%20id_token&grant_type=authorization_code&state=8d86fcb1-f720-3a95-1070-0a4eb8d050ab&nonce=code&)

=> <span style="color:green">Test passed</span>

#### Refresh Token
Logout based on the refresh token. 

[AUTHORIZATION_ENDPOINT?client\_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect\_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid%20offline\_access&response\_type=code%20id\_token&grant\_type=authorization\_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&](AUTHORIZATION_ENDPOINT?client_id=1585cd2b-c8aa-1179-a6aa-b55fb9024802&redirect_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid%20offline_access&response_type=code%20id_token&grant_type=authorization_code&state=1585cd2b-c8aa-1179-a6aa-b55fb9024802&nonce=refresh_token&)

=> <span style="color:green">Test passed</span>

#### Refreshed Access Token
Logout based on an access token obtained from a refresh token.

[AUTHORIZATION_ENDPOINT?client\_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect\_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid%20offline\_access&response\_type=code%20id\_token&grant\_type=authorization\_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&](AUTHORIZATION_ENDPOINT?client_id=0a0036ff-52fa-a2ad-c884-af80ae377730&redirect_uri=http://127.0.0.1:4711/logout-app/&scope=saml%20openid%20offline_access&response_type=code%20id_token&grant_type=authorization_code&state=0a0036ff-52fa-a2ad-c884-af80ae377730&nonce=refreshed_access_token&)

=> <span style="color:green">Test passed</span>

### Initiating Logout via SAML Session Cookies
The application must use the `/saml/logout` endpoint and rely on the user-agent to submit associated cookies. This endpoint must not support CORS as the HTTP `Origin` header would become `null` after a redirect and invalidate further processing.

#### Logout with no Authentication Session
The Authorization Server will redirect the application to the default or requested `return` URL.

[ISSUER/saml/logout](ISSUER/saml/logout)

=> <span style="color:green">Test passed</span>

#### Logout with one Authentication Session
There are two cases to consider: (i) The application uses the optional parameter `auth_id` to initate the logout or there only is one session based on the SAML session cookies.

##### Leveraging the parameter `auth_id`

With `auth_id` set to `openid`

[AUTHORIZATION_ENDPOINT?client\_id=952cf7e3-be59-d56e-1177-7cde1233e920&redirect\_uri=http://127.0.0.1:4711/logout-webapp/&scope=saml%20openid&response\_type=id\_token%20token&grant\_type=implicit&state=952cf7e3-be59-d56e-1177-7cde1233e920&](AUTHORIZATION_ENDPOINT?client_id=952cf7e3-be59-d56e-1177-7cde1233e920&redirect_uri=http://127.0.0.1:4711/logout-webapp/&scope=saml%20openid&response_type=id_token%20token&grant_type=implicit&state=952cf7e3-be59-d56e-1177-7cde1233e920&)

=> <span style="color:green">Test passed</span>

Without `auth_id` set but only one logical SP having a session

[AUTHORIZATION_ENDPOINT?client\_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&redirect\_uri=http://127.0.0.1:4711/logout-webapp/&scope=saml%20openid&response\_type=id\_token%20token&grant\_type=implicit&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&](AUTHORIZATION_ENDPOINT?client_id=2dbbfbf0-cfda-2860-99ff-552278db2e71&redirect_uri=http://127.0.0.1:4711/logout-webapp/&scope=saml%20openid&response_type=id_token%20token&grant_type=implicit&state=2dbbfbf0-cfda-2860-99ff-552278db2e71&)

=> <span style="color:green">Test passed</span>

#### Logout with two Authentication Sessions
This behaviour is currently not implemented. The Authorization Server will display an Error Page informing the User to close the user-agent, typically the Wb-Browser.

[AUTHORIZATION_ENDPOINT?client\_id=f8910358-fed1-3d49-8183-913ddede237e&redirect\_uri=http://127.0.0.1:4711/logout-webapp/&scope=saml&response\_type=token&grant\_type=implicit&state=AUTHORIZATION\_ENDPOINT%3Fclient\_id%3D952cf7e3-be59-d56e-1177-7cde1233e920%26redirect\_uri%3Dhttp%3A%2F%2F127.0.0.1%3A4711%2Flogout-webapp%2F%26scope%3Dsaml%2520openid%26response\_type%3Did\_token%2520token%26grant\_type%3Dimplicit%26state%3Dmulti%26](AUTHORIZATION_ENDPOINT?client_id=f8910358-fed1-3d49-8183-913ddede237e&redirect_uri=http://127.0.0.1:4711/logout-webapp/&scope=saml&response_type=token&grant_type=implicit&state=AUTHORIZATION_ENDPOINT%3Fclient_id%3D952cf7e3-be59-d56e-1177-7cde1233e920%26redirect_uri%3Dhttp%3A%2F%2F127.0.0.1%3A4711%2Flogout-webapp%2F%26scope%3Dsaml%2520openid%26response_type%3Did_token%2520token%26grant_type%3Dimplicit%26state%3Dmulti%26)

