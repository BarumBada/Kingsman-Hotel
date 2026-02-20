<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: messages.php?msg=deleted");
    exit();
}

$stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="mb-5">
            <h1 class="display-5">Guest Inquiries</h1>
            <p class="text-muted">Reviewing guest inquiries and contact messages.</p>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="kingsman-alert success mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-shield-check fs-4 me-3"></i>
                    <div>Message deleted from active records.</div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($messages)): ?>
                <div class="col-12">
                    <div class="card kingsman-card p-5 text-center text-muted">
                        <i class="bi bi-envelope-open fs-1 d-block mb-3"></i>
                        No new messages received.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="col-md-6">
                        <div class="card kingsman-card p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="gold-text mb-1">
                                        <?php echo htmlspecialchars($msg['subject']); ?>
                                    </h5>
                                    <p class="small text-muted mb-0">From:
                                        <?php echo htmlspecialchars($msg['name']); ?> (
                                        <?php echo htmlspecialchars($msg['email']); ?>)
                                    </p>
                                </div>
                                <span class="small text-muted">
                                    <?php echo date('M d, H:i', strtotime($msg['created_at'])); ?>
                                </span>
                            </div>
                            <div class="bg-dark p-3 rounded mb-3"
                                style="font-size: 0.9rem; border-left: 2px solid var(--primary-gold);">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                            <div class="d-flex justify-content-end">
                                <a href="messages.php?delete=<?php echo $msg['id']; ?>"
                                    class="btn btn-outline-danger btn-sm px-2 border-0"
                                    onclick="return confirm('Classified: Purge this inquiry from records?')"
                                    title="Purge Intel">
                                    <i class="bi bi-trash fs-5"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>