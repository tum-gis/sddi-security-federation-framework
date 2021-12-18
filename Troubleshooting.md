# Troubleshooting

[Create/Update Client ID, Client Secret and Redirect URL](#createupdate-client-id-client-secret-and-redirect-url)

[Update SSL certificates](#update-ssl-certificates)

[Update Metadata of Google IdP](#update-metadata-of-google-idp)

[Logging in SimpleSAMLPHP](#logging-in-simplesamlphp)

[Login does not work](#login-does-not-work)

[Cookie names](#cookie-names)

[Failed to decrypt symmetric key: Key is missing data to perform the decryption](#failed-to-decrypt-symmetric-key-key-is-missing-data-to-perform-the-decryption)

## Create/Update Client ID, Client Secret and Redirect URL

### Create new

Open:

https://ssdas.gis.bgu.tum.de/registerapps

Create the following type of applications:

| Application type | Scopes |
|---|---|
| Desktop web application | Cryptoname, Profile, Email, SAML |

[comment]: <> (| Web service | Cryptoname, SAML |)

This type of applications can be used for both the communication between SP (SSDSOS1 and SSDSOS2) with the RS (SSDWFS) and the web client. 
This shall have a client ID, a client secret and a redirect URL.

A list of all registered applications can then be shown using:

https://ssdas.gis.bgu.tum.de/listapps

Copy the client ID and the client Secret to [SSDSOS1 and SSDSOS2](SP) and [SSDWFS](RS).

Copy the client ID, the client secret and the redirect URL in the [web client](Web%20Client).

### Update registered applications

Registering a new application using the same login and an existing application name 
will replace the registered application with this name.

Registering a new application using the same login and the same existing application name 
with a different version value shall update the corresponding registered application.

### Update client ID, client secret and redirect URL

Simply replace these values in the [SP](SP) and [RS](RS) 
as well as in the [web application](Web%20Client).

[*To the top*](#troubleshooting) 

---------------------------


## Update SSL certificates
Normally the certificates and private keys are stored in the directory ``/etc/ssl/certs/``.
Other directories should (already) only have symbolic links to these files.
Such directories might be:
    
+ For **Authorization Server**:
  + Certificate type: Shibboleth IdP/SAML
  + ``/etc/pki/tls/certs/`` 
      (used in file ``/etc/httpd/conf.d/ssl.conf``)
  + ``/opt/authorization-server/pki/`` 
      (used in file ``/opt/authorization-server/config/config.php``)
  + ``/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/`` 
      (used in file ``/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/config/authsources.php``)
    
    For more information please refer to [the instructions](AS/authorization-server/README.md#manage-ssl-certificates).

+ For **Discovery Service**:
  + Certificate type: Web Server
  + ``/etc/pki/tls/certs/``
    (used in file ``/etc/httpd/conf.d/ssl.conf``)

  For more information please refer to [the instructions](DS/README.md#configure-apache-server-for-wayf).

+ For **Google IdP**:
  + Certificate type: Web Server
  + ``/etc/pki/tls/certs/``
    (used in file ``/etc/httpd/conf.d/ssl.conf``)
  + ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/cert/``
    (used in file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/authsources.php`` 
    and file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-hosted.php``)

  For more information please refer to [the instructions](Google-IdP/README.md#set-up-simplesamlphp-identity-provider).

+ For **Service Providers** like SOS1 and SOS2:
  + Certificate type: Shibboleth IdP/SAML 
  + ``/etc/ssl/certs/`` (used in file ``/etc/apache2/sites-available/sos-https.conf``)
  + ``/etc/shibboleth/`` (used in ``/etc/shibboleth/shibboleth2.xml``)

  For more information please refer to [the instructions](SP/README.md#manage-ssl-certificates).

+ Fore **Resource Provider** like WFS:
  + Certificate type: Web Server
  + ``/etc/pki/tls/certs/``
    (used in file ``/etc/httpd/conf.d/ssl.conf``)

  For more information please refer to [the instructions](RS/README.md#install-apache-web-server).

1. Copy the certificates and private keys to the directory ``/etc/ssl/certs/``.
    
2. Check if the symbolic links already exist according to the above mentioned instructions for the current server.
    
3. Restore the certificates and private keys after copying:
    ```bash
    restorecon -RvF /etc/ssl/certs/
    ```

4. Remove the passphrase from the private key (especially for the Authorization Server):
    ```bash
    openssl rsa -in private_key.pem -out private_key_no_passphrase.pem
    ```

5. Assign group and owner to ``apache``:
    ```bash
    chown apache:apache certificate.pem
    chown apache:apache private_key.pem
    chown apache:apache private_key_no_passphrase.pem
    chown apache:apache chain.pem
    ```

6. Change permissions accordingly:
    ```bash
    chmod 644 ./certificate.pem
    chmod 400 ./private_key.pem
    chmod 400 ./private_key_no_passphrase.pem
    chmod 644 ./chain.pem
    ```

7. Update metadata:
    + When updating **Authorization Server**:
        +   *Will the metadata on the web of the Authorization Server be updated as well by the admins?*
        +   Go to the Discovery Service and repeat the [instructions](DS/README.md#preparing-the-wayf-for-the-first-use).
        +   Copy the metadata of Authorization Server SPs (``oauth``, ``oidc-profile`` and ``openid``) (flat format):
            https://ssdas.gis.bgu.tum.de/simplesaml/module.php/saml/sp/metadata.php/oauth?output=xhtml
            https://ssdas.gis.bgu.tum.de/simplesaml/module.php/saml/sp/metadata.php/oidc-profile?output=xhtml
            https://ssdas.gis.bgu.tum.de/simplesaml/module.php/saml/sp/metadata.php/openid?output=xhtml
        +   Go to Google Idp and paste them or replace the old metadata in the file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-sp-remote.php``:
            ```bash
            <?php
            
            $metadata['https://ssdsos1.gis.bgu.tum.de/shibboleth'] = array (
                ...
            );
            
            $metadata['https://ssdsos2.gis.bgu.tum.de/shibboleth'] = array (
                ...
            );
        
            $metadata['https://ssdas.gis.bgu.tum.de/oauth'] = array (
                ...
            ):
            
            $metadata['https://ssdas.gis.bgu.tum.de/oidc-profile'] = array (
                ...
            ):
            
            $metadata['https://ssdas.gis.bgu.tum.de/openid'] = array (
                ...
            ):
            
            ```
    
    + When updating **Google IdP**:
        +   Go to https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php?output=xhtml and copy the metadata (flat format)
        +   Go to Authorization Server and paste it or replace the old metadata in the file ``/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php``:
            ```php
            <?php
            
            $metadata['https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php'] = array (
                ...
            );
            
            ```
        +   Go to Discovery Service and repeat the steps in the [documentation](DS/README.md#preparing-the-wayf-for-the-first-use).
        
    + When updating **SOS1 and SOS2**:
        + Download the metadata from:
          https://ssdsosX.gis.bgu.tum.de/Shibboleth.sso/Metadata
        + This file is in XML format but flat format is needed. To convert go to:
          https://google-idp.gis.bgu.tum.de/simplesaml/admin/metadata-converter.php
          + Username: ``admin``
          + Password: value of ``'auth.adminpassword'`` of file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/config.php`` in Google Idp 
        + And paste the converted flat format into this file:
          ```bash
          sudo vi /var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-sp-remote.php
          # /*
          #  * SSDSOSX
          #  */
          # <Delete the old content and paste the content of the first link here>
          ```

[*To the top*](#troubleshooting)

---------------------------

## Update metadata of Google IdP

The metadata of Google IdP is used in the [Discovery Service](DS) and both SOS1 and SOS2 in [SP](SP).
Download the new metadata and rewrite. Please refer to the corresponding instructions in each service.

[*To the top*](#troubleshooting)

---------------------------

## Logging in SimpleSAMLphp

By default SimpleSAMLphp uses ``syslog`` to mange logs. This sometimes does not work with every system. 
To change this to write to log files, follow the following steps:

1.  Edit file ``.../vendor/simplesamlphp/simplesamlphp/config/config.php``:
    ```php
    'logging.handler' => 'file', // 'syslog'
    'loggingdir' => 'log/',
    ```

1.  By default the log file shall be created in ``.../vendor/simplesamlphp/simplesamlphp/log/simplesamlphp.log``:
    ```bash
    cd .../vendor/simplesamlphp/simplesamlphp
    mkdir log
    touch log/simplesamlphp.log
    chown apache:apache -R log/
    chmod g+w -R log/
    chcon -Rv --type=httpd_sys_rw_content_t log/
    ```

1.  Restart Apache server:
    ```bash
    systemctl restart httpd 
    ```

[*To the top*](#troubleshooting)

---------------------------

## Login does not work 
Error: The login cannot be done with the message such as:
*   Incorrect cookies, deactivated cookies 
*   Login via back-button or bookmarks
*   IdP and SP time out of sync
*   Etc.

This can be solved by synchronizing the clock between servers using ``ntp`` and ``ntpdate``.

For example in CentOS:

```bash
# Source: https://thebackroomtech.com/2019/01/17/configure-centos-to-sync-with-ntp-time-servers/

# Install and enable ntp and ntpdate
yum install ntp ntpdate
systemctl start ntpd
systemctl enable ntpd
systemctl status ntpd

# Tell ntpdate to use an unprivileged port for outgoing packets with the -u switch 
# and to write logging output to the system syslog facility using the -s switch
ntpdate -u -s 0.centos.pool.ntp.org 1.centos.pool.ntp.org 2.centos.pool.ntp.org
systemctl restart ntpd

# Check if the clock has been synchronized
timedatectl

# Set the hardware clock to the current system time
hwclock -w
```

[*To the top*](#troubleshooting)

---------------------------

## Cookie names
The cookies must be shared between different participating servers and services, 
such as the Authorization Server, the Discovery Service, the Google IdP, the SOS1 and SOS2 as well as the WFS.

The demo uses the following names:

| Key    | Value               |
|--------|---------------------|
| Domain | ``.gis.bgu.tum.de`` |
| Prefix | ``SDDI``            |
| Path   | ``/``               |

[*To the top*](#troubleshooting)

---------------------------

## Failed to decrypt symmetric key: Key is missing data to perform the decryption
Remove the passphrase from the private key and overwrite it:
```bash
# Remove passphrase and overwrite
openssl rsa -in private_key.pem -out private_key.pem
```

If a new private key is created, follow the instructions to [update ssl certificates](#update-ssl-certificates):
```bash
# Remove passphrase and create a new private key
openssl rsa -in private_key.pem -out private_key_no_passphrase.pem
```

[*To the top*](#troubleshooting)

---------------------------
