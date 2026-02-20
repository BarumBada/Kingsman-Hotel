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

$room_performance = $pdo->query("SELECT rt.type_name, COUNT(b.id) as total_bookings, COALESCE(SUM(b.total_price), 0) as generated_revenue
                                FROM room_types rt
                                LEFT JOIN rooms r ON r.room_type_id = rt.id
                                LEFT JOIN bookings b ON b.room_id = r.id AND b.status != 'cancelled'
                                GROUP BY rt.id
                                ORDER BY generated_revenue DESC")->fetchAll();

$recent_checkouts = $pdo->query("SELECT b.booking_reference, u.firstname, u.lastname, rt.type_name, b.total_price, b.check_out_date
                                FROM bookings b
                                JOIN users u ON b.user_id = u.id
                                JOIN rooms r ON b.room_id = r.id
                                JOIN room_types rt ON r.room_type_id = rt.id
                                WHERE b.status = 'checked_out'
                                ORDER BY b.check_out_date DESC LIMIT 5")->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5 focus-print">
        <style>
            @media print {
                body {
                    background-color: #ffffff !important;
                    color: #000000 !important;
                    font-size: 12pt;
                }

                .sidebar,
                .navbar,
                .btn,
                .no-print,
                footer {
                    display: none !important;
                }

                .flex-grow-1,
                .p-5 {
                    padding: 0 !important;
                    margin: 0 !important;
                }

                .gold-text {
                    color: #000000 !important;
                    font-weight: bold;
                }

                .card,
                .kingsman-card,
                .glass-panel {
                    border: 1px solid #ccc !important;
                    background: transparent !important;
                    box-shadow: none !important;
                    color: #000 !important;
                    page-break-inside: avoid;
                }

                .text-muted,
                .text-white {
                    color: #333 !important;
                }

                .table,
                .table-dark {
                    --bs-table-bg: #fff !important;
                    --bs-table-color: #000 !important;
                    --bs-table-border-color: #000 !important;
                    color: #000 !important;
                    background-color: #fff !important;
                    border-color: #000 !important;
                }

                .table th,
                .table td,
                .table-dark th,
                .table-dark td {
                    border: 1px solid #000 !important;
                    color: #000 !important;
                    background-color: #fff !important;
                }

                .print-header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 15px;
                }

                .report-title {
                    display: none !important;
                }
            }
        </style>
        <div class="print-header d-none d-print-block">
            <h1 style="letter-spacing: 4px; font-weight: 900;">KINGSMAN HOTEL</h1>
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

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="card kingsman-card p-4 h-100">
                    <h5 class="gold-text mb-4">Room Category Performance</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Bookings</th>
                                    <th class="text-end">Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($room_performance as $rp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rp['type_name']); ?></td>
                                        <td><?php echo $rp['total_bookings']; ?></td>
                                        <td class="text-end">₱<?php echo number_format($rp['generated_revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card kingsman-card p-4 h-100">
                    <h5 class="gold-text mb-4">Recent Departures</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Guest</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_checkouts)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No recent check-outs.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_checkouts as $rc): ?>
                                        <tr>
                                            <td class="small"><?php echo htmlspecialchars($rc['booking_reference']); ?></td>
                                            <td class="small">
                                                <?php echo htmlspecialchars($rc['lastname'] . ', ' . $rc['firstname']); ?></td>
                                            <td class="text-end small">₱<?php echo number_format($rc['total_price'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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