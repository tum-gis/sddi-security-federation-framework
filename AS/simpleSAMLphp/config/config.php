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

    'application' => array(
        'baseURL' => 'https://as.sddi.secure-dimensions.de'
    ),
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
    'store.sql.dsn'                 => 'mysql:dbname=samlas;host=172.17.0.1',

    'store.sql.username' => 'php',
    'store.sql.password' => 'password',

    'store.sql.prefix' => 'SimpleSAMLphp',

);
