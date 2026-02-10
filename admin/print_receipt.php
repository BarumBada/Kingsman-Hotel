<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    die("Invalid request selection.");
}
$stmt = $pdo->prepare("SELECT b.*, u.firstname, u.lastname, u.email, rt.type_name, rt.price_per_night 
                     FROM bookings b 
                     JOIN users u ON b.user_id = u.id 
                     JOIN rooms r ON b.room_id = r.id
                     JOIN room_types rt ON r.room_type_id = rt.id
                     WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    die("Record not found.");
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Receipt -
        <?php echo $booking['booking_reference']; ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: white;
            color: black;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        .receipt-header {
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .receipt-header h1 {
            letter-spacing: 5px;
            text-transform: uppercase;
            margin: 0;
        }

        .receipt-header p {
            font-size: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }

        .gold-border {
            border: 1px solid #000;
            padding: 20px;
        }

        .table-receipt th {
            border-bottom: 1px solid #000;
            padding: 10px 0;
            text-align: left;
            text-transform: uppercase;
            font-size: 12px;
        }

        .table-receipt td {
            padding: 10px 0;
            font-size: 14px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print();">

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="receipt-header">
                    <h1>KINGSMAN</h1>
                    <p>Operational Performance Brief - Folio</p>
                </div>

                <div class="d-flex justify-content-between mb-4">
                    <div>
                        <h5 class="text-uppercase fw-bold">Guest Identity</h5>
                        <p class="mb-0">
                            <?php echo htmlspecialchars($booking['firstname'] . ' ' . $booking['lastname']); ?>
                        </p>
                        <p class="mb-0">
                            <?php echo htmlspecialchars($booking['email']); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <h5 class="text-uppercase fw-bold">Operational Record</h5>
                        <p class="mb-0">Folio: <strong>
                                <?php echo $booking['booking_reference']; ?>
                            </strong></p>
                        <p class="mb-0">Report Date:
                            <?php echo date('Y-m-d'); ?>
                        </p>
                        <p class="mb-0">Status: <span class="text-uppercase fw-bold">
                                <?php echo $booking['status']; ?>
                            </span></p>
                    </div>
                </div>

                <table class="table table-receipt w-100 mb-5">
                    <thead>
                        <tr>
                            <th>Operational Description</th>
                            <th class="text-center">Nightly Rate</th>
                            <th class="text-end">Total Yield</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong>
                                    <?php echo htmlspecialchars($booking['type_name']); ?>
                                </strong><br>
                                <small class="text-muted">Stay:
                                    <?php echo $booking['check_in_date']; ?> to
                                    <?php echo $booking['check_out_date']; ?>
                                </small>
                            </td>
                            <td class="text-center">₱
                                <?php echo number_format($booking['price_per_night'], 2); ?>
                            </td>
                            <td class="text-end">₱
                                <?php echo number_format($booking['total_price'], 2); ?>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="text-end pt-4"><strong>Final Settlement</strong></td>
                            <td class="text-end pt-4"><strong>₱
                                    <?php echo number_format($booking['total_price'], 2); ?>
                                </strong></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-center mt-5 small text-muted">
                    <p>System Generated Document | Kingsman Hotel Management</p>
                    <div class="no-print mt-4">
                        <button onclick="window.print()" class="btn btn-dark px-5">Physical Print</button>
                        <button onclick="window.close()" class="btn btn-outline-secondary px-3 ms-2">Exit Folio</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>