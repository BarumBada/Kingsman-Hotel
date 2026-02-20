<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_reservation'])) {
    $guest_id = $_POST['user_id'];
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in_date'];
    $check_out = $_POST['check_out_date'];

    // Calculate days and total price
    $date1 = new DateTime($check_in);
    $date2 = new DateTime($check_out);
    $days = $date2->diff($date1)->format("%a");
    if ($days == 0)
        $days = 1;

    $stmt = $pdo->prepare("SELECT price_per_night FROM room_types rt JOIN rooms r ON r.room_type_id = rt.id WHERE r.id = ?");
    $stmt->execute([$room_id]);
    $price = $stmt->fetchColumn();
    $total_price = $price * $days;

    $ref = 'KMN-' . strtoupper(substr(md5(uniqid()), 0, 8));

    $insert = $pdo->prepare("INSERT INTO bookings (user_id, room_id, check_in_date, check_out_date, total_price, status, booking_reference) VALUES (?, ?, ?, ?, ?, 'confirmed', ?)");
    $insert->execute([$guest_id, $room_id, $check_in, $check_out, $total_price, $ref]);

    header("Location: bookings.php?msg=created");
    exit();
}

// Fetch available guests
$guests = $pdo->query("SELECT id, firstname, lastname, email FROM users WHERE role = 'user' ORDER BY lastname ASC")->fetchAll();

// Fetch available rooms
$rooms = $pdo->query("SELECT r.id, r.room_number, rt.type_name, rt.price_per_night 
                      FROM rooms r 
                      JOIN room_types rt ON r.room_type_id = rt.id 
                      WHERE r.status = 'available' AND rt.status = 'active'
                      ORDER BY rt.type_name, r.room_number")->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>
    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Create Reservation</h1>
                <p class="text-muted">Manually allocate a suite to a registered guest.</p>
            </div>
            <a href="bookings.php" class="btn btn-outline-gold"><i class="bi bi-arrow-left me-2"></i>Back to
                Bookings</a>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card kingsman-card glass-panel p-5 border-0 shadow-lg">
                    <form method="POST" action="">
                        <input type="hidden" name="create_reservation" value="1">

                        <h4 class="gold-text mb-4">1. Guest Selection</h4>
                        <div class="mb-4">
                            <label class="form-label text-muted">Select Registered Guest</label>
                            <select name="user_id" class="form-select bg-dark text-white border-gold" required>
                                <option value="">-- Choose a Guest --</option>
                                <?php foreach ($guests as $g): ?>
                                    <option value="<?php echo $g['id']; ?>">
                                        <?php echo htmlspecialchars($g['lastname'] . ', ' . $g['firstname'] . ' (' . $g['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text text-muted mt-2"><i class="bi bi-info-circle me-1"></i>Guest must have
                                an existing account.</div>
                        </div>

                        <hr class="border-secondary my-4">

                        <h4 class="gold-text mb-4">2. Suite Allocation</h4>
                        <div class="mb-4">
                            <label class="form-label text-muted">Select Available Room</label>
                            <select name="room_id" class="form-select bg-dark text-white border-gold" required>
                                <option value="">-- Choose a Suite --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?php echo $r['id']; ?>">
                                        <?php echo htmlspecialchars($r['type_name'] . ' - Room ' . $r['room_number'] . ' (₱' . number_format($r['price_per_night'], 2) . '/night)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr class="border-secondary my-4">

                        <h4 class="gold-text mb-4">3. Duration of Stay</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Arrival Date</label>
                                <input type="date" name="check_in_date"
                                    class="form-control bg-dark text-white border-gold" required
                                    min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Departure Date</label>
                                <input type="date" name="check_out_date"
                                    class="form-control bg-dark text-white border-gold" required
                                    min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>

                        <div class="text-end mt-5">
                            <button type="submit" class="btn btn-kingsman btn-lg px-5">Confirm Allocation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>