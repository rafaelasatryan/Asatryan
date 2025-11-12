<?php
require_once "config.php"; // здесь уже session_start();
require_once "functions.php";

// Удаляем все данные сессии
logout_user();

// Перенаправляем на страницу входа
header("Location: login.php");
exit;