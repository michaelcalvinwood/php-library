<?php
declare(strict_types=1);
require_once('./vendor/autoload.php');

use Firebase\JWT\JWT;

// create the payload data as an associative array

$email = "mwood@pymnts.com";
$data = [
    'email' => $email
];



