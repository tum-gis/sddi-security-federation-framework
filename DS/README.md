# Discovery Service (WAYF)
To setup the SAML2 Discovery Service (or WAYF) is basically split into three steps:

* Download the SWITCHwayf
* Apply the basic (vanilla) configuration as described in the `README.md` file provided with the SWITCHwayf.
* Apply the SDDI specific configuration

## Download from SWITCH gitlab
The SWITCHwayf package is Open Source and can be downloaded from the SWITCH Gitlab:
[SWITCHwayf-v1.21](https://gitlab.switch.ch/aai/SWITCHwayf/-/archive/v1.21/SWITCHwayf-v1.21.tar.gz)

----------------------
#### Note
The SWITCHwayf package can also be downloaded by cloning this Github repository:
````
yum -y install git
git clone https://github.com/tum-gis/sddi-security-federation-framework
cp -r sddi-security-federation-framework/DS/ discovery-service/
````

----------------------

## Installation and Basic Configuration
For this installation we will use the following directory structure:

* `.` directory will contain the scripts required to configure the IdP pull-down list
* `html` directory will contain the runtime of the WAYF

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

### Copy the SDDI specific files
Copying the following files into the directory `/opt/discovery-service/html`:

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


## Preparing the WAYF for the first use
*Note: Make sure you have installed `curl`.*

In order to compile the list of IdPs, you need to download the following SAML2 metadata files:

* Google IdP: You can download the SAML2 metadata URL via the URL, specific for your installation. The URL is displayed in the SimpleSAMLphp GUI: <host>/simplesaml/module.php/core/frontpage_federation.php
* eduGain: You can download this file from the DFN repository: `curl http://www.aai.dfn.de/fileadmin/metadata/dfn-aai-edugain+idp-metadata.xml`

Before the WAYF can be used for the first time **and** each time the list of IdPs changes, the WAYF's list of IdPs must be synchronized. This is a manual process and can done via the following steps:

````
cd /opt/discovery-service
./compose_metadata.sh > metadata.xml
php createIDProviderConfig.php metadata.xml > html/IDProvider.conf.php
cd html
php readMetadata.php
````
*Note: Please make sure that you have adopted the `compose_metadata.sh` file to relect the file names for the SAML2 metadata (lines 14 and 15).*

## Test
Once the configuration is complete, you can open the WAYF in a Web Browser and check if all expected IdP organizations are listed. The URL to use is `https://<your domain name for the DS>/WAYF`. 
