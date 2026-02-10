<?php
include 'includes/header.php';
require_once 'config/db.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $message = "Passwords do not match.";
        $messageType = "danger";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $email = $_SESSION['reset_email'];

        $stmt = $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
        $stmt->execute([$hashed, $email]);

        $message = "Password updated successfully. You may now login.";
        $messageType = "success";
        unset($_SESSION['reset_email']);
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card kingsman-card p-5">
                <div class="text-center mb-4">
                    <h2 class="gold-text">Set New Password</h2>
                    <p class="">Enter your new secure password below.</p>
                </div>

                <?php if ($message): ?>
                    <div class="kingsman-alert <?php echo $messageType; ?> mb-4">
                        <div class="d-flex align-items-center">
                            <i
                                class="bi <?php echo $messageType == 'success' ? 'bi-shield-check' : 'bi-exclamation-octagon'; ?> fs-4 me-3"></i>
                            <div>
                                <?php echo $message; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($messageType != 'success'): ?>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-kingsman w-100 py-3">Update Password</button>
                    </form>
                <?php else: ?>
                    <div class="text-center">
                        <a href="login.php" class="btn btn-kingsman w-100 py-3">Login to Dashboard</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>