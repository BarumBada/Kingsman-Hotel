<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_info = $stmt->fetch();

$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$total_bookings_pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('pending', 'confirmed')")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status != 'cancelled'")->fetchColumn() ?? 0;

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="card kingsman-card glass-panel p-4 mb-5 border-0">
            <div class="d-flex align-items-center">
                <img src="<?php echo '../assets/img/' . ($admin_info['profile_image'] ? 'avatars/' . $admin_info['profile_image'] : 'arthur.jpg'); ?>"
                    alt="Arthur" class="rounded-circle border border-gold me-4 shadow"
                    style="width: 100px; height: 100px; object-fit: cover;">
                <div>
                    <h1 class="display-5 mb-1 gold-text">Management Dashboard</h1>
                    <p class="lead text-muted mb-0">Welcome back. Property operations are nominal.</p>
                </div>
                <div class="ms-auto">
                    <a href="reports.php" class="btn btn-kingsman btn-lg px-4">Operational Reports</a>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="card kingsman-card text-center p-4 glass-panel border-0 shadow">
                    <i class="bi bi-people gold-text mb-3 fs-3"></i>
                    <h5 class="text-muted small text-uppercase mb-1">Total Guests</h5>
                    <h2 class="gold-text mb-0"><?php echo $total_users; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kingsman-card text-center p-4 glass-panel border-0 shadow">
                    <i class="bi bi-door-open gold-text mb-3 fs-3"></i>
                    <h5 class="text-muted small text-uppercase mb-1">Inventory</h5>
                    <h2 class="gold-text mb-0"><?php echo $total_rooms; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kingsman-card text-center p-4 glass-panel border-0 shadow">
                    <i class="bi bi-calendar-check gold-text mb-3 fs-3"></i>
                    <h5 class="text-muted small text-uppercase mb-1">Pending Requests</h5>
                    <h2 class="gold-text mb-0"><?php echo $total_bookings_pending; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kingsman-card text-center p-4 glass-panel border-0 shadow">
                    <i class="bi bi-currency-dollar gold-text mb-3 fs-3"></i>
                    <h5 class="text-muted small text-uppercase mb-1">Total Revenue</h5>
                    <h2 class="gold-text mb-0">₱<?php echo number_format($total_revenue, 0); ?></h2>
                </div>
            </div>
        </div>

        <div class="mt-5">
            <h3 class="mb-4">Recent Activity</h3>
            <div class="table-responsive">
                <table class="table table-kingsman">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Guest</th>
                            <th>Suite Type</th>
                            <th>Arrival Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_stmt = $pdo->query("SELECT b.*, u.firstname, u.lastname, rt.type_name 
                                                   FROM bookings b 
                                                   JOIN users u ON b.user_id = u.id 
                                                   JOIN rooms r ON b.room_id = r.id
                                                   JOIN room_types rt ON r.room_type_id = rt.id 
                                                   ORDER BY b.created_at DESC 
                                                    LIMIT 5");
                        $recent_ops = $recent_stmt->fetchAll();

                        if (empty($recent_ops)):
                            ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-3"></i>
                                    System status nominal. No active bookings detected.
                                </td>
                            </tr>
                        <?php else:
                            foreach ($recent_ops as $op):
                                ?>
                                <tr>
                                    <td class="gold-text fw-bold"><?php echo htmlspecialchars($op['booking_reference']); ?></td>
                                    <td><?php echo htmlspecialchars($op['firstname'] . ' ' . $op['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($op['type_name']); ?></td>
                                    <td class="small"><?php echo date('M d, Y', strtotime($op['check_in_date'])); ?></td>
                                    <td>
                                        <span class="badge rounded-pill bg-<?php
                                        echo ($op['status'] == 'confirmed') ? 'success' :
                                            (($op['status'] == 'pending') ? 'warning' :
                                                (($op['status'] == 'checked_in') ? 'primary' : 'secondary'));
                                        ?> opacity-75">
                                            <?php echo strtoupper($op['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>