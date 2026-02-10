<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once 'config/db.php';
require_once 'includes/mail_helper.php';
include 'includes/header.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'] ?? null;
    $lastname = $_POST['lastname'];
    $suffixname = $_POST['suffixname'] ?? null;
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];

    try {
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $stmt = $pdo->prepare("INSERT INTO users (firstname, middlename, lastname, suffixname, email, password, phone, otp_code, otp_expiry, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->execute([$firstname, $middlename, $lastname, $suffixname, $email, $password, $phone, $otp, $expiry]);

        $_SESSION['verify_email'] = $email;

        $email_body = "<h3>Verify Your Account</h3>
                      <p>Thank you for choosing Kingsman Hotel.</p>
                      <p>To finalize your account registration, please use the following verification code:</p>
                      <div style='background: #111; padding: 20px; font-size: 24px; color: #c5a021; text-align: center; border: 1px solid #c5a021;'>
                        " . $otp . "
                      </div>
                      <p>This code will expire in 10 minutes.</p>";
        $branded_html = get_branded_template("Account Verification", $email_body);
        send_kingsman_mail($firstname . ' ' . $lastname, "Your Verification Code", $branded_html);

        header("Location: verify.php?simulated_otp=" . $otp);
        exit();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $message = "Email already exists.";
        } else {
            $message = "Error: " . $e->getMessage();
        }
        $messageType = "danger";
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card kingsman-card p-4">
                <div class="text-center mb-4">
                    <h2 class="gold-text">Create Guest Account</h2>
                    <p class="">Register for a bespoke hospitality experience.</p>
                </div>

                <?php if ($message): ?>
                    <div class="kingsman-alert <?php echo $messageType; ?> mb-4">
                        <div class="d-flex align-items-center">
                            <i
                                class="bi <?php echo $messageType == 'success' ? 'bi-shield-check' : 'bi-exclamation-octagon'; ?> fs-4 me-3"></i>
                            <div><?php echo $message; ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="firstname" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middlename" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastname" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Suffix</label>
                            <input type="text" name="suffixname" class="form-control">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-kingsman w-100 py-3">Create Account</button>
                    <div class="text-center mt-4">
                        <p class="">Already a member? <a href="login.php" class="gold-text text-decoration-none">Login
                                to your account</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>