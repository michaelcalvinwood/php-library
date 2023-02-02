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

