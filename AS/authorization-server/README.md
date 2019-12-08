# OAuth2 / OpenID Connect Authorization Server with SAML2 Federated Authentication
This project implements an Authorization Server (AS) with OpenID Connect that supports the login via SAML2.

The main functionality for the Authorization Server is based on the OAuth2 library from Brent Shaffer. The SAML2 authentication is based on SimpleSAMLPHP.

Please observe [documentation for the OAuth2 Library](https://bshaffer.github.io/oauth2-server-php-docs/).

Please observe [documentation for the SimpleSAMLPHP library](https://simplesamlphp.org/).

## Disclaimer
This documentation describes how to install and configure the Authorization Server in a tutorial fashion. The final configuration may not be suitable to be operated in a production environment without security hardening of the operating system, installed services, etc.

Please follow specialized documentation to configure a production strength system.

## Description
The main SAML authenticaiton functionality is implemented as an extension to the OAuth2 library. You find the extensions in the `lib` sub-folders.

The main functions of the authorization server is implemented in file `.../www/as.php` and the configuration can be achieved via the `.../config/config.php` file.

## Dependencies
This implementation requires PHP 7.2 and different extensions and the following libraries:

* [OAuth2 library](https://github.com/bshaffer/oauth2-server-php)
* [SimpleSAMLphp](https://simplesamlphp.org/)

## Installation
The installation of the AS is mainly achieved via the PHP composer tool.

This documentation leverages Centos 7 to describe the installation of OS packages.

### Preperation
The following installation is based on a CENTOS 7 image.

After installing the 'raw' OS, it is good practise to update the packages:

````
yum -y update
````

It is also a good idea to make sure that a basic firewall is in place before continuing with the installation.

````
yum -y install firewalld
systemctl enable firewalld
systemctl start firewalld
````

Verify the open ports remaining:

````
firewall-cmd --list-all
````

This should list `services: dhcpv6-client ssh`.

Once the installation of the Authorization Server is complete, please allow inbound connections on HTTPS:

````
firewall-cmd --zone=public --permanent --add-service=https
firewall-cmd --reload
````

Verify the open ports should now include `https`

````
firewall-cmd --list-all
````


### Install Apache Web Server
The AS operates on HTTPS which requires to also install the `mod_ssl` module.

````
yum -y install httpd mod_ssl
````

Please configure the Apache Web Server with a proper certificate to operate on HTTPS. (The details how to do that is outside the scope of this documentation).

You find an example configuration for deploying the Authorization in the configuration section below.

### Install PHP 7.2
On CENTOS 7 you can install PHP 7.2 from the remi repository.

````
yum install -y http://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum install -y yum-utils
yum-config-manager --enable remi-php72
yum -y install php php-opcache php-xml php-dom php-mcrypt php-mysql php-intl php-mbstring php-bcmath php-soap php-pgsql php-mongodb
````

### Install PHP Composer
To install the PHP coposer tool, please execute the following command in a terminal:

````
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
````

This installs the PHP Composer in `/usr/local/bin` as executeable.

### Install MySQL
This implementation of the Authorization Server uses database schemes that are different from the ones used by the OAuth2 library. The main reason is the support for use claims originating from SAML authentication.

````
yum -y install wget
wget http://repo.mysql.com/mysql-community-release-el7-5.noarch.rpm
rpm -ivh mysql-community-release-el7-5.noarch.rpm
yum -y update
yum -y install mysql-server
systemctl enable mysqld
systemctl start mysqld
```` 

After a successful installation it is recommended to harden MySQL. A simplistic way to do that is this:

````
mysql_secure_installation
````

### Install Postgresql v11
The version 11 is important as some SQL commands are only supported starting V11.

````
rpm -Uvh https://yum.postgresql.org/11/redhat/rhel-7-x86_64/pgdg-redhat-repo-latest.noarch.rpm
yum install postgresql11-server postgresql11 postgresql11-contrib  -y
/usr/pgsql-11/bin/postgresql-11-setup initdb
systemctl enable postgresql-11.service
systemctl start postgresql-11.service
````

Modify local access:

````
vi /var/lib/pgsql/11/data/pg_hba.conf
````

Modify line `local   all             all                                     peer` to `local   all             all                                     md5`.

### Install the Authorization Server
To download a copy of the AS code, simply use `git clone`. Make sure you have installed `git`.

````
yum -y install git
````

Then run the command below in a terminal:

````
cd /opt
git clone https://github.com/securedimensions/authorization-server.git
````

Once completed, please change into the directory `/opt/authorization-server`. This is the home directory for all further installation and will be referred to via `...` in the subsequent documentation.

Before you execute the PHP Composer, please make sure you have installed `unzip`

````
yum -y install unzip
````

Execute the following command in directory `authorization-server`:

````
composer install
````

This will download the required PHP packages to run the Authorization Server including the OAuth2 and SimpleSAMLphp libraries into the `vendor` directory. The SimpleSAMLphp package is required for the SAML based authentication.


## Configuration
Different software packages must co=operatoe to make the AS work:

* Database
* Apache Web Server
* SimpleSAMLphp

### MySQL Database
Create the Authorization Server database (`samlas` for this documentation).

````
mysql
mysql> CREATE DATABASE samlas;
```` 

The database tables will be created automatically if not exist. in order to change this default behaviour, please change the following entry in the `config/config.php` file:

````
'create_db' => false,
````

Enable the Event Scheduler in the database (please consult the help for your database) and import the following event triggers to cleanup expired authorization codes, access tokens and refresh tokens.

````
mysql> source deleteExpiredAuthorizationCodesEvent.sql;
mysql> source deleteExpiredAccessTokensEvent.sql;
mysql> source deleteExpiredRefreshTokensEvent.sql;
````

To start the Event Scheduler (MySQL on CENTOS) add the entry `event_scheduler = on` under the `[mysqld]` section in `/etc/my.cnf` and restart mysqld.

````
mysql> CREATE USER 'php'@'localhost' IDENTIFIED BY 'password';
mysql> GRANT ALL PRIVILEGES ON samlas.* TO 'php'@'localhost';
mysql> FLUSH PRIVILEGES;
````

For enabling the tests the Authorization Server automatically creates the test applications, if the `create_db` entry is set to `true`.
To change this default behaviour, change the default value in the `config/config.php` file to false:

````
'create_test_clients' => false,
````

### Postgresql Database
For the Authorization Server, version 11 must be installed.

Create the Authorization Server database (`samlas` for this documentation).

````
cd ...
su postgres
createuser php;
cratedb samlas -O php;
psql -c "ALTER user php WITH ENCRYPTED PASSWORD 'password'";
psql -U php -W samlas
samlas=# \q
```` 

Grant privileges to database

````
su postgres
psql samlas
samlas=# GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public to php;
samlas=# GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public to php;
samlas=# GRANT ALL PRIVILEGES ON DATABASE samlas to php;
````

### Authorization Server

#### Logging
The AS writes - by default - a logfile to `.../log/as.log`. Please make sure that the `log` directory is writeable to apache:

````
cd ...
chown -R apache:apache log
```` 

#### Keys
Create a private/public key pair in the `.../pki` directory. Please follow the details form that documentation in [PKI README](./pki/README.md) to setup the keys.

Once the installation inside the `pki` directory is complete, please make the directory available to the Web Server process:

````
chown -R apache:apache pki
````

#### Setup Config.php
Configue the Authorization Server vi `.../config/config.php`

* configure the Discovry Service with `ds_url`
* set the `secret`to a meaningful salt

### SimpleSAMLphp
In any case, it is recommended to follow the detailed documentation available on the [SimpleSAMLphp homepage](https://simplesamlphp.org/).

The aim of the following documentation is **not** to replace the comprehensive documentation provided by SimpleSAMLphp. Instead, it is ment as a short add-on to be able to configure the library for supporting the AS in a required way.

This requires to configure SimpleSAMLphp to act as a SAML2 Service Provider to support the authentication for the AS. Please note that the AS leverages two logical instances of a SAML SP to support the strict differenciation into

* logical instance `oauth` that must be configured to **not** request attributes from IdPs
* logical instance `openid` that must be configured to **request** attributes from IdPs 


#### Keys and Certificate
The directory `.../cert` must be created and at least contain a private key and a valid (not self-signed) certificate to sign (and eventually encrypt) SAML communicaation. Please follow your companies policy to create a private key and to obtain a globally valid certificate.

Pease put the private key and certificate into the `.../cert` directory and configure the associated entires in the `.../config/authsources.php` file.

```php
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

        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',

        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/oauth',

        'discoURL' => '<this is the same URL as you provided in the AS config/config.php under ds_url>',

        'privatekey' => '<filename for the private key>.pem',

        'certificate' => '<filename for the certificate>.crt',

        'sign.logout' => true,
    ),
    // This is the SAML2 SP authentication source that shall be configured to NOT request user attributes
    'oidc-openid' => array(
        'saml:SP',

        'NameIDPolicy' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',

        'entityID' => 'https://' . $_SERVER['SERVER_NAME'] . '/oidc-profile',

        'discoURL' => '<this is the same URL as you provided in the AS config/config.php under ds_url>',

        'privatekey' => '<filename for the private key>.pem',

        'certificate' => '<filename for the certificate>.crt',

        'sign.logout' => true,
    )
);
```

Example `authsources.php` file.

```php
<?php
/*
 * This is the stripped configuration of SimpleSAMLphp for Secure Dimensions Authorization Server
 *
 */

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$config = array(

    'baseurlpath' => 'https://as.sddi.secure-dimensions.de/simplesaml/',
    'certdir' => 'cert/',
    'loggingdir' => 'log/',
    'datadir' => 'data/',
    'tempdir' => '/tmp/simplesaml',

    'technicalcontact_name' => 'Andreas Matheus',
    'technicalcontact_email' => 'support@secure-dimensions.de',

    'timezone' => 'Europe/Berlin',

    'secretsalt' => '59fmwccn2iu3829dd209j0fj3fke0h45dslxt03f',

    'auth.adminpassword' => 'ThisIsSecure',

    'admin.protectindexpage' => false,
    'admin.protectmetadata' => false,

    'admin.checkforupdates' => true,

    'trusted.url.domains' => array('apps.sddi.secure-dimensions.de'),

    'trusted.url.regex' => false,

    'enable.http_post' => false,

    'debug' => array(
        'saml' => true,
        'backtraces' => true,
        'validatexml' => false,
    ),

    'showerrors' => true,
    'errorreporting' => true,

    'logging.level' => SimpleSAML\Logger::DEBUG,
    'logging.handler' => 'errorlog',

    'logging.facility' => defined('LOG_LOCAL5') ? constant('LOG_LOCAL5') : LOG_USER,

    'logging.processname' => 'simplesamlphp',

    'logging.logfile' => 'simplesamlphp.log',

    'statistics.out' => array(),

    'proxy' => null,

    'enable.saml20-idp' => false,
    'enable.shib13-idp' => false,
    'enable.adfs-idp' => false,
    'enable.wsfed-sp' => false,
    'enable.authmemcookie' => false,

    'default-wsfed-idp' => 'urn:federation:pingfederate:localhost',

    'shib13.signresponse' => true,

    'session.duration' => 8 * (60 * 60), // 8 hours.

    'session.datastore.timeout' => (4 * 60 * 60), // 4 hours

    'session.state.timeout' => (60 * 60), // 1 hour

    'session.cookie.name' => 'SDDISessionID',

    'session.cookie.lifetime' => 0,

    'session.cookie.path' => '/',

    'session.cookie.domain' => '.sddi.secure-dimensions.de',

    'session.cookie.secure' => true,

    'session.phpsession.cookiename' => 'SDDIPHP',
    'session.phpsession.savepath' => null,
    'session.phpsession.httponly' => true,

    'session.authtoken.cookiename' => 'SDDIAuthToken',

    'session.rememberme.enable' => false,
    'session.rememberme.checked' => false,
    'session.rememberme.lifetime' => (14 * 86400),

    'language' => array(
        'priorities' => array(
            'no' => array('nb', 'nn', 'en', 'se'),
            'nb' => array('no', 'nn', 'en', 'se'),
            'nn' => array('no', 'nb', 'en', 'se'),
            'se' => array('nb', 'no', 'nn', 'en'),
        ),
    ),

    'language.available' => array(
        'en', 'no', 'nn', 'se', 'da', 'de', 'sv', 'fi', 'es', 'ca', 'fr', 'it', 'nl', 'lb', 
        'cs', 'sl', 'lt', 'hr', 'hu', 'pl', 'pt', 'pt-br', 'tr', 'ja', 'zh', 'zh-tw', 'ru',
        'et', 'he', 'id', 'sr', 'lv', 'ro', 'eu', 'el', 'af'
    ),
    'language.rtl' => array('ar', 'dv', 'fa', 'ur', 'he'),
    'language.default' => 'en',

    'language.parameter.name' => 'language',
    'language.parameter.setcookie' => true,

    'language.cookie.name' => 'language',
    'language.cookie.domain' => null,
    'language.cookie.path' => '/',
    'language.cookie.secure' => false,
    'language.cookie.httponly' => false,
    'language.cookie.lifetime' => (60 * 60 * 24 * 900),

    'language.i18n.backend' => 'SimpleSAMLphp',

    'attributes.extradictionary' => null,

    'theme.use' => 'default',

    'template.auto_reload' => false,

    'production' => true,

    'idpdisco.enableremember' => true,
    'idpdisco.rememberchecked' => true,

    'idpdisco.validate' => true,

    'idpdisco.extDiscoveryStorage' => null,

    'idpdisco.layout' => 'dropdown',

    'authproc.sp' => array(

        10 => array(
            'class' => 'core:AttributeMap', 
	    'oid2name',
	    'urn:oasis:names:tc:SAML:attribute:subject-id' => 'subject-id',
        ),

        // Adopts language from attribute to use in UI
        90 => 'core:LanguageAdaptor',

    ),

    'metadata.sources' => array(
        array('type' => 'flatfile'),
	array('type' => 'flatfile', 'directory' => 'metadata/metafresh-dfn', 'file' => 'saml20-idp-remote.php'),
	array('type' => 'flatfile', 'directory' => 'metadata/metafresh-eduGain', 'file' => 'saml20-idp-remote.php'),
    ),

    'metadata.sign.enable' => false,

    'metadata.sign.privatekey' => null,
    'metadata.sign.privatekey_pass' => null,
    'metadata.sign.certificate' => null,
    'metadata.sign.algorithm' => null,

    'store.type'                    => 'sql',
    'store.sql.dsn'                 => 'mysql:dbname=samlas;host=localhost;port=3306',

    'store.sql.username' => 'php',
    'store.sql.password' => 'password',

    'store.sql.prefix' => 'SimpleSAMLphp',

);
```

Example config.php file


#### Metadata Management
In order to be able to leverage the SAML authentication in an existing federation, the metadata of the two SPs must be registered with a coordination center that is responsible. For example in case the AS is operated in Germany and  you want to have the AS allow federted login from DFN AAI, then you must register the SPs metadata with them. Please follow the DFN AAI instructions [here](https://doku.tid.dfn.de/:de:start)

When registering the SP instances with the Coordination Center, it is important to keep in mind that the configuration of the wo instances differ regarding the request of user attributes: The `oauth` instance must be configured to not force the IdP to release user attributes and the `openid` instance must be configured to request a unique user identifier plus attributes to fill the openid claims for the scopes `email` and `profile`.

#### Fetching Federtion Metadata
Once the SPs are registered, the SPs must fetch the metadata of the IdPs that are trusted for login. SimpleSAMLphp supports two options: (i) manual management for the circle of trust and (ii) automatic trust.

In case you like to setup the AS with automatic trust establishment, this can be achieved as documented in the SimpleSAMLphp wiki: <https://simplesamlphp.org/docs/stable/simplesamlphp-automated_metadata>

In case there are other (project specific) IdPs that are not registered with another coorination center, please load their metadata manually.

The following configuration file fetches the DFN and eduGAIN metadata automatically (`config/config-metarefresh.php`):

```php
<?php

/*
Copyright © 2019 Secure Dimensions GmbH

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

$config = array(

	'sets' => array(

		'dfn' => array(
			'cron'		=> array('daily'),
			'sources'	=> array(
				array(
					'src' => 'https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-basic-metadata.xml',
					'certificates' => array(
						'dfn-aai.g2.pem'
					),
					'template' => array(
						'tags'	=> array('dfn'),
						'authproc' => array(
							51 => array('class' => 'core:AttributeMap', 'oid2name'),
						),
					),

				),
			),
			'expireAfter' 		=> 60*60*24*4, // Maximum 4 days cache time
			'outputDir' 	=> 'metadata/metafresh-dfn/',

			/*
			 * Which output format the metadata should be saved as.
			 * Can be 'flatfile' or 'serialize'. 'flatfile' is the default.
			 */
			'outputFormat' => 'flatfile',
		),
                'eduGain' => array(
                        'cron'          => array('daily'),
                        'sources'       => array(
                                array(
                                        'src' => 'https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-edugain+idp-metadata.xml',
                                        'template' => array(
                                                'tags'  => array('eduGain'),
                                                'authproc' => array(
                                                        51 => array('class' => 'core:AttributeMap', 'oid2name'),
                                                ),
                                        ),

                                ),
                        ),
                        'expireAfter'           => 60*60*24*4, // Maximum 4 days cache time
                        'outputDir'     => 'metadata/metafresh-eduGain/',

                        /*
                         * Which output format the metadata should be saved as.
                         * Can be 'flatfile' or 'serialize'. 'flatfile' is the default.
                         */
                        'outputFormat' => 'flatfile',
                ),
	),
);
```

The metadata is fetched via he user of the Web Server (in the case of httpd this is `apache`). It is required to create the sorage directories and make use `apache` owner.

````
cd ...
mkdir -p metadata/metafresh-eduGain
mkdir -p metadata/metafresh-dfn
chown apache:apache metadata/metafresh*
````

The automated fetching of metadata requires the certificate of the coordination center - which is DFN in the example above. Pleae make sure you download the `dfn-aai.g2.pem` file and store it into the `cert` directory.

For initializing the metadata you can manually fetch the metadata from the configured sources using user `apache`:

````
cd ...
cd metadata
su -s /bin/bash apache -c "php metarefresh.php"
````

The metadata will expire after the configured time (default 96 hours). To keep the metadata fresh, please configure crontab to fetch the metadata each day for example. It is important the the user apache runs the script! Use `crontab -e` to add the following line:

````
01 1 * * * su apache -s /bin/bash -c "cd /opt/authorization-server/vendor/simplesamlphp/simplesamlphp/metadata/ && php metarefresh.php"
````

### Apache Web Server
The Apache Web Server must be configured to rewrite the API path to be executed by `as.php`. This can simply be achieved by the following example configuration

````
    RewriteCond "%{REQUEST_URI}"  "^/oauth"  [OR]
    RewriteCond "%{REQUEST_URI}"  "^/openid"  [OR]
    RewriteCond "%{REQUEST_URI}"  "^/saml" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/listapps" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/registerapps" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/authorizedapps" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/logoutapps" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/listapp" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/listapps" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/listoperators" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/.well-known" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/NoPrivacyStatement" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/PrivacyStatement" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/CookieStatement" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/TermsOfUse" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/IdPs" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/Operators" [OR]
    RewriteCond "%{REQUEST_URI}"  "^/DiscoveryService"
    RewriteRule (.*) /as.php/$1 [qsappend,L]
````

To enable the SimpleSAMLphp library, please add he following to the `as.conf`:

````
SetEnv SIMPLESAMLPHP_CONFIG_DIR /opt/authorization-server/vendor/simplesamlphp/simplesamlphp/config
Alias /simplesaml /opt/authorization-server/vendor/simplesamlphp/simplesamlphp/www
````


### Testing
The deployed Authorization Server can be tested via a set of test applications and a Test Web Server that simulates the different applications. 

All information regarding testing can be in the `test/AS` [directory](test/AS/README.md)

#### Preperation (sort)
The Test Web Server displays the homepage for testing which is generated from `TEST.md`. So first, you need to run `composer install` to install the dependencies. Change into the `.../test/AS` directory.

````
composer install
````

Note: when asked to re-use the existing composer.json file in `/opt/authorization-server` answer *no*.

Next, you need to set the domain name of your deployed AS and start the Web Server.

````
export OPENID_CONFIGURATION=https://<your domain name for the Authorization Server>/.well-known/openid-configuration
php -S 127.0.0.1:4711 -t html
````

#### Use the Test Web Server
Please use your favorite Web Browser and navigate to URL <http://127.0.0.1:4711>.

Happy testing!
