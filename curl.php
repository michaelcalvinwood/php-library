<?php
function get($url, $connectionTimeout = 3, $resultTimeout = 5) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectionTimeout); 
    curl_setopt($ch, CURLOPT_TIMEOUT, $resultTimeout);
  
    $response = curl_exec($ch);
    curl_close($ch);
  
    return $response;
}