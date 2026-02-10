<?php
session_start();
if (!isset($_SESSION['user_id'])) {
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
    $middlename = $_POST['middlename'];
    $lastname = $_POST['lastname'];
    $phone = $_POST['phone'];
    $profile_image = $user['profile_image'];
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['avatar']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = "guest_" . $user_id . "_" . time() . "." . $ext;
            $upload_path = dirname(__DIR__) . "/assets/img/avatars/" . $new_name;

            if (!is_dir(dirname($upload_path))) {
                if (!mkdir(dirname($upload_path), 0777, true)) {
                    $error_log[] = "Failed to create guest image directory.";
                }
            }

            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                $profile_image = $new_name;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET firstname = ?, middlename = ?, lastname = ?, phone = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([$firstname, $middlename, $lastname, $phone, $profile_image, $user_id]);
        $_SESSION['full_name'] = $firstname . ' ' . $lastname;
        $message = "Your profile information has been updated.";
        $messageType = "success";

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        $message = "System error: " . $e->getMessage();
        $messageType = "danger";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);
            $message = "Security settings updated successfully.";
            $messageType = "success";
        } else {
            $message = "Passwords do not match.";
            $messageType = "danger";
        }
    } else {
        $message = "Current password incorrect.";
        $messageType = "danger";
    }
}


include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php
    $is_admin_path = false;
    include dirname(__DIR__) . '/admin/includes/sidebar.php';
    ?>

    <div class="flex-grow-1 p-5">
        <div class="mb-5">
            <h1 class="display-5 gold-text">Account & Security Preferences</h1>
            <p class="text-muted">Manage your personal information and security settings.</p>
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

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card kingsman-card p-4">
                    <h4 class="gold-text mb-4">Personal Details</h4>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <img src="<?php echo '../assets/img/' . ($user['profile_image'] ? 'avatars/' . $user['profile_image'] : 'room_placeholder.jpg'); ?>"
                                    alt="Guest Profile" class="rounded-circle border border-gold"
                                    style="width: 120px; height: 120px; object-fit: cover;">
                                <div class="position-absolute bottom-0 end-0">
                                    <label for="avatar-upload" class="btn btn-kingsman btn-sm p-1 rounded-circle"
                                        style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-camera"></i>
                                    </label>
                                </div>
                            </div>
                            <input type="file" id="avatar-upload" name="avatar" class="d-none" accept="image/*">
                            <p class="small text-muted mt-2">Update Profile Photo</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="firstname" class="form-control"
                                value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middlename" class="form-control"
                                value="<?php echo htmlspecialchars($user['middlename']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastname" class="form-control"
                                value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-kingsman w-100 mt-3">Update
                            Details</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card kingsman-card p-4">
                    <h4 class="gold-text mb-4">Security Preferences</h4>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-outline-danger w-100 mt-3"
                            style="border-radius: 0; letter-spacing: 2px;">UPDATE SECURITY</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>