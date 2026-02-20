<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

if (isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = $_GET['update_status'];
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);

    $b_stmt = $pdo->prepare("SELECT user_id, booking_reference FROM bookings WHERE id = ?");
    $b_stmt->execute([$id]);
    $booking = $b_stmt->fetch();

    $notif_msg = "Booking status for " . $booking['booking_reference'] . " has been updated to: " . strtoupper($new_status);
    $notif_stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Booking Update', ?)");
    $notif_stmt->execute([$booking['user_id'], $notif_msg]);

    header("Location: bookings.php?msg=updated");
    exit();
}

// Handle Filtering
$where_clauses = [];
$params = [];

if (!empty($_GET['status_filter'])) {
    $where_clauses[] = "b.status = ?";
    $params[] = $_GET['status_filter'];
}

if (!empty($_GET['date_from'])) {
    $where_clauses[] = "b.check_in_date >= ?";
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where_clauses[] = "b.check_in_date <= ?";
    $params[] = $_GET['date_to'];
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$stmt = $pdo->prepare("SELECT b.*, u.firstname, u.lastname, rt.type_name 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN rooms r ON b.room_id = r.id
                     JOIN room_types rt ON r.room_type_id = rt.id 
                     $where_sql
                     ORDER BY b.created_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pending_reservations = 0;
$guests_on_property = 0;
foreach ($bookings as $b) {
    if ($b['status'] == 'confirmed')
        $pending_reservations++;
    if ($b['status'] == 'checked_in')
        $guests_on_property++;
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Reservation Records</h1>
                <p class="text-muted">Awaiting Arrival: <span
                        class="gold-text fw-bold"><?php echo $pending_reservations; ?></span> | On-Property: <span
                        class="text-success fw-bold"><?php echo $guests_on_property; ?></span></p>
            </div>
            <a href="inventory.php" class="btn btn-outline-gold">Room Inventory</a>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="kingsman-alert success mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle fs-4 me-3"></i>
                    <div>Reservation status updated. Notification sent to guest.</div>
                </div>
            </div>
        <?php endif; ?>

        <form method="GET" action="bookings.php" class="card kingsman-card glass-panel p-4 mb-4 border-0 shadow-lg">
            <h5 class="gold-text mb-3">Filter Reservations</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Arrival From</label>
                    <input type="date" name="date_from"
                        class="form-control form-control-sm bg-dark text-white border-gold"
                        value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Arrival To</label>
                    <input type="date" name="date_to"
                        class="form-control form-control-sm bg-dark text-white border-gold"
                        value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status_filter" class="form-select form-select-sm bg-dark text-white border-gold">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'checked_in') ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-kingsman btn-sm w-100 me-2 py-2">Apply Filters</button>
                    <a href="bookings.php" class="btn btn-outline-secondary btn-sm w-100 py-2">Clear</a>
                </div>
            </div>
        </form>

        <div class="card kingsman-card glass-panel p-4 border-0 shadow-lg">
            <div class="table-responsive">
                <table id="bookingsTable" class="table table-dark table-hover mb-0">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">Reference</th>
                            <th>Guest Identity</th>
                            <th>Suite Type</th>
                            <th>Arrival</th>
                            <th>Departure</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="ps-4 gold-text fw-bold">
                                    <?php if ($booking['status'] == 'confirmed'): ?>
                                        <span class="pulse-red d-inline-block me-2"
                                            style="width: 8px; height: 8px; border-radius: 50%;"></span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($booking['type_name']); ?></td>
                                <td class="small"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                                <td class="small"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'bg-gold';
                                    if ($booking['status'] == 'cancelled')
                                        $badge_class = 'bg-danger';
                                    if ($booking['status'] == 'checked_out')
                                        $badge_class = 'bg-secondary';
                                    if ($booking['status'] == 'checked_in')
                                        $badge_class = 'bg-success';
                                    ?>
                                    <span
                                        class="badge rounded-pill <?php echo $badge_class; ?> text-white font-weight-bold">
                                        <?php echo strtoupper($booking['status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="d-flex justify-content-end align-items-center gap-1">
                                        <a href="print_receipt.php?id=<?php echo $booking['id']; ?>" target="_blank"
                                            class="btn btn-outline-gold text-white btn-sm border-0 px-2"
                                            title="Print Strategic Receipt">
                                            <i class="bi bi-printer fs-6"></i>
                                        </a>
                                        <?php if (!in_array($booking['status'], ['cancelled', 'checked_out'])): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-outline-gold text-white btn-sm border-0 px-2"
                                                    type="button" data-bs-toggle="dropdown" title="Engagement Protocols">
                                                    <i class="bi bi-three-dots-vertical fs-6"></i>
                                                </button>
                                                <ul
                                                    class="dropdown-menu dropdown-menu-dark shadow-lg border-gold dropdown-menu-end">
                                                    <li class="dropdown-header small text-uppercase gold-text"
                                                        style="font-size: 0.6rem; letter-spacing: 1px;">Operational Protocols
                                                    </li>
                                                    <?php if ($booking['status'] == 'confirmed' || $booking['status'] == 'pending'): ?>
                                                        <li><a class="dropdown-item py-2 small d-flex align-items-center"
                                                                href="bookings.php?update_status=checked_in&id=<?php echo $booking['id']; ?>">
                                                                <i class="bi bi-box-arrow-in-right me-2 text-success"></i> INITIATE
                                                                CHECK-IN
                                                            </a></li>
                                                    <?php endif; ?>
                                                    <?php if ($booking['status'] == 'pending'): ?>
                                                        <li><a class="dropdown-item py-2 small d-flex align-items-center text-info"
                                                                href="edit_booking.php?id=<?php echo $booking['id']; ?>">
                                                                <i class="bi bi-pencil-square me-2"></i> MODIFY PARAMETERS
                                                            </a></li>
                                                    <?php endif; ?>
                                                    <?php if ($booking['status'] == 'checked_in'): ?>
                                                        <li><a class="dropdown-item py-2 small d-flex align-items-center text-warning"
                                                                href="bookings.php?update_status=checked_out&id=<?php echo $booking['id']; ?>">
                                                                <i class="bi bi-box-arrow-right me-2"></i> TERMINATE STAY (OUT)
                                                            </a></li>
                                                    <?php endif; ?>
                                                    <?php if ($booking['status'] != 'cancelled' && $booking['status'] != 'checked_out'): ?>
                                                        <li>
                                                            <hr class="dropdown-divider border-secondary opacity-25">
                                                        </li>
                                                        <li><a class="dropdown-item py-2 small d-flex align-items-center text-danger"
                                                                href="bookings.php?update_status=cancelled&id=<?php echo $booking['id']; ?>">
                                                                <i class="bi bi-x-circle me-2"></i> VOID RESERVATION
                                                            </a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No reservations active at the moment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- DataTables JS & CSS -->
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function () {
        $('#bookingsTable').DataTable({
            "order": [], // Leave default ordering or define specific logic
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search records..."
            },
            "pageLength": 10,
            "dom": "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6 text-end'f>>" +
                "<'row'<'col-sm-12'tr>>" +
                "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
        });

        // Add kingsman styling to datatable elements after initialization
        setTimeout(() => {
            $('.dataTables_filter input').addClass('form-control form-control-sm bg-dark text-white border-gold ms-2').removeClass('form-control-sm').css('display', 'inline-block').css('width', 'auto');
            $('.dataTables_length select').addClass('form-select form-select-sm bg-dark text-white border-gold mx-2').removeClass('form-select-sm').css('display', 'inline-block').css('width', 'auto');
        }, 100);
    });
</script>

<style>
    /* Custom datatables dark styling for Kingsman */
    .page-item.active .page-link {
        background-color: var(--primary-gold) !important;
        border-color: var(--primary-gold) !important;
        color: #000 !important;
    }

    .page-link {
        background-color: #1a1a1a;
        border-color: #333;
        color: var(--primary-gold);
    }

    .page-link:hover {
        background-color: #333;
        color: #fff;
        border-color: var(--primary-gold);
    }

    .dataTables_info,
    .dataTables_length,
    .dataTables_filter {
        color: #aaa !important;
    }
</style>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>