<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cms'])) {
    try {
        $pdo->beginTransaction();
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        $pdo->commit();
        $message = "Website settings updated successfully.";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Failed to update settings: " . $e->getMessage();
        $messageType = "danger";
    }
}

$settings = [];
$stmt = $pdo->query("SELECT * FROM site_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="mb-5">
            <h1 class="display-5">Content Management</h1>
            <p class="">Tailor the hotel landing page experience.</p>
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

        <div class="card kingsman-card glass-panel p-5 border-0 shadow-lg">
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h4 class="gold-text border-bottom border-gold pb-2 mb-4">Hero Section</h4>
                        <div class="mb-3">
                            <label class="form-label">Hero Title</label>
                            <input type="text" name="settings[hero_title]" class="form-control"
                                value="<?php echo htmlspecialchars($settings['hero_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hero Subtitle</label>
                            <textarea name="settings[hero_subtitle]" class="form-control"
                                rows="3"><?php echo htmlspecialchars($settings['hero_subtitle']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-12 mb-4">
                        <h4 class="gold-text border-bottom border-gold pb-2 mb-4">About Section</h4>
                        <div class="mb-3">
                            <label class="form-label">About Title</label>
                            <input type="text" name="settings[about_title]" class="form-control"
                                value="<?php echo htmlspecialchars($settings['about_title']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">About Text</label>
                            <textarea name="settings[about_text]" class="form-control"
                                rows="5"><?php echo htmlspecialchars($settings['about_text']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">About Image URL</label>
                            <input type="text" name="settings[about_img]" class="form-control"
                                value="<?php echo htmlspecialchars($settings['about_img']); ?>">
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" name="update_cms" class="btn btn-kingsman px-5 py-3">Publish Changes</button>
                    <a href="index.php" target="_blank" class="btn btn-outline-secondary ms-3 py-3">Preview Site</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>