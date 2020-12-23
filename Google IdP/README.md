# How to install and setup SimpleSAMLphp server for Google IdP

***All instructions and commands given in this documentation should be executed in CentOS 7.***

### Base installations

1.  First install Apache, Firewall, PHP, etc. as described in [AS](../AS/authorization-server/README.md).

1.  NGINX

    *   Install:
        ````bash
        yum install epel-release && yum install nginx
        ````

    *   See NGINX version:
        ````bash
        nginx -v
        ````
    
    *   Test NGINX configurations:
        ````bash
        nginx -t
        ````
    
    *   The default locations for NGINX config file:
        ````bash
        /etc/nginx/nginx.conf
        ````
    
    *   Start NGINX:
        ````bash
        systemctl start nginx
        ````

3.  Install wget, curl, etc.
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

*   Remember to add the chain certificate ``/etc/ssl/certs/google-idp_chain.pem`` as well!

*   If ``httpd`` is unable to start, run the following command:
    ````bash
    restorecon -RvF /etc/ssl/certs/ 
    ````

### Setup SimpleSAMLphp

The following instructions are taken from the 
[official documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-install).

1.  Download and install SimpleSAMLphp
    ````bash
    cd /var
    wget https://github.com/simplesamlphp/simplesamlphp/releases/download/v1.18.8/simplesamlphp-1.18.8.tar.gz
    tar xzf simplesamlphp-1.18.8.tar.gz
    rm -f simplesamlphp-1.18.8.tar.gz
    mv simplesamlphp-x.y.z simplesamlphp
    ````

1.  Configure Apache

    *   Open ``/etc/httpd/conf.d/ssl.conf``:
        ````bash
        <VirtualHost *>
            ServerName google-idp.gis.bgu.tum.de
            DocumentRoot "/var/www/html"
    
            SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/simplesamlphp/config
    
            Alias /simplesaml /var/simplesamlphp/www
        
            SSLCertificateFile /etc/pki/tls/certs/google-idp_cert.pem
            SSLCertificateKeyFile /etc/pki/tls/certs/google-idp_key.pem
            SSLCertificateChainFile /etc/pki/tls/certs/google-idp_chain.pem
    
            <Directory /var/simplesamlphp/www>
                Require all granted
            </Directory>
        </VirtualHost>
        ````
    
    *   Apache needs permission to display web pages and execute PHP files:
        ````bash
        chmod +x -R /var/simplesamlphp/
        chcon -R -t httpd_sys_content_t /var/simplesamlphp/
        ````
   
1.  Configure NGINX

    *   Open ``/etc/nginx/nginx.conf``:
        ````bash
        server {
            listen 443 ssl;
            server_name idp.example.com;
    
            ssl_certificate        /etc/pki/tls/certs/google-idp_cert.pem;
            ssl_certificate_key    /etc/pki/tls/certs/google-idp_key.pem;
            ssl_protocols          TLSv1.3 TLSv1.2;
            ssl_ciphers            EECDH+AESGCM:EDH+AESGCM;
    
            location ^~ /simplesaml {
                alias /var/simplesamlphp/www;
    
                location ~ ^(?<prefix>/simplesaml)(?<phpfile>.+?\.php)(?<pathinfo>/.*)?$ {
                    include          fastcgi_params;
                    #fastcgi_pass     $fastcgi_pass;
                    fastcgi_pass 127.0.0.1:9000;
                    fastcgi_param SCRIPT_FILENAME $document_root$phpfile;
    
                    # Must be prepended with the baseurlpath
                    fastcgi_param SCRIPT_NAME /simplesaml$phpfile;
    
                    fastcgi_param PATH_INFO $pathinfo if_not_empty;
                }
            }
        }
        ````
    
1.  Configure SimpleSAMLphp:

    *   Open ``/var/simplesamlphp/config/config.conf``:
        ````bash
        'baseurlpath' => 'https://google-idp.gis.bgu.tum.de/simplesaml/',
        
        'auth.adminpassword' => 'setnewpasswordhere',
        
        # Run tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null;echo
        'secretsalt' => 'randombytesinsertedhere',
        
        'technicalcontact_name' => 'Son H. Nguyen',
        'technicalcontact_email' => 'son.nguyen@tum.de',
        
        # http://php.net/manual/en/timezones.php
        'timezone' => 'Europe/Berlin',
        ````
    
### Set up SimpleSAMLphp Identity Provider

1.  Go to [Google Console](https://console.developers.google.com/), 
    create an OAuth application. Then create credentials for an OAuth web application.
    This will give a Google client ID and a secret.

1.  Configure the file ``/var/simplesamlphp/config/authsources.php`` 
    (more information on the configuration parameters 
    [here](https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote)):
    ````bash
    'google' => [
        'sign.logout' => true,
        'validate.logout' => true,
        'redirect.sign' => true,
        'privatekey' => '/etc/pki/tls/certs/google-idp_key.pem',
        'certificate' => '/etc/pki/tls/certs/google-idp_cert.pem',

        'authgoogleOIDC:GoogleOIDC',
        'key' =>'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
        'secret' => 'YOUR_GOOGLE_SECRET',
    ],
    ````
    
1.  Configure the file ``/var/simplesamlphp/metadata/saml20-sp-remote.php`` to trust SSDSOS1, SSDSOS2 and SSDAS:
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
    
1.  Delete all the examples in ``/var/simplesamlphp/metadata/saml20-sp-remote.php``.
