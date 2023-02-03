<?php

// add the following to functions.php

function cpiUuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}
  
function set_authentication_cookie() {
    if (!isset($_COOKIE['cpi-device-id'])) setcookie('cpi-device-id', cpiUuid(), mktime (0, 0, 0, 12, 31, 2099), "/");
}
add_action( 'after_setup_theme', 'set_authentication_cookie', -1);
