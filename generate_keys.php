<?php

if (!extension_loaded('openssl')) {
  die("OpenSSL extension is not loaded. Please enable it in php.ini\n");
}

if (!is_dir('config/jwt')) {
  mkdir('config/jwt', 0777, true);
}

$config = [
  "digest_alg" => "sha256",
  "private_key_bits" => 4096,
  "private_key_type" => OPENSSL_KEYTYPE_RSA,
];

$opensslCnf = null;
$possiblePaths = [
  php_ini_loaded_file() ? dirname(php_ini_loaded_file()) . '/extras/ssl/openssl.cnf' : null,
  'C:/Program Files/php-8.4.1-Win32-vs17-x64/extras/ssl/openssl.cnf',
  'C:/php/extras/ssl/openssl.cnf',
  'C:/xampp/php/extras/ssl/openssl.cnf',
  'C:/wamp64/bin/php/php8.4/extras/ssl/openssl.cnf',
];

foreach ($possiblePaths as $path) {
  if ($path && file_exists($path)) {
    $config['config'] = $path;
    echo "Using OpenSSL config: $path\n";
    break;
  }
}

$privateKey = openssl_pkey_new($config);

if (!$privateKey) {
  die("Failed to generate private key. Error: " . openssl_error_string() . "\n");
}

openssl_pkey_export($privateKey, $privateKeyPem, null, $config);

$publicKeyPem = openssl_pkey_get_details($privateKey)['key'];

file_put_contents('config/jwt/private.pem', $privateKeyPem);
file_put_contents('config/jwt/public.pem', $publicKeyPem);

echo "JWT keys generated successfully!\n";
echo "Private key: config/jwt/private.pem\n";
echo "Public key: config/jwt/public.pem\n";

echo "\nVerification:\n";
$verifyPrivate = file_exists('config/jwt/private.pem') ? '' : '';
$verifyPublic = file_exists('config/jwt/public.pem') ? '' : '';
echo "Private key file: $verifyPrivate\n";
echo "Public key file: $verifyPublic\n";


//не работает SSL, поэтому так 
