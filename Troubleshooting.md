# Troubleshooting

[Create/Update Client ID, Client Secret and Redirect URL](#createupdate-client-id-client-secret-and-redirect-url)

[Update SSL certificates](#update-ssl-certificates)

[Login does not work](#login-does-not-work)

[Cookie names](#cookie-names)

[Failed to decrypt symmetric key: Key is missing data to perform the decryption](#failed-to-decrypt-symmetric-key-key-is-missing-data-to-perform-the-decryption)

### Create/Update Client ID, Client Secret and Redirect URL

#### Create new

Open:

https://ssdas.gis.bgu.tum.de/registerapps

Create the following types of applications:

| Application type | Scopes |
|---|---|
| Client-side web application | Cryptoname, Profile, Email, SAML |
| Web service | Cryptoname, SAML |

The first application is used for the web client and shall have a client ID and a redirect URL.

The second application is used for the communication between SP (SSDSOS1 and SSDSOS2) with the RS (SSDWFS) 
and shall have a client ID and a client secret.

A list of all registered applications can be shown using:

https://ssdas.gis.bgu.tum.de/listapps

Copy the Client ID, Client Secret and Redirect URL to:
+   [SSDSOS1 and SSDSOS2](SP)

#### Update registered applications

Registering a new application using the same login and an existing application name 
will replace the registered application with this name.

Registering a new application using the same login and the same existing application name 
with a different version value shall update the corresponding registered application.

#### Update client ID, client secret and redirect URL

Simply replace these values in the [SP](SP) and [RS](RS) 
as well as in the [web application](Web%20Client).

### Update SSL certificates
Normally the certificates and private keys are stored in the directory ``/etc/ssl/certs/``.
Other directories should (already) only have symbolic links to these files.
Such directories might be:
    
+   For **Authorization Server**:
    +   ``/etc/pki/tls/certs/`` 
        (used in file ``/etc/httpd/conf.d/ssl.conf``)
    +   ``/opt/authorization-server/pki/`` 
        (used in file ``/opt/authorization-server/config/config.php``)
    +   ``/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/cert/`` 
        (used in file ``/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/config/authsources.php``)
    
    For more information please refer to [the instructions](AS/authorization-server/README.md#manage-ssl-certificates).

+   For **Discovery Service**:
    +   ``/etc/pki/tls/certs/``
        (used in file ``/etc/httpd/conf.d/ssl.conf``)

    For more information please refer to [the instructions](DS/README.md#configure-apache-server-for-wayf).

+   For **Google IdP**:
    +   ``/etc/pki/tls/certs/``
        (used in file ``/etc/httpd/conf.d/ssl.conf``)
    +   ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/cert/``
        (used in file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/config/authsources.php`` 
        and file ``/var/google-idp/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-hosted.php``)

    For more information please refer to [the instructions](Google-IdP/README.md#set-up-simplesamlphp-identity-provider).

+   For **Service Providers** like SOS1 and SOS2:
    +   ``/etc/ssl/certs/`` (used in file ``/etc/apache2/sites-available/sos-https.conf``)
    +   ``/etc/shibboleth/`` (used in ``/etc/shibboleth/shibboleth2.xml``)

    For more information please refer to [the instructions](SP/README.md#manage-ssl-certificates).

1.  Copy the certificates and private keys to the directory ``/etc/ssl/certs/``.
    
1.  Check if the symbolic links already exist according to the above mentioned instructions for the current server.
    
1.  Restore the certificates and private keys after copying:
    ```bash
    restorecon -RvF /etc/ssl/certs/
    ```

1.  Remove the passphrase from the private key (especially for the Authorization Server):
    ```bash
    openssl rsa -in private_key.pem -out private_key_no_passphrase.pem
    ```

1.  Assign group and owner to ``apache``:
    ```bash
    chown apache:apache certificate.pem
    chown apache:apache private_key.pem
    chown apache:apache private_key_no_passphrase.pem
    chown apache:apache chain.pem
    ```

1.  Change permissions accordingly:
    ```bash
    chmod 644 ./certificate.pem
    chmod 400 ./private_key.pem
    chmod 400 ./private_key_no_passphrase.pem
    chmod 644 ./chain.pem
    ```

1.  Update metadata:
    +   When updating **Authorization Server**:
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
    
    +   When updating **Google IdP**:
        +   Go to https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php?output=xhtml and copy the metadata (flat format)
        +   Go to Authorization Server and paste it or replace the old metadata in the file ``/opt/authorization-server/vendor/simplesamlphp/simplesamlphp/metadata/saml20-idp-remote.php``:
            ```php
            <?php
            
            $metadata['https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php'] = array (
                ...
            );
            
            ```
        +   Go to Discovery Service and repeat the steps in the [documentation](DS/README.md#preparing-the-wayf-for-the-first-use).
        
    +   When updating **SOS1 and SOS2**:
        +   Copy the metadata of SOS1, SOS2, Authorization Server SPs (``oauth``, ``oidc-profile`` and ``openid``) (flat format):
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
    
### Login does not work 
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

### Cookie names
The cookies must be shared between different participating servers and services, 
such as the Authorization Server, the Discovery Service, the Google IdP, the SOS1 and SOS2 as well as the WFS.

The demo uses the following names:

| Key    | Value               |
|--------|---------------------|
| Domain | ``.gis.bgu.tum.de`` |
| Prefix | ``SDDI``            |
| Path   | ``/``               |

### Failed to decrypt symmetric key: Key is missing data to perform the decryption
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
