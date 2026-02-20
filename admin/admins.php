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
$current_admin_id = $_SESSION['user_id'];

// Handle Toggle Account Status (Blocking/Activate)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    if ($id == $current_admin_id) {
        header("Location: admins.php?msg=self_block_error");
        exit();
    }
    $new_status = $_GET['toggle_status'];
    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    header("Location: admins.php?msg=status_updated");
    exit();
}

// Handle Delete Admin
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    if ($id == $current_admin_id) {
        header("Location: admins.php?msg=self_delete_error");
        exit();
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    if ($stmt->execute([$id])) {
        header("Location: admins.php?msg=deleted");
    } else {
        header("Location: admins.php?msg=error");
    }
    exit();
}

// Handle Add/Edit Admin
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if (isset($_POST['add_admin'])) {
        $password = password_hash($_POST['password'] ?: 'password123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, email, password, phone, role, account_status, is_verified) VALUES (?, ?, ?, ?, ?, 'admin', 'active', 1)");
        if ($stmt->execute([$firstname, $lastname, $email, $password, $phone])) {
            header("Location: admins.php?msg=added");
        } else {
            $error = "Email might already be in use.";
        }
    } elseif (isset($_POST['edit_admin'])) {
        $id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$firstname, $lastname, $email, $phone, $id])) {
            header("Location: admins.php?msg=updated");
        } else {
            $error = "Update failed. Check email uniqueness.";
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
$admins = $stmt->fetchAll();

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="d-flex">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex-grow-1 p-5">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="display-5">Administrator Management</h1>
                <p class="text-muted">High-level access control for Kingsman operational staff.</p>
            </div>
            <button class="btn btn-kingsman px-4" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                <i class="bi bi-shield-plus me-2"></i> Appoint New Admin
            </button>
        </div>

        <?php if ($msg == 'added'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-check-circle-fill me-2"></i> New administrator
                appointed successfully.</div>
        <?php elseif ($msg == 'updated'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-check-circle-fill me-2"></i> Administrator profile
                updated.</div>
        <?php elseif ($msg == 'deleted'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-trash-fill me-2"></i> Admin credentials revoked and
                record purged.</div>
        <?php elseif ($msg == 'status_updated'): ?>
            <div class="kingsman-alert success mb-4"><i class="bi bi-shield-lock-fill me-2"></i> Admin operational status
                modified.</div>
        <?php elseif ($msg == 'self_block_error'): ?>
            <div class="kingsman-alert error mb-4"><i class="bi bi-exclamation-octagon me-2"></i> Security Protocol: You
                cannot suspend your own active session account.</div>
        <?php elseif ($msg == 'self_delete_error'): ?>
            <div class="kingsman-alert error mb-4"><i class="bi bi-exclamation-octagon me-2"></i> Security Protocol: You
                cannot delete your own active session account.</div>
        <?php elseif ($error): ?>
            <div class="kingsman-alert error mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="card kingsman-card glass-panel p-4 border-0 shadow-lg">
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0" id="adminsTable">
                    <thead>
                        <tr class="text-muted small text-uppercase">
                            <th class="ps-4">Admin Agent</th>
                            <th>Contact Details</th>
                            <th>Status</th>
                            <th class="pe-4 text-end">Action / Protocol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo '../assets/img/' . ($admin['profile_image'] ? 'avatars/' . $admin['profile_image'] : 'arthur.jpg'); ?>"
                                            class="rounded-circle border border-gold me-3"
                                            style="width: 45px; height: 45px; object-fit: cover;">
                                        <div>
                                            <div class="fw-bold gold-text">
                                                <?php echo htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.65rem;">AGENT ID: #
                                                <?php echo str_pad($admin['id'], 5, '0', STR_PAD_LEFT); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope-at me-2 gold-text"></i>
                                        <?php echo htmlspecialchars($admin['email']); ?>
                                    </div>
                                    <div class="small"><i class="bi bi-phone me-2 gold-text"></i>
                                        <?php echo htmlspecialchars($admin['phone']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge rounded-pill bg-<?php echo $admin['account_status'] == 'active' ? 'success' : 'danger'; ?> opacity-75">
                                        <?php echo strtoupper($admin['account_status']); ?>
                                    </span>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="btn-group shadow-sm">
                                        <button class="btn btn-outline-gold btn-sm px-2 border-0"
                                            onclick="editAdmin(<?php echo htmlspecialchars(json_encode($admin)); ?>)"
                                            title="Edit Protocol">
                                            <i class="bi bi-pencil-square fs-6"></i>
                                        </button>

                                        <?php if ($admin['id'] != $current_admin_id): ?>
                                            <?php if ($admin['account_status'] == 'active'): ?>
                                                <a href="admins.php?toggle_status=blocked&id=<?php echo $admin['id']; ?>"
                                                    class="btn btn-outline-warning btn-sm px-2 border-0" title="Suspend Credentials"
                                                    onclick="return confirm('Suspend this admin\'s credentials?')">
                                                    <i class="bi bi-slash-circle fs-6"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="admins.php?toggle_status=active&id=<?php echo $admin['id']; ?>"
                                                    class="btn btn-outline-success btn-sm px-2 border-0"
                                                    title="Reactivate Credentials">
                                                    <i class="bi bi-check-circle fs-6"></i>
                                                </a>
                                            <?php endif; ?>

                                            <button class="btn btn-outline-danger btn-sm px-2 border-0"
                                                onclick="confirmDelete(<?php echo $admin['id']; ?>)" title="Purge Agent Record">
                                                <i class="bi bi-trash fs-6"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary btn-sm px-2 border-0 disabled"
                                                title="Current Session Active">
                                                <i class="bi bi-person-lock fs-6"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-gold text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title gold-text">Appoint Administrator</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small gold-text">First Name</label>
                            <input type="text" name="firstname" class="form-control bg-dark text-white border-secondary"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small gold-text">Last Name</label>
                            <input type="text" name="lastname" class="form-control bg-dark text-white border-secondary"
                                required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small gold-text">Official Email</label>
                            <input type="email" name="email" class="form-control bg-dark text-white border-secondary"
                                required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small gold-text">Contact Number</label>
                            <input type="text" name="phone" class="form-control bg-dark text-white border-secondary"
                                required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small gold-text">Admin Password</label>
                            <input type="password" name="password"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Default: password123">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_admin" class="btn btn-kingsman px-4">Appoint Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-gold text-white">
            <div class="modal-header border-secondary">
                <h5 class="modal-title gold-text">Revise Admin Protocol</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="user_id" id="editAdminId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small gold-text">First Name</label>
                            <input type="text" name="firstname" id="editFirstname"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small gold-text">Last Name</label>
                            <input type="text" name="lastname" id="editLastname"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small gold-text">Official Email</label>
                            <input type="email" name="email" id="editEmail"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small gold-text">Contact Number</label>
                            <input type="text" name="phone" id="editPhone"
                                class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Abstain</button>
                    <button type="submit" name="edit_admin" class="btn btn-kingsman px-4">Update Details</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function editAdmin(admin) {
        document.getElementById('editAdminId').value = admin.id;
        document.getElementById('editFirstname').value = admin.firstname;
        document.getElementById('editLastname').value = admin.lastname;
        document.getElementById('editEmail').value = admin.email;
        document.getElementById('editPhone').value = admin.phone;
        new bootstrap.Modal(document.getElementById('editAdminModal')).show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Revoke Credentials?',
            text: "This will permanently remove this administrator from the system.",
            icon: 'warning',
            showCancelButton: true,
            background: '#1a1a1a',
            color: '#fff',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'CONFIRM REVOCATION',
            cancelButtonText: 'CANCEL'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'admins.php?delete_id=' + id;
            }
        });
    }
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>