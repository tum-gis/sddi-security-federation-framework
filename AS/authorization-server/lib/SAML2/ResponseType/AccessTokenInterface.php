<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2\ResponseType;

use OAuth2\ResponseType\ResponseTypeInterface as ResponseTypeInterface;
use OAuth2\ResponseType\AccessTokenInterface as BaseAccessTokenInterface;

/**
 * @author Brent Shaffer <bshafs at gmail dot com>
 * @author Andreas Matheus @ Secure Dimensions
 */
interface AccessTokenInterface extends BaseAccessTokenInterface
{
    /**
     * Handle the creation of access token, also issue refresh token if supported / desirable.
     *
     * @param mixed  $client_id           - client identifier related to the access token.
     * @param mixed  $user_id             - user ID associated with the access token
     * @param mixed  $auth_id             - SAML2 Auth Source to be associated with the access token
     * @param string $scope               - OPTONAL scopes to be stored in space-separated string.
     * @param bool   $includeRefreshToken - if true, a new refresh_token will be added to the response
     *
     * @see http://tools.ietf.org/html/rfc6749#section-5
     * @ingroup oauth2_section_5
     */
    public function createAccessToken($client_id, $user_id, $scope = null, $includeRefreshToken = true, $auth_id = null);

}
