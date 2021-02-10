# Resource Server
The protection of an API hosted on an Apache Web Server can be enforced by enabling the API endpoint to act as an OAuth2 Resource Server. An OAuth2 Resource Server as defined in [RFC6750](https://tools.ietf.org/html/rfc6750) can be enabled by deploying the `OAuthnBearerHandler` as an Apache Web Server [PerlAuthnHandler](https://perl.apache.org/docs/2.0/user/handlers/http.html) for the endpoint path.

The `OAuthnBearerHandler` handler introspects the intercepted Apache HTTP request and checks if an OAuth2 Bearer access token exists either in the HTTP Header, query string or body. In case an access token is found, the handler uses the [Token Introspection](https://tools.ietf.org/html/rfc7662) endpoint from the configured OAuth2 Authorization Server to test for the validity of the token.

This version of the handler does permit the intercepted request if the introspected access token is valid (active). Any other error response received from the Introspection endpoint is forwarded to the client.

## Installation
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

The original certificates and private keys should be stored centrally in the directory
``/etc/ssl/certs/``.
Other directories should only have symbolic links to these files.

**IMPORTANT**: The passphrase must be removed from the private key!
```bash
openssl rsa -in private_key.pem -out private_key_no_passphrase.pem
```

1.  Configure ``apache``:
    ```bash
    vi /etc/httpd/conf.d/ssl.conf
    ```
    And set the following line:
    ```bash
    ...
    
    SSLCertificateKeyFile /etc/pki/tls/certs/private_key_no_passphrase.pem
    
    ...
    ```

1.  Change the owner and group of the certificates and private keys to ``apache``:
    ```bash
    cd /etc/ssl/certs/
    chown apache:apache ./private_key_no_passphrase.pem
    chown apache:apache ./private_key.pem
    chown apache:apache ./certificate.pem
    chown apache:apache ./chain.pem
    ```

    Then change the permission to read-only for the owner of the private key
    and write-only for the owner of the certificate file:
    ```bash
    chmod 400 ./private_key_no_passphrase.pem
    chmod 400 ./private_key.pem
    chmod 644 ./certificate.pem
    chmod 644 ./chain.pem
    ```

---------------------

##### Note:

If ``httpd`` is unable to start, run the following command if you copied the certificate and key file from `/home/user`
to either `/etc/ssl/certs/` or `/etc/pki/tls/certs/` (they are both a symbolic link):

```bash
restorecon -RvF /etc/ssl/certs/
```

---------------------

The deployment of the `OAuthnBearerHandler` hanlder requires to install `mod_perl` and dependency packages: 

```bash
yum -y install mod_perl perl-libapreq2 perl-libwww-perl perl-JSON perl-LWP-Protocol-https perl-Crypt-SSLeay
```


Once that installation completed, deploy the `OAuthnBearerHandler` handler. The handler use the namespace `SD` and must therefore be put into a directory named `SD`. The `SD` directory must be created somewhere in the INC-path used by the perl installation. For CENTOS 7, the following command creates the directory for the handler:

````
mkdir -p /usr/local/lib64/perl5/SD
````

The final stept is to copy (scp) the `OAuthnBearerHandler.pm` file into the created directory.

----------------
#### Note
This can be achieved by cloning the Github Repository and copying the `OAuthnBearerHandler.pm`.

````
yum -y install git
git clone https://github.com/tum-gis/sddi-security-federation-framework
cp sddi-security-federation-framework/RS/ /usr/local/lib64/perl5/SD/
````

----------------

### Install Tomcat

The following steps are summarized from 
[this page](https://linuxize.com/post/how-to-install-tomcat-9-on-centos-7/).

Install Java:
```bash
yum install java-1.8.0-openjdk-devel
```

Create user ``tomcat``:
```bash
useradd -m -U -d /opt/tomcat -s /bin/false tomcat
```

Download Tomcat 9 (not tested with Tomcat 10!):
```bash
cd /tmp
wget https://downloads.apache.org/tomcat/tomcat-9/v9.0.43/bin/apache-tomcat-9.0.43.tar.gz
```

Extract:
```bash
tar -xf apache-tomcat-9.0.43.tar.gz
mv apache-tomcat-9.0.43/opt/tomcat/
```

Manage multiple versions by creating a symbolic link to the latest one:
```bash
ln -s /opt/tomcat/apache-tomcat-9.0.43 /opt/tomcat/latest
```

Change ownership:
```bash
chown -R tomcat: /opt/tomcat
```

Make the scripts executable:
```bash
sh -c 'chmod +x /opt/tomcat/latest/bin/*.sh'
```

Create a ``systemd`` file ``/etc/systemd/system/tomcat.service``:
```
[Unit]
Description=Tomcat 9 servlet container
After=network.target

[Service]
Type=forking

User=tomcat
Group=tomcat

Environment="JAVA_HOME=/usr/lib/jvm/jre"
Environment="JAVA_OPTS=-Djava.security.egd=file:///dev/urandom"

Environment="CATALINA_BASE=/opt/tomcat/latest"
Environment="CATALINA_HOME=/opt/tomcat/latest"
Environment="CATALINA_PID=/opt/tomcat/latest/temp/tomcat.pid"
Environment="CATALINA_OPTS=-Xms512M -Xmx1024M -server -XX:+UseParallelGC"

ExecStart=/opt/tomcat/latest/bin/startup.sh
ExecStop=/opt/tomcat/latest/bin/shutdown.sh

[Install]
WantedBy=multi-user.target
```

Reload:
```bash
systemctl daemon-reload
systemctl enable tomcat
systemctl start tomcat
systemctl status tomcat
```

Add user for login in ``/opt/tomcat/latest/conf/tomcat-users.xml```:
```xml
<tomcat-users>
<!--
    Comments
-->
   <role rolename="admin-gui"/>
   <role rolename="manager-gui"/>
   <user username="admin" password="<PASSWORD>" roles="admin-gui,manager-gui"/>
</tomcat-users>
```

Restart Tomcat:
```bash
systemctl restart tomcat
```

### Reverse proxy for Tomcat in Apache

The following steps are summarized from 
[this page](https://yallalabs.com/linux/how-to-configure-apache-as-a-reverse-proxy-for-apache-tomcat-server/).

Edit the file ``/etc/httpd/conf/http.conf``:
```
ProxyRequests Off
ProxyPass / http://localhost:8080
ProxyPassReverse / http://localhost:8080/
```

Check config:
```bash
httpd -t
apachectl configtest
```

Allow Apache to connect to the web in SELinux:
```bash
setsebool -P httpd_can_network_connect=1
```

Restart Apache:
```bash
systemctl restart httpd
```

The following link shall be redirected to SSL Tomcat automatically:

https://ssdwfs.gis.bgu.tum.de/

## Apache Web Server Configuration

Configurations for HTTP:
```
<VirtualHost *:80>
    ServerName ssdwfs.gis.bgu.tum.de

    ServerSignature Off

    RewriteEngine On
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [END,QSA,R=permanent]

    ErrorLog /var/log/httpd/redirect.error.log
    LogLevel warn
    
    // NEW
    ProxyPass / ajp://localhost:8009/
    ProxyPassReverse / ajp://localhost:8009/
    <Proxy ajp://localhost:8009>
            ProxyPreserveHost On
            Require all granted
    </Proxy>
</VirtualHost>
```

Configurations for HTTPS:
```
<VirtualHost *:443>
    ServerName ssdwfs.gis.bgu.tum.de
    DocumentRoot /var/www/html
    LogLevel warn rewrite:warn ssl:warn
    ErrorLog /var/log/httpd/wfs.sddi.error.log

    SSLEngine on
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1.1
    SSLCipherSuite ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:DHE-DSS-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA256:ADH-AES256-GCM-SHA384:ADH-AES256-SHA256:ECDH-RSA-AES256-GCM-SHA384:ECDH-ECDSA-AES256-GCM-SHA384:ECDH-RSA-AES256-SHA384:ECDH-ECDSA-AES256-SHA384:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:DHE-DSS-AES128-GCM-SHA256:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES128-SHA256:DHE-DSS-AES128-SHA256:ADH-AES128-GCM-SHA256:ADH-AES128-SHA256:ECDH-RSA-AES128-GCM-SHA256:ECDH-ECDSA-AES128-GCM-SHA256:ECDH-RSA-AES128-SHA256:ECDH-ECDSA-AES128-SHA256
    SSLHonorCipherOrder on
    SSLCertificateFile /etc/pki/tls/certs/<KEY>
    SSLCertificateKeyFile /etc/pki/tls/private/<CERT>
    SSLCertificateChainFile /etc/pki/tls/certs/<CHAIN>

    // NEW
    JKMount /* ajp13_worker
    
# 129.187.38.211 is the public IP address of ssdwfs.gis.bgu.tum.de
ProxyPass /citydb-wfs-qeop/wfs http://129.187.38.211/citydb-wfs-qeop/wfs retry=5
<Proxy http://129.187.38.211>
    <Limit OPTIONS>
        SetEnvIf Origin (.+) ORIGIN=$1
        Header always set Access-Control-Allow-Origin "%{ORIGIN}e" env=ORIGIN
        Header always set Access-Control-Allow-Credentials true

        SetEnvIf Access-Control-Request-Method (.+) METHOD=$1

        Header always set Access-Control-Allow-Headers "authorization"
        Header always set Access-Control-Allow-Methods "%{METHOD}e" env=METHOD
        #Header always set Access-Control-Max-Age "600"
        RewriteEngine On
        RewriteCond %{REQUEST_METHOD} OPTIONS
        RewriteRule ^(.*)$ $1 [R=200,END]
        Require all granted
    </Limit>

    AuthType Bearer
    AuthName "SSDI Security Demo"
    Require valid-user
    PerlAuthenHandler SD::OAuthnBearerHandler
    PerlOptions +ParseHeaders +SetupEnv +GlobalRequest
    PerlSetVar ClientId <CLIENT_ID>
    PerlSetVar ClientSecret <CLIENT_SECRET>
    PerlSetVar ValidateURL https://ssdas.gis.bgu.tum.de/oauth/tokeninfo
</Proxy>

ProxyPass /citydb-wfs-qeop/wfsx http://129.187.38.211/citydb-wfs-qeop/wfs retry=5

<Directory "/var/www/html">
    Require all granted
</Directory>

Alias /TermsOfUse /var/www/html/TermsOfUse.html
Alias /PrivacyStatement /var/www/html/PrivacyStatement.html
Alias /CookieStatement /var/www/html/CookieStatement.html
</VirtualHost>
```


The activation of the `OAuthnBearerHandler` and its configuration can take place in the Apache configuration file:
```
PerlAuthenHandler SD::OAuthnBearerHandler
```

**Note:** *It is important that the `OAuthnBearerHandler` handler uses a valid `client_id` and `client_secret` 
resulting from a former registration with the Authorization Server as a Service Application.*
