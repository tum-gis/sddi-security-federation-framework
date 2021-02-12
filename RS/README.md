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
yum -y install epel-release mod_perl mod_perl-devel perl-libapreq2 perl-libwww-perl perl-JSON perl-LWP-Protocol-https perl-Crypt-SSLeay
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

### Install and initiate PostgreSQL with PostGIS

#### Install PostgreSQL 9.5 and PostGIS

The following steps are summarized from 
[this tutorial](https://www.postgresonline.com/journal/archives/362-An-almost-idiots-guide-to-install-PostgreSQL-9.5,-PostGIS-2.2-and-pgRouting-2.1.0-with-Yum.html).

Check system version:
```bash
uname -a
cat /etc/redhat-release
```

Install ``rpm`` for CentOS ``x86_64``:
```bash
rpm -ivh https://download.postgresql.org/pub/repos/yum/reporpms/EL-7-x86_64/pgdg-redhat-repo-latest.noarch.rpm
```

List all packages from ``pgdg95``:
```bash
sudo yum list | grep pgdg95
```

Install PostgreSQL 9.5:
```bash
yum install postgresql95 postgresql95-server postgresql95-libs postgresql95-contrib postgresql95-devel
```

Change password of the user ``postgres``:
```bash
psql -U postgres
ALTER USER postgres with password '<PASSWORD>';
```

Run the service on boot:
```bash
su root
service postgresql-9.5 initdb
```

If the following error appears:

``The service command supports only basic LSB actions (start, stop, restart, try-restart, reload, force-reload, status). 
For other actions, please try to use systemctl.``

Execute:
```bash
/usr/pgsql-9.5/bin/postgresql95-setup initdb
```

Then:
```bash
service postgresql-9.5 start
# List of services
chkconfig --list
# Or in CentOS 7+:
systemctl list-unit-files
# Start postgresql on boot
chkconfig postgresql-9.5 on
```

Install ``adminpack``:
```bash
su postgres
cd ~/
/usr/pgsql-9.5/bin/psql -p 5432 -c "CREATE EXTENSION adminpack;"
```

Install PostGIS binaries:
```bash
yum -y install epel-release
yum install postgis2_95 postgis2_95-client
```

Install ``ogrfdw``:
```bash
yum install ogr_fdw95
```

#### Initiate database

In this scenario, an old existing database shall be copied to a new one.

Locate ``pg_hba.conf``:
```bash
locate pg_hba.conf
# Ubuntu: /etc/postgresql/9.5/main/pg_hba.conf
# CentOS: /var/lib/pgsql/9.5/data/pg_hba.conf
```

Edit the file ``pg_hba.conf`` (do this for both servers that host the old and new database):
Change the line
```
# Ubuntu
local all postgres peer
# CentOS
local all all peer
```
to
```
# Ubuntu
local all postgres md5
# CentOS
local all all md5
```

Restart PostgreSQL:
```
# Ubuntu
service postgresql restart
# CentOS
systemctl restart postgresql-9.5
```

Export the entire old database from the ***old server*** to a file:
```sql
pg_dump -U postgres -d <DATABASE> -f <FILENAME>.sql
```

Copy the created file to the ***new server***:
```bash
# Execute this line in the new server
scp <SSH_USER>@<IP_ADDRESS_OF_OLD_SERVER>:<PATH>/<FILENAME>.sql
```

Create a new database on the ***new server***:
```sql
CREATE DATABASE <NEW_DB>;
\connect <NEW_DB>;
CREATE EXTENSION postgis;
CREATE EXTENSION postgis_topology;
```

Check version:
```sql
SELECT postgis_full_version();
```

Allow database to connect to the web in SELinux:
```bash
setsebool -P httpd_can_network_connect_db=1
```

Restore the exported database on the ***new server***:
```bash
psql -U postgres -d <NEW_DB> -f <FILENAME>.sql
```


### Add WFS plugin to Tomcat

Copy the ``.war`` file, such as:
```bash
cp citydb-wfs-qeop.war /opt/tomcat/latest/webapps
chown tomcat:tomcat citydb-wfs-qeop.war
```

Then restart Tomcat:
```bash
systemctl restart tomcat
```

Configure the file ``/opt/tomcat/latest/webapps/citydb-wfs-qeop/config.xml``:
```bash
chown tomcat:tomcat /opt/tomcat/latest/webapps/citydb-wfs-qeop/config.xml
```

```xml
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<wfs xmlns="http://www.3dcitydb.org/importer-exporter/config"
  xmlns:ows="http://www.opengis.net/ows/1.1" xmlns:xlink="http://www.w3.org/1999/xlink"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.3dcitydb.org/importer-exporter/config schemas/config/config.xsd">
  <capabilities>
    <owsMetadata>
      <ows:ServiceIdentification>
        <ows:Title>virtualcityDATABASE Web Feature Service</ows:Title>
        <ows:ServiceType>WFS</ows:ServiceType>
        <ows:ServiceTypeVersion>2.0.2</ows:ServiceTypeVersion>
        <ows:ServiceTypeVersion>2.0.0</ows:ServiceTypeVersion>
      </ows:ServiceIdentification>
      <ows:ServiceProvider>
        <ows:ProviderName/>
        <ows:ServiceContact/>
      </ows:ServiceProvider>
    </owsMetadata>
  </capabilities>
  <featureTypes>
    <featureType>
      <name>Building</name>
      <ows:WGS84BoundingBox>
        <ows:LowerCorner>-180 -90</ows:LowerCorner>
        <ows:UpperCorner>180 90</ows:UpperCorner>
      </ows:WGS84BoundingBox>
    </featureType>
    <featureType>
      <name>Road</name>
      <ows:WGS84BoundingBox>
        <ows:LowerCorner>-180 -90</ows:LowerCorner>
        <ows:UpperCorner>180 90</ows:UpperCorner>
      </ows:WGS84BoundingBox>
    </featureType>
    <version isDefault="true">2.0</version>
    <version>1.0</version>
  </featureTypes>
  <operations>
    <requestEncoding>
      <method>KVP+XML</method>
      <useXMLValidation>true</useXMLValidation>
    </requestEncoding>
    <constraints>
      <supportAdHocQueries>true</supportAdHocQueries>
    </constraints>
    <GetPropertyValue isEnabled="true">
      <outputFormat>application/gml+xml; version=3.1</outputFormat>
      <outputFormat>GML3.1+GZIP</outputFormat>
      <useCityDBADE>false</useCityDBADE>
    </GetPropertyValue>
    <GetFeature>
      <outputFormat>application/gml+xml; version=3.1</outputFormat>
      <outputFormat>GML3.1+GZIP</outputFormat>
      <useCityDBADE>false</useCityDBADE>
    </GetFeature>
    <ManageStoredQueries isEnabled="true"/>
    <Transaction isEnabled="true">
      <Insert>
        <inputFormat>application/gml+xml; version=3.1</inputFormat>
        <importAppearances>true</importAppearances>
      </Insert>
      <Update>
        <inputFormat>application/gml+xml; version=3.1</inputFormat>
      </Update>
      <nativeActions>
        <InsertComplexProperty isEnabled="true"/>
      </nativeActions>
    </Transaction>
  </operations>
  <filterCapabilities>
    <scalarCapabilities>
      <logicalOperators>true</logicalOperators>
      <comparisonOperators>
        <operator>PropertyIsEqualTo</operator>
        <operator>PropertyIsNotEqualTo</operator>
        <operator>PropertyIsLessThan</operator>
        <operator>PropertyIsGreaterThan</operator>
        <operator>PropertyIsLessThanOrEqualTo</operator>
        <operator>PropertyIsGreaterThanOrEqualTo</operator>
        <operator>PropertyIsLike</operator>
        <operator>PropertyIsNull</operator>
        <operator>PropertyIsNil</operator>
        <operator>PropertyIsBetween</operator>
      </comparisonOperators>
    </scalarCapabilities>
    <spatialCapabilities>
      <operator>BBOX</operator>
      <operator>Equals</operator>
      <operator>Disjoint</operator>
      <operator>Touches</operator>
      <operator>Within</operator>
      <operator>Overlaps</operator>
      <operator>Intersects</operator>
      <operator>Contains</operator>
      <operator>DWithin</operator>
      <operator>Beyond</operator>
    </spatialCapabilities>
  </filterCapabilities>
  <appearance doExport="false">
    <textureCache isActive="false"/>
  </appearance>
  <database>
    <referenceSystems>
      <referenceSystem id="WGS84">
        <srid>4326</srid>
        <gmlSrsName>http://www.opengis.net/def/crs/epsg/0/4326</gmlSrsName>
        <description>WGS 84</description>
      </referenceSystem>
    </referenceSystems>
    <connection 
      initialSize="10" 
      maxActive="100" 
      maxIdle="50" 
      minIdle="0" 
      suspectTimeout="60"
      timeBetweenEvictionRunsMillis="30000" 
      minEvictableIdleTimeMillis="60000">
      <description/>
      <type>PostGIS</type>
      <server>localhost</server>
      <port>5432</port>
      <sid><DATABASE></sid>
      <user><USER></user>
      <password><PASSWORD></password>
    </connection>
  </database>
  <server>
    <externalServiceURL>https://ssdwfs.gis.bgu.tum.de/citydb-wfs-qeop</externalServiceURL>
    <maxParallelRequests>30</maxParallelRequests>
    <waitTimeout>60</waitTimeout>
    <enableCORS>false</enableCORS>
  </server>
  <uidCache>
    <mode>database</mode>
  </uidCache>
  <logging>
    <file logLevel="info"/>
  </logging>
</wfs>
```

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
</VirtualHost>
```

Configurations for HTTPS 
(please refer to the
[web service registration](../Troubleshooting.md#createupdate-client-id-client-secret-and-redirect-url)
to get the ``<CLIENT_ID>`` and ``<CLIENT_SECRET>``):
```xml
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

    # If the WFS is hosted on the same server, otherwise change localhost to the IP address of the WFS host
    ProxyPass /citydb-wfs-qeop/wfs http://localhost/citydb-wfs-qeop/wfs retry=5
    <Proxy http://localhost>
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

    # If the WFS is hosted on the same server, otherwise change localhost to the IP address of the WFS host
    ProxyPass /citydb-wfs-qeop/wfsx http://localhost/citydb-wfs-qeop/wfs retry=5
    
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
