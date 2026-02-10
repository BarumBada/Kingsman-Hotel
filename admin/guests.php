<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = $_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    header("Location: guests.php?msg=status_updated");
    exit();
}

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
$guests = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="mb-5">
            <h1 class="display-5">Guest Management</h1>
            <p class="text-muted">A comprehensive overview of all registered guests and members.</p>
        </div>

        <div class="card kingsman-card glass-panel p-4 border-0 shadow-lg">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">Guest Name</th>
                            <th>Contact Email</th>
                            <th>Phone Number</th>
                            <th>Account Status</th>
                            <th class="pe-4 text-end">Management Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($guests as $guest): ?>
                            <tr>
                                <td class="ps-4 fw-bold gold-text">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo '../assets/img/' . ($guest['profile_image'] ? 'avatars/' . $guest['profile_image'] : 'galahad.jpg'); ?>"
                                            class="rounded-circle border border-gold me-3"
                                            style="width: 40px; height: 40px; object-fit: cover;">
                                        <div>
                                            <?php echo htmlspecialchars($guest['firstname'] . ' ' . $guest['lastname']); ?>
                                            <div class="small text-muted" style="font-size: 0.65rem;">ID:
                                                #<?php echo str_pad($guest['id'], 5, '0', STR_PAD_LEFT); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small"><?php echo htmlspecialchars($guest['email']); ?></td>
                                <td class="small"><?php echo htmlspecialchars($guest['phone']); ?></td>
                                <td>
                                    <span
                                        class="badge rounded-pill bg-<?php echo $guest['account_status'] == 'active' ? 'success' : 'danger'; ?> text-white font-weight-bold">
                                        <?php echo strtoupper($guest['account_status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($guest['account_status'] == 'active'): ?>
                                        <a href="guests.php?toggle_status=blocked&id=<?php echo $guest['id']; ?>"
                                            class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size: 0.7rem;"
                                            onclick="return confirm('Suspend this guest account?')">SUSPEND</a>
                                    <?php else: ?>
                                        <a href="guests.php?toggle_status=active&id=<?php echo $guest['id']; ?>"
                                            class="btn btn-outline-success btn-sm py-0 px-2"
                                            style="font-size: 0.7rem;">ACTIVATE</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>