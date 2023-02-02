<?php
declare(strict_types=1);
require_once('./vendor/autoload.php');
require_once('../simple-env.php');

print_r ($_ENV);

use Firebase\JWT\JWT;

// create the payload data as an associative array

$email = "mwood@pymnts.com";
$data = [
    'email' => $email
];

$jwt = JWT::encode(
    $data,
    $_ENV['JWT_SECRET_KEY'],
    'HS512'
);

echo $jwt;

/* sending the token
  const res = await fetch('/resource.php', {
    headers: {
      'Authorization': `Bearer ${store.JWT}`
    }
  });
*/

/* If using Apache, add the following to the virtual host file
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} ^(.+)$
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
*/

function checkHeaderForAuthorization() {
    if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        header('HTTP/1.0 400 Bad Request');
        echo 'Token not found in request';
        exit;
    }
    return $_SERVER['HTTP_AUTHORIZATION'];
}
