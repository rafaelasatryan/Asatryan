<?php
require_once 'config.php';
require_once 'functions.php';

// Проверка авторизации
require_login();

// Получаем фильтры из GET
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$date = $_GET['date'] ?? '';

// Базовый SQL с JOIN на trips
$sql = "SELECT t.*, tr.from_city, tr.to_city, tr.trip_date
        FROM trains t
        INNER JOIN trips tr ON t.id_trip = tr.id_trip
        WHERE 1=1";
$params = [];

// Добавляем фильтры, если они заданы
if ($from) {
    $sql .= " AND tr.from_city LIKE :from_city";
    $params['from_city'] = "%$from%";
}
if ($to) {
    $sql .= " AND tr.to_city LIKE :to_city";
    $params['to_city'] = "%$to%";
}
if ($date) {
    $sql .= " AND tr.trip_date = :trip_date";
    $params['trip_date'] = $date;
}

$sql .= " ORDER BY tr.trip_date ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trains = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Расписание поездов</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgBAMAAACBVGfHAAAAIVBMVEVmZtJ9cv99cf5wZe59cP99cf8aFnkNC2ijm//s7v/Y1//2WFp1AAAABXRSTlMCabzY7jySakQAAACRSURBVCjPY2BgVHYJhQIXIwEGBgbVUCQQxMDAFIoCFBhEUQUCUXWA9JiiCgQzuKIKhGAKhBIQCMUmEJxeXl5mGto5c+bMGWCBsHIgSA1dCRSYCRYIBwmURoD4s9AFZiAJRKELgMycuRRdoBVJYCVCAGIt1BKEw6DuCiXKL4QCCEMAI5AxogEjojCiEiOy0ZMDADp1tgQfdRxOAAAAAElFTkSuQmCC">
<style>
body { background-color: #edf2fa; }
.train-card { 
    background: #fff;
    border-radius: 15px;
    padding: 15px;
    width: 90%;
    margin: 15px auto;
}
.train-card img {
    width: 200px;
    height: 120px;
    object-fit: cover;
    border-radius: 10px;
}
.search-box {
    width: 90%;
    max-width: 1300px;
    background: #293a50;
    padding: 20px;
    border-radius: 16px;
    margin: 5px auto 30px auto;
}
.namese {
    background: rgba(114,114,114,.5);
    border-radius: 8px;
    padding: 1px 6px;
    color: white;
    cursor: pointer;
}
</style>
</head>
<body>
<div class="container">

    <div class="row mt-3 ms-4 ps-4">
        <div class="col">
            <a href="index.php"><img src="data:image/svg+xml;base64,PHN2ZyBmaWxsPSJub25lIiB2aWV3Qm94PSIwIDAgNTMwIDIwNSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJNNzQuMTMyIDEyNy43ODNjNS42NDQgMCAxMC4yMjktNC41ODQgMTAuMjI5LTEwLjIyOXYtOC41ODZjMC0xLjAzMy44MzUtMS44NjggMS44NjgtMS44NjhoNDQuMDE2YzEuMDMzIDAgMS44NjguODM1IDEuODY4IDEuODY4djguNTk5YzAgMzIuMDEyLTI1Ljk1NiA1Ny45NjgtNTcuOTY4IDU3Ljk2OC0zMi4wMTEgMC01Ny45NjctMjUuOTU2LTU3Ljk2Ny01Ny45NjhWOTYuNWExLjg2NiAxLjg2NiAwIDAgMC0xLjg2OC0xLjg2OEgxLjg2OEExLjg2NiAxLjg2NiAwIDAgMSAwIDkyLjc2NFY1OS4wM2MwLTEuMDMzLjgzNS0xLjg2OCAxLjg2OC0xLjg2OGgxMi40MjhhMS44NjYgMS44NjYgMCAwIDAgMS44NjktMS44NjhWMzMuOTYyYzAtMS4wMzQuODM0LTEuODY4IDEuODY4LTEuODY4aDQ0LjAwMmMxLjAzNCAwIDEuODY4LjgzNCAxLjg2OCAxLjg2OHYyMS4zMzJjMCAxLjAzMy44MzUgMS44NjggMS44NjkgMS44NjhoNTMuNTU1YzEuMDMzIDAgMS44NjguODM1IDEuODY4IDEuODY4djMzLjcyYTEuODY2IDEuODY2IDAgMCAxLTEuODY4IDEuODY5SDY1Ljc3MmExLjg2NiAxLjg2NiAwIDAgMC0xLjg2OSAxLjg2OHYyMS4wNjdjMCA1LjY0NSA0LjU4NSAxMC4yMjkgMTAuMjMgMTAuMjI5em0xMjMuNjQ2IDBjNS42NDUgMCAxMC4yMjktNC41ODQgMTAuMjI5LTEwLjIyOVY1OS4wM2MwLTEuMDMzLjgzNS0xLjg2OCAxLjg2OC0xLjg2OGg0NC45MTdjMS4wNiAwIDEuOTA4LjkwMSAxLjg1NSAxLjk2MWwtLjkwMSA1OC40MzFjMCAzMi4wMTEtMjUuOTU2IDU3Ljk2OC01Ny45NjggNTcuOTY4LTMyLjAxMSAwLTU3Ljk2Ny0yNS45NTctNTcuOTY3LTU3Ljk2OFY1OS4wM2MwLTEuMDMzLjgzNS0xLjg2OCAxLjg2OC0xLjg2OGg0NC4wMDJjMS4wMzQgMCAxLjg2OC44MzUgMS44NjggMS44Njh2NTguNTI0YzAgNS42NDUgNC41ODUgMTAuMjI5IDEwLjIyOSAxMC4yMjl6IiBmaWxsPSIjMGQwYjY4Ii8+PHBhdGggZD0iTTMyNy4zNzQgOTUuNTMzbC01LjYwNSAyMi43MzdjLTEuMjU5IDQuNjI0IDIuMDY3IDkuMzY3IDYuODYzIDkuNWExMy45MDUgMTMuOTA1IDAgMCAwIDEzLjgwNy0xMC4yNTZsMi40NjQtOS4wNjJhMS44NzEgMS44NzEgMCAwIDEgMS44MDItMS4zNzhoNDMuODdhMS44NjggMS44NjggMCAwIDEgMS44MDIgMi4zNThsLTUuMzggMTkuNzQyYy03LjQ1OSAyNy4zNDgtMzIuMzAyIDQ2LjMzNC02MC42NTcgNDYuMzM0LTM0LjE5NyAwLTU5LjA0LTMyLjUxNC01MC4wNDQtNjUuNTE5bDMuMDg3LTEzLjQ3NWExLjg3MyAxLjg3MyAwIDAgMC0xLjgwMi0yLjM1OWgtMTEuOTc3YTEuODY4IDEuODY4IDAgMCAxLTEuODAyLTIuMzU4bDkuMTk1LTMzLjczNGExLjg3MiAxLjg3MiAwIDAgMSAxLjgwMi0xLjM3OGgxNC4wMDVhMS44NyAxLjg3IDAgMCAwIDEuODAyLTEuMzc4bDYuNzU3LTIyLjM1MmExLjg3MiAxLjg3MiAwIDAgMSAxLjgwMi0xLjM3OGg0My44N2ExLjg2OCAxLjg2OCAwIDAgMSAxLjgwMiAyLjM1OGwtNi4yMjggMjAuMzkyYTEuODczIDEuODczIDAgMCAwIDEuODAyIDIuMzU4aDUzLjM3YTEuODY4IDEuODY4IDAgMCAxIDEuODAyIDIuMzU4bC05LjE5NSAzMy43MzRhMS44NzIgMS44NzIgMCAwIDEtMS44MDIgMS4zNzhoLTU1LjM5N2MtLjg2MS4wMTMtMS41OS41Ny0xLjgxNSAxLjM3OHptMTU1LjA3NC0zNy4yNThsLTE2LjMyNCA1OS45MjhhMTIuOTg4IDEyLjk4OCAwIDAgMS0xMi41MzQgOS41OGMtNS4zIDAtOS4xNTUtNS4wNDgtNy43NjQtMTAuMTYzbDE1LjkxMy01OC4zNjVhMS44NzMgMS44NzMgMCAwIDAtMS44MDItMi4zNThoLTQzLjg3Yy0uODM1IDAtMS41NzcuNTU3LTEuODAyIDEuMzc4bC0xNC43MDcgNTMuOTc5Yy04LjY3OSAzMS44NTMgMTUuMzAzIDYzLjI1NCA0OC4zMjIgNjMuMjU0IDI5LjczMiAwIDU1Ljc5NC0xOS45MDEgNjMuNjExLTQ4LjZsMTguNDQ0LTY3LjY2NmExLjg3MyAxLjg3MyAwIDAgMC0xLjgwMi0yLjM1OGgtNDMuODdjLS44NDguMDI2LTEuNTkuNTgzLTEuODE1IDEuMzkxeiIgZmlsbD0iIzdkNzFmZiIvPjwvc3ZnPg==" width="138" height="60" alt="Logo"></a>
        </div>
        <div class="col text-end">
            Вы вошли как <strong><?= htmlspecialchars($_SESSION['user_login']) ?></strong> | 
            <a href="logout.php">Выйти</a> | 
            <a href="text.php?preset=1">ЛР-4: Работа с текстом</a>
        </div>
    </div>

      <div class="row pt-5 ms-5 ps-2">
    <div class="col">
      <h4 class="fw-bold">Выберите день, чтобы найти ж/д билеты Москва — Санкт-Петербург</h4>
    </div>
    </div>

    <!-- Фильтр -->
    <div class="search-box mb-3">
        <form method="GET" class="row g-3 align-items-start">
            <div class="col">
                <input type="text" class="form-control mb-1" name="from" placeholder="Откуда" value="<?= htmlspecialchars($from) ?>">
            </div>
            <div class="col">
                <input type="text" class="form-control mb-1" name="to" placeholder="Куда" value="<?= htmlspecialchars($to) ?>">
            </div>
            <div class="col">
                <input type="date" class="form-control mb-1" name="date" value="<?= htmlspecialchars($date) ?>">
            </div>
            <div class="col-auto d-grid">
                <button type="submit" class="btn btn-primary">Применить фильтр</button>
            </div>
            <div class="col-auto d-grid">
                <a href="trains.php" class="btn btn-secondary">Очистить фильтр</a>
            </div>
        </form>
    </div>

    <!-- Карточки поездов -->
    <?php if ($trains): ?>
        <?php foreach ($trains as $row): ?>
            <div class="train-card row align-items-center">
                <div class="col-auto">
                    <img src="image.php?file=<?= urlencode($row['img_path']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
                </div>
                <div class="col">
                    <h5><?= htmlspecialchars($row['name']) ?></h5>
                    <p><?= htmlspecialchars($row['from_city']) ?> → <?= htmlspecialchars($row['to_city']) ?></p>
                    <p>Дата: <?= htmlspecialchars($row['trip_date']) ?></p>
                    <p><?= htmlspecialchars($row['description']) ?></p>
                    <p>Вагонов: <?= htmlspecialchars($row['number_of_wagons']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <h2 class="ms-5 ps-3 text-danger">Рейсы не найдены</h2>
    <?php endif; ?>

</div>
</body>
</html>