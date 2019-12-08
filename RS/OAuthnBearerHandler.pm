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
# Copyright 2017-2019 Secure Dimensions GmbH

package SD::OAuthnBearerHandler;

use strict;
use warnings;

use Apache2::Request();
use Apache2::RequestRec();
use Apache2::RequestUtil();
use Apache2::Access ();
use Apache2::Log();
use APR::Table ();
use Apache2::Const -compile => qw(OK AUTH_REQUIRED HTTP_INTERNAL_SERVER_ERROR DECLINED);
use HTTP::Request::Common;
use LWP::UserAgent;
use JSON qw( decode_json );
use MIME::Base64 qw(decode_base64 encode_base64);
use URI::Escape qw(uri_escape uri_unescape);
use LWP::Protocol::https;
use IO::Socket::SSL qw( SSL_VERIFY_NONE );


sub handler {

    my $r = Apache2::Request->new(shift);
    my $log = $r->log;
    no strict 'refs';
    
    #client_id and secret for appliction 
    my $client_id        = $r->dir_config('ClientId');
    my $client_secret    = $r->dir_config('ClientSecret');
    my $validate_url     = $r->dir_config('ValidateURL');
    my $realm            = $r->auth_name();
    $log->debug("ClientId ", $client_id);
    $log->debug("ClientSecret ", $client_secret);
    $log->debug("ValidateURL ", $validate_url);
        
    # Keep Apache 2.4 mod_authz_core happy
    $r->user("");

    # Let's check if we got a an access_token as HTTP header...
    my $access_token = $r->headers_in->{'Authorization'};
    if (defined $access_token)
    {
        $access_token =~ s/Bearer //;
        $log->debug("access_token from HTTP Authorization header: ", $access_token);    
    }
        
    # Let's check if we got a an access_token as query parameter...
    if (not defined $access_token)
    {
        $access_token = $r->param('access_token');
        if (defined $access_token)
        {
            $log->debug("access_token from query string: ", $access_token);
        }
    }
        
    if (not defined $access_token)
    {
        $log->debug("No access token");
        $r->err_headers_out->set("WWW-Authenticate" => 'Bearer realm="'.$realm.'", error_description="Access Token missing"');
        return Apache2::Const::AUTH_REQUIRED;
    }
    
    # First: Check access token validity (make sure the user has not revoked it)
    my $req = HTTP::Request->new( POST => $validate_url);
    $req->authorization_basic($client_id, $client_secret);
    #$req->header( Authorization => q{Bearer } . $access_token );
    my $post_data = 'token=' . $access_token;
    $req->content_type('application/x-www-form-urlencoded');
    $req->content($post_data);
    
    my $agent = LWP::UserAgent->new(
        ssl_opts => {
                SSL_version => 'TLSv12:!SSLv2:!SSLv3:!TLSv1:!TLSv11',
                verify_hostname => 0,
                SSL_verify_mode => SSL_VERIFY_NONE
        });
    $agent->timeout(10);
    $log->debug("sending token validation request: ". $req->content);
    my $res = $agent->request($req);

    $log->debug("Response code: ", $res->code);
    $log->debug("Response status: ", $res->status_line);

    if ($res->is_success)
    {
        my $message = $res->decoded_content();
        $log->debug("Response message: ", $message);
        my $json_decoded = decode_json($res->content());

        if($json_decoded->{'active'}) {
            $log->info("access token is valid");
        }
        else {
                        $log->error("access token is NOT valid: ".$access_token);
                        $r->err_headers_out->set("WWW-Authenticate" => 'Bearer realm="'.$realm.'", error="invalid_token", error_description="Access Token invalid"');
                        $r->status_line("401 Access Token invalid");
                        return Apache2::Const::AUTH_REQUIRED;
            }
    }   
    elsif ($res->code() eq 404)
    {
        $log->error("AS validate URL does not exist: " . $validate_url);
        return Apache2::Const::HTTP_INTERNAL_SERVER_ERROR;
    }
    else {
	my $message = $res->decoded_content();
        $log->debug("Response message: ", $message);
        my $json_decoded = decode_json($res->content());

        $log->error("error code: ".$res->code);
        $log->error("error: ".$json_decoded->{'error'});
        $log->error("error_description: ".$json_decoded->{'error_description'});
        $r->err_headers_out->set("WWW-Authenticate" => 'Bearer realm="'.$realm.'", error="'.$json_decoded->{'error'}.'", error_description="'.$json_decoded->{'error_description'}.'"');
        $r->status_line($res->code() . ' ' . $res->status_line);

        return $res->code();
    } 
    
    return Apache2::Const::OK;

}
1;
