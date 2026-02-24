<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type_name = $_POST['type_name'];
    $description = $_POST['description'];
    $price = $_POST['price_per_night'];
    $capacity = $_POST['max_capacity'];
    $id = $_POST['room_id'] ?? null;

    if ($id) {
        $stmt = $pdo->prepare("UPDATE room_types SET type_name = ?, description = ?, price_per_night = ?, max_capacity = ? WHERE id = ?");
        $stmt->execute([$type_name, $description, $price, $capacity, $id]);
        $msg = "updated";
    } else {
        $stmt = $pdo->prepare("INSERT INTO room_types (type_name, description, price_per_night, max_capacity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type_name, $description, $price, $capacity]);
        $msg = "added";
    }
    header("Location: rooms.php?msg=" . $msg);
    exit();
}

if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = $_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE room_types SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    header("Location: rooms.php?msg=updated");
    exit();
}

$stmt = $pdo->query("SELECT * FROM room_types ORDER BY id ASC");
$rooms = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>
    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Room Category Management</h1>
                <p class="text-muted">Configure and manage the luxury room categories of the hotel.</p>
            </div>
            <button class="btn btn-kingsman" data-bs-toggle="modal" data-bs-target="#suiteModal"
                onclick="clearModal()">Add New Category</button>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="kingsman-alert success mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-shield-check fs-4 me-3"></i>
                    <div>Room category <?php echo $_GET['msg']; ?> successfully.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php
            $i = 1;
            foreach ($rooms as $room):
                $reveal_class = "reveal reveal-" . min($i, 5);
                $i++;
                ?>
                <div class="col-md-4 <?php echo $reveal_class; ?>">
                    <div class="card kingsman-card h-100 shadow-lg border-0">
                        <div class="position-relative overflow-hidden">
                            <img src="../assets/img/<?php echo $room['thumbnail_image']; ?>" class="card-img-top"
                                style="height: 220px; object-fit: cover; transition: transform 0.5s ease;">
                            <div class="position-absolute top-0 end-0 p-3">
                                <span
                                    class="badge bg-<?php echo $room['status'] == 'active' ? 'success' : 'secondary'; ?> bg-opacity-75 backdrop-blur px-3 py-2 small shadow-sm">
                                    <?php echo strtoupper($room['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <h4 class="gold-text mb-2" style="letter-spacing: 0.5px;">
                                <?php echo htmlspecialchars($room['type_name']); ?>
                            </h4>
                            <p class="small text-white-50 mb-4 flex-grow-1">
                                <?php echo htmlspecialchars($room['description']); ?>
                            </p>
                            <div
                                class="d-flex justify-content-between align-items-end mb-4 border-top border-secondary pt-3 opacity-75">
                                <div>
                                    <div class="text-muted small text-uppercase fw-bold"
                                        style="font-size: 0.6rem; letter-spacing: 1px;">Rate per Night</div>
                                    <div class="fs-4 gold-text fw-bold">
                                        ₱<?php echo number_format($room['price_per_night'], 2); ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small text-uppercase fw-bold"
                                        style="font-size: 0.6rem; letter-spacing: 1px;">Max Capacity</div>
                                    <div class="text-white fw-bold"><i
                                            class="bi bi-people me-1"></i><?php echo $room['max_capacity']; ?></div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="#"
                                    onclick="confirmToggle('rooms.php?toggle_status=<?php echo $room['status'] == 'active' ? 'archived' : 'active'; ?>&id=<?php echo $room['id']; ?>', '<?php echo $room['status'] == 'active' ? 'Archive' : 'Activate'; ?>')"
                                    class="btn btn-outline-<?php echo $room['status'] == 'active' ? 'danger' : 'success'; ?> btn-sm flex-grow-1 py-2 d-flex align-items-center justify-content-center border-0"
                                    style="background: rgba(<?php echo $room['status'] == 'active' ? '255,107,107' : '46,204,113'; ?>, 0.1);"
                                    title="<?php echo $room['status'] == 'active' ? 'Archive Category' : 'Activate Category'; ?>">
                                    <i
                                        class="bi <?php echo $room['status'] == 'active' ? 'bi-archive' : 'bi-check-circle'; ?> me-2"></i>
                                    <?php echo $room['status'] == 'active' ? 'ARCHIVE' : 'ACTIVATE'; ?>
                                </a>
                                <button class="btn btn-kingsman btn-sm px-3 py-2"
                                    onclick="editSuite(<?php echo htmlspecialchars(json_encode($room)); ?>)"
                                    title="Edit Category">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="suiteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kingsman-card glass-panel border-gold shadow-lg">
            <div class="modal-header border-gold">
                <h5 class="modal-title gold-text small text-uppercase" style="letter-spacing: 2px;" id="modalTitle">
                    <i class="bi bi-gear-fill me-2"></i> Add Room Category
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body py-4 px-5">
                    <input type="hidden" name="room_id" id="room_id">
                    <div class="mb-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Category Name</label>
                        <input type="text" name="type_name" id="type_name"
                            class="form-control bg-dark text-white border-secondary" placeholder="e.g. Executive Suite"
                            required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small text-muted text-uppercase fw-bold">Description</label>
                        <textarea name="description" id="description"
                            class="form-control bg-dark text-white border-secondary" rows="3"
                            placeholder="Describe the room amenities and features..." required></textarea>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">Rate Nightly (₱)</label>
                            <input type="number" step="0.01" name="price_per_night" id="price_per_night"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small text-muted text-uppercase fw-bold">Max Capacity</label>
                            <input type="number" name="max_capacity" id="max_capacity"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-kingsman btn-sm px-4">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    function clearModal() {
        document.getElementById('room_id').value = '';
        document.getElementById('type_name').value = '';
        document.getElementById('description').value = '';
        document.getElementById('price_per_night').value = '';
        document.getElementById('max_capacity').value = '';
        document.getElementById('modalTitle').innerText = 'Add Room Category';
    }

    function editSuite(room) {
        document.getElementById('room_id').value = room.id;
        document.getElementById('type_name').value = room.type_name;
        document.getElementById('description').value = room.description;
        document.getElementById('price_per_night').value = room.price_per_night;
        document.getElementById('max_capacity').value = room.max_capacity;
        document.getElementById('modalTitle').innerText = 'Edit Room Category';
        new bootstrap.Modal(document.getElementById('suiteModal')).show();
    }

    function confirmToggle(url, action) {
        let actionWord = action.toLowerCase();
        let isArchive = actionWord === 'archive';
        Swal.fire({
            title: `Confirm ${action}?`,
            text: `Are you sure you want to ${actionWord} this room category?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isArchive ? '#d33' : '#198754',
            cancelButtonColor: '#2b2b2b',
            confirmButtonText: `Yes, ${actionWord} it!`,
            background: '#1a1a1a',
            color: '#cda434',
            customClass: {
                popup: 'border border-gold kingsman-card',
                confirmButton: isArchive ? 'btn btn-outline-danger' : 'btn btn-outline-success',
                cancelButton: 'btn btn-outline-secondary ms-2'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>