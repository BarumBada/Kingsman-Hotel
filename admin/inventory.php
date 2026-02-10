<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_room'])) {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $status = $_POST['status'];
    $id = $_POST['room_id'] ?? null;

    try {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE rooms SET room_number = ?, room_type_id = ?, status = ? WHERE id = ?");
            $stmt->execute([$room_number, $room_type_id, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id, status) VALUES (?, ?, ?)");
            $stmt->execute([$room_number, $room_type_id, $status]);
        }
        header("Location: inventory.php?msg=success");
        exit();
    } catch (PDOException $e) {
        $error = "Operation Failed: " . $e->getMessage();
    }
}

if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$_GET['delete_id']]);
        header("Location: inventory.php?msg=deleted");
        exit();
    } catch (PDOException $e) {
        $error = "Cannot delete: Room may be attached to existing reservations.";
    }
}

$stmt = $pdo->query("SELECT r.*, rt.type_name FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id ORDER BY r.room_number ASC");
$inventory = $stmt->fetchAll();

$room_types = $pdo->query("SELECT id, type_name FROM room_types WHERE status = 'active'")->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Room Inventory</h1>
                <p class="text-muted">Tracking individual physical rooms and their current operational status.</p>
            </div>
            <button class="btn btn-kingsman" data-bs-toggle="modal" data-bs-target="#roomModal"
                onclick="clearRoomModal()">Add New Room</button>
        </div>

        <?php if (isset($error)): ?>
            <div class="kingsman-alert danger mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card kingsman-card p-4 shadow-lg border-gold">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">Room Number</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td class="ps-4 fw-bold gold-text">
                                    <?php echo htmlspecialchars($item['room_number']); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['type_name']); ?>
                                </td>
                                <td>
                                    <span
                                        class="badge rounded-pill bg-<?php echo $item['status'] == 'available' ? 'success' : 'warning'; ?> text-white font-weight-bold">
                                        <?php echo strtoupper($item['status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <button class="btn text-white  btn-outline-gold btn-sm py-0 px-2 small me-2"
                                        onclick='editRoom(<?php echo json_encode($item); ?>)'>EDIT</button>
                                    <a href="inventory.php?delete_id=<?php echo $item['id']; ?>"
                                        class="btn btn-outline-danger btn-sm py-0 px-2 small"
                                        onclick="return confirm('Permanently remove this room from inventory?')">DELETE</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($inventory)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">No rooms currently in inventory.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kingsman-card border-gold glass-panel">
            <div class="modal-header border-gold">
                <h5 class="modal-title gold-text" id="roomModalTitle">Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="m_room_id">
                    <div class="mb-3">
                        <label class="form-label">Room Number (e.g. 101)</label>
                        <input type="text" name="room_number" id="m_room_number" class="form-control" placeholder="101"
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Category</label>
                        <select name="room_type_id" id="m_room_type_id" class="form-select" required>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room Status</label>
                        <select name="status" id="m_status" class="form-select" required>
                            <option value="available">AVAILABLE</option>
                            <option value="maintenance">MAINTENANCE / CLEANING</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_room" class="btn btn-kingsman">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function clearRoomModal() {
        document.getElementById('m_room_id').value = '';
        document.getElementById('m_room_number').value = '';
        document.getElementById('m_room_type_id').selectedIndex = 0;
        document.getElementById('m_status').selectedIndex = 0;
        document.getElementById('roomModalTitle').innerText = 'Add New Room';
    }

    function editRoom(data) {
        document.getElementById('m_room_id').value = data.id;
        document.getElementById('m_room_number').value = data.room_number;
        document.getElementById('m_room_type_id').value = data.room_type_id;
        document.getElementById('m_status').value = data.status;
        document.getElementById('roomModalTitle').innerText = 'Edit Room Details';
        new bootstrap.Modal(document.getElementById('roomModal')).show();
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>