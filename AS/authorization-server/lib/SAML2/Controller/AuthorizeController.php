<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2\Controller;

use OAuth2\Storage\ClientInterface;
use OAuth2\ScopeInterface;
use OAuth2\RequestInterface;
use OAuth2\ResponseInterface;
use OAuth2\Scope;
use OAuth2\Controller\AuthorizeControllerInterface as BaseAuthorizeControllerInterface;
//use OAuth2\Controller\AuthorizeController as BaseAuthorizeController;

use SAML2\Controller\AuthorizeControllerInterface;

use InvalidArgumentException;

require 'AuthorizeControllerInterface.php';

/**
 * @see AuthorizeControllerInterface
 */
class AuthorizeController implements AuthorizeControllerInterface, BaseAuthorizeControllerInterface
{

    /**
     * @var string
     */
    private $scope;

    /**
     * @var int
     */
    private $state;

    /**
     * @var mixed
     */
    private $client_id;

    /**
     * @var string
     */
    private $redirect_uri;

    /**
     * The response type
     *
     * @var string
     */
    private $response_type;

    /**
     * @var ClientInterface
     */
    protected $clientStorage;

    /**
     * @var array
     */
    protected $responseTypes;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var ScopeInterface
     */
    protected $scopeUtil;

    /**
     * Constructor
     *
     * @param ClientInterface $clientStorage REQUIRED Instance of OAuth2\Storage\ClientInterface to retrieve client information
     * @param array           $responseTypes OPTIONAL Array of OAuth2\ResponseType\ResponseTypeInterface objects.  Valid array
     *                                       keys are "code" and "token"
     * @param array           $config        OPTIONAL Configuration options for the server:
     * @param ScopeInterface  $scopeUtil     OPTIONAL Instance of OAuth2\ScopeInterface to validate the requested scope
     * @code
     *     $config = array(
     *         'allow_implicit' => false,            // if the controller should allow the "implicit" grant type
     *         'enforce_state'  => true              // if the controller should require the "state" parameter
     *         'require_exact_redirect_uri' => true, // if the controller should require an exact match on the "redirect_uri" parameter
     *         'redirect_status_code' => 302,        // HTTP status code to use for redirect responses
     *     );
     * @endcode
     */
    public function __construct(ClientInterface $clientStorage, array $responseTypes = array(), array $config = array(), ScopeInterface $scopeUtil = null)
    {
        $this->clientStorage = $clientStorage;
        $this->responseTypes = $responseTypes;
        $this->config = array_merge(array(
            'allow_implicit' => false,
            'enforce_state'  => true,
            'require_exact_redirect_uri' => true,
            'redirect_status_code' => 302,
        ), $config);

        if (is_null($scopeUtil)) {
            $scopeUtil = new Scope();
        }
        $this->scopeUtil = $scopeUtil;
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    public function validateAuthorizeRequest(RequestInterface $request, ResponseInterface $response)
    {
        // Make sure a valid client id was supplied (we can not redirect because we were unable to verify the URI)
        if (!$client_id = $request->query('client_id', $request->request('client_id'))) {
            // We don't have a good URI to use
            $response->setError(400, 'invalid_client', "No client id supplied");

            return false;
        }

        // Get client details
        if (!$clientData = $this->clientStorage->getClientDetails($client_id)) {
            $response->setError(400, 'invalid_client', 'The client id supplied is invalid');

            return false;
        }

        $registered_redirect_uri = isset($clientData['redirect_uri']) ? $clientData['redirect_uri'] : '';

        // Make sure a valid redirect_uri was supplied. If specified, it must match the clientData URI.
        // @see http://tools.ietf.org/html/rfc6749#section-3.1.2
        // @see http://tools.ietf.org/html/rfc6749#section-4.1.2.1
        // @see http://tools.ietf.org/html/rfc6749#section-4.2.2.1
        if ($supplied_redirect_uri = $request->query('redirect_uri', $request->request('redirect_uri'))) {
            // validate there is no fragment supplied
            $parts = parse_url($supplied_redirect_uri);
            if (isset($parts['fragment']) && $parts['fragment']) {
                $response->setError(400, 'invalid_uri', 'The redirect URI must not contain a fragment');

                return false;
            }

            // validate against the registered redirect uri(s) if available
            if ($registered_redirect_uri && !$this->validateRedirectUri($supplied_redirect_uri, $registered_redirect_uri)) {
                $response->setError(400, 'redirect_uri_mismatch', 'The redirect URI provided is missing or does not match', '#section-3.1.2');

                return false;
            }
            $redirect_uri = $supplied_redirect_uri;
        } else {
            // use the registered redirect_uri if none has been supplied, if possible
            if (!$registered_redirect_uri) {
                $response->setError(400, 'invalid_uri', 'No redirect URI was supplied or stored');

                return false;
            }

            if (count(explode(' ', $registered_redirect_uri)) > 1) {
                $response->setError(400, 'invalid_uri', 'A redirect URI must be supplied when multiple redirect URIs are registered', '#section-3.1.2.3');

                return false;
            }
            $redirect_uri = $registered_redirect_uri;
        }

        // Select the response type
        $response_type = $request->query('response_type', $request->request('response_type'));

        // for multiple-valued response types - make them alphabetical
        if (false !== strpos($response_type, ' ')) {
            $types = explode(' ', $response_type);
            sort($types);
            $response_type = ltrim(implode(' ', $types));
        }

        $state = $request->query('state', $request->request('state'));

        // type and client_id are required
        if (!$response_type || !in_array($response_type, $this->getValidResponseTypes())) {
            $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'invalid_request', 'Invalid or missing response type', null);

            return false;
        }

        if ($response_type == self::RESPONSE_TYPE_CODE) {
            if (!isset($this->responseTypes['code'])) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unsupported_response_type', 'authorization code grant type not supported', null);

                return false;
            }
            if (!$this->clientStorage->checkRestrictedGrantType($client_id, 'authorization_code')) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unauthorized_client', 'The grant type is unauthorized for this client_id', null);

                return false;
            }
            if ($this->responseTypes['code']->enforceRedirect() && !$redirect_uri) {
                $response->setError(400, 'redirect_uri_mismatch', 'The redirect URI is mandatory and was not supplied');

                return false;
            }
	} elseif ($response_type == self::RESPONSE_TYPE_CODE_ID_TOKEN) {
            if (!isset($this->responseTypes['code id_token'])) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unsupported_response_type', 'authorization code grant type not supported', null);

                return false;
            }
            if (!$this->clientStorage->checkRestrictedGrantType($client_id, 'authorization_code')) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unauthorized_client', 'The grant type is unauthorized for this client_id', null);

                return false;
            }
            if ($this->responseTypes['code']->enforceRedirect() && !$redirect_uri) {
                $response->setError(400, 'redirect_uri_mismatch', 'The redirect URI is mandatory and was not supplied');
        
                return false;
            }

	    // check if the scope for the client allow to request an id_token
	    $client_scopes = explode(' ' ,$this->clientStorage->getClientScope($client_id));
	    if (array_search('openid', $client_scopes) === FALSE)
	    {
		$response->setError(403, 'insufficient_scope', 'The registered scope for the provided client_id does not allow to request the response type "code id_token". Please use response tpye "code".');
	
	 	return false;
	    }

        } else {
            if (!$this->config['allow_implicit']) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unsupported_response_type', 'implicit grant type not supported', null);

                return false;
            }
            if (!$this->clientStorage->checkRestrictedGrantType($client_id, 'implicit')) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'unauthorized_client', 'The grant type is unauthorized for this client_id', null);

                return false;
            }

            // check if the scope for the client allow to request an id_token
            $client_scopes = explode(' ' ,$this->clientStorage->getClientScope($client_id));
            if (($response_type == self::RESPONSE_TYPE_ID_TOKEN) && (array_search('openid', $client_scopes) === FALSE))
            {
                $response->setError(403, 'insufficient_scope', 'The registered scope for the provided client_id does not allow to request the response type "id_token". Please use response tpye "token".');

                return false;
            }
        }

        // validate requested scope if it exists
        $requestedScope = $this->scopeUtil->getScopeFromRequest($request);

        if ($requestedScope) {
            // restrict scope by client specific scope if applicable,
            // otherwise verify the scope exists
            $clientScope = $this->clientStorage->getClientScope($client_id);
            if ((empty($clientScope) && !$this->scopeUtil->scopeExists($requestedScope))
                || (!empty($clientScope) && !$this->scopeUtil->checkScope($requestedScope, $clientScope))) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'invalid_scope', 'An unsupported scope was requested', null);

                return false;
            }
        } else {
            // use a globally-defined default scope
            $defaultScope = $this->scopeUtil->getDefaultScope($client_id);

            if (false === $defaultScope) {
                $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, $state, 'invalid_client', 'This application requires you specify a scope parameter', null);

                return false;
            }

            $requestedScope = $defaultScope;
        }

        // Validate state parameter exists (if configured to enforce this)
        if ($this->config['enforce_state'] && !$state) {
            $response->setRedirect($this->config['redirect_status_code'], $redirect_uri, null, 'invalid_request', 'The state parameter is required');

            return false;
        }

        // save the input data and return true
        $this->scope         = $requestedScope;
        $this->state         = $state;
        $this->client_id     = $client_id;
        // Only save the SUPPLIED redirect URI (@see http://tools.ietf.org/html/rfc6749#section-4.1.3)
        $this->redirect_uri  = $supplied_redirect_uri;
        $this->response_type = $response_type;

        $nonce = $request->query('nonce');

        // Validate required nonce for "code id_token"
        if (!$nonce && ($this->response_type == self::RESPONSE_TYPE_CODE_ID_TOKEN)) {
            $response->setError(400, 'invalid_nonce', 'This application requires you specify a nonce parameter');

            return false;
        }

        $this->nonce = $nonce;

        return true;
    }

    /**
     * Handle the authorization request
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param boolean           $is_authorized
     * @param mixed             $auth_id
     * @param mixed             $user_id
     * @return mixed|void
     * @throws InvalidArgumentException
     */
    public function handleAuthorizeRequest(RequestInterface $request, ResponseInterface $response, $is_authorized, $user_id = null, $auth_id = null)
    {

        if (!is_bool($is_authorized)) {
            throw new InvalidArgumentException('Argument "is_authorized" must be a boolean.  This method must know if the user has granted access to the client.');
        }

        // We repeat this, because we need to re-validate. The request could be POSTed
        // by a 3rd-party (because we are not internally enforcing NONCEs, etc)
        if (!$this->validateAuthorizeRequest($request, $response)) {
            return;
        }

        // If no redirect_uri is passed in the request, use client's registered one
        if (empty($this->getRedirectUri())) {
            $clientData              = $this->clientStorage->getClientDetails($this->getClientId());
            $registered_redirect_uri = $clientData['redirect_uri'];
        }

        // the user declined access to the client's application
        if ($is_authorized === false) {
            $redirect_uri = $this->getRedirectUri() ?: $registered_redirect_uri;
            $this->setNotAuthorizedResponse($request, $response, $redirect_uri, $user_id);

            return;
        }

        // build the parameters to set in the redirect URI
        if (!$params = $this->buildAuthorizeParameters($request, $response, $user_id, $auth_id)) {
            return;
        }

        $authResult = $this->responseTypes[$this->getResponseType()]->getAuthorizeResponse($params, $user_id);

        list($redirect_uri, $uri_params) = $authResult;

        if (empty($redirect_uri) && !empty($registered_redirect_uri)) {
            $redirect_uri = $registered_redirect_uri;
        }

        $uri = $this->buildUri($redirect_uri, $uri_params);

        // return redirect response
        $response->setRedirect($this->config['redirect_status_code'], $uri);
    }

    /**
     * We have made this protected so this class can be extended to add/modify
     * these parameters
     *
     * @TODO: add dependency injection for the parameters in this method
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param mixed $user_id
     * @param mixed $auth_id
     * @return array
     */
    protected function buildAuthorizeParameters($request, $response, $user_id, $auth_id = null)
    {
        $params = array(    
            'scope'         => $this->getScope(),
            'state'         => $this->getState(),
            'client_id'     => $this->getClientId(),
            'redirect_uri'  => $this->getRedirectUri(),
            'response_type' => $this->getResponseType(),
	    'auth_id' 	    => $auth_id
        );
        
        // Generate an id token if needed.
        if ($this->needsIdToken($this->getScope()) && ($this->getResponseType() == self::RESPONSE_TYPE_CODE_ID_TOKEN)) {
            $params['id_token'] = $this->responseTypes['id_token']->createIdToken($this->getClientId(), $user_id, $this->nonce);
        }
        // add the nonce to return with the redirect URI
        $params['nonce'] = $this->nonce;

        return $params;
    }

    /**
     * Build the absolute URI based on supplied URI and parameters.
     *
     * @param string $uri    An absolute URI.
     * @param array  $params Parameters to be append as GET.
     *
     * @return string
     * An absolute URI with supplied parameters.
     *
     * @ingroup oauth2_section_4
     */
    private function buildUri($uri, $params)
    {   
        $parse_url = parse_url($uri);
        
        // Add our params to the parsed uri
        foreach ($params as $k => $v) {
            if (isset($parse_url[$k])) {
                $parse_url[$k] .= "&" . http_build_query($v, '', '&');
            } else {
                $parse_url[$k] = http_build_query($v, '', '&');
            }
        }
        
        // Put the uri back together
        return
              ((isset($parse_url["scheme"])) ? $parse_url["scheme"] . "://" : "")
            . ((isset($parse_url["user"])) ? $parse_url["user"]
            . ((isset($parse_url["pass"])) ? ":" . $parse_url["pass"] : "") . "@" : "")
            . ((isset($parse_url["host"])) ? $parse_url["host"] : "") 
            . ((isset($parse_url["port"])) ? ":" . $parse_url["port"] : "")
            . ((isset($parse_url["path"])) ? $parse_url["path"] : "")
            . ((isset($parse_url["query"]) && !empty($parse_url['query'])) ? "?" . $parse_url["query"] : "")
            . ((isset($parse_url["fragment"])) ? "#" . $parse_url["fragment"] : "")
        ;
    }

    protected function getValidResponseTypes()
    {
        return array(
            self::RESPONSE_TYPE_TOKEN,
            self::RESPONSE_TYPE_CODE,
            self::RESPONSE_TYPE_ID_TOKEN,
            self::RESPONSE_TYPE_ID_TOKEN_TOKEN,
            self::RESPONSE_TYPE_CODE_ID_TOKEN
        );
    }

    /**
     * Internal method for validating redirect URI supplied
     *
     * @param string $inputUri The submitted URI to be validated
     * @param string $registeredUriString The allowed URI(s) to validate against.  Can be a space-delimited string of URIs to
     *                                    allow for multiple URIs
     * @return bool
     * @see http://tools.ietf.org/html/rfc6749#section-3.1.2
     */
    protected function validateRedirectUri($inputUri, $registeredUriString)
    {
        if (!$inputUri || !$registeredUriString) {
            return false; // if either one is missing, assume INVALID
        }

        $registered_uris = preg_split('/\s+/', $registeredUriString);
        foreach ($registered_uris as $registered_uri) {
            if ($this->config['require_exact_redirect_uri']) {
                // the input uri is validated against the registered uri using exact match
                if (strcmp($inputUri, $registered_uri) === 0) {
                    return true;
                }
            } else {
                $registered_uri_length = strlen($registered_uri);
                if ($registered_uri_length === 0) {
                    return false;
                }

                // the input uri is validated against the registered uri using case-insensitive match of the initial string
                // i.e. additional query parameters may be applied
                if (strcasecmp(substr($inputUri, 0, $registered_uri_length), $registered_uri) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns whether the current request needs to generate an id token.
     *
     * ID Tokens are a part of the OpenID Connect specification, so this
     * method checks whether OpenID Connect is enabled in the server settings
     * and whether the openid scope was requested.
     *
     * @param string $request_scope - A space-separated string of scopes.
     * @return boolean - TRUE if an id token is needed, FALSE otherwise.
     */
    public function needsIdToken($request_scope)
    {
        // see if the "openid" scope exists in the requested scope
        return $this->scopeUtil->checkScope('openid', $request_scope);
    }

    /**
     * @return mixed
     */
    public function getNonce()
    {
        return $this->nonce;
    }

    /**
     * Convenience method to access the scope
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Convenience method to access the state
     *
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Convenience method to access the client id
     *
     * @return mixed
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * Convenience method to access the redirect url
     *
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirect_uri;
    }

    /**
     * Convenience method to access the response type
     *
     * @return string
     */
    public function getResponseType()
    {
        return $this->response_type;
    }

    public function getResponseTypes()
    {
	return $this->getValidResponseTypes();
    }

}
