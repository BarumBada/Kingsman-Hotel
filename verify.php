<?php
include 'includes/header.php';
require_once 'config/db.php';
date_default_timezone_set('Asia/Manila');

$message = '';
$messageType = '';

if (isset($_GET['sent']) && $_GET['sent'] == 1) {
    $message = "A verification code has been sent to your email address.";
    $messageType = "info";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? ($_SESSION['verify_email'] ?? '');
    $code = $_POST['otp_code'];
    $now = date('Y-m-d H:i:s');

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND otp_code = ? AND otp_expiry > ?");
    $stmt->execute([$email, $code, $now]);
    $user = $stmt->fetch();

    if ($user) {
        $update = $pdo->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expiry = NULL WHERE id = ?");
        $update->execute([$user['id']]);

        if (isset($_GET['type']) && $_GET['type'] == 'reset') {
            $_SESSION['reset_email'] = $email;
            header("Location: reset_password.php");
            exit();
        }

        $message = "Verification successful. Your account has been activated. You may now log in.";
        $messageType = "success";
        unset($_SESSION['verify_email']);
    } else {
        $message = "Verification failed: Invalid or expired activation code.";
        $messageType = "danger";
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card kingsman-card p-5">
                <div class="text-center mb-4">
                    <h2 class="gold-text">Account Verification</h2>
                    <p class="">Enter your 6-digit verification code below.</p>
                </div>

                <?php if ($message): ?>
                    <div class="kingsman-alert <?php echo $messageType; ?> mb-4">
                        <div class="d-flex align-items-center">
                            <i
                                class="bi <?php echo $messageType == 'success' ? 'bi-shield-check' : ($messageType == 'info' ? 'bi-info-circle' : 'bi-exclamation-octagon'); ?> fs-4 me-3"></i>
                            <div>
                                <?php echo $message; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">Verification Code</label>
                        <input type="text" name="otp_code" class="form-control text-center fs-2 fw-bold" maxlength="6"
                            pattern="\d{6}" required placeholder="000000" style="letter-spacing: 10px;">
                    </div>
                    <button type="submit" class="btn btn-kingsman w-100 py-3">Verify Account</button>

                    <?php if ($messageType == 'success'): ?>
                        <div class="text-center mt-4">
                            <a href="login.php" class="btn btn-outline-gold w-100">Proceed to Login</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>