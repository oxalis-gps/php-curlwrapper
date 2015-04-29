<?php
require_once('./CurlWrapper.php');

// アクセスに使用するCURLのオプション。
$curl_default_option = array(
// CURLOPT_FILETIME   => TRUE,
    CURLOPT_TIMEOUT    => 1,
    CURLOPT_MAXREDIRS  => 1,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_CONNECTTIMEOUT => 1,
    CURLOPT_HEADER => TRUE,
);

$crawl_urls = array(
    'http://www.example.com/'
);

// Curlにurlを全部登録して一気に並列リクエストする
$curl = new CurlWrapper();
foreach($crawl_urls as $url) {
    $url = trim($url);
    $curl->appendCurlChannelQueue($url, $curl_default_option);
}
$curl->execQueueRequest();

foreach($crawl_urls as $url) {
    $url = trim($url);
    $info = $curl->getInfo($url);
    $content = $curl->getContents($url);
    echo $content;
}
