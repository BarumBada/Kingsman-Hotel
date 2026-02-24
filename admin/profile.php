<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: ../logout.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $profile_image = $user['profile_image'];

    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = "admin_" . $user_id . "_" . time() . "." . $ext;
            $upload_path = dirname(__DIR__) . "/assets/img/avatars/" . $new_name;

            if (!is_dir(dirname($upload_path))) {
                mkdir(dirname($upload_path), 0777, true);
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $profile_image = $new_name;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([$firstname, $lastname, $email, $phone, $profile_image, $user_id]);
        $_SESSION['full_name'] = $firstname . ' ' . $lastname;
        $message = "Operational credentials and profile updated.";
        $messageType = "success";

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "Update failed: " . $e->getMessage();
        $messageType = "danger";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $message = "Security settings updated. New password established.";
            $messageType = "success";
        } else {
            $message = "Password mismatch. Aborting update.";
            $messageType = "danger";
        }
    } else {
        $message = "Current security credential incorrect. Access denied.";
        $messageType = "danger";
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="mb-5">
            <h1 class="display-5 gold-text text-uppercase" style="letter-spacing: 2px;">Profile Management</h1>
            <p class="text-muted">Manage your administrative identity and security clearance.</p>
        </div>

        <?php if ($message): ?>
            <div class="kingsman-alert <?php echo ($messageType == 'success') ? 'success' : 'error'; ?> mb-4">
                <i
                    class="bi <?php echo ($messageType == 'success') ? 'bi-shield-check' : 'bi-exclamation-octagon'; ?> fs-4 me-3"></i>
                <div>
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-md-5">
                <div class="card kingsman-card p-5 glass-panel border-0 shadow-lg h-100">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="text-center mb-5">
                            <div class="position-relative d-inline-block">
                                <img src="<?php echo '../assets/img/' . ($user['profile_image'] ? 'avatars/' . $user['profile_image'] : 'arthur.jpg'); ?>"
                                    alt="Admin Profile" class="rounded-circle border border-gold shadow-lg"
                                    style="width: 150px; height: 150px; object-fit: cover;">
                                <div class="position-absolute bottom-0 end-0">
                                    <label for="avatar-upload" class="btn btn-kingsman btn-sm p-2 rounded-circle shadow"
                                        style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-camera-fill"></i>
                                    </label>
                                </div>
                            </div>
                            <input type="file" id="avatar-upload" name="avatar" class="d-none" accept="image/*">
                            <h4 class="mt-4 gold-text mb-1">
                                <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                            </h4>
                            <span class="badge bg-gold text-dark px-3 py-1 mt-1">LEVEL: ADMINISTRATOR</span>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small gold-text text-uppercase" style="letter-spacing: 1px;">First
                                Name</label>
                            <input type="text" name="firstname"
                                class="form-control bg-dark text-white border-secondary py-2"
                                value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small gold-text text-uppercase" style="letter-spacing: 1px;">Last
                                Name</label>
                            <input type="text" name="lastname"
                                class="form-control bg-dark text-white border-secondary py-2"
                                value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small gold-text text-uppercase"
                                style="letter-spacing: 1px;">Official Email</label>
                            <input type="email" name="email"
                                class="form-control bg-dark text-white border-secondary py-2"
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small gold-text text-uppercase"
                                style="letter-spacing: 1px;">Contact Number</label>
                            <input type="text" name="phone"
                                class="form-control bg-dark text-white border-secondary py-2"
                                value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile"
                            class="btn btn-kingsman w-100 py-3 mt-3 fw-bold text-uppercase">Update Personal
                            Update Settings</button>
                    </form>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card kingsman-card p-5 glass-panel border-0 shadow-lg h-100">
                    <h4 class="gold-text mb-4 text-uppercase fw-bold" style="letter-spacing: 2px;">Security Access Layer
                    </h4>
                    <p class="text-muted small mb-5">Modify your operational password to maintain high-level security
                        integrity.</p>

                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="form-label small gold-text text-uppercase"
                                style="letter-spacing: 1px;">Current Identity Key (Password)</label>
                            <input type="password" name="current_password"
                                class="form-control bg-dark text-white border-secondary py-2" required>
                        </div>
                        <div class="row mb-5">
                            <div class="col-md-6 mb-4 mb-md-0">
                                <label class="form-label small gold-text text-uppercase"
                                    style="letter-spacing: 1px;">New Identity Key</label>
                                <input type="password" name="new_password"
                                    class="form-control bg-dark text-white border-secondary py-2" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small gold-text text-uppercase"
                                    style="letter-spacing: 1px;">Confirm New Key</label>
                                <input type="password" name="confirm_password"
                                    class="form-control bg-dark text-white border-secondary py-2" required>
                            </div>
                        </div>

                        <div class="bg-dark p-4 rounded border border-secondary mb-4">
                            <div class="d-flex">
                                <i class="bi bi-info-circle gold-text fs-3 me-3"></i>
                                <div>
                                    <h6 class="gold-text mb-1">Security Standard</h6>
                                    <p class="small text-muted mb-0">Identity keys should be complex and unique.
                                        Operational efficiency depends on discrete security measures.</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="change_password"
                            class="btn btn-outline-danger w-100 py-3 fw-bold text-uppercase"
                            style="letter-spacing: 2px;">Update Security Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>