<?php
require __DIR__ . "/redis/vendor/predis/predis/autoload.php";
Predis\Autoloader::register();

$client = new Predis\Client([
    'host' => 'services.pymnts.com',
    'port' => 6379,
    'database' => 1,
    'connectTimeout' => 2.5,
    'password' => '{defaultUserPasswordHere}',
    'ssl' => ['verify_peer' => false]
]);

// get current URL

// add URL to set
    // SADD key value


$value = $client->get('test');
$client->quit();