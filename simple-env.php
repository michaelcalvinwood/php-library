<?php

$pairs = file_get_contents('.env');
if ($pairs !== false) {
    $lines = explode("\n", $pairs);

    print_r($lines);

    $numLines = count($lines);
    for ($i = 0; $i < $numLines; ++$i) {
        $loc = strpos($lines[$i], '=');
        if ($loc === false) continue;
        $key = substr($lines[$i], 0, $loc);
        $val = substr($lines[$i], $loc+1);

        $val = trim($val, "\"'");
        $_ENV[$key] = $val;
    }

    print_r($_ENV);
}
