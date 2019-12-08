<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$config = array(

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        'core:AdminPassword',
    ),

    // This is the SAML2 SP authentication source that shall be configured to NOT request user attributes
    'oauth' => array(
        'saml:SP',

	// this entry is different for both SPs
        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',

	// this entry is different for both SPs
        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/oauth',

	// this entry is identical for both SPs
        'discoURL' => 'https://ds.sddi.secure-dimensions.de/WAYF',

	// this entry is identical for both SPs
        'privatekey' => 'as.sddi.secure-dimensions.de.pem',

	// this entry is identical for both SPs
        'certificate' => 'as.sddi.secure-dimensions.de.crt',

        'sign.logout' => true,
    ),
    // This is the SAML2 SP authentication source that shall be configured to NOT request user attributes
    'oidc-profile' => array(
        'saml:SP',

	// this entry is different for both SPs
        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

	// this entry is different for both SPs
        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/oidc-profile',

        'discoURL' => 'https://ds.sddi.secure-dimensions.de/WAYF',

	// this entry is identical for both SPs
        'privatekey' => 'as.sddi.secure-dimensions.de.pem',

	// this entry is identical for both SPs
        'certificate' => 'as.sddi.secure-dimensions.de.crt',

        'sign.logout' => true,
    )
);
