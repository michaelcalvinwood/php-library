<?php

/* Problem: The function, uniqid(), is not a sufficient replacement for generating RFC 4122 compliant UUIDs 
    as it may not always generate a unique value (especially if your computer is fast) 
    and it can only generate a maximum of 23 characters.

   Solution: mcwUuid
*/

function mcwUuid() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

