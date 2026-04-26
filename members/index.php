<?php
// C:/Users/Kyle/GYM MEMBERSHIP/members/index.php
$pageTitle = 'Members';
require_once '../includes/header.php';

// Pagination and Search
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 10;
$offset = ($page - 1) * $limit;

$search      = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$params      = [];
$whereParts  = [];

// Always exclude archived members
$whereParts[] = "deleted_at IS NULL";

if (!empty($search)) {
    $whereParts[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if (!empty($statusFilter)) {
    $whereParts[] = "status = ?";
    $params[] = $statusFilter;
}
$whereSQL = 'WHERE ' . implode(' AND ', $whereParts);

// Total for pagination
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM members $whereSQL");
$stmtTotal->execute($params);
$total      = $stmtTotal->fetch()->total;
$totalPages = ceil($total / $limit);

// Records
$stmt = $pdo->prepare("SELECT * FROM members $whereSQL ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <span class="card-title">All Members <span style="color:var(--text-muted);font-weight:400;font-size:13px;">(<?= number_format($total) ?>)</span></span>
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="archived.php" class="btn btn-ghost btn-sm"><i class="fas fa-box-archive"></i> Archived</a>
            <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Member</a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" style="display:flex;gap:10px;align-items:center;padding:14px 22px;border-bottom:1px solid var(--border);flex-wrap:wrap;">
        <div class="input-group" style="flex:1;min-width:200px;">
            <i class="input-icon fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="form-control" placeholder="Search name, email, or phone…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <select name="status" class="form-control" style="width:160px;">
            <option value="">All Statuses</option>
            <option value="Active"    <?= $statusFilter==='Active'    ? 'selected' : '' ?>>Active</option>
            <option value="Inactive"  <?= $statusFilter==='Inactive'  ? 'selected' : '' ?>>Inactive</option>
            <option value="Suspended" <?= $statusFilter==='Suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if ($search || $statusFilter): ?>
            <a href="index.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Member</th>
                    <th>Phone</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($members as $member): ?>
                    <tr>
                        <td class="td-muted"><?= $member->id ?></td>
                        <td>
                            <div class="td-name"><?= htmlspecialchars($member->full_name) ?></div>
                            <div class="td-muted" style="font-size:12px;"><?= htmlspecialchars($member->email) ?></div>
                        </td>
                        <td class="td-muted"><?= htmlspecialchars($member->phone ?: '—') ?></td>
                        <td class="td-muted"><?= formatDate($member->join_date) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($member->status) ?>">
                                <?= $member->status ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <a href="view.php?id=<?= $member->id ?>" class="btn btn-ghost btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                <a href="edit.php?id=<?= $member->id ?>" class="btn btn-ghost btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
                                <a href="<?= APP_URL ?>/payments/add.php?member_id=<?= $member->id ?>" class="btn btn-ghost btn-sm" title="Record Payment"><i class="fas fa-credit-card"></i></a>
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <a href="delete.php?id=<?= $member->id ?>" class="btn btn-danger btn-sm"
                                       onclick="return confirm('Delete <?= htmlspecialchars(addslashes($member->full_name)) ?>? This cannot be undone.')"
                                       title="Delete"><i class="fas fa-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($members)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-users"></i></div>
                            <h3><?= $search || $statusFilter ? 'No matches found' : 'No members yet' ?></h3>
                            <p><?= $search || $statusFilter ? 'Try adjusting your search or filter.' : 'Add your first member to get started.' ?></p>
                            <?php if (!$search && !$statusFilter): ?>
                                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Member</a>
                            <?php endif; ?>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"
                   class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
