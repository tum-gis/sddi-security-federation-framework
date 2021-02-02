# Troubleshooting

[Update SSL certificates](#update-ssl-certificates)

[Login does not work](#login-does-not-work)

[Failed to decrypt symmetric key: Key is missing data to perform the decryption](#failed-to-decrypt-symmetric-key-key-is-missing-data-to-perform-the-decryption)


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