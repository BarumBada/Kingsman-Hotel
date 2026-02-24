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

// Gather unique statuses for the filter
$all_statuses = array_unique(array_column($inventory, 'status'));

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex" style="min-height: 100vh;">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <div class="p-4 p-lg-5 flex-grow-1">

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="gold-text mb-0" style="letter-spacing: 2px;">Room Inventory</h2>
                    <p class="text-muted small mb-0 mt-1">Manage and track physical rooms and their operational status.
                    </p>
                </div>
                <button class="btn btn-kingsman btn-sm px-4" data-bs-toggle="modal" data-bs-target="#roomModal"
                    onclick="clearRoomModal()">
                    <i class="bi bi-plus-circle me-2"></i> Add New Room
                </button>
            </div>

            <!-- Alerts -->
            <?php if (isset($error)): ?>
                <div class="kingsman-alert danger mb-4">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div class="kingsman-alert success mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle fs-4 me-3"></i>
                        <div>Room inventory successfully updated.</div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
                <div class="kingsman-alert success mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle fs-4 me-3"></i>
                        <div>Room successfully removed from inventory.</div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filter Bar -->
            <div class="card glass-panel p-3 mb-4" style="border-radius: 6px; background-color: #151515;">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <span class="text-muted small text-uppercase"
                            style="letter-spacing: 1.5px; font-size: 0.65rem;">
                            <i class="bi bi-funnel me-1"></i> Filters
                        </span>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <select id="filterCategory"
                            class="form-select form-select-sm bg-dark text-white border-secondary"
                            onchange="applyFilters()">
                            <option value="all">All Categories</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['type_name']); ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <select id="filterStatus" class="form-select form-select-sm bg-dark text-white border-secondary"
                            onchange="applyFilters()">
                            <option value="all">All Statuses</option>
                            <?php foreach ($all_statuses as $st): ?>
                                <option value="<?php echo $st; ?>"><?php echo strtoupper($st); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <input type="text" id="filterSearch"
                            class="form-control form-control-sm bg-dark text-white border-secondary"
                            placeholder="Search room number..." oninput="applyFilters()">
                    </div>
                    <div class="col-auto ms-auto">
                        <span class="text-muted small" id="roomCount"><?php echo count($inventory); ?> rooms</span>
                    </div>
                </div>
            </div>

            <!-- Room Cards Grid -->
            <div class="row g-3" id="roomCardsGrid">
                <?php if (empty($inventory)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-building d-block fs-1 text-muted opacity-50 mb-3"></i>
                        <p class="text-muted">No rooms currently in inventory.</p>
                        <button class="btn btn-kingsman btn-sm" data-bs-toggle="modal" data-bs-target="#roomModal"
                            onclick="clearRoomModal()">Add Your First Room</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($inventory as $item):
                        $status_color = match ($item['status']) {
                            'available' => '#2ecc71',
                            'occupied' => '#e74c3c',
                            'maintenance' => '#f39c12',
                            default => '#95a5a6'
                        };
                        $status_icon = match ($item['status']) {
                            'available' => 'bi-check-circle-fill',
                            'occupied' => 'bi-person-fill',
                            'maintenance' => 'bi-wrench-adjustable',
                            default => 'bi-question-circle'
                        };
                        ?>
                        <div class="col-6 col-md-4 col-lg-3 col-xl-2 room-card-item"
                            data-category="<?php echo htmlspecialchars($item['type_name']); ?>"
                            data-status="<?php echo $item['status']; ?>"
                            data-room="<?php echo htmlspecialchars($item['room_number']); ?>">
                            <div class="card h-100 text-center p-3 position-relative"
                                style="background-color: #151515; border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; border-top: 3px solid <?php echo $status_color; ?>; transition: all 0.3s ease; cursor: default;">

                                <!-- Room Number -->
                                <h4 class="gold-text mb-1 mt-1"
                                    style="font-family: var(--font-heading); font-size: 1.4rem; letter-spacing: 1px;">
                                    <?php echo htmlspecialchars($item['room_number']); ?>
                                </h4>

                                <!-- Category -->
                                <p class="text-muted small mb-2" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                                    <?php echo htmlspecialchars($item['type_name']); ?>
                                </p>

                                <!-- Status Badge -->
                                <div class="mb-3">
                                    <span class="badge rounded-pill px-3 py-1"
                                        style="background: <?php echo $status_color; ?>20; color: <?php echo $status_color; ?>; font-size: 0.6rem; letter-spacing: 1px;">
                                        <i class="bi <?php echo $status_icon; ?> me-1"></i>
                                        <?php echo strtoupper($item['status']); ?>
                                    </span>
                                </div>

                                <!-- Actions -->
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-sm px-2 py-1"
                                        style="background: rgba(218,165,32,0.1); color: #DAA520; border: none; border-radius: 4px; font-size: 0.75rem;"
                                        onclick='editRoom(<?php echo json_encode($item); ?>)' title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-sm px-2 py-1"
                                        style="background: rgba(231,76,60,0.1); color: #e74c3c; border: none; border-radius: 4px; font-size: 0.75rem;"
                                        onclick="confirmDelete('inventory.php?delete_id=<?php echo $item['id']; ?>')"
                                        title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
        <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
    </div>
</div>

<!-- Room Modal -->
<div class="modal fade" id="roomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-gold"
            style="background: rgba(10, 10, 10, 0.97); backdrop-filter: blur(20px); border-radius: 8px;">
            <div class="modal-header border-secondary border-opacity-10 pb-3">
                <h6 class="modal-title gold-text mb-0" id="roomModalTitle"
                    style="letter-spacing: 2px; font-size: 0.8rem;">ADD NEW ROOM</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body py-4">
                    <input type="hidden" name="room_id" id="m_room_id">
                    <div class="mb-3">
                        <label class="form-label text-muted"
                            style="font-size: 0.65rem; letter-spacing: 1.5px; text-transform: uppercase;">Room
                            Number</label>
                        <input type="text" name="room_number" id="m_room_number" class="form-control"
                            placeholder="e.g. 101" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted"
                            style="font-size: 0.65rem; letter-spacing: 1.5px; text-transform: uppercase;">Room
                            Category</label>
                        <select name="room_type_id" id="m_room_type_id" class="form-select" required>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted"
                            style="font-size: 0.65rem; letter-spacing: 1.5px; text-transform: uppercase;">Room
                            Status</label>
                        <select name="status" id="m_status" class="form-select" required>
                            <option value="available">AVAILABLE</option>
                            <option value="occupied">OCCUPIED</option>
                            <option value="maintenance">MAINTENANCE / CLEANING</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10 pt-3">
                    <button type="button" class="btn btn-sm px-3 text-muted" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_room" class="btn btn-kingsman btn-sm px-4">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 CSS & JS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>

<script>
    function clearRoomModal() {
        document.getElementById('m_room_id').value = '';
        document.getElementById('m_room_number').value = '';
        document.getElementById('m_room_type_id').selectedIndex = 0;
        document.getElementById('m_status').selectedIndex = 0;
        document.getElementById('roomModalTitle').innerText = 'ADD NEW ROOM';
    }

    function editRoom(data) {
        document.getElementById('m_room_id').value = data.id;
        document.getElementById('m_room_number').value = data.room_number;
        document.getElementById('m_room_type_id').value = data.room_type_id;
        document.getElementById('m_status').value = data.status;
        document.getElementById('roomModalTitle').innerText = 'EDIT ROOM DETAILS';
        new bootstrap.Modal(document.getElementById('roomModal')).show();
    }

    function confirmDelete(url) {
        Swal.fire({
            title: 'Delete Room?',
            text: "This room will be permanently removed from the system inventory.",
            icon: 'warning',
            showCancelButton: true,
            background: '#1a1a1a',
            color: '#fff',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'CONFIRM DELETE',
            cancelButtonText: 'CANCEL'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    // === Filter Logic ===
    function applyFilters() {
        const category = document.getElementById('filterCategory').value.toLowerCase();
        const status = document.getElementById('filterStatus').value.toLowerCase();
        const search = document.getElementById('filterSearch').value.toLowerCase().trim();
        const cards = document.querySelectorAll('.room-card-item');
        let visible = 0;

        cards.forEach(card => {
            const cardCategory = card.dataset.category.toLowerCase();
            const cardStatus = card.dataset.status.toLowerCase();
            const cardRoom = card.dataset.room.toLowerCase();

            const matchCategory = (category === 'all' || cardCategory === category);
            const matchStatus = (status === 'all' || cardStatus === status);
            const matchSearch = (!search || cardRoom.includes(search));

            if (matchCategory && matchStatus && matchSearch) {
                card.style.display = '';
                visible++;
            } else {
                card.style.display = 'none';
            }
        });

        document.getElementById('roomCount').textContent = visible + ' room' + (visible !== 1 ? 's' : '');
    }

    // Add hover effect to cards
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.room-card-item .card').forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 8px 32px rgba(0,0,0,0.4)';
            });
            card.addEventListener('mouseleave', function () {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    });
</script>
</body>

</html>