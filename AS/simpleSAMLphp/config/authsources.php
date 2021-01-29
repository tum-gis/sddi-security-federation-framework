<?php

/*
Copyright © 2021 Technical University of Munich

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$config = array(

    // This is a authentication source which handles admin authentication.
    'admin' => array(
        'core:AdminPassword',
    ),

    // This is the SAML2 SP authentication source that enables the use of applications
    // without the collection of personal data from the IdP.
    'oauth' => array(
        'saml:SP',

        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',

        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/oauth',

        'discoURL' => 'https://<DISCOVERY_SERVER>/WAYF',

        'privatekey' => '/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/<KEY_FILE>',

        'certificate' => '/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/<CERT_FILE>',

        'sign.logout' => true,

		'SingleLogoutServiceBinding' => array(
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        'SingleLogoutServiceLocation' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-logout.php/oauth',

        'UIInfo' => array(
            'DisplayName' => array(
                'en' => 'SDDI Authorization Server (oauth)',
                'de' => 'SDDI Authorization Server (oauth)',
            ),
            'Description' => array(
                'en' => 'SDDI Authorization Server (oauth) without the collection of personal data',
                'de' => 'SDDI Authorization Server (oauth) ohne Sammlung personenbezogener Daten',
            ),
            'InformationURL' => array(
                'en' => 'https://www.lrg.tum.de/en/gis/projects/smart-district-data-infrastructure/',
                'de' => 'https://www.lrg.tum.de/gis/projekte/sddi/',
            ),
            'PrivacyStatementURL' => array(
                'en' => 'https://' . $_SERVER['SERVER_NAME'] . '/PrivacyStatement',
                'de' => 'https://' . $_SERVER['SERVER_NAME'] . '/PrivacyStatement',
            ),
        ),

        'contacts' => array(
            array(
                'contactType'       => 'support',
                'emailAddress'      => 'john.doe@example.com',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+0123456789',
                'company'           => 'Example Inc.',
            ),
            array(
                'contactType'       => 'technical',
                'emailAddress'      => 'john.doe@example.com',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+0123456789',
                'company'           => 'Example Inc',
            ),
        ),

        'OrganizationName' => array(
            'en' => 'Technical University of Munich',
            'de' => 'Technische Universitaet Muenchen',
        ),
        'OrganizationDisplayName' => array(
            'en' => 'Technical University of Munich',
            'de' => 'Technische Universitaet Muenchen',
        ),
        'OrganizationURL' => array(
            'en' => 'https://www.tum.de/en/',
            'de' => 'https://www.tum.de/',
        ),

        'name' => array(
            // Name required for AttributeConsumingService-element
            'en' => 'SDDI Authorization Server (oauth)',
            'de' => 'SDDI Authorization Server (oauth)',
        ),
        'description' => array(
            'en' => 'SDDI Authorization Server (oauth) without the collection of personal data',
            'de' => 'SDDI Authorization Server (oauth) ohne Sammlung personenbezogener Daten',
        ),
        'attributes' => array(
            // Specify friendly names for these attributes
            'o' => 'urn:oid:2.5.4.10',
        ),
        'attributes.required' => array(
        ),
        'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
        'attributes.index' => 1,

        'AssertionConsumerService' => array(
            array(
                'index' => 0,
                'Location' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-acs.php/oauth',
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ),
            array(
                'index' => 1,
                'Location' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-acs.php/oauth',
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
            ),
        ),
    ),
    // This is the SAML2 SP authentication source that shall be configured to NOT request user attributes
    'oidc-profile' => array(
        'saml:SP',

        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/oidc-profile',

        'discoURL' => 'https://<DISCOVERY_SERVER>/WAYF',

        'privatekey' => '/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/<KEY_FILE>',

        'certificate' => '/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/<CERT_FILE>',

        'sign.logout' => true,

        'SingleLogoutServiceBinding' => array(
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        'SingleLogoutServiceLocation' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-logout.php/oidc-profile',

        'UIInfo' => array(
            'DisplayName' => array(
                'en' => 'SDDI Authorization Server (oid-profile)',
                'de' => 'SDDI Authorization Server (oidc-profile)',
            ),
            'Description' => array(
                'en' => 'SDDI Authorization Server (oidc-profile)',
                'de' => 'SDDI Authorization Server (oidc-profile)',
            ),
            'InformationURL' => array(
                'en' => 'https://www.lrg.tum.de/en/gis/projects/smart-district-data-infrastructure/',
                'de' => 'https://www.lrg.tum.de/gis/projekte/sddi/',
            ),
            'PrivacyStatementURL' => array(
                'en' => 'https://' . $_SERVER['SERVER_NAME'] . '/PrivacyStatement',
                'de' => 'https://' . $_SERVER['SERVER_NAME'] . '/PrivacyStatement',
            ),
        ),

        'contacts' => array(
            array(
                'contactType'       => 'support',
                'emailAddress'      => 'john.doe@example.com',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+0123456789',
                'company'           => 'Example Inc.',
            ),
            array(
                'contactType'       => 'technical',
                'emailAddress'      => 'john.doe@example.com',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+0123456789',
                'company'           => 'Example Inc.',
            ),
        ),

        'OrganizationName' => array(
            'en' => 'Technical University of Munich',
            'de' => 'Technische Universitaet Muenchen',
        ),
        'OrganizationDisplayName' => array(
            'en' => 'Technical University of Munich',
            'de' => 'Technische Universitaet Muenchen',
        ),
        'OrganizationURL' => array(
            'en' => 'https://www.tum.de/en/',
            'de' => 'https://www.tum.de/',
        ),

        'name' => array(
            // Name required for AttributeConsumingService-element
            'en' => 'SDDI Authorization Server (oidc-profile)',
            'de' => 'SDDI Authorization Server (oidc-profile)',
        ),
        'description' => array(
            'en' => 'SDDI Authorization Server (oidc-profile)',
            'de' => 'SDDI Authorization Server (oidc-profile)',
        ),
        'attributes' => array(
            // Specify friendly names for these attributes
            'eduPersonTargetedID' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
            'givenName' => 'urn:oid:2.5.4.42',
            'sn' => 'urn:oid:2.5.4.4',
        ),
        'attributes.required' => array (
            'eduPersonTargetedID' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
            'givenName' => 'urn:oid:2.5.4.42',
            'sn' => 'urn:oid:2.5.4.4',
        ),
        'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
        'attributes.index' => 1,

        'AssertionConsumerService' => array(
            array(
                'index' => 0,
                'Location' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-acs.php/oidc-profile',
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ),
        ),
    ),
    // This is the SAML2 SP authentication source that shall be configured to NOT request user attributes
    'openid' => array(
        'saml:SP',

        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/openid',

        'discoURL' => 'https://<DISCOVERY_SERVER>/WAYF',

        'privatekey' => '/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/<KEY_FILE>',

        'certificate' => '/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/<CERT_FILE>',

        'sign.logout' => true,

        'SingleLogoutServiceBinding' => array(
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
        ),
        'SingleLogoutServiceLocation' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-logout.php/openid',

        'UIInfo' => array(
            'DisplayName' => array(
                'en' => 'SDDI Authorization Server (openid)',
                'de' => 'SDDI Authorization Server (openid)',
            ),
            'Description' => array(
                'en' => 'SDDI Authorization Server (openid)',
                'de' => 'SDDI Authorization Server (openid)',
            ),
            'InformationURL' => array(
                'en' => 'https://www.lrg.tum.de/en/gis/projects/smart-district-data-infrastructure/',
                'de' => 'https://www.lrg.tum.de/gis/projekte/sddi/',
            ),
            'PrivacyStatementURL' => array(
                'en' => 'https://ssdas.gis.bgu.tum.de/PrivacyStatement',
                'de' => 'https://ssdas.gis.bgu.tum.de/PrivacyStatement',
            ),
        ),

        'contacts' => array(
            array(
                'contactType'       => 'support',
                'emailAddress'      => 'john.doe@example.com',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+0123456789',
                'company'           => 'Example Inc.',
            ),
            array(
                'contactType'       => 'technical',
                'emailAddress'      => 'john.doe@example.com',
                'givenName'         => 'John',
                'surName'           => 'Doe',
                'telephoneNumber'   => '+0123456789',
                'company'           => 'Example Inc.',
            ),
        ),

        'OrganizationName' => array(
            'en' => 'Technical University of Munich',
            'de' => 'Technische Universitaet Muenchen',
        ),
        'OrganizationDisplayName' => array(
            'en' => 'Technical University of Munich',
            'de' => 'Technische Universitaet Muenchen',
        ),
        'OrganizationURL' => array(
            'en' => 'https://www.tum.de/en/',
            'de' => 'https://www.tum.de/',
        ),

        'name' => array(
            // Name required for AttributeConsumingService-element
            'en' => 'SDDI Authorization Server (oidc-profile)',
            'de' => 'SDDI Authorization Server (oidc-profile)',
        ),
        'description' => array(
            'en' => 'SDDI Authorization Server (oidc-profile)',
            'de' => 'SDDI Authorization Server (oidc-profile)',
        ),
        'attributes' => array(
            // Specify friendly names for these attributes
            'eduPersonTargetedID' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
            'givenName' => 'urn:oid:2.5.4.42',
            'sn' => 'urn:oid:2.5.4.4',
        ),
        'attributes.required' => array (
            'eduPersonTargetedID' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.10',
            'givenName' => 'urn:oid:2.5.4.42',
            'sn' => 'urn:oid:2.5.4.4',
        ),
        'attributes.NameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',
        'attributes.index' => 1,

        'AssertionConsumerService' => array(
            array(
                'index' => 0,
                'Location' => 'https://' . $_SERVER['SERVER_NAME'] . '/simplesaml/module.php/saml/sp/saml2-acs.php/openid',
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ),
        ),
    ),
);
