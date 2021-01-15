# Install and set up SimpleSAMLphp server for Google IdP

***All instructions and commands given in this documentation should be executed in CentOS 7.***

### Base installations

1.  First install Apache, Firewall, PHP, etc. as described in [AS](../AS/authorization-server/README.md).

1.  Install wget, curl, etc.
    ````bash
    yum install wget
    yum install curl
    ````
    
### Create a self-signed certificate

*   The Discovery Service only needs to trust the Identity Provider,
hence a self-signed certificate would suffice here:
    ````bash
    cd /etc/ssl/certs
    openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out google-idp_cert.pem -keyout google-idp_key.pem
    ````
    with the following information:
    ````
    countryName = DE
    stateOrProvinceName = Bayern
    localityName = Muenchen
    organizationName = Technische Universitaet Muenchen
    organizationalUnitName = Ingenieurfakultaet Bau Geo Umwelt
    commonName = google-idp.gis.bgu.tum.de
    ````

*   Allow only ``root`` to have read access to the key file:
    ````bash
    chmod +x google-idp_key.pem
    ````

*   Remember to add the chain certificate ``/etc/ssl/certs/google-idp_chain.pem`` 
    as well if this certificate is not self-signed.

*   If ``httpd`` is unable to start, run the following command:
    ````bash
    restorecon -RvF /etc/ssl/certs/ 
    ````

### Setup SimpleSAMLphp

1.  First use a [customized version](https://github.com/cirrusidentity/simplesamlphp-module-authoauth2) 
    of SimpleSAMLphp to support Google OAuth
    ````bash
    cd /var
    mkdir google-idp
    /usr/local/bin/composer require cirrusidentity/simplesamlphp-module-authoauth2
    ````
    This will create a directory ``vendor`` in the current directory ``/var/google-idp``.

1.  Configure Apache
    *   Open ``/etc/httpd/conf.d/ssl.conf``:
        ````bash
        <VirtualHost *>
            ServerName google-idp.gis.bgu.tum.de
            DocumentRoot "/var/www/html"
    
            SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/google-idp/vendor/simplesamlphp/simplesamlphp/config
    
            Alias /simplesaml /var/google-idp/vendor/simplesamlphp/simplesamlphp/www
        
            SSLCertificateFile /etc/pki/tls/certs/google-idp_cert.pem
            SSLCertificateKeyFile /etc/pki/tls/certs/google-idp_key.pem
            SSLCertificateChainFile /etc/pki/tls/certs/google-idp_chain.pem
    
            <Directory /var/google-idp/vendor/simplesamlphp/simplesamlphp/www>
                Require all granted
            </Directory>
        </VirtualHost>
        ````

1.  Download and install the [official SimpleSAMLphp](https://simplesamlphp.org/docs/stable/simplesamlphp-install)
    ````bash
    cd /tmp
    wget https://github.com/simplesamlphp/simplesamlphp/releases/download/v1.18.8/simplesamlphp-1.18.8.tar.gz
    tar xzf simplesamlphp-1.18.8.tar.gz
    rm -f simplesamlphp-1.18.8.tar.gz
    mv simplesamlphp-x.y.z simplesamlphp
    ````

1.  Copy files:
    ````bash
    cp /tmp/simplesamlphp/config/* /var/google-idp/vendor/simplesamlphp/simplesamlphp/config/
    cp /tmp/simplesamlphp/metadata/* /var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/
    ````
    
1.  Configure SimpleSAMLphp

    *   Open ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/config.php``:
        ````bash
        'baseurlpath' => 'https://google-idp.gis.bgu.tum.de/simplesaml/',
        
        'auth.adminpassword' => 'setnewpasswordhere',
        
        # Run tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
        'secretsalt' => 'randombytesinsertedhere',
        
        'technicalcontact_name' => 'Son H. Nguyen',
        'technicalcontact_email' => 'son.nguyen@tum.de',
        
        # http://php.net/manual/en/timezones.php
        'timezone' => 'Europe/Berlin',
        
        'enable.saml20-idp' => true,
        ````
    
### Set up SimpleSAMLphp Identity Provider

1.  Go to the [Google API Console](https://console.developers.google.com/), 
    create an OAuth application. Then create credentials for an OAuth web application.
    This will give a Google client ID and a secret.

1.  Configure the file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/authsources.php`` 
    (for more information on the configuration parameters, please refer to 
    [the official documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-sp) and
    [here](https://github.com/cirrusidentity/simplesamlphp-module-authoauth2#generic-google)):
    ````php
     'google' => [
        'authoauth2:OAuth2',
        'template' => 'GoogleOIDC',
        // *** Certs ***
        'sign.logout' => true,
        'validate.logout' => true,
        'redirect.sign' => true,
        'privatekey' => '/etc/pki/tls/certs/google-idp_key.pem',
        'certificate' => '/etc/pki/tls/certs/google-idp_cert.pem',
        // *** Google Endpoints ***
        'urlAuthorize' => 'https://accounts.google.com/o/oauth2/auth',
        'urlAccessToken' => 'https://accounts.google.com/o/oauth2/token',
        'urlResourceOwnerDetails' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        // *** My application ***
        'clientId' => 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
        'clientSecret' => 'YOUR_GOOGLE_SECRET',
        'scopes' =>  [
            'openid',
            'email',
            'profile'
        ],
        'scopeSeparator' => ' ',
    ],
    ````
    
1.  Comment ``default-sp`` out in the file 
    ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/authsources.php``.
    
1.  Edit the following lines in the file 
    ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-hosted.php``
    ````php
    'privatekey' => '/etc/pki/tls/certs/google-idp_key.pem',
    'certificate' => '/etc/pki/tls/certs/google-idp_cert.pem',

    'auth' => 'google',
    
    // *** Metadata attributes ***
    'UIInfo' => [
        'DisplayName' => [
            'en' => 'SDDI Google IdP',
            'de' => 'SDDI Google IdP',
        ],
        'Description' => [
            'en' => 'Google IdP for the SDDI Security Framework',
            'de' => 'Google IdP für das Projekt SDDI Security Framework',
        ],
        'InformationURL' => [
            'en' => 'https://www.lrg.tum.de/en/gis/projects/smart-district-data-infrastructure/',
            'de' => 'https://www.lrg.tum.de/gis/projekte/sddi/',
        ],
        /*
        'PrivacyStatementURL' => [
            'en' => 'http://example.com/privacy/en',
            'de' => 'http://example.com/privacy/de',
        ],
        'Keywords' => [
            'en' => ['communication', 'federated session'],
            'de' => ['Kommunikation', 'Foederationssesion'],
        ],
        */
        'Logo' => [
            [
                'url' => 'https://lh3.googleusercontent.com/COxitqgJr1sJnIDe8-jiKhxDx1FrYbtRHKJ9z_hELisAlapwE9LUPh6fcXIfb5vwpbMl4xl9H9TRFPc5NOO8Sb3VSgIBrfRYvW6cUA',
                'height' => 16,
                'width'  => 16,
                //'lang'   => 'en',
            ],
            /*
            [
                'url' => 'http://example.com/logo2.png',
                'height' => 201,
                'width' => 401,
            ],
            */
        ],
    ],
    ````
    Please refer to the [documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-metadata-extensions-ui) 
    for more information on the attributes used here.

1.  Configure the file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-sp-remote.php``
    to trust SSDSOS1, SSDSOS2 and SSDAS
    ````php
    /*
     * SSDSOS1
     */
    $metadata['https://ssdsos1.gis.bgu.tum.de/shibboleth'] = [
        'entityid' => 'https://ssdsos1.gis.bgu.tum.de/shibboleth',
        'contacts' => [],
        'metadata-set' => 'saml20-sp-remote',
        'AssertionConsumerService' => [
            0 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'Location' => 'https://ssdsos1.gis.bgu.tum.de/Shibboleth.sso/SAML2/POST',
                'index' => 1,
            ],
            1 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
                'Location' => 'https://ssdsos1.gis.bgu.tum.de/Shibboleth.sso/SAML2/POST-SimpleSign',
                'index' => 2,
            ],
            2 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                'Location' => 'https://ssdsos1.gis.bgu.tum.de/Shibboleth.sso/SAML2/Artifact',
                'index' => 3,
            ],
            3 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
                'Location' => 'https://ssdsos1.gis.bgu.tum.de/Shibboleth.sso/SAML2/ECP',
                'index' => 4,
            ],
        ],
    ];
    
    /*
     * SSDSOS2
     */
    $metadata['https://ssdsos2.gis.bgu.tum.de/shibboleth'] = [
        'entityid' => 'https://ssdsos2.gis.bgu.tum.de/shibboleth',
        'contacts' => [],
        'metadata-set' => 'saml20-sp-remote',
        'AssertionConsumerService' => [
            0 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'Location' => 'https://ssdsos2.gis.bgu.tum.de/Shibboleth.sso/SAML2/POST',
                'index' => 1,
            ],
            1 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
                'Location' => 'https://ssdsos2.gis.bgu.tum.de/Shibboleth.sso/SAML2/POST-SimpleSign',
                'index' => 2,
            ],
            2 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                'Location' => 'https://ssdsos2.gis.bgu.tum.de/Shibboleth.sso/SAML2/Artifact',
                'index' => 3,
            ],
            3 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
                'Location' => 'https://ssdsos2.gis.bgu.tum.de/Shibboleth.sso/SAML2/ECP',
                'index' => 4,
            ],
        ],
    ];
    
    /*
     * SSDAS
     */
    $metadata['https://ssdas.gis.bgu.tum.de/shibboleth'] = [
        'entityid' => 'https://ssdas.gis.bgu.tum.de/shibboleth',
        'contacts' => [],
        'metadata-set' => 'saml20-sp-remote',
        'AssertionConsumerService' => [
            0 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
                'Location' => 'https://ssdas.gis.bgu.tum.de/Shibboleth.sso/SAML2/POST',
                'index' => 1,
            ],
            1 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign',
                'Location' => 'https://ssdas.gis.bgu.tum.de/Shibboleth.sso/SAML2/POST-SimpleSign',
                'index' => 2,
            ],
            2 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact',
                'Location' => 'https://ssdas.gis.bgu.tum.de/Shibboleth.sso/SAML2/Artifact',
                'index' => 3,
            ],
            3 => [
                'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:PAOS',
                'Location' => 'https://ssdas.gis.bgu.tum.de/Shibboleth.sso/SAML2/ECP',
                'index' => 4,
            ],
        ],
    ];
    ````
    
1.  Delete all the examples in ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/saml20-sp-remote.php``.

1.  Apache needs permission to display web pages and execute PHP files
    ````bash
    chmod +x -R /var/google-idp/
    chcon -R -t httpd_sys_content_t /var/google-idp/
    ````

1.  Add the following URL to the list of authorized redirect URLs in the 
    [Google API Console](https://console.developers.google.com/)
    ````url
    https://google-idp.gis.bgu.tum.de/simplesaml/module.php/authoauth2/linkback.php
    ````
    
1.  Restart Apache
    ````bash
    systemctl restart httpd
    ````