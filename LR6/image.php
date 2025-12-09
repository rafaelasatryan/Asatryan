<?php
require_once "config.php";
require_once "functions.php";

require_login(); // проверка авторизации

$filename = basename($_GET['file'] ?? '');
$filepath = __DIR__ . "/images/$filename";

if (!file_exists($filepath)) {
    header("HTTP/1.1 404 Not Found");
    exit("Файл не найден.");
}

$mime = mime_content_type($filepath);
header("Content-Type: $mime");
readfile($filepath);
exit;