<?php
include 'includes/header.php';
require_once 'config/db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $content = $_POST['message'];

    try {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $content]);
        $message = "Thank you for your message. Our guest relations team will review it and contact you shortly.";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Transmission Failed: " . $e->getMessage();
        $messageType = "danger";
    }
}
?>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="text-center mb-5">
                <h1 class="display-4">Guest Relations</h1>
                <p class="text-muted">Professional inquiries and bespoke requests.</p>
                <div style="width: 80px; height: 3px; background-color: var(--primary-gold); margin: 20px auto;"></div>
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

            <div class="card kingsman-card p-5">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter your name">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Inquiry Subject</label>
                        <input type="text" name="subject" class="form-control" required placeholder="Bespoke Request">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Your Message</label>
                        <textarea name="message" class="form-control" rows="5" required
                            placeholder="How can we assist you?"></textarea>
                    </div>
                    <button type="submit" class="btn btn-kingsman w-100 py-3">Send Message</button>
                </form>
            </div>

            <div class="mt-5 text-center">
                <p class="small text-muted mb-2">Our Flagship Location</p>
                <h5 class="gold-text">Savile Row, London</h5>
                <p class="small">Strictly by appointment only.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>