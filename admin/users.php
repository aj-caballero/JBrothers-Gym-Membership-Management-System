<?php
// C:/Users/Kyle/GYM MEMBERSHIP/admin/users.php
$pageTitle = 'Manage Staff Users';
require_once '../includes/header.php';
require_admin();

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">System Users</h3>
        <button class="btn btn-primary" onclick="document.getElementById('addUserModal').style.display='block'">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>

    <!-- Alert rendering from $_GET was handled minimally in index logic previously. We can just add inline here: -->
    <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
        <div class="alert" style="background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid #28a745;">User added successfully.</div>
    <?php endif; ?>
    <?php if (isset($_GET['error']) && $_GET['error'] === 'exists'): ?>
        <div class="alert">Email already exists.</div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u->id ?></td>
                        <td><?= htmlspecialchars($u->full_name) ?></td>
                        <td><?= htmlspecialchars($u->email) ?></td>
                        <td><span class="badge" style="background: <?= $u->role === 'admin' ? '#17a2b8' : '#6c757d' ?>"><?= ucfirst($u->role) ?></span></td>
                        <td><?= formatDate($u->created_at) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal for adding user -->
<div id="addUserModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
    <div class="card" style="width:100%; max-width:500px; margin: 10% auto;">
        <div class="card-header">
            <h3>Add User</h3>
            <button onclick="document.getElementById('addUserModal').style.display='none'" class="btn-icon">&times;</button>
        </div>
        <form action="../auth/register.php" method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control">
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Create User</button>
        </form>
    </div>
</div>

<script>
// Expose simple modal trick
window.onclick = function(event) {
    let modal = document.getElementById('addUserModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
