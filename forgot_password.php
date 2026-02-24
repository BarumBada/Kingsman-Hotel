<?php
include 'includes/header.php';
require_once 'config/db.php';
date_default_timezone_set('Asia/Manila');

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

        require_once 'includes/mail_helper.php';
        $email_body = "<h3>Password Recovery</h3>
                      <p>You have requested to reset your password.</p>
                      <p>Please use the following recovery code to proceed:</p>
                      <div style='background: #111; padding: 20px; font-size: 24px; color: #c5a021; text-align: center; border: 1px solid #c5a021;'>
                        " . $otp . "
                      </div>
                      <p>This code will expire in 15 minutes.</p>";
        $branded_html = get_branded_template("Password Recovery", $email_body);
        send_kingsman_mail($email, "Your Password Recovery Code", $branded_html);

        $_SESSION['verify_email'] = $email;
        header("Location: verify.php?sent=1&type=reset");
        exit();
    } else {
        $message = "A verification code has been sent to your email address.";
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
                        <input type="email" name="email" class="form-control" required placeholder="guest@example.com">
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