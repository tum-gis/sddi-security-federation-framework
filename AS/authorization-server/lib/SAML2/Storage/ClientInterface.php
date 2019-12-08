<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SAML2\Storage;

use OAuth2\Storage\ClientInterface as BaseClientInterface;

interface ClientInterface extends BaseClientInterface
{

    public function getClientIds();
    /**
    * offer to use a combination of client_id and user_id
    * client_id == null && user_id == null => return details for ALL applications
    * client_id == null && user_id != null => return details for applications matching client_id
    * client_id != null && user_id == null => return details for applications matching user_id
    * client_id != null && user_id != null => return details for applications matching client_id && user_id
    **/
    public function getUserClientsDetails($user_id = null);

    /**
    * get details for operators and their registered applications
    **/
    public function getClientOperators();

}
