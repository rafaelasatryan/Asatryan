<?php
// ====== ОБРАБОТКА ЭКСПОРТА ======
$export_message = "";
$import_message = "";

// Подключение к БД
$conn = new mysqli("localhost", "root", "", "transport");
$conn->set_charset("utf8");
if ($conn->connect_error) die("Ошибка подключения: " . $conn->connect_error);

// --- Экспорт ---
if (isset($_POST['export'])) {
    $rows = [];
    $res = $conn->query("SELECT * FROM trains");
    while ($r = $res->fetch_assoc()) $rows[] = $r;

    // Добавляем guid, если нет
    foreach ($rows as &$r) {
        if (!isset($r['guid'])) $r['guid'] = bin2hex(random_bytes(16));
    }

    $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    // Выдача пользователю
    header("Content-Type: application/json");
    header("Content-Disposition: attachment; filename=trains_exported.json");
    echo $json;
    exit;
}

// --- Импорт ---
if (isset($_POST['import'])) {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $import_message = "Ошибка загрузки файла.";
    } else {
        $tmp = $_FILES['file']['tmp_name'];
        $json = file_get_contents($tmp);
        $data = json_decode($json, true);

        if ($data === null) {
            $import_message = "Некорректный JSON.";
        } else {
            // Проверка структуры
            $required = ['id', 'img_path', 'name', 'id_trip', 'description', 'number_of_wagons', 'guid'];
            $valid = true;
            foreach ($data as $row) {
                foreach ($required as $field) {
                    if (!array_key_exists($field, $row)) {
                        $valid = false;
                        break 2;
                    }
                }
            }

            if (!$valid) {
                $import_message = "Структура JSON не соответствует таблице trains. Импорт отменён.";
            } else {
                // Создание таблицы (если нет)
                $conn->query("
                    CREATE TABLE IF NOT EXISTS trains_imported (
                        id INT(10) NOT NULL,
                        img_path VARCHAR(100) NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        id_trip INT(10) NOT NULL,
                        description VARCHAR(255) NULL,
                        number_of_wagons INT(11) NOT NULL,
                        guid VARCHAR(36) NOT NULL UNIQUE,
                        PRIMARY KEY (guid)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
                ");

                // UPSERT
                $stmt = $conn->prepare("
                    INSERT INTO trains_imported
                    (id, img_path, name, id_trip, description, number_of_wagons, guid)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                         id=VALUES(id),
                         img_path=VALUES(img_path),
                         name=VALUES(name),
                         id_trip=VALUES(id_trip),
                         description=VALUES(description),
                         number_of_wagons=VALUES(number_of_wagons)
                ");

                $count = 0;
                foreach ($data as $r) {
                    $stmt->bind_param(
                        "issisis",
                        $r["id"],
                        $r["img_path"],
                        $r["name"],
                        $r["id_trip"],
                        $r["description"],
                        $r["number_of_wagons"],
                        $r["guid"]
                    );
                    $stmt->execute();
                    $count++;
                }

                $import_message = "Файл обработан. Создана таблица <b>trains_imported</b>. Добавлено/обновлено записей: <b>$count</b>.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ЛР5 — Импорт / Экспорт JSON</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgBAMAAACBVGfHAAAAIVBMVEVmZtJ9cv99cf5wZe59cP99cf8aFnkNC2ijm//s7v/Y1//2WFp1AAAABXRSTlMCabzY7jySakQAAACRSURBVCjPY2BgVHYJhQIXIwEGBgbVUCQQxMDAFIoCFBhEUQUCUXWA9JiiCgQzuKIKhGAKhBIQCMUmEJxeXl5mGto5c+bMGWCBsHIgSA1dCRSYCRYIBwmURoD4s9AFZiAJRKELgMycuRRdoBVJYCVCAGIt1BKEw6DuCiXKL4QCCEMAI5AxogEjojCiEiOy0ZMDADp1tgQfdRxOAAAAAElFTkSuQmCC">
</head>
<body class="bg-light">

<div class="container py-5">
    <h1 class="text-center mb-5">ЛР5 — Импорт / Экспорт данных</h1>

    <?php if ($export_message): ?>
        <div class="alert alert-info"><?= $export_message ?></div>
    <?php endif; ?>

    <?php if ($import_message): ?>
        <div class="alert alert-warning"><?= $import_message ?></div>
    <?php endif; ?>

    <div class="row mb-5">
        <div class="col-md-6 mx-auto">
            <form method="post">
                <button class="btn btn-primary w-100 mb-2" name="export" type="submit">Экспортировать JSON</button>
                <div class="text-muted text-center">Формат: JSON | Способ: скачивание пользователю</div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mx-auto">
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="file" accept=".json" class="form-control mb-2" required>
                <button class="btn btn-success w-100" name="import" type="submit">Импортировать JSON</button>
                <div class="text-muted text-center mt-1">Формат: JSON | Источник: загруженный пользователем файл</div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
