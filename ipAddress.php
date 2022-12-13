<?php
function getIPAddress() {  
    if(isset($_SERVER['HTTP_CLIENT_IP'])) {  
        $ip = $_SERVER['HTTP_CLIENT_IP'];  
    }  
    elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {  
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];  
    }  
    else{  
        $ip = $_SERVER['REMOTE_ADDR'];  
    }  

    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    } 
        
    return 'invalid';
}  

function jsSetUserIpAddress() {
    $ip = getIPAddress();

    echo "<script>\n";
    echo "const userIpAddress = '$ip'";
    echo "</script>";
}