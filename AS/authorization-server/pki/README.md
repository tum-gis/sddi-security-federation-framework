# Authorization Server Keys
The Authorization Server requires a private RSA key to digitally sign the `id_token`. The public key is made publically available via the
`/.well-known/jwks.json` file.

Further, a public key is required to validate software statements used for dynamic client registration via the `/oauth/register` endpoint. How an operator obtains a digitally signed software statement is out of scope for this documentation.

## Generate RSA key-pair for use with `id_token`
First, generate a private/public key pair for signing `id_token` using OpenSSL.

````
openssl genrsa -out AS_Private_Key.key 2048
openssl rsa -in AS_Private_Key.key -pubout -out AS_Public_Key.pem
openssl rsa -in AS_Private_Key.key -out AS_Private_Key.pem
````

In case you like to use different filenames, please adjust the settings `public_key` and `private_key` in `.../config/config.php`.

You should protect the private key (`AS_Private_Key.pem`). One way to do so is changing the ownership to the user that executes the Web Server (e.g. `apache`) and change the mode to `400`. For example:

````
chown apache:apache AS_Private_Key.pem
chmod 400 AS_Private_Key.pem
````

## Create the JWK Representation
There are many different tools available to help creating the JWK representation. 

### Download and Configure Chilkat PHP library for JWK
For this documentation, we use the Chilkat PHP library to create the JWK representation of the public key as described [here](https://www.example-code.com/phpExt/publickey_rsa_get_jwk_format.asp).

For CENTOS x86_64 you can download the library for PHP 7.2:

````
cd /opt
wget https://chilkatdownload.com/9.5.0.84/chilkat-9.5.0.84.2-php-7.2-x86_64-linux.tar.gz
tar xzf chilkat-9.5.0.84.2-php-7.2-x86_64-linux.tar.gz
cd chilkat-9.5.0.84.2-php-7.2-x86_64-linux
cp chilkat_9_5_0.so /usr/lib64/php/modules
````

For using the library, you need to - temporarily - enable DL and configure the extension directory. Modify the following settings in `/etc/php.ini`:

````
enable_dl = On
````

### Create the JWK representation
Before you can use the library, the php extension `php-opcache` is required:

````
yum -y install php-opcache
````

Executing the provided example PHP program to print the required information to complete the JWK file:

````
cd .../pki
ln -s /opt/chilkat-9.5.0.84.2-php-7.2-x86_64-linux.tar.gz/chilkat_9_5_0.ph
php jwks.php > jwks.json
````

This creates the jwks.json file with this content (the '...' is complete with proper information):

```` 
{"keys": [
{
  "alg": "RS256",
  "kty": "RSA",
  "use": "sig",
  "n": "...",
  "e": "...",
  "kid": "ASPublicKey",
  "x5t": "ASPublicKey"
}
]}
```` 

In case you like to use a different filename please adjust the setting in `.../config/config.php`.

Finally, make sure you disbale DL in `/etc/php.ini`

````
enable_dl = Off
````
