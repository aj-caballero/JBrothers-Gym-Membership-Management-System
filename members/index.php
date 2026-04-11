<?php
// C:/Users/Kyle/GYM MEMBERSHIP/members/index.php
$pageTitle = 'Members';
require_once '../includes/header.php';

// Pagination and Search
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$searchQuery = "";
$params = [];

if (!empty($search)) {
    $searchQuery = "WHERE full_name LIKE ? OR email LIKE ? OR phone LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}

// Get total for pagination
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM members $searchQuery");
$stmtTotal->execute($params);
$total = $stmtTotal->fetch()->total;
$totalPages = ceil($total / $limit);

// Get records
$sql = "SELECT * FROM members $searchQuery ORDER BY id DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Members</h3>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Member</a>
    </div>

    <form method="GET" class="form-row" style="margin-bottom: 20px;">
        <div class="form-group" style="flex: 2;">
            <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="form-group" style="flex: 0;">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td><?= $member->id ?></td>
                        <td><?= htmlspecialchars($member->full_name) ?></td>
                        <td><?= htmlspecialchars($member->email) ?></td>
                        <td><?= htmlspecialchars($member->phone) ?></td>
                        <td><?= formatDate($member->join_date) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($member->status) ?>">
                                <?= $member->status ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $member->id ?>" class="btn btn-sm" style="background:#17a2b8; color:#fff;" title="View"><i class="fas fa-eye"></i></a>
                            <a href="edit.php?id=<?= $member->id ?>" class="btn btn-sm" style="background:#ffc107; color:#000;" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="delete.php?id=<?= $member->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This cannot be undone.');" title="Delete"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($members)): ?>
                    <tr><td colspan="7">No members found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Basic Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="btn btn-sm <?= ($page == $i) ? 'btn-primary' : '' ?>" style="border: 1px solid var(--border);"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
