<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';
$total_revenue = $pdo->query("SELECT SUM(total_price) FROM bookings WHERE status != 'cancelled'")->fetchColumn() ?? 0;
$total_reservations = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status != 'cancelled'")->fetchColumn();
$active_guests = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM bookings WHERE status IN ('confirmed', 'checked_in')")->fetchColumn();
$total_suites = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupied_suites = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'checked_in'")->fetchColumn();
$occupancy_rate = $total_suites > 0 ? ($occupied_suites / $total_suites) * 100 : 0;

$avg_stay = $pdo->query("SELECT AVG(DATEDIFF(check_out_date, check_in_date)) FROM bookings WHERE status != 'cancelled'")->fetchColumn() ?? 0;
$most_booked_room = $pdo->query("SELECT rt.type_name, COUNT(b.id) as count 
                                FROM bookings b 
                                JOIN rooms r ON b.room_id = r.id
                                JOIN room_types rt ON r.room_type_id = rt.id 
                                GROUP BY rt.id 
                                ORDER BY count DESC LIMIT 1")->fetch();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5 focus-print">
        <div class="print-header d-none">
            <h1>KINGSMAN HOTEL</h1>
            <p>Operational Performance Summary</p>
            <div class="text-end small">Generated: <?php echo date('Y-m-d H:i'); ?> | System Generated</div>
        </div>

        <div class="mb-5 report-title">
            <h1 class="display-5">Operational Performance Report</h1>
            <p class="text-muted">Analyzing property metrics for optimal service delivery.</p>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card kingsman-card p-4">
                    <h5 class="text-muted small text-uppercase">Gross Revenue</h5>
                    <h2 class="gold-text">₱<?php echo number_format($total_revenue, 2); ?></h2>
                    <p class="small text-success mb-0"><i class="bi bi-graph-up-arrow me-1"></i> +12.5% vs Q1</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kingsman-card p-4">
                    <h5 class="text-muted small text-uppercase">Hotel Occupancy</h5>
                    <h2 class="gold-text"><?php echo round($occupancy_rate, 1); ?>%</h2>
                    <p class="small text-muted mb-0"><?php echo $occupied_suites; ?>/<?php echo $total_suites; ?> suites
                        occupied.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kingsman-card p-4">
                    <h5 class="text-muted small text-uppercase">Total Reservations</h5>
                    <h2 class="gold-text"><?php echo $total_reservations; ?></h2>
                    <p class="small text-muted mb-0"><?php echo $active_guests; ?> guests currently on-property.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kingsman-card p-4">
                    <h5 class="text-muted small text-uppercase">Avg. Stay Duration</h5>
                    <h2 class="gold-text"><?php echo round($avg_stay, 1); ?> Days</h2>
                    <p class="small text-muted mb-0">Within industry standards.</p>
                </div>
            </div>
        </div>

        <div class="card kingsman-card p-5 text-center no-print border-0">
            <i class="bi bi-printer gold-text mb-4" style="font-size: 3rem;"></i>
            <h3>Print Report</h3>
            <p class="text-muted mb-4">Generate a high-fidelity physical report for management review.
            </p>
            <button class="btn btn-kingsman px-5 py-3" onclick="window.print()">Print Report</button>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>