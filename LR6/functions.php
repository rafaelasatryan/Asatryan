<?php
// Проверка авторизации
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Получение логина авторизованного пользователя
function current_user() {
    return $_SESSION['user_login'] ?? null;
}

// Требуем авторизацию
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

// Выход
function logout_user() {
    session_unset();
    session_destroy();
}

// Хэширование пароля
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Проверка пароля
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}