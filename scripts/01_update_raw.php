<?php
$basePath = dirname(__DIR__);

// get the latest zip file
$zipFile = $basePath . '/raw/raw.zip';
$page = file_get_contents('https://data.gov.tw/dataset/6569');
$pos = strpos($page, 'https://serv.gcis.nat.gov.tw/');
if(false === $pos) {
    die('location changed');
}
$posEnd = strpos($page, 'zip', $pos);
if(false === $posEnd) {
    die('location changed');
} else {
    $posEnd += 3;
}
$rawUrl = substr($page, $pos, $posEnd - $pos);
$parts = explode('/', $rawUrl);
foreach($parts AS $k => $part) {
    if($k > 2) {
        $parts[$k] = urlencode($part);
    }
}
$zipUrl = implode('/', $parts);
file_put_contents($zipFile, file_get_contents($zipUrl));