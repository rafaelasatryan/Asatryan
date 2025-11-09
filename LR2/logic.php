<?php
require 'config.php';

// Получаем фильтры из GET
$from_city = $_GET['from_city'] ?? '';
$to_city = $_GET['to_city'] ?? '';
$trip_date = $_GET['trip_date'] ?? '';

// Базовый запрос
$sql = "SELECT t.id, t.name AS train_name, tr.from_city, tr.to_city, tr.trip_date,
               t.img_path, t.description, t.number_of_wagons
        FROM trains t
        INNER JOIN trips tr ON t.id_trip = tr.id_trip
        WHERE 1=1";

$params = [];
if ($from_city) {
    $sql .= " AND tr.from_city = :from_city";
    $params['from_city'] = $from_city;
}
if ($to_city) {
    $sql .= " AND tr.to_city = :to_city";
    $params['to_city'] = $to_city;
}
if ($trip_date) {
    $sql .= " AND tr.trip_date = :trip_date";
    $params['trip_date'] = $trip_date;
}

// Выполняем запрос
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trains = $stmt->fetchAll();