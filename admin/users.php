<?php
// C:/Users/Kyle/GYM MEMBERSHIP/admin/users.php
$pageTitle = 'User Accounts';
require_once '../includes/header.php';
require_admin();

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">System Users</span>
        <button class="btn btn-primary" onclick="document.getElementById('addUserModal').classList.add('open')">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
        <div class="alert alert-success" style="margin:16px 22px 0;"><i class="fas fa-circle-check"></i> User added successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
        <div class="alert alert-danger" style="margin:16px 22px 0;"><i class="fas fa-circle-exclamation"></i> That email address is already in use.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="td-muted"><?= $u->id ?></td>
                        <td class="td-name"><?= htmlspecialchars($u->full_name) ?></td>
                        <td class="td-muted"><?= htmlspecialchars($u->email) ?></td>
                        <td>
                            <span class="badge badge-<?= $u->role ?>">
                                <?= ucfirst($u->role) ?>
                            </span>
                        </td>
                        <td class="td-muted"><?= formatDate($u->created_at) ?></td>
                        <td>
                            <a href="edit_user.php?id=<?= $u->id ?>" class="btn btn-ghost btn-sm">
                                <i class="fas fa-pen"></i> Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-user-shield"></i></div>
                            <h3>No users found</h3>
                            <p>Add the first staff account to get started.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus" style="color:var(--accent);margin-right:8px;"></i> Add New User</h3>
            <button class="btn-icon" onclick="document.getElementById('addUserModal').classList.remove('open')">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form action="../auth/register.php" method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="e.g. Juan dela Cruz" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="staff@gym.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="roleSelect" class="form-control">
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div id="permissionsGroup" class="form-group">
                    <label>Module Access (Staff Only)</label>
                    <div style="display:flex; flex-wrap:wrap; gap:12px; margin-top:4px;">
                        <?php foreach (['members','plans','payments','attendance','reports'] as $mod): ?>
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--text-primary);text-transform:none;letter-spacing:0;font-weight:500;cursor:pointer;">
                                <input type="checkbox" name="permissions[]" value="<?= $mod ?>" checked style="accent-color:var(--accent);">
                                <?= ucfirst($mod) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addUserModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('roleSelect').addEventListener('change', function() {
    document.getElementById('permissionsGroup').style.display = (this.value === 'admin') ? 'none' : 'block';
});
</script>

<?php require_once '../includes/footer.php'; ?>
