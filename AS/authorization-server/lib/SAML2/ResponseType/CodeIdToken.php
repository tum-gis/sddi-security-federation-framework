<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2\ResponseType;

use OAuth2\OpenID\ResponseType\CodeIdToken as BaseCodeIdToken;
use OAuth2\OpenID\ResponseType\IdTokenInterface;
use SAML2\ResponseType\CodeIdTokenInterface;
use SAML2\ResponseType\CodeInterface;

require 'CodeIdTokenInterface.php';

class CodeIdToken extends BaseCodeIdToken implements CodeIdTokenInterface
{
    /**
     * @var AuthorizationCodeInterface
     */
    protected $authCode;

    /**
     * @var IdTokenInterface
     */
    protected $idToken;

    /**
     * @param AuthorizationCodeInterface $authCode
     * @param IdTokenInterface           $idToken
     */
    public function __construct(CodeInterface $authCode, IdTokenInterface $idToken)
    {
        $this->authCode = $authCode;
        $this->idToken = $idToken;
    }

    /**
     * @param array $params
     * @param mixed $user_id
     * @return mixed
     */
    public function getAuthorizeResponse($params, $user_id = null, $auth_id = null)
    {
        $result = $this->authCode->getAuthorizeResponse($params, $user_id, $auth_id);
        $resultIdToken = $this->idToken->getAuthorizeResponse($params, $user_id, $auth_id);
        $result[1]['query']['id_token'] = $resultIdToken[1]['fragment']['id_token'];

        return $result;
    }

}
