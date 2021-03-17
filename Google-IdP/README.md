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

*   Prepare an SSL certificate ``google-idp_cert.pem`` and a private key ``google-idp_key.pem``.
    Alternatively, create a self-signed certificate only for testing:
    ````bash
    cd /etc/ssl/certs
    openssl req -newkey rsa:3072 -new -x509 -days 3652 -nodes -out google-idp_cert.pem -keyout google-idp_key.pem
    ````
    The resulting private key in this case does not have any passphrase.

*   Allow only ``root`` to have read access to the key file:
    ````bash
    chmod +x google-idp_key.pem
    ````

*   Remember to add the chain certificate ``/etc/ssl/certs/google-idp_chain.pem`` 
    as well if this certificate is not self-signed.
    
*   The certificates and private keys are stored in ``/etc/ssl/certs``.
    The directory ``/etc/pki/tls/certs`` shall then automatically create symbolic links to the files in this directory.
    
*   Allow ``apache`` to access these files:
    ```bash
    chown apache:apache google-idp_cert.pem
    chown apache:apache google-idp_key.pem
    chown apache:apache google-idp_chain.pem
    ```

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
    *   Open ``/etc/httpd/conf/httpd.conf``:
    ```bash
    ServerName google-idp.gis.bgu.tum.de
     # Redirect all http to https
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI}
    ```
    
    In the case of redirecting all HTTP requests to HTTPS, HTTP must be allowed in the firewall:
    ```
    firewall-cmd --permanent --zone=public --add-service=http
    firewall-cmd --reload
    firewall-cmd --list-all
    ```

    *   Open ``/etc/httpd/conf.d/ssl.conf``:
        ````bash
        <VirtualHost *>
            ServerName google-idp.gis.bgu.tum.de
            DocumentRoot "/var/www/html"
    
            SetEnv SIMPLESAMLPHP_CONFIG_DIR /var/google-idp/vendor/simplesamlphp/simplesamlphp/config
    
            Alias /simplesaml /var/google-idp/vendor/simplesamlphp/simplesamlphp/www
        
            RewriteEngine on
            RewriteCond %{REQUEST_URI} ^/$
            RewriteRule (.*) /simplesaml [R=301]
        
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
        
        'session.cookie.name' => 'SDDISessionID',
        'session.cookie.domain' => '.gis.bgu.tum.de',
        'session.phpsession.cookiename' => 'SimpleSAML',
        'session.authtoken.cookiename' => 'SimpleSAMLAuthToken',
        
        'session.cookie.secure' => true,
        ````

### Create a database for SimpleSAMLphp

SimpleSAMLphp requires a database to store SAML sessions. 
This can be PHPSession, SQL, Memcache or Redis.
In this section, MySQL + PostgreSQL shall be used:

##### Install MySQL
```
yum -y install wget
cd /tmp
wget http://repo.mysql.com/mysql-community-release-el7-5.noarch.rpm
rpm -ivh mysql-community-release-el7-5.noarch.rpm
yum -y update
yum -y install mysql-server
systemctl enable mysqld
systemctl start mysqld
```

After a successful installation it is recommended to harden MySQL. 
A simplistic way to do that is this:
````
mysql_secure_installation
````

Create the Google IdP database (`samlidp` and `samldb` for this documentation).
````bash
mysql
````
OR as ``root``
````
mysql -u root -p
````
then
````bash
mysql> CREATE DATABASE samlidp;
mysql> CREATE DATABASE samldb;
````

To start the Event Scheduler (MySQL on CENTOS) add/edit the following entry in `/etc/my.cnf`:
```
[mysqld]
event_scheduler = on
```
Then restart:
````bash
service mysqld restart
````

Then:
```
mysql -u root -p # or mysql
```
````
mysql> CREATE USER 'php'@'localhost' IDENTIFIED BY 'password';
mysql> GRANT ALL PRIVILEGES ON samlidp.* TO 'php'@'localhost';
mysql> GRANT ALL PRIVILEGES ON samldb.* TO 'php'@'localhost';
mysql> FLUSH PRIVILEGES;
````

##### Altternative: Install PostgreSQL
The version 11 is important as some SQL commands are only supported starting V11.
````
cd /tmp
#rpm -Uvh https://yum.postgresql.org/11/redhat/rhel-7-x86_64/pgdg-redhat-repo-latest.noarch.rpm
rpm -Uvh https://download.postgresql.org/pub/repos/yum/reporpms/EL-7-x86_64/pgdg-redhat-repo-latest.noarch.rpm
yum install postgresql11-server postgresql11 postgresql11-contrib  -y
/usr/pgsql-11/bin/postgresql-11-setup initdb
systemctl enable postgresql-11.service
systemctl start postgresql-11.service
yum -y install oidentd
````

Modify local access:
````
vi /var/lib/pgsql/11/data/pg_hba.conf
````

Modify line `local all all peer`
to `local all all md5`
and add line `host samlidp php 127.0.0.1/32 md5`.

Change password of the user ``postgres``:
```bash
# Add the following line in ``/var/lib/pgsql/11/data/pg_hba.conf``
local all all trust

sudo -U postgres psql
ALTER USER postgres with password '<PASSWORD>';

# Then delete the added line in ``/var/lib/pgsql/11/data/pg_hba.conf``
# local all all trust

# Restart
systemctl restart postgresql-11
```

Create the database `samlidp` and `samldb`:
````
cd ...
su postgres
createuser php;
createdb samlidp -O php;
createdb samldb -O php;
psql -c "ALTER user php WITH ENCRYPTED PASSWORD 'password'";
psql -U php -W samlidp
samlidp=# \q
psql -U php -W samldb
samlidp=# \q
```` 

Grant privileges to database
````
su postgres
psql samlidp
samlidp=# GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public to php;
samlidp=# GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public to php;
samlidp=# GRANT ALL PRIVILEGES ON DATABASE samlidp to php;
samlidp=# GRANT ALL PRIVILEGES ON DATABASE samldb to php;
````

##### Configure SimpleSAMLphp
Then edit the file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/config.php``:
```php
'store.type' => 'sql',
#'store.sql.dsn' => 'pgsql:dbname=samlidp;host=localhost;port=5432',
'store.sql.dsn' => 'mysql:dbname=samlidp;host=localhost;port=3306',
'store.sql.username' => 'php',
'store.sql.password' => 'password',
'store.sql.prefix' => 'SimpleSAMLphp',

#'database.dsn' => 'pgsql:dbname=samldb;host=localhost;port=5432',
'database.dsn' => 'mysql:dbname=samldb;host=localhost;port=3306',
'database.username' => 'php',
'database.password' => 'password',
'database.prefix' => 'SimpleSAMLphp',
```

Allow Apache to connect to remote database:
```
# Check SELinux
sestatus

# See flags on httpd
getsebool -a | grep httpd

# Allow Apache to connect to remote database through SELinux
setsebool httpd_can_network_connect_db 1

# Use -P option to make the change permanent
# (or else the flag will be set to 0 again after reboot)
setsebool -P httpd_can_network_connect_db 1
```

Restart:
```
systemctl restart httpd
systemctl restart mysqld
systemctl restart postgresql-11
```

### Logging in SimpleSAMLphp

Please follow [the instructions](/Troubleshooting.md#logging-in-simplesamlphp) 
to configure the logging in SimpleSAMLphp.
    
### Set up SimpleSAMLphp Identity Provider

1.  Go to the [Google API Console](https://console.developers.google.com/), 
    create an OAuth application. Then create credentials for an OAuth web application.
    This will give a Google client ID and a secret.

1.  Since the default directory for certificates and private keys for SimpleSAMLPHP is
    in the directory ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/cert``
    as given in the variable ``'certdir' => 'cert/''``
    in the file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/config.php``:
    + EITHER change the path to the variable ``certdir``
    + OR create symbolic links to existing certificates and private keys in the directory
      ``/etc/ssl/certs/``:
    ```bash
    cd /var/google-idp/vendor/simplesamlphp/simplesamlphp
    mkdir cert
    cd cert
    ln -s /etc/ssl/certs/google-idp_cert.pem ./
    ln -s /etc/ssl/certs/google-idp_key.pem ./
    ln -s /etc/ssl/certs/google-idp_chain.pem ./
    ```

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
        'privatekey' => 'google-idp_key.pem',
        'certificate' => 'google-idp_cert.pem',
        // *** Google Endpoints ***
        //'urlAuthorize' => 'https://accounts.google.com/o/oauth2/auth',
        //'urlAccessToken' => 'https://accounts.google.com/o/oauth2/token',
        //'urlResourceOwnerDetails' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        // *** My application ***
        'clientId' => 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com',
        'clientSecret' => 'YOUR_GOOGLE_SECRET',
        //'scopes' =>  [
        //    'openid',
        //    'email',
        //    'profile'
        //],
        //'scopeSeparator' => ' ',
    ],
    ````
    
1.  Comment ``default-sp`` out in the file 
    ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/authsources.php``.
    
1.  Edit the following lines in the file 
    ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-hosted.php``
    ````php
    // Relative to certdir given in config.php
    'privatekey' => 'google-idp_key.pem',
    'certificate' => 'google-idp_cert.pem',

    'auth' => 'google',
    
    'SingleLogoutServiceBinding' => array(
        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
        'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
    ),
    
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
    to trust SSDSOS1 and SSDSOS2.
    
    The metadata of SOS1 and SOS2 (Shibboleth) can be retrieved using:

    https://ssdsos1.gis.bgu.tum.de/Shibboleth.sso/Metadata

    https://ssdsos2.gis.bgu.tum.de/Shibboleth.sso/Metadata

    This produces metadata in XML format. To convert it to SimpleSAMLPHP metadata format, use the converter:

    https://google-idp.gis.bgu.tum.de/simplesaml/admin/metadata-converter.php

    ```php
    /*
     * SSDSOS1
     */
    $metadata['https://ssdsos1.gis.bgu.tum.de/shibboleth'] = [
        // Contents ...
    ];
    
    /*
     * SSDSOS2
     */
    $metadata['https://ssdsos2.gis.bgu.tum.de/shibboleth'] = [
        // Contents ...
    ];
    ```
    
1.  Further append the metadata with the following SPs from the Authorization Server to the file 
    ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-sp-remote.php``:
    
    https://ssdas.gis.bgu.tum.de/simplesaml/module.php/saml/sp/metadata.php/oauth?output=xhtml
    
    https://ssdas.gis.bgu.tum.de/simplesaml/module.php/saml/sp/metadata.php/oidc-profile?output=xhtml
    
    https://ssdas.gis.bgu.tum.de/simplesaml/module.php/saml/sp/metadata.php/openid?output=xhtml
    
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
    
### Configure claims in response from Google

The response from Google after a successful login using ``'template' => 'GoogleOIDC'`` 
mentioned [above](#set-up-simplesamlphp-identity-provider) contain claims such as 
``sub``, ``name``, ``family_name``, ``given_name``, etc. 
(see [documentation](https://developers.google.com/identity/protocols/oauth2/openid-connect#obtainuserinfo)).
These must be transformed so that the Authorization Server can make sense of.
    
1.  Go to ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml-idp-hosted.php`` and add the following element:

    ***With the template ``GoogleOIDC``:***
    ```php
    'authproc' => [
        // Convert oidc names to ldap friendly names
        90 => ['class' => 'core:AttributeMap',  'authoauth2:oidc2name'],
        // Create an additional attribute subject-id that has the same value as uid
        92 => array(
            'class' => 'core:AttributeCopy',
            'uid' => 'subject-id',
        ),
        // Change value true and false to 1 and 0
        93 => [
            'class' => 'core:AttributeAlter',
            'subject' => 'oidc.email_verified',
            'pattern' => '/true/',
            'replacement' => '1',
        ],
        94 => [
            'class' => 'core:AttributeAlter',
            'subject' => 'oidc.email_verified',
            'pattern' => '/false/',
            'replacement' => '0',
        ],
        // Rename attributes for compatibility with the Authorization Server (see as.php)
        95 => [
            'class' => 'core:AttributeMap',
            'oidc.picture' => 'picture',
            'oidc.email_verified' => 'emailVerified',
            'oidc.locale' => 'locale',
        ],
    ],
    ```
    
    This changes the attribute names (as identified by the class ``'core:AttributeMap'``) contained in the Google response to those compatible with the Authorization Server,
    as listed in lines [133-160](../AS/authorization-server/www/as.php) of ``as.php``.
    The file [oidc2name](https://github.com/cirrusidentity/simplesamlphp-module-authoauth2/blob/master/attributemap/oidc2name.php)
    contains a pre-defined set of mapping rules according to the [OpenID Connect specs](https://openid.net/specs/openid-connect-core-1_0.html#Claims).
    This is further appended by additional four rules as shown above.
    The number ``90`` and ``95`` indicate the priority, in which the rules are executed: smaller priorities first.
    This means that the pre-defined rules in ``oidc2name`` shall be executed first.

    For more information on the ``core:AttributeMap``, please refer to the [documentation](https://simplesamlphp.org/docs/stable/core:authproc_attributemap).
    It is also possible to not only change attribute names, but also insert, delete, etc. them,
    please refer to the classes listed in the [documentation](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc#section_2).
    
    ***Alternatively, without the template ``GoogleOIDC``:***
    ```php
    'authproc' => [
        // Create an additional attribute NameID that has the same value as sub
        1 => array(
            'class' => 'saml:PersistentNameID',
            'attribute' => 'sub',
            'NameQualifier' => TRUE,
            'nameId' => TRUE,
        ),
        // Create an additional attribute subject-id that has the same value as sub
        2 => array(
            'class' => 'core:AttributeCopy',
            'sub' => array('subject-id', 'uid'),
        ),
        // Change value true and false to 1 and 0
        93 => [
            'class' => 'core:AttributeAlter',
            'subject' => 'email_verified',
            'pattern' => '/true/',
            'replacement' => '1',
        ],
        94 => [
            'class' => 'core:AttributeAlter',
            'subject' => 'email_verified',
            'pattern' => '/false/',
            'replacement' => '0',
        ],
        // Rename attributes for compatibility with the Authorization Server (see as.php)
        95 => [
            'class' => 'core:AttributeMap',
            'name' => 'displayName',
            'given_name' => 'givenName',
            'family_name' => 'sn',
            'email_verified' => 'emailVerified',
        ],
    ],
    ```
    
    ***This can also be done in ``config.php``:***
    ```php
    /*
     * Authentication processing filters that will be executed for all IdPs
     * Both Shibboleth and SAML 2.0
     */
    'authproc.idp' => array(
        1 => array(
            'class' => 'saml:TransientNameID',
        ),
        // Create an additional attribute NameID that has the same value as sub
        2 => array(
            'class' => 'saml:PersistentNameID',
            'attribute' => 'sub',
        ),
        // Add sub to the attributes and save in $state
        3 => array(
            'class' => 'saml:PersistentNameID2TargetedID',
            'attribute' => 'sub',
            'nameId' => FALSE,
        ),
        // Create an additional attribute subject-id that has the same value as sub
        4 => array(
            'class' => 'core:AttributeCopy',
            'sub' => array('subject-id', 'uid'),
        ),
        // Change value true and false to 1 and 0
        5 => array(
            'class' => 'core:AttributeAlter',
            'subject' => 'email_verified',
            'pattern' => '/true/',
            'replacement' => '1',
        ),
        6 => array(
            'class' => 'core:AttributeAlter',
            'subject' => 'email_verified',
            'pattern' => '/false/',
            'replacement' => '0',
        ),
        7 => array(
            'class' => 'core:AttributeAdd',
            'homeOrganization' => array('Google'),
        ),
        // Rename attributes for compatibility with the Authorization Server (see as.php)
        // https://commons.lbl.gov/display/IDMgmt/Attribute+Definitions#AttributeDefinitions-organizationNameorganizationName
        10 => array(
            'class' => 'core:AttributeMap',
            'homeOrganization' => 'urn:oid:1.3.6.1.4.1.25178.1.2.9',
            'name' => 'urn:oid:2.16.840.1.113730.3.1.241',
            'email' => 'urn:oid:0.9.2342.19200300.100.1.3',
            'email' => 'urn:oid:1.2.840.113549.1.9.1',
            'given_name' => 'urn:oid:2.5.4.42',
            'family_name' => 'urn:oid:2.5.4.4',
            'email_verified' => 'emailVerified',
            'subject-id' => 'urn:oasis:names:tc:SAML:attribute:subject-id',
            'uid' => 'urn:oid:0.9.2342.19200300.100.1.1',
        ),
        // Adopts language from attribute to use in UI
        30 => 'core:LanguageAdaptor',
        45 => array(
            'class'         => 'core:StatisticsWithAttribute',
            'attributename' => 'realm',
            'type'          => 'saml20-idp-SSO',
        ),
        /*
        // When called without parameters, it will fallback to filter attributes ‹the old way›
        // by checking the 'attributes' parameter in metadata on IdP hosted and SP remote.
        50 => array(
        'class' => 'core:AttributeLimit',
        ),
        */
        // If language is set in Consent module it will be added as an attribute.
        99 => 'core:LanguageAdaptor',
    ),
    ```
    
1.  Note that existing rules with smaller priority number that affect the same attributes might interfere with the rules executed afterwards.
    For this reason, it is recommended to **remove or adjust** the following elements in ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/config.php``:
    +   ``'authproc.idp' => [ ... ],``
    +   ``'authproc.sp' => [ ... ],``
    
1.  To test the new response, go to
    
    https://ssdas.gis.bgu.tum.de/simplesaml/module.php/core/authenticate.php?as=openid

    and login using a Google account. The following results should be shown:

    ```json
    "Attributes": {
        "uid": [
            "123456748912345678912"
        ],
        "cn": [
            "Son N"
        ],
        "givenName": [
            "Son"
        ],
        "sn": [
            "N"
        ],
        "picture": [
            "https://example.com/picture"
        ],
        "mail": [
            "example@gmail.com"
        ],
        "emailVerified": [
            "true"
        ],
        "locale": [
            "en"
        ]
    },
    ```

The attribute names from/accepted by different sources are summarized as follows:

| **Google response** | **Template ``GoogleOIDC``** | **Authorization server** |
|---------------------|-----------------------------|--------------------------|
| ``sub`` | ``oidc.sub`` | ``uid`` |
| ``name`` | ``oidc.name`` | ``cn`` |
| ``given_name`` | ``oidc.given_name`` | ``givenName`` |
| ``family_name`` | ``oidc.family_name`` | ``sn`` |
| ``picture`` | ``oidc.picture`` | ``picture`` |
| ``email`` | ``oidc.email`` | ``mail`` |
| ``email_verified`` | ``oidc.email_verified`` | ``emailVerified`` |
| ``locale`` | ``oidc.locale`` | ``locale`` |

### IMPORTANT NOTE

Google sends by default access to some personal information such as ``namme``, ``given_name``, ``family_name``, ``picture``, ``email``, etc.
and this happens without involving the Authorization Server.
In other words, the Google response is currently the same for all three SPs of the Authorization Server (``oauth``, ``oidc-profile``, ``openid``).

One future work is to investigate if it is possible to develop different Attribute Release Filters for each type 
of the SPs ``oauth``, ``oidc-profile``, ``openid`` in order to make the responses conform to the EU GDPR. 
The next step is to develop or employ a GoogleAuth Adapter for each of the above-mentioned SP.
