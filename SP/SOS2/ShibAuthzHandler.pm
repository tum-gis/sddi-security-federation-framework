# Secure Dimensions licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# Copyright 2019 Secure Dimensions GmbH

package SD::ShibAuthzHandler;
  
  use strict;
  use warnings;
  
  use Apache2::Access ();
  use Apache2::RequestRec ();
  use Apache2::RequestUtil ();
  
  use APR::Table ();

  use Apache2::Log ();
  use Apache2::Const -compile => qw(OK HTTP_UNAUTHORIZED);
  
  sub handler {
      my $r = shift;
  
      my $log = $r->log;

      my $idp_identifier = $r->headers_in->{"Shib-Identity-Provider"};
      if (!$idp_identifier)
      {
        $log->error("IdP Identifier not set!");
        return Apache2::Const::HTTP_UNAUTHORIZED;
      }

      $log->debug("IdP Identifier: " . $idp_identifier);

      if ($idp_identifier eq 'https://google-idp.gis.bgu.tum.de/simplesaml/saml2/idp/metadata.php')
      {
        return Apache2::Const::HTTP_UNAUTHORIZED;
      }
      else
      {
        return Apache2::Const::OK;
      }
  }
  
  1;
