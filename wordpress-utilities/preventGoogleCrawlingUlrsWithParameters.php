<?php

$queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
 if ($queryString) header('X-Robots-Tag: noindex');