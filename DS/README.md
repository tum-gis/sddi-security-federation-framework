# Discovery Service (WAYF)
To setup the SAML2 Discovery Service (or WAYF) is basically split into three steps:

* Download the SWITCHwayf
* Apply the basic (vanilla) configuration as described in the `README.md` file provided with the SWITCHwayf.
* Apply the SDDI specific configuration

## Base installations

Install Apache Web Server, Firewall, PHP, etc.
Please refer to the beginning of [AS](../AS/authorization-server/README.md).
Then install/update the certificates.

## Download from SWITCH gitlab
The SWITCHwayf package is Open Source and can be downloaded from the SWITCH Gitlab:
[SWITCHwayf-v1.21](https://gitlab.switch.ch/aai/SWITCHwayf/-/archive/v1.21/SWITCHwayf-v1.21.tar.gz)

----------------------
#### Note
The SWITCHwayf package can also be downloaded by cloning this Github repository:
````
yum -y install git
cd /opt
git clone https://github.com/tum-gis/sddi-security-federation-framework
cp -r sddi-security-federation-framework/DS/ discovery-service/
````

----------------------

## Installation and Basic Configuration
For this installation we will use the following directory structure:

* `...` the working directory ``/opt/discovery-service``
* `.` directory will contain the scripts required to configure the IdP pull-down list
* `html` directory will contain the runtime of the WAYF

Install ``curl``:
````bash
yum install curl
````

Extract `SWITCHwayf-v1.21.tar.gz` into `/opt/discovery-service/html` directory as it contains the runtime files.

````
cd /opt/discovery-service
tar xzf SWITCHwayf-v1.21.tar.gz
mv SWITCHwayf-v1.21 html
cd html
````

The instructions from the `README.md` can be used to obtain additional information on the configuration options. In particular, follow the instructions how to modify the `config.php` file.

## Configure the WAYF for SDDI
The adoption of the basic configuration of the SWITCHwayf can be achieved by using the local copy of the `config.php` file as a reference. You need to adopt the URL based on your deployment.

Please make sure that the Cookie names used in the `config.php` file match the names used in the `CookieStatement.html`.

### Keep the SDDI specific files
Move all files and directories from ``/opt/discovery-service/html/SWITCHwayf-v1.21`` to ``/opt/discovery-service/html``.

Make sure the following files in the directory `/opt/discovery-service/html` are NOT overridden:

* CookieStatement.html
* PrivacyStatement.html
* TermsOfUse.html
* custom-footer.php
* custom-header.php
* custom-body.php
* custom-notice.php
* custom-settings.php
* images/small-federation-logo.png
* images/federation-logo.png
* images/organization-logo.png
* images/SD_16_16.png
* images/SD_180_120.png
* (config.php)

This means do NOT copy the following files (these are default images):
````bash
/opt/discovery-service/html/SWITCHwayf-v1.21/images/small-federation-logo.png
/opt/discovery-service/html/SWITCHwayf-v1.21/images/federation-logo.png
/opt/discovery-service/html/SWITCHwayf-v1.21/images/organization-logo.png
````
since they would otherwise replace the customized logos etc. in ``/opt/discovery-service/html`` with the default ones.

Make sure the binary file ``WAYF`` is now in the directory ``/opt/discovery-service/html``.

## Preparing the WAYF for the first use
*Note: Make sure you have installed `curl`.*

In order to compile the list of IdPs, you need to download the following SAML2 metadata files:

* Google IdP: You can download the SAML2 metadata URL via the URL specific for your installation. 
  The URL is displayed in the SimpleSAMLphp GUI: 
  ````bash
  cd /opt/discovery-service
  curl -k https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php -o google-idp-metadata.xml 
  ````
* eduGain: You can download this file from the DFN repository: 
  ````bash
  cd /opt/discovery-service
  curl https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-edugain+idp-metadata.xml -o dfn-aai-edugain+idp-metadata.xml 
  curl https://www.aai.dfn.de/fileadmin/metadata/dfn-aai-idp-metadata.xml -o dfn-aai-idp-metadata.xml 
  ````
  If the name of these metadata files were changed, 
  reflect them in the file ``/opt/discovery-service/compose_metadata.sh``.
  
* Edit the file ``/opt/discovery-service/createIDProviderConfig.php``:
  Replace ``<entityID of the Google IdP>`` in line 166 with the metadata URL of the Google IdP,
  such as:
  
  https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php
  

Before the WAYF can be used for the first time **and** each time the list of IdPs changes, 
the WAYF's list of IdPs must be synchronized. 
This is a manual process and can be done via the following steps:

* Run the following commands:
  ```bash
  cd /opt/discovery-service
  ./compose_metadata.sh > metadata.xml
  ```

* Edit ``/opt/discover-service/metadata.xml``:
  * Make sure there is only one ``<?xml...>`` in the entire document
  
* Run the following commands:
  ````bash
  cd /opt/discovery-service
  php createIDProviderConfig.php metadata.xml > html/IDProvider.conf.php
  cd html
  php readMetadata.php
  ````
  
* If more memory is needed, add the following lines to the beginning of the ``readMetadata.php`` file:
  ````php
  // Increase memory size for big metadata files
  ini_set('memory_limit', '200M');
  ````

[comment]: <> (* Modify `compose_metadata.sh` file to reflect the file names for the SAML2 metadata &#40;lines 14 and 15&#41;.)

[comment]: <> (* Execute the following commands:)

[comment]: <> (  ````)

[comment]: <> (  cd /opt/discovery-service)

[comment]: <> (  ./compose_metadata.sh > metadata.xml)

[comment]: <> (  php createIDProviderConfig.php metadata.xml > html/IDProvider.conf.php)

[comment]: <> (  cd html)

[comment]: <> (  php readMetadata.php)

[comment]: <> (  ````)

## Configure Apache server for WAYF

Configure the file ``/etc/httpd/conf.d/ssl.conf`` using the parameters given in
[this config file](etc/httpd/conf.d/ds.conf).

## Test
Once the configuration is complete, you can open the WAYF in a Web Browser and check if all expected IdP organizations are listed. The URL to use is `https://<your domain name for the DS>/WAYF`. 
