<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote 
 */

$metadata['https://google-idp.sddi.secure-dimensions.de/simplesaml/saml2/idp/metadata.php'] = array (
  'entityid' => 'https://google-idp.sddi.secure-dimensions.de/simplesaml/saml2/idp/metadata.php',
  'contacts' => 
  array (
    0 => 
    array (
      'contactType' => 'technical',
      'givenName' => 'Andreas',
      'surName' => 'Matheus',
      'emailAddress' => 
      array (
        0 => 'support@secure-dimensions.de',
      ),
    ),
  ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://google-idp.sddi.secure-dimensions.de/simplesaml/saml2/idp/SSOService.php',
    ),
  ),
  'SingleLogoutService' => 
  array (
    0 => 
    array (
      'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
      'Location' => 'https://google-idp.sddi.secure-dimensions.de/simplesaml/saml2/idp/SingleLogoutService.php',
    ),
  ),
  'ArtifactResolutionService' => 
  array (
  ),
  'NameIDFormats' => 
  array (
    0 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
  ),
  'keys' => 
  array (
    0 => 
    array (
      'encryption' => false,
      'signing' => true,
      'type' => 'X509Certificate',
      'X509Certificate' => 'MIIFLzCCA5egAwIBAgIJAIm8zfdCZeSwMA0GCSqGSIb3DQEBCwUAMIGtMQswCQYDVQQGEwJERTEQMA4GA1UECAwHQmF2YXJpYTEPMA0GA1UEBwwGTXVuaWNoMR8wHQYDVQQKDBZTZWN1cmUgRGltZW5zaW9ucyBHbWJIMS0wKwYDVQQDDCRnb29nbGUtaWRwLnNkZGkuc2VjdXJlLWRpbWVuc2lvbnMuZGUxKzApBgkqhkiG9w0BCQEWHHN1cHBvcnRAc2VjdXJlLWRpbWVuc2lvbnMuZGUwHhcNMTkwMzEzMTYwOTI0WhcNMjkwMzEyMTYwOTI0WjCBrTELMAkGA1UEBhMCREUxEDAOBgNVBAgMB0JhdmFyaWExDzANBgNVBAcMBk11bmljaDEfMB0GA1UECgwWU2VjdXJlIERpbWVuc2lvbnMgR21iSDEtMCsGA1UEAwwkZ29vZ2xlLWlkcC5zZGRpLnNlY3VyZS1kaW1lbnNpb25zLmRlMSswKQYJKoZIhvcNAQkBFhxzdXBwb3J0QHNlY3VyZS1kaW1lbnNpb25zLmRlMIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAruGk0saY8gElgjjJ4eIl4dj5u5h1jJlsDYzwkAArf7N9e173x2Dbeyi3/sFj4Fh/g4mb9k4Haea7muhML62jUrazfKbrAltaQ83Gx+j+is9o24XeHni4Xg+WX0/4w3o1vbqFwUE/BLxFZaupQjwUI5mSNvCJa+zyg/iVqLUg3pc8icUfjnRaKNXjw6dAhY2aHaVTk+HX9pchNhCuhcyjCOAVh93Y3mViODO4UtEYBWTlWQP7hr4rUnF0WAKpbst41lp4tv87cLERK0eQZo2t6H8I7g97yy45jIbUmvHDnqOqgM93bqqX8sqqJmwfg2YAMX5dKwg+lF9SAD1IFe2UOrBj1E1AdHFXykO8pr72YZ4ENiHTTQPvTMDA39LOnx3Y/ZY7eexKHzdb0L/MYn5RgzE3lztiaJGkMO5Pd9r32KIISvwAbfy6yVfuiSSAVJLcHvzQXaJYYaXVkZc8DKES11fN1yc5yIoeUVnMmhZ1Ic9Suwu8XQ/57MccBq6Ed2VlAgMBAAGjUDBOMB0GA1UdDgQWBBQN8a8fALJqnbcyqvkDK/JErNivbzAfBgNVHSMEGDAWgBQN8a8fALJqnbcyqvkDK/JErNivbzAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBgQAOP1ow1hZY6wbtUsoZKqY0hYi2dNnwgQFPDOjguKPGcyNDngXDCPu/yBcG3reILgaOz2G+KtD1MemG+gPSCTvZB/ijghvbmnMAqNhdcum/sZ9HoJDPSpD4nrfmZv1OY4g/brjJ5dAzYdJB1Y6LahOunZjYZKXzjQZHwdU/pibMDQDFqVZZH9smWP54S2jtJC+A25I1tHHjfie7q/CVgdPfEYhtHUzNEuDZmxABTSy79JQl5ywhhTttvaYexII1AC3vqlAauxoF3BVl1uHdDjnsfmhFEOV7PTAOIRbmnTHXVxdItjl5tdwZLEnnkPe9aCRYfurrmzLgbi2Ujy0sobxonX4FQB37HAtfWz4erK6xwcQEGE3wtXCubRIQUWWMYO/6vvf2sWfDeFWdZq8+Xi0Iak/Hb+SiEUfzsQ0gBfuhAOzHyxg+4bQovEwqjH+RfEVi3Plu9kadwHxYEAcuVVjWk33EJGllx4TBk0RcVoZddsI7H/ondjNNGpYfLoIdBaA=',
    ),
    1 => 
    array (
      'encryption' => true,
      'signing' => false,
      'type' => 'X509Certificate',
      'X509Certificate' => 'MIIFLzCCA5egAwIBAgIJAIm8zfdCZeSwMA0GCSqGSIb3DQEBCwUAMIGtMQswCQYDVQQGEwJERTEQMA4GA1UECAwHQmF2YXJpYTEPMA0GA1UEBwwGTXVuaWNoMR8wHQYDVQQKDBZTZWN1cmUgRGltZW5zaW9ucyBHbWJIMS0wKwYDVQQDDCRnb29nbGUtaWRwLnNkZGkuc2VjdXJlLWRpbWVuc2lvbnMuZGUxKzApBgkqhkiG9w0BCQEWHHN1cHBvcnRAc2VjdXJlLWRpbWVuc2lvbnMuZGUwHhcNMTkwMzEzMTYwOTI0WhcNMjkwMzEyMTYwOTI0WjCBrTELMAkGA1UEBhMCREUxEDAOBgNVBAgMB0JhdmFyaWExDzANBgNVBAcMBk11bmljaDEfMB0GA1UECgwWU2VjdXJlIERpbWVuc2lvbnMgR21iSDEtMCsGA1UEAwwkZ29vZ2xlLWlkcC5zZGRpLnNlY3VyZS1kaW1lbnNpb25zLmRlMSswKQYJKoZIhvcNAQkBFhxzdXBwb3J0QHNlY3VyZS1kaW1lbnNpb25zLmRlMIIBojANBgkqhkiG9w0BAQEFAAOCAY8AMIIBigKCAYEAruGk0saY8gElgjjJ4eIl4dj5u5h1jJlsDYzwkAArf7N9e173x2Dbeyi3/sFj4Fh/g4mb9k4Haea7muhML62jUrazfKbrAltaQ83Gx+j+is9o24XeHni4Xg+WX0/4w3o1vbqFwUE/BLxFZaupQjwUI5mSNvCJa+zyg/iVqLUg3pc8icUfjnRaKNXjw6dAhY2aHaVTk+HX9pchNhCuhcyjCOAVh93Y3mViODO4UtEYBWTlWQP7hr4rUnF0WAKpbst41lp4tv87cLERK0eQZo2t6H8I7g97yy45jIbUmvHDnqOqgM93bqqX8sqqJmwfg2YAMX5dKwg+lF9SAD1IFe2UOrBj1E1AdHFXykO8pr72YZ4ENiHTTQPvTMDA39LOnx3Y/ZY7eexKHzdb0L/MYn5RgzE3lztiaJGkMO5Pd9r32KIISvwAbfy6yVfuiSSAVJLcHvzQXaJYYaXVkZc8DKES11fN1yc5yIoeUVnMmhZ1Ic9Suwu8XQ/57MccBq6Ed2VlAgMBAAGjUDBOMB0GA1UdDgQWBBQN8a8fALJqnbcyqvkDK/JErNivbzAfBgNVHSMEGDAWgBQN8a8fALJqnbcyqvkDK/JErNivbzAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBgQAOP1ow1hZY6wbtUsoZKqY0hYi2dNnwgQFPDOjguKPGcyNDngXDCPu/yBcG3reILgaOz2G+KtD1MemG+gPSCTvZB/ijghvbmnMAqNhdcum/sZ9HoJDPSpD4nrfmZv1OY4g/brjJ5dAzYdJB1Y6LahOunZjYZKXzjQZHwdU/pibMDQDFqVZZH9smWP54S2jtJC+A25I1tHHjfie7q/CVgdPfEYhtHUzNEuDZmxABTSy79JQl5ywhhTttvaYexII1AC3vqlAauxoF3BVl1uHdDjnsfmhFEOV7PTAOIRbmnTHXVxdItjl5tdwZLEnnkPe9aCRYfurrmzLgbi2Ujy0sobxonX4FQB37HAtfWz4erK6xwcQEGE3wtXCubRIQUWWMYO/6vvf2sWfDeFWdZq8+Xi0Iak/Hb+SiEUfzsQ0gBfuhAOzHyxg+4bQovEwqjH+RfEVi3Plu9kadwHxYEAcuVVjWk33EJGllx4TBk0RcVoZddsI7H/ondjNNGpYfLoIdBaA=',
    ),
  ),
  'RegistrationInfo' => 
  array (
    'registrationAuthority' => 'urn:mace:tum:sddi',
  ),
  'UIInfo' => 
  array (
    'DisplayName' => 
    array (
      'en' => 'Google IdP',
    ),
    'Description' => 
    array (
      'en' => 'Google IdP Gateway to SDDI',
    ),
    'InformationURL' => 
    array (
      'en' => 'https://google-idp.sddi.secure-dimensions.de/simplesaml',
    ),
    'PrivacyStatementURL' => 
    array (
      'en' => 'https://google-idp.sddi.secure-dimensions.de/PrivacyStatement',
    ),
    'Logo' => 
    array (
      0 => 
      array (
        'url' => 'https://ssl.gstatic.com/images/icons/gplus-16.png',
        'height' => 16,
        'width' => 16,
        'lang' => 'en',
      ),
      1 => 
      array (
        'url' => 'https://developers.google.com/identity/images/btn_google_signin_light_normal_web.png',
        'height' => 50,
        'width' => 80,
      ),
    ),
  ),
  'name' => 
  array (
    'en' => 'Google IdP',
  ),
);

