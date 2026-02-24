<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once 'config/db.php';
include 'includes/header.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['account_status'] == 'blocked') {
            $message = "Access Denied: Your account has been suspended by management.";
            $messageType = "danger";
        } elseif ($user['is_verified'] == 0) {
            $_SESSION['verify_email'] = $email;
            header("Location: verify.php?msg=unverified");
            exit();
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['firstname'] . ' ' . $user['lastname'];
            $_SESSION['user_email'] = $user['email'];

            header("Location: dashboard.php");
            exit();
        }
    } else {
        $message = "Invalid email or password.";
        $messageType = "danger";
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card kingsman-card p-4">
                <div class="text-center mb-4">
                    <h2 class="gold-text">Welcome Back</h2>
                    <p class="">Login to your account.</p>
                </div>

                <?php if ($message): ?>
                    <div class="kingsman-alert danger mb-4">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-octagon fs-4 me-3"></i>
                            <div><?php echo $message; ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="guest@example.com" required>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between">
                            <label class="form-label">Password</label>
                            <a href="forgot_password.php" class="small gold-text text-decoration-none">Forgot
                                Password?</a>
                        </div>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-kingsman w-100 py-3">Login</button>
                    <div class="text-center mt-4">
                        <p class="">Don't have an account? <a href="register.php"
                                class="gold-text text-decoration-none">Create an account</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>