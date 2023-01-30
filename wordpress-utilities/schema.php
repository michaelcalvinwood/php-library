<?php
function videoSchema($title, $description, $thumbnail, $videoUrl, $duration, $uploadDate) {

$minutes = floor($duration/60);
$seconds = $duration % 60;

echo '<script type="application/ld+json">';
echo '{';
echo '"@context": "https://schema.org",';
echo '"@type": "VideoObject",';
echo '"name": "' . $title . '",';
echo '"description": "' . $description . '",';
echo '"thumbnailUrl": "' . $thumbnail . '",';
echo '"duration": "PT' . $minutes . 'M' . $seconds . 'S",';
echo '"contentUrl": "' . $videoUrl . '",';
echo '"uploadDate": "' . $uploadDate . '"';
echo '}';
echo '</script>';
}
