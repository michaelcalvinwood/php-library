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

/*
    Example $expiration:
        6 minutes
    $expirationDate must be YYYY-MM-DD
*/

function jwtEncode($data, $expiration = '2 hours', $expirationDate = '') {
    $issuedAt   = new DateTimeImmutable();
    $data['iat'] = $issuedAt->getTimestamp();
    $data['nbf'] = $issuedAt->getTimestamp();
    if ($expirationDate) {
        $expiration = new DateTimeImmutable($expirationDate);
        $data['exp'] = $expiration->getTimestamp();
    } else {
        $data['exp'] = $issuedAt->modify('+' . $expiration)->getTimestamp();
    }

    $jwt = JWT::encode(
        $data,
        $_ENV['JWT_SECRET_KEY'],
        'HS512'
    );
    return $jwt;
}


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

function retrieveJwtTokenFromHeader() {
    if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        header('HTTP/1.0 400 Bad Request');
        echo 'Token not found in request';
        exit;
    }
    $jwt = $matches[1];
    if (! $jwt) {
        // No token was able to be extracted from the authorization header
        header('HTTP/1.0 400 Bad Request');
        exit;
    }
    $token = JWT::decode($jwt, $_ENV['JWT_SECRET_KEY'], ['HS512']);

    $now = new DateTimeImmutable();
    $serverName = $_ENV['JWT_ISSUING_DOMAIN'];

    if ($token->iss !== $serverName ||
        $token->nbf > $now->getTimestamp() ||
        $token->exp < $now->getTimestamp())
    {
        header('HTTP/1.1 401 Unauthorized');
        exit;
    }
    return $token;
}

