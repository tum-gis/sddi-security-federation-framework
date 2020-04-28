#!/bin/bash

##
#Copyright © 2019 Secure Dimensions GmbH
#
#Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
#
#The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
#
#THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
##

echo `date +%s` + 604000|bc| awk '{printf("<?xml version=\"1.0\" encoding=\"UTF-8\"?><md:EntitiesDescriptor xmlns:md=\"urn:oasis:names:tc:SAML:2.0:metadata\" xmlns:ds=\"http://www.w3.org/2000/09/xmldsig#\" xmlns:idpdisc=\"urn:oasis:names:tc:SAML:profiles:SSO:idp-discovery-protocol\" xmlns:init=\"urn:oasis:names:tc:SAML:profiles:SSO:request-init\" xmlns:mdattr=\"urn:oasis:names:tc:SAML:metadata:attribute\" xmlns:mdrpi=\"urn:oasis:names:tc:SAML:metadata:rpi\" xmlns:mdui=\"urn:oasis:names:tc:SAML:metadata:ui\" xmlns:remd=\"http://refeds.org/metadata\" xmlns:saml=\"urn:oasis:names:tc:SAML:2.0:assertion\" xmlns:saml1md=\"urn:mace:shibboleth:metadata:1.0\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" ID=\"SDDI_1507530579\" validUntil=\"%s\">",strftime("%FT%TZ",$1))}'
cat <file with AS Metadata>
cat <file with Google IdP Metadata>
sed -n '/EntityDescriptor/,$p' dfn-aai-edugain+idp-metadata.xml|grep -v EntitiesDescriptor
echo "</md:EntitiesDescriptor>"
