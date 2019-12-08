# Discovery Service (WAYF)
To setup the SAML2 Discovery Service (or WAYF) is basically split into three steps:

* Download the SWITCHwayf
* Apply the basic (vanilla) configuration as described in the `README.md` file provided with the SWITCHwayf.
* Apply the SDDI specific configuration

## Download from SWITCH gitlab
The SWITCHwayf package is Open Source and can be downloaded fron the SWITCH Gitlab:
[SWITCHwayf-v1.21](https://gitlab.switch.ch/aai/SWITCHwayf/-/archive/v1.21/SWITCHwayf-v1.21.tar.gz)

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
Note: Make sure you have installed `curl`.

Before the WAYF can be used for the first time **and** each time the list of IdPs change, the WAYF's list of IdPs must be synchronized. This is a manual process and can done via the following steps:

````
cd /opt/discovery-service
./compose_metadata.sh > metadata.xml
php createIDProviderConfig.php metadata.xml > html/IDProvider.conf.php
cd html
php readMetadata.php
````

## Test
Once the configuration is complete, you can open the WAYF in a Web Browser and check if all expected IdP organizations are listed. The URL to use is `https://<your domain name for the DS>/WAYF`. 
