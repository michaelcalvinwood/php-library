<?php
function removeImages($content) {
    $dom = new DOMDocument();
    @$dom->loadHTML($content);  // Using @ to hide any parse warning sometimes resulting from markup errors
    $dom->preserveWhiteSpace = false;
    
    $images = $dom->getElementsByTagName('img');
    $imgs = array();
    foreach($images as $img) {
        $imgs[] = $img;
    }
    foreach($imgs as $img) {
        $img->parentNode->removeChild($img);
    }

    return $dom->saveHTML();
}