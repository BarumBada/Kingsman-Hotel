<?php
include 'includes/header.php';
require_once 'config/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $update = $pdo->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE email = ?");
        $update->execute([$otp, $expiry, $email]);

        $_SESSION['verify_email'] = $email;
        header("Location: verify.php?simulated_otp=" . $otp . "&type=reset");
        exit();
    } else {
        $message = "If this email exists in our system, a recovery code has been sent.";
        $messageType = "info";
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card kingsman-card p-5">
                <div class="text-center mb-4">
                    <h2 class="gold-text">Password Recovery</h2>
                    <p class="">Enter your email to reset your account password.</p>
                </div>

                <?php if ($message): ?>
                    <div class="kingsman-alert <?php echo $messageType; ?> mb-4">
                        <div class="d-flex align-items-center">
                            <i
                                class="bi <?php echo $messageType == 'info' ? 'bi-info-circle' : 'bi-exclamation-octagon'; ?> fs-4 me-3"></i>
                            <div>
                                <?php echo $message; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="agent@kingsman.com">
                    </div>
                    <button type="submit" class="btn btn-kingsman w-100 py-3">Send Recovery Code</button>
                    <div class="text-center mt-4">
                        <a href="login.php" class="gold-text text-decoration-none">Return to Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>