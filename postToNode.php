<?php

// receiving JSON POST data from node
$_DATA = json_decode(file_get_contents("php://input"),true);

// sending JSON POST data to node
function postToNode($data, $url, $port = 80, $debug = false) {
    $payload = json_encode($data);
 
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_PORT, $port);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($payload))
    );
    
    $result = curl_exec($ch);
    
    if ($result === false && $debug) echo curl_error($ch);

    curl_close($ch);

    return $result;
}
