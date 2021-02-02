# SDDI - Security Components as Open Source
This work was delivered as part of the project [Smart District Data Infrastructure (SDDI)](https://www.lrg.tum.de/en/gis/projects/smart-district-data-infrastructure/) funded by [Climate-KIC](https://www.climate-kic.org/) of the [European Institute of Innovation and Technology (EIT)](https://eit.europa.eu/).

The software is provided "AS IS" as expressed in the attached LICENSE.

* [AS](AS/authorization-server/README.md): This directory contains the Authorization Server as Open Source based on the MIT license and installation instructions to deploy the software.
* [DS](DS/README.md): This directory contains the documentations how to set up the IdP Discovery Service (WAYF) based on the WAYF developed by SWITCH.
* [RS](RS/README.md): This directory contains the software and installation instructions for operating the WFS endpoint using Bearer Token protection.
* [SP](SP/README.md): This directory contains the software and installation instructions to set up the SOS1 and SOS2 using HTTP Cookie and Bearer Token protection.
* [Google-IdP](Google-IdP/README.md): This directory contains the software installation instructions to set up the SimpleSAMLphp for Google IdP.
* [Troubleshooting](Troubleshooting.md): Guides for maintenance as well as solving common errors.

The API description of the Authorization Server is available as OpenAPI located in the `/api/` path of the deployed Authorization Server.

The test cases for ensuring the correct functioning of the Authorization Server are available from the [test case documentation](AS/authorization-server/test/AS/TEST.md) file. That documentation also contains a description how to execute the tests with an Authorization Server deployment.

In collaboration with: 
[**Secure Dimensions GmbH**](https://www.secure-dimensions.de/index.html.de).
    
Maintainer: 
[**Son H. Nguyen**](https://www.lrg.tum.de/en/gis/our-team/staff/son-h-nguyen/), 
[Chair of Geoinformatics](https://www.lrg.tum.de/en/gis/home/), 
[Department of Aerospace and Geodesy](https://www.lrg.tum.de/en/flr/home/), 
[Technical University of Munich](https://www.tum.de/en/).

