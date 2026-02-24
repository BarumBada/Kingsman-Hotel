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

// === Stats Queries ===
$total_users = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_pending = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();
$total_confirmed = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn();
$total_checked_in = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'")->fetchColumn();
$total_checked_out = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_out'")->fetchColumn();
$total_cancelled = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE status NOT IN ('cancelled')")->fetchColumn();
$total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

// Revenue Performance Data (Last 6 Months)
$revenue_query = $pdo->query("SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(total_price) as revenue 
                             FROM bookings 
                             WHERE status NOT IN ('cancelled') 
                             GROUP BY month 
                             ORDER BY created_at ASC 
                             LIMIT 6");
$revenue_data = $revenue_query->fetchAll(PDO::FETCH_ASSOC);
$revenue_labels = json_encode(array_column($revenue_data, 'month'));
$revenue_values = json_encode(array_column($revenue_data, 'revenue'));

// Top Assets
$top_assets = $pdo->query("SELECT rt.type_name, COUNT(b.id) as bookings, SUM(b.total_price) as total_revenue
                            FROM room_types rt
                            JOIN rooms r ON rt.id = r.room_type_id
                            JOIN bookings b ON r.id = b.room_id
                            WHERE b.status != 'cancelled'
                            GROUP BY rt.id
                            ORDER BY total_revenue DESC
                            LIMIT 5")->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex" style="min-height: 100vh;">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <div class="p-4 p-lg-5 flex-grow-1">

            <!-- Welcome Header -->
            <div class="d-flex align-items-center mb-5 flex-wrap gap-3">
                <img src="<?php echo '../assets/img/' . ($admin_info['profile_image'] ? 'avatars/' . $admin_info['profile_image'] : 'arthur.jpg'); ?>"
                    alt="Admin" class="rounded-circle border border-gold shadow"
                    style="width: 72px; height: 72px; object-fit: cover;">
                <div class="flex-grow-1">
                    <h2 class="gold-text mb-0" style="letter-spacing: 2px;">Management Dashboard</h2>
                    <p class="text-muted small mb-0 mt-1">Welcome back,
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>. All systems operational.
                    </p>
                </div>
                <a href="reports.php" class="btn btn-kingsman btn-sm px-4">
                    <i class="bi bi-bar-chart me-2"></i>Reports
                </a>
            </div>

            <!-- Revenue Banner -->
            <div class="card glass-panel border-gold p-4 mb-4"
                style="border-radius: 6px; border-left: 4px solid var(--primary-gold) !important;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <p class="text-muted small text-uppercase mb-1" style="letter-spacing: 1.5px;">Total Revenue</p>
                        <h2 class="gold-text mb-0 display-6">₱<?php echo number_format($total_revenue, 2); ?></h2>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success bg-opacity-25 text-success px-3 py-2">
                            <i class="bi bi-graph-up-arrow me-1"></i> Live
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-5">
                <!-- Total Users -->
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <a href="/admin/guests.php" class="text-decoration-none d-block h-100">
                        <div class="card stat-card h-100 text-center p-4"
                            style="background-color:black;border:1 solid white">
                            <div class="stat-icon-wrap mx-auto">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <p class="stat-label">Total Guests</p>
                            <h3 class="stat-value"><?php echo $total_users; ?></h3>
                        </div>
                    </a>
                </div>
                <!-- Pending -->
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <a href="/admin/bookings.php?status_filter=pending" class="text-decoration-none d-block h-100">
                        <div class="card stat-card h-100 text-center p-4"
                            style="--stat-accent: #f39c12; background-color:black;border:1 solid white">
                            <div class="stat-icon-wrap mx-auto">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <p class="stat-label">Pending</p>
                            <h3 class="stat-value"><?php echo $total_pending; ?></h3>
                        </div>
                    </a>
                </div>
                <!-- Confirmed -->
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <a href="/admin/bookings.php?status_filter=confirmed" class="text-decoration-none d-block h-100">
                        <div class="card stat-card h-100 text-center p-4"
                            style="--stat-accent: #2ecc71;background-color:black;border:1 solid white">
                            <div class="stat-icon-wrap mx-auto">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <p class="stat-label">Confirmed</p>
                            <h3 class="stat-value"><?php echo $total_confirmed; ?></h3>
                        </div>
                    </a>
                </div>
                <!-- Checked In -->
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <a href="/admin/bookings.php?status_filter=checked_in" class="text-decoration-none d-block h-100">
                        <div class="card stat-card h-100 text-center p-4"
                            style="--stat-accent: #1abc9c;background-color:black;border:1 solid white">
                            <div class="stat-icon-wrap mx-auto">
                                <i class="bi bi-box-arrow-in-right"></i>
                            </div>
                            <p class="stat-label">Checked In</p>
                            <h3 class="stat-value"><?php echo $total_checked_in; ?></h3>
                        </div>
                    </a>
                </div>
                <!-- Checked Out -->
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <a href="/admin/bookings.php?status_filter=checked_out" class="text-decoration-none d-block h-100">
                        <div class="card stat-card h-100 text-center p-4"
                            style="--stat-accent: #95a5a6;background-color:black;border:1 solid white">
                            <div class="stat-icon-wrap mx-auto">
                                <i class="bi bi-box-arrow-right"></i>
                            </div>
                            <p class="stat-label">Checked Out</p>
                            <h3 class="stat-value"><?php echo $total_checked_out; ?></h3>
                        </div>
                    </a>
                </div>
                <!-- Cancelled -->
                <div class="col-sm-6 col-md-4 col-xl-2">
                    <a href="/admin/bookings.php?status_filter=cancelled" class="text-decoration-none d-block h-100">
                        <div class="card stat-card h-100 text-center p-4"
                            style="--stat-accent: #e74c3c;background-color:black;border:1 solid white">
                            <div class="stat-icon-wrap mx-auto">
                                <i class="bi bi-x-octagon-fill"></i>
                            </div>
                            <p class="stat-label">Cancelled</p>
                            <h3 class="stat-value"><?php echo $total_cancelled; ?></h3>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Data Grid (Charts & Lists) -->
            <div class="row g-4 mb-5">
                <!-- Revenue Chart -->
                <div class="col-lg-8">
                    <div class="card glass-panel border-gold p-4 h-100">
                        <h5 class="card-title text-white mb-4">
                            <i class="bi bi-graph-up me-2"></i>Revenue Performance
                        </h5>
                        <div style="height: 300px;">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>
                </div>
                <!-- Service Requests feed -->
                <div class="col-lg-4">
                    <div class="card glass-panel p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="gold-text mb-0" style="font-size: 1rem; letter-spacing: 1px;">
                                <i class="bi bi-gear-wide-connected me-2"></i>Provisioning
                            </h4>
                            <span class="badge bg-gold bg-opacity-25 text-warning small">PENDING</span>
                        </div>
                        <div style="max-height: 280px; overflow-y: auto;">
                            <?php
                            $req_stmt = $pdo->query("SELECT sr.*, u.firstname, u.lastname, b.booking_reference 
                                                   FROM service_requests sr
                                                   JOIN users u ON sr.user_id = u.id
                                                   JOIN bookings b ON sr.booking_id = b.id
                                                   WHERE sr.status = 'pending'
                                                   ORDER BY sr.created_at DESC LIMIT 5");
                            $requests = $req_stmt->fetchAll();

                            if (empty($requests)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-check fs-2 d-block mb-2 opacity-25"></i>
                                    <span class="small">No active requests detected.</span>
                                </div>
                            <?php else: ?>
                                <?php foreach ($requests as $req): ?>
                                    <div class="p-3 mb-2 rounded bg-dark bg-opacity-50 border-start border-gold border-3">
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span
                                                class="gold-text fw-bold text-uppercase"><?php echo htmlspecialchars($req['service_type']); ?></span>
                                            <span
                                                class="text-white-50"><?php echo htmlspecialchars($req['booking_reference']); ?></span>
                                        </div>
                                        <p class="small mb-1 text-white opacity-75">
                                            <?php echo htmlspecialchars($req['firstname'] . ' ' . $req['lastname']); ?>
                                        </p>
                                        <div class="d-flex justify-content-end">
                                            <button
                                                class="btn btn-link gold-text p-0 small text-decoration-none">ACKNOWLEDGE</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Asset Performance -->
            <div class="mb-5">
                <h4 class="gold-text mb-3"><i class="bi bi-gem me-2"></i>Tactical Asset Performance</h4>
                <div class="card glass-panel border-0 p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-dark table-hover mb-0">
                            <thead>
                                <tr class="text-muted small text-uppercase"
                                    style="font-size: 0.7rem; letter-spacing: 1.5px;">
                                    <th class="ps-4 py-3">Suite Category</th>
                                    <th class="py-3 text-center">Deployments</th>
                                    <th class="py-3 text-end pe-4">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_assets as $asset): ?>
                                    <tr>
                                        <td class="ps-4 py-3 gold-text"><?php echo htmlspecialchars($asset['type_name']); ?>
                                        </td>
                                        <td class="py-3 text-center"><?php echo $asset['bookings']; ?></td>
                                        <td class="py-3 text-end pe-4 fw-bold">
                                            ₱<?php echo number_format($asset['total_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- End of p-4 -->
    </div> <!-- End of flex-grow-1 -->
</div> <!-- End of d-flex -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo $revenue_labels; ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo $revenue_values; ?>,
                    borderColor: '#cda434',
                    backgroundColor: 'rgba(205, 164, 52, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#cda434',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#888', font: { size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#888', font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>