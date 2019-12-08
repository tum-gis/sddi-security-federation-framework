<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface as BaseUserClaimsInterface;

interface UserClaimsInterface extends BaseUserClaimsInterface
{

    const EXTENDED_CLAIMS = 'openid profile email saml offline_acces';
    // fields returned for the claims above
    const OPENID_CLAIM_VALUES    = 'sub auth_time';
    const SAML_CLAIM_VALUES    = 'idp_country idp_name idp_identifier idp_origin';
    const OFFLINE_ACCESS_CLAIM_VALUES = '';
    /**
    Allows to set the user claims from user attributes received from another entity
    */
    public function setUserClaims($user_id, $claims);

    public function getUUID($username);
}
