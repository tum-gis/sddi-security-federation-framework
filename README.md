# SDDI - Security Components as Open Source
This work was delivered as part of the project [Smart District Data Infrastructure (SDDI)](https://www.lrg.tum.de/en/gis/projects/smart-district-data-infrastructure/) funded by [Climate-KIC](https://www.climate-kic.org/) of the [European Institute of Innovation and Technology (EIT)](https://eit.europa.eu/).

The software is provided "AS IS" as expressed in the attached LICENSE.

* [AS](AS/authorization-server/README.md): This directory contains the Authorization Server as Open Source based on the MIT license and installation instructions to deploy the software.
* [DS](DS/README.md): This directory contains the documentations how to setup the IdP Discovery Service (WAYF) based on the WAYF developed by SWITCH.
* [RS](RS/README.md): This directory contains the software and installation instructions for operating the WFS endpoint using Bearer Token protection.
* [SP](SP/README.md): This directory contains the software ad installation instructions to setup the SOS1 and SOS2 using HTTP Cookie and Bearer Token protection.

The API description of the Authorization Server is avialable as OpenAPI located in the `/api/` path of the deployed Authorization Server.

The test cases for ensuring the correct functioning of the Authorization Server are available from the [test case documentation](AS/authorization-server/test/AS/TEST.md) file. That documentation also contains a description how to execute the tests with an Authorization Server deployment.


31.12.2019 - Secure Dimensions GmbH

To easily follow this Read-Me it is helpful to be familiar with the usage of Docker and the Linux Command Line. A good starting point for Docker is the [Quickstart](https://docs.docker.com/get-started/) in the Docker documentation. For the Linux Command Line there are the following tutorials: [Console Commands](https://ubuntu.com/tutorials/command-line-for-beginner), [Basic Vim Commands](https://linuxhandbook.com/basic-vim-commands/).
