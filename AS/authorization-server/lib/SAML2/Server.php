<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2;

use OAuth2\Server as BaseServer;
use OAuth2\Controller\ResourceControllerInterface;
use OAuth2\Controller\ResourceController;
use OAuth2\OpenID\Controller\UserInfoControllerInterface;
use OAuth2\OpenID\Controller\UserInfoController;
use OAuth2\ClientAssertionType\ClientAssertionTypeInterface;
use OAuth2\ClientAssertionType\HttpBasic;
use SAML2\Controller\TokenControllerInterface;
use SAML2\Controller\TokenController;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;

use SAML2\Controller\AuthorizeControllerInterface;
use SAML2\Controller\AuthorizeController;
use SAML2\ResponseType\ResponseTypeInterface;
use SAML2\ResponseType\Code as CodeResponseType;
use SAML2\ResponseType\AccessToken as AccessTokenResponseType;
use SAML2\ResponseType\JwtAccessToken;
use SAML2\ResponseType\CodeIdToken;
use OAuth2\OpenID\ResponseType\IdToken as IdTokenResponseType;
use SAML2\ResponseType\IdTokenToken as IdTokenTokenResponseType;
use SAML2\ResponseType\CodeIdToken as CodeIdTokenResponseType;

use OAuth2\TokenType\TokenTypeInterface;
use OAuth2\TokenType\Bearer;
use OAuth2\GrantType\UserCredentials;
use OAuth2\GrantType\ClientCredentials;
use SAML2\GrantType\RefreshToken;
use SAML2\GrantType\AuthorizationCode;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\ClientInterface;
use OAuth2\Storage\JwtAccessToken as JwtAccessTokenStorage;
use OAuth2\Storage\JwtAccessTokenInterface;

use InvalidArgumentException;
use LogicException;

require __DIR__ . '/Controller/AuthorizeController.php';
require __DIR__ . '/Controller/TokenControllerInterface.php';
require __DIR__ . '/Controller/TokenController.php';
require __DIR__ . '/GrantType/AuthorizationCode.php';
require __DIR__ . '/GrantType/RefreshToken.php';

/**
* Server class for SAML2
* This class serves as a convience class which wraps the other Controller classes
*
* @see \OAuth2\Controller\ResourceController
* @see \SAML2\Controller\AuthorizeController
* @see \OAuth2\Controller\TokenController
*/
class Server extends BaseServer implements 
    AuthorizeControllerInterface,
    TokenControllerInterface,
    UserInfoControllerInterface
{
    /**
     * @var array
     */
    protected $responseTypeMap = array(
        'code' => 'SAML2\ResponseType\CodeInterface',
        'token' => 'SAML2\ResponseType\AccessTokenInterface',
        'id_token' => 'OAuth2\OpenID\ResponseType\IdTokenInterface',
        'id_token token' => 'OAuth2\OpenID\ResponseType\IdTokenTokenInterface',
        'code id_token' => 'SAML2\ResponseType\CodeIdTokenInterface',
    );

    /**
     * @param mixed                        $storage             (array or OAuth2\Storage) - single object or array of objects implementing the
     *                                                          required storage types (ClientCredentialsInterface and AccessTokenInterface as a minimum)
     * @param array                        $config              specify a different token lifetime, token header name, etc
     * @param array                        $grantTypes          An array of OAuth2\GrantType\GrantTypeInterface to use for granting access tokens
     * @param array                        $responseTypes       Response types to use. array keys should be "code" and "token" for
     *                                                          Access Token and Authorization Code response types
     * @param TokenTypeInterface           $tokenType           The token type object to use. Valid token types are "bearer" and "mac"
     * @param ScopeInterface               $scopeUtil           The scope utility class to use to validate scope
     * @param ClientAssertionTypeInterface $clientAssertionType The method in which to verify the client identity.  Default is HttpBasic
     *
     * @ingroup oauth2_section_7
     */
    public function __construct($storage = array(), array $config = array(), array $grantTypes = array(), array $responseTypes = array(), TokenTypeInterface $tokenType = null, ScopeInterface $scopeUtil = null, ClientAssertionTypeInterface $clientAssertionType = null)
    {
        $storage = is_array($storage) ? $storage : array($storage);
        $this->storages = array();
        foreach ($storage as $key => $service) {
            $this->addStorage($service, $key);
        }

        // merge all config values.  These get passed to our controller objects
        $this->config = array_merge(array(
            'use_jwt_access_tokens'        => false,
            'jwt_extra_payload_callable' => null,
            'store_encrypted_token_string' => true,
            'use_openid_connect'       => false,
            'id_lifetime'              => 3600,
            'access_lifetime'          => 3600,
            'www_realm'                => 'Service',
            'token_param_name'         => 'access_token',
            'token_bearer_header_name' => 'Bearer',
            'enforce_state'            => true,
            'require_exact_redirect_uri' => true,
            'allow_implicit'           => false,
            'allow_credentials_in_request_body' => true,
            'allow_public_clients'     => true,
            'always_issue_new_refresh_token' => false,
            'unset_refresh_token_after_use' => true,
        ), $config);


        $this->tokenType = $tokenType;
        $this->scopeUtil = $scopeUtil;
        $this->clientAssertionType = $clientAssertionType;
	$this->responseTypes = $responseTypes;

    }

    /**
     * Redirect the user appropriately after approval.
     *
     * After the user has approved or denied the resource request the
     * authorization server should call this function to redirect the user
     * appropriately.
     *
     * @param RequestInterface  $request - The request should have the follow parameters set in the querystring:
     * - response_type: The requested response: an access token, an authorization code, or both.
     * - client_id: The client identifier as described in Section 2.
     * - redirect_uri: An absolute URI to which the authorization server will redirect the user-agent to when the
     *   end-user authorization step is completed.
     * - scope: (optional) The scope of the resource request expressed as a list of space-delimited strings.
     * - state: (optional) An opaque value used by the client to maintain state between the request and callback.
     *
     * @param ResponseInterface $response      - Response object
     * @param bool              $is_authorized - TRUE or FALSE depending on whether the user authorized the access.
     * @param mixed             $user_id       - Identifier of user who authorized the client
     * @param mixed             $auth_id       - Identifier of the SAML SP Authentication Source
     * @return ResponseInterface
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4
     *
     * @ingroup oauth2_section_4
     */
    public function handleAuthorizeRequest(RequestInterface $request, ResponseInterface $response, $is_authorized, $user_id = null, $auth_id = null)
    {
        $this->response = $response;
        $this->getAuthorizeController()->handleAuthorizeRequest($request, $this->response, $is_authorized, $user_id, $auth_id);

        return $this->response;
    }

}
