<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once dirname(__DIR__) . '/config/db.php';

$msg = $_GET['msg'] ?? '';
$error = '';

// Handle Toggle Account Status (Blocking/Activate)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $new_status = $_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    header("Location: guests.php?msg=status_updated");
    exit();
}

// Handle Delete Guest
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
    if ($stmt->execute([$id])) {
        header("Location: guests.php?msg=deleted");
    } else {
        header("Location: guests.php?msg=error");
    }
    exit();
}

// Handle Add/Edit Guest
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if (isset($_POST['add_guest'])) {
        $password = password_hash($_POST['password'] ?: 'password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, password, phone, role, account_status, is_verified) VALUES (?, ?, ?, ?, ?, 'user', 'active', 1)");
        if ($stmt->execute([$firstname, $lastname, $email, $password, $phone])) {
            header("Location: guests.php?msg=added");
        } else {
            $error = "Email might already be in use.";
        }
    } elseif (isset($_POST['edit_guest'])) {
        $id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$firstname, $lastname, $email, $phone, $id])) {
            header("Location: guests.php?msg=updated");
        } else {
            $error = "Update failed. Check email uniqueness.";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
$guests = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Guest Management</h1>
                <p class="text-muted">Direct control over the hotel's guest registry.</p>
            </div>
            <button class="btn btn-kingsman px-4" data-bs-toggle="modal" data-bs-target="#addGuestModal">
                <i class="bi bi-person-plus-fill me-2"></i> Add New Guest
            </button>
        </div>

        <?php if ($msg == 'added'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-check-circle-fill me-2"></i> New guest
                added successfully.</div>
        <?php elseif ($msg == 'updated'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-check-circle-fill me-2"></i> Guest profile updated.
            </div>
        <?php elseif ($msg == 'deleted'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-trash-fill me-2"></i> Guest record deleted from
                registry.</div>
        <?php elseif ($msg == 'status_updated'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-shield-lock-fill me-2"></i> Guest access status
                modified.</div>
        <?php elseif ($error): ?>
            <div class="kingsman-alert error mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card kingsman-card glass-panel p-4 border-0 shadow-lg">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0" id="guestsTable">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">Guest Name</th>
                            <th>Contact Details</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;
                        foreach ($guests as $guest):
                            $reveal_class = "reveal reveal-" . min($i, 5);
                            $i++;
                            ?>
                            <tr class="<?php echo $reveal_class; ?>">
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo '../assets/img/' . ($guest['profile_image'] ? 'avatars/' . $guest['profile_image'] : 'galahad.jpg'); ?>"
                                            class="rounded-circle border border-gold p-1 me-3 shadow-sm"
                                            style="width: 48px; height: 48px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold gold-text" style="letter-spacing: 0.5px;">
                                                <?php echo htmlspecialchars($guest['firstname'] . ' ' . $guest['lastname']); ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 0.65rem; font-weight: 500;">
                                                UID: <span
                                                    class="text-white-50">#<?php echo str_pad($guest['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small mb-1"><i
                                            class="bi bi-envelope-at me-2 opacity-50"></i><?php echo htmlspecialchars($guest['email']); ?>
                                    </div>
                                    <div class="small text-muted"><i
                                            class="bi bi-phone me-2 opacity-50"></i><?php echo htmlspecialchars($guest['phone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge bg-<?php echo $guest['account_status'] == 'active' ? 'success' : 'danger'; ?> bg-opacity-10 text-<?php echo $guest['account_status'] == 'active' ? 'success' : 'danger'; ?> border border-<?php echo $guest['account_status'] == 'active' ? 'success' : 'danger'; ?> border-opacity-25 px-3 py-2 small">
                                        <?php echo strtoupper($guest['account_status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-outline-gold btn-sm px-2 border-0"
                                            onclick="editGuest(<?php echo htmlspecialchars(json_encode($guest)); ?>)"
                                            title="Edit Guest">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </button>

                                        <?php if ($guest['account_status'] == 'active'): ?>
                                            <a href="guests.php?toggle_status=blocked&id=<?php echo $guest['id']; ?>"
                                                class="btn btn-outline-warning btn-sm px-2 border-0" title="Block Guest"
                                                onclick="return confirm('Suspend this guest\'s credentials?')">
                                                <i class="bi bi-slash-circle fs-6"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="guests.php?toggle_status=active&id=<?php echo $guest['id']; ?>"
                                                class="btn btn-outline-success btn-sm px-2 border-0" title="Activate Guest">
                                                <i class="bi bi-check-circle fs-6"></i>
                                            </a>
                                        <?php endif; ?>

                                        <button class="btn btn-outline-danger btn-sm px-2 border-0"
                                            onclick="confirmDelete(<?php echo $guest['id']; ?>)" title="Delete Guest">
                                            <i class="bi bi-trash fs-6"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Guest Modal -->
<div class="modal fade" id="addGuestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kingsman-card glass-panel border-gold shadow-lg">
            <div class="modal-header border-gold">
                <h5 class="modal-title gold-text small text-uppercase" style="letter-spacing: 2px;">
                    <i class="bi bi-person-plus-fill me-2"></i> Add New Guest
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body py-4 px-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">First Name</label>
                            <input type="text" name="firstname" class="form-control bg-dark text-white border-secondary"
                                placeholder="Lancelot" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Last Name</label>
                            <input type="text" name="lastname" class="form-control bg-dark text-white border-secondary"
                                placeholder="Galahad" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i
                                        class="bi bi-envelope"></i></span>
                                <input type="email" name="email"
                                    class="form-control bg-dark text-white border-secondary"
                                    placeholder="agent@kingsman.com" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i
                                        class="bi bi-phone"></i></span>
                                <input type="text" name="phone" class="form-control bg-dark text-white border-secondary"
                                    placeholder="+44 20 7946 0958" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Temporary Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-muted"><i
                                        class="bi bi-key"></i></span>
                                <input type="password" name="password"
                                    class="form-control bg-dark text-white border-secondary"
                                    placeholder="Default: password123">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_guest" class="btn btn-kingsman btn-sm px-4">Add
                        Guest</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Guest Modal -->
<div class="modal fade" id="editGuestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content kingsman-card glass-panel border-gold shadow-lg">
            <div class="modal-header border-gold">
                <h5 class="modal-title gold-text small text-uppercase" style="letter-spacing: 2px;">
                    <i class="bi bi-pencil-square me-2"></i> Edit Guest Profile
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="editUserId">
                <div class="modal-body py-4 px-5">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">First Name</label>
                            <input type="text" name="firstname" id="editFirstname"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted text-uppercase fw-bold">Last Name</label>
                            <input type="text" name="lastname" id="editLastname"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Email Address</label>
                            <input type="email" name="email" id="editEmail"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-muted text-uppercase fw-bold">Phone Number</label>
                            <input type="text" name="phone" id="editPhone"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-gold">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_guest" class="btn btn-kingsman btn-sm px-4">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function editGuest(guest) {
        document.getElementById('editUserId').value = guest.id;
        document.getElementById('editFirstname').value = guest.firstname;
        document.getElementById('editLastname').value = guest.lastname;
        document.getElementById('editEmail').value = guest.email;
        document.getElementById('editPhone').value = guest.phone;
        new bootstrap.Modal(document.getElementById('editGuestModal')).show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Guest?',
            text: "This will permanently remove the guest record and history.",
            icon: 'warning',
            showCancelButton: true,
            background: '#1a1a1a',
            color: '#fff',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'CONFIRM DELETE',
            cancelButtonText: 'CANCEL'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'guests.php?delete_id=' + id;
            }
        });
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>