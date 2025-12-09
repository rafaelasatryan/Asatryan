<?php
require_once "config.php";
require_once "functions.php";

$errors = [];
$values = [
    'login'=>'', 'password'=>'', 'password2'=>'',
    'fio'=>'', 'vk'=>'', 'blood'=>'', 'rh'=>''
];

if (is_logged_in()) {
    echo "Вы уже авторизованы как «" . current_user() . "». <a href='logout.php'>Выйти</a>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = array_merge($values, $_POST);

    // Проверка email
    if (!filter_var($values['login'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Логин должен быть валидным email.";
    }

    // Проверка пароля
    $password = $values['password'];
    $password2 = $values['password2'];
    if ($password !== $password2) {
        $errors[] = "Пароли не совпадают.";
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $password)) {
        $errors[] = "Пароль должен быть >7 символов, содержать большие и маленькие латинские буквы и цифры.";
    }

    // Проверка существующего пользователя
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$values['login']]);
    if ($stmt->fetch()) {
        $errors[] = "Пользователь с таким логином уже существует.";
    }

    if (!$errors) {
        $hash = hash_password($password);
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (email, password_hash, full_name, vk_link, blood_group, rh_factor)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $values['login'],
            $hash,
            $values['fio'],
            $values['vk'],
            $values['blood'],
            $values['rh']
        ]);

        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Регистрация</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgBAMAAACBVGfHAAAAIVBMVEVmZtJ9cv99cf5wZe59cP99cf8aFnkNC2ijm//s7v/Y1//2WFp1AAAABXRSTlMCabzY7jySakQAAACRSURBVCjPY2BgVHYJhQIXIwEGBgbVUCQQxMDAFIoCFBhEUQUCUXWA9JiiCgQzuKIKhGAKhBIQCMUmEJxeXl5mGto5c+bMGWCBsHIgSA1dCRSYCRYIBwmURoD4s9AFZiAJRKELgMycuRRdoBVJYCVCAGIt1BKEw6DuCiXKL4QCCEMAI5AxogEjojCiEiOy0ZMDADp1tgQfdRxOAAAAAElFTkSuQmCC">
</head>
<body class="bg-light">
<div class="container mt-5">
<h2>Регистрация</h2>

<?php if($errors): ?>
<div class="alert alert-danger">
    <ul>
        <?php foreach($errors as $err) echo "<li>".htmlspecialchars($err)."</li>"; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label>Email (логин)</label>
        <input type="email" name="login" class="form-control" value="<?= htmlspecialchars($values['login']) ?>" required>
    </div>
    <div class="mb-3">
        <label>Пароль</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Повторите пароль</label>
        <input type="password" name="password2" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>ФИО</label>
        <input type="text" name="fio" class="form-control" value="<?= htmlspecialchars($values['fio']) ?>">
    </div>
    <div class="mb-3">
        <label>Ссылка на профиль ВК</label>
        <input type="url" name="vk" class="form-control" value="<?= htmlspecialchars($values['vk']) ?>">
    </div>
    <div class="mb-3">
        <label>Группа крови</label>
        <input type="text" name="blood" class="form-control" value="<?= htmlspecialchars($values['blood']) ?>">
    </div>
    <div class="mb-3">
        <label>Резус-фактор</label>
        <input type="text" name="rh" class="form-control" value="<?= htmlspecialchars($values['rh']) ?>">
    </div>
    <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
    <a href="login.php" class="btn btn-link">Войти</a>
</form>
</div>
</body>
</html>