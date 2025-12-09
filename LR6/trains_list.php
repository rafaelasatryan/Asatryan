<?php
require_once "config.php";
require_once "functions.php";

// проверка авторизации
require_login();

// TableModule для поездов
class TrainsModule {
    private $pdo;
    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getAll() {
        $sql = "SELECT t.id, t.name, t.img_path, t.description, t.number_of_wagons,
                       tr.from_city, tr.to_city, tr.trip_date
                FROM trains t
                INNER JOIN trips tr ON t.id_trip = tr.id_trip
                ORDER BY tr.trip_date ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function add($data, $file) {
        $img = $this->uploadFile($file);
        $id_trip = $this->getOrCreateTrip($data['from_city'], $data['to_city'], $data['trip_date']);
        $stmt = $this->pdo->prepare("INSERT INTO trains (name,img_path,description,number_of_wagons,id_trip) VALUES (?,?,?,?,?)");
        $stmt->execute([$data['name'],$img,$data['description'],$data['number_of_wagons'],$id_trip]);
    }

    public function update($id, $data, $file = null) {
        $params = [$data['name'], $data['description'], $data['number_of_wagons']];
        $sql = "UPDATE trains SET name=?, description=?, number_of_wagons=?";
        if ($file && $file['tmp_name']) {
            $img = $this->uploadFile($file);
            $sql .= ", img_path=?";
            $params[] = $img;
        }
        $id_trip = $this->getOrCreateTrip($data['from_city'], $data['to_city'], $data['trip_date']);
        $sql .= ", id_trip=? WHERE id=?";
        $params[] = $id_trip;
        $params[] = $id;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM trains WHERE id=?");
        $stmt->execute([$id]);
    }

    private function getOrCreateTrip($from,$to,$date) {
        $stmt = $this->pdo->prepare("SELECT id_trip FROM trips WHERE from_city=? AND to_city=? AND trip_date=?");
        $stmt->execute([$from,$to,$date]);
        $row = $stmt->fetch();
        if ($row) return $row['id_trip'];
        $stmt = $this->pdo->prepare("INSERT INTO trips (from_city,to_city,trip_date) VALUES (?,?,?)");
        $stmt->execute([$from,$to,$date]);
        return $this->pdo->lastInsertId();
    }

    private function uploadFile($file) {
        if ($file && $file['tmp_name']) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $name = uniqid().".$ext";
            move_uploaded_file($file['tmp_name'], "images/$name");
            return $name;
        }
        return 'no_img.png';
    }
}

$trainsModule = new TrainsModule($pdo);

// Обработка POST для добавления и редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_id'])) {
        $trainsModule->update($_POST['edit_id'], $_POST, $_FILES['img_path'] ?? null);
    } else {
        $trainsModule->add($_POST, $_FILES['img_path']);
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Обработка удаления
if (isset($_GET['delete'])) {
    $trainsModule->delete($_GET['delete']);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$trains = $trainsModule->getAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>CRUD Поездов</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACAAAAAgBAMAAACBVGfHAAAAIVBMVEVmZtJ9cv99cf5wZe59cP99cf8aFnkNC2ijm//s7v/Y1//2WFp1AAAABXRSTlMCabzY7jySakQAAACRSURBVCjPY2BgVHYJhQIXIwEGBgbVUCQQxMDAFIoCFBhEUQUCUXWA9JiiCgQzuKIKhGAKhBIQCMUmEJxeXl5mGto5c+bMGWCBsHIgSA1dCRSYCRYIBwmURoD4s9AFZiAJRKELgMycuRRdoBVJYCVCAGIt1BKEw6DuCiXKL4QCCEMAI5AxogEjojCiEiOy0ZMDADp1tgQfdRxOAAAAAElFTkSuQmCC">
<style>
.train-card {
    border-radius: 12px; 
    padding: 15px; 
    margin-bottom: 20px; 
    background-color: #f8f9fa;
    display: flex;
    align-items: stretch;
    gap: 15px;
}
.train-card img {
    width: 200px;
    height: 140px;
    object-fit: cover;
    border-radius: 8px;
}
.train-info {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    width: 100%;
}
.train-description {
    max-height: 3em;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
</head>
<body>
<div class="container mt-3">
    <a href="trains.php" class="btn btn-secondary btn-sm">&larr; Вернуться к trains.php</a>
</div>
<div class="container py-5">
<h1 class="mb-4">Список поездов</h1>
<button class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#addModal">Добавить поезд</button>

<div class="row row-cols-1 row-cols-md-2 g-4">
<?php if ($trains): ?>
    <?php foreach ($trains as $t): ?>
        <div class="col">
            <div class="train-card">
                <img src="images/<?= htmlspecialchars($t['img_path']) ?>" alt="<?= htmlspecialchars($t['name']) ?>">
                <div class="train-info">
                    <div>
                        <h5><?= htmlspecialchars($t['name']) ?></h5>
                        <p class="mb-1 text-muted"><?= htmlspecialchars($t['from_city'] . " → " . $t['to_city']) ?></p>
                        <p class="mb-1"><strong>Дата:</strong> <?= htmlspecialchars($t['trip_date']) ?></p>
                        <p class="mb-1"><strong>Вагонов:</strong> <?= htmlspecialchars($t['number_of_wagons']) ?></p>
                        <p class="train-description"><?= htmlspecialchars($t['description']) ?></p>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal" data-train='<?= json_encode($t, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>Редактировать</button>
                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-train-id="<?= $t['id'] ?>" data-train-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>">Удалить</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Поездов пока нет.</p>
<?php endif; ?>
</div>
</div>

<!-- Модальные окна -->

<!-- Удаление -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Удаление поезда</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Вы действительно хотите удалить поезд <strong id="modalTrainName"></strong>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Удалить</a>
      </div>
    </div>
  </div>
</div>

<!-- Редактирование -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Редактировать поезд</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" id="edit_name" required></div>
        <div class="mb-3"><label>Откуда</label><input type="text" class="form-control" name="from_city" id="edit_from_city" required></div>
        <div class="mb-3"><label>Куда</label><input type="text" class="form-control" name="to_city" id="edit_to_city" required></div>
        <div class="mb-3"><label>Дата поездки</label><input type="date" class="form-control" name="trip_date" id="edit_trip_date" required></div>
        <div class="mb-3"><label>Описание</label><textarea class="form-control" name="description" id="edit_description"></textarea></div>
        <div class="mb-3"><label>Количество вагонов</label><input type="number" class="form-control" name="number_of_wagons" id="edit_number_of_wagons" required></div>
        <div class="mb-3"><label>Картинка (оставьте пустым, чтобы не менять)</label><input type="file" class="form-control" name="img_path"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <button type="submit" class="btn btn-primary">Сохранить</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!-- Добавление -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title">Добавить поезд</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label>Название</label><input type="text" class="form-control" name="name" required></div>
        <div class="mb-3"><label>Откуда</label><input type="text" class="form-control" name="from_city" required></div>
        <div class="mb-3"><label>Куда</label><input type="text" class="form-control" name="to_city" required></div>
        <div class="mb-3"><label>Дата поездки</label><input type="date" class="form-control" name="trip_date" required></div>
        <div class="mb-3"><label>Описание</label><textarea class="form-control" name="description"></textarea></div>
        <div class="mb-3"><label>Количество вагонов</label><input type="number" class="form-control" name="number_of_wagons" required></div>
        <div class="mb-3"><label>Картинка</label><input type="file" class="form-control" name="img_path" required></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
        <button type="submit" class="btn btn-success">Добавить</button>
      </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Удаление
const deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const trainId = button.getAttribute('data-train-id');
    const trainName = button.getAttribute('data-train-name');
    deleteModal.querySelector('#modalTrainName').textContent = trainName;
    deleteModal.querySelector('#confirmDeleteBtn').href = '?delete=' + trainId;
});

// Редактирование
const editModal = document.getElementById('editModal');
editModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const train = JSON.parse(button.getAttribute('data-train'));

    editModal.querySelector('#edit_id').value = train.id;
    editModal.querySelector('#edit_name').value = train.name;
    editModal.querySelector('#edit_from_city').value = train.from_city;
    editModal.querySelector('#edit_to_city').value = train.to_city;
    editModal.querySelector('#edit_trip_date').value = train.trip_date;
    editModal.querySelector('#edit_description').value = train.description;
    editModal.querySelector('#edit_number_of_wagons').value = train.number_of_wagons;
});
</script>
</body>
</html>
