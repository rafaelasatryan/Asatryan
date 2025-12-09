<?php
require_once "config.php";
require_once "functions.php";

$errors = [];
$login = '';

if (is_logged_in()) {
    echo "Вы уже авторизованы как «" . current_user() . "». <a href='logout.php'>Выйти</a>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if (!$user) {
        $errors[] = "Логина нет в системе.";
    } else {
        $failed = (int)$user['failed_attempts'];
        $last = $user['last_failed_attempt'] ? strtotime($user['last_failed_attempt']) : 0;
        $now = time();

        if ($failed >= 3 && ($now - $last) < 3600) {
            $errors[] = "Слишком много неверных попыток. Попробуйте через " . ceil((3600 - ($now - $last))/60) . " минут.";
        } else {
            if (!verify_password($password, $user['password_hash'])) {
                $failed++;
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE id = ?");
                $stmt->execute([$failed, $user['id']]);
                $errors[] = "Неверный пароль.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                header("Location: trains.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Авторизация</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgBAMAAACBVGfHAAAAIVBMVEVmZtJ9cv99cf5wZe59cP99cf8aFnkNC2ijm//s7v/Y1//2WFp1AAAABXRSTlMCabzY7jySakQAAACRSURBVCjPY2BgVHYJhQIXIwEGBgbVUCQQxMDAFIoCFBhEUQUCUXWA9JiiCgQzuKIKhGAKhBIQCMUmEJxeXl5mGto5c+bMGWCBsHIgSA1dCRSYCRYIBwmURoD4s9AFZiAJRKELgMycuRRdoBVJYCVCAGIt1BKEw6DuCiXKL4QCCEMAI5AxogEjojCiEiOy0ZMDADp1tgQfdRxOAAAAAElFTkSuQmCC">
</head>
<body class="bg-light">
<div class="container mt-5">
<h2>Авторизация</h2>

<?php if ($errors): ?>
<div class="alert alert-danger">
    <ul>
        <?php foreach ($errors as $err) echo "<li>".htmlspecialchars($err)."</li>"; ?>
    </ul>
</div>
<?php endif; ?>

<form method="post">
    <div class="mb-3">
        <label>Email (логин)</label>
        <input type="email" name="login" class="form-control" value="<?= htmlspecialchars($login) ?>" required>
    </div>
    <div class="mb-3">
        <label>Пароль</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Войти</button>
    <a href="register.php" class="btn btn-link">Регистрация</a>
</form>
</div>
</body>
</html>