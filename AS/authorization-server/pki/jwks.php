<?php

// Modified by Secure Dimensions to meet specfici purpose


// The version number (9_5_0) should match version of the Chilkat extension used, omitting the micro-version number.
// For example, if using Chilkat v9.5.0.48, then include as shown here:
include("chilkat_9_5_0.php");

//  Note: This example requires Chilkat v9.5.0.66 or later.

//  Load a PEM file into memory.
$sbPem = new CkStringBuilder();
$success = $sbPem->LoadFile('AS_Public_Key.pem','utf-8');
if ($success != true) {
    print 'Failed to load PEM file.' . "\n";
    exit;
}

//  Load the PEM into a public key object.
$pubKey = new CkPublicKey();
$success = $pubKey->LoadFromString($sbPem->getAsString());
if ($success != true) {
    print $pubKey->lastErrorText() . "\n";
    exit;
}

//  Get the public key in JWK format:
$jwk = $pubKey->getJwk();

//  The GetJwk method will return the JWK in the most compact JSON format possible,
//  as a single line with no extra whitespace.  To get a more human-readable JWK (for this example),
//  load into a Chilkat JSON object and emit non-compact:

$json = new CkJsonObject();
$json->Load($jwk);
$json->put_EmitCompact(false);
$json->AppendString('use','sig');
$json->AppendString('kid','ASPublicKey');
$json->AppendString('x5t','ASPublicKey');

//  Now examine the JSON:
print '{"keys": [' . PHP_EOL . $json->emit() . ']}' . PHP_EOL;

?>
