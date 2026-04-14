<?php
// members/archived.php — View archived (soft-deleted) members
$pageTitle = 'Archived Members';
require_once '../includes/header.php';
require_admin();

// Pagination
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$params = [];
$whereExtra = '';
if (!empty($search)) {
    $whereExtra = "AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE deleted_at IS NOT NULL $whereExtra");
$totalStmt->execute($params);
$total      = (int)$totalStmt->fetchColumn();
$totalPages = ceil($total / $limit);

$stmt = $pdo->prepare("SELECT * FROM members WHERE deleted_at IS NOT NULL $whereExtra ORDER BY deleted_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$archived = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-box-archive" style="color:var(--warning);margin-right:8px;"></i>Archived Members</h1>
        <p>These members have been archived and are hidden from normal operations. You can restore them at any time.</p>
    </div>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Members</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'restored'): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> Member restored successfully.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Archived Members <span style="color:var(--text-muted);font-weight:400;font-size:13px;">(<?= number_format($total) ?>)</span></span>
    </div>

    <!-- Search -->
    <form method="GET" style="display:flex;gap:10px;align-items:center;padding:14px 22px;border-bottom:1px solid var(--border);flex-wrap:wrap;">
        <div class="input-group" style="flex:1;min-width:200px;">
            <i class="input-icon fas fa-magnifying-glass"></i>
            <input type="text" name="search" class="form-control" placeholder="Search name, email, or phone…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Search</button>
        <?php if ($search): ?>
            <a href="archived.php" class="btn btn-ghost">Clear</a>
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
                    <th>Archived On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archived as $m): ?>
                    <tr>
                        <td class="td-muted"><?= $m->id ?></td>
                        <td>
                            <div class="td-name"><?= htmlspecialchars($m->full_name) ?></div>
                            <div class="td-muted" style="font-size:12px;"><?= htmlspecialchars($m->email) ?></div>
                        </td>
                        <td class="td-muted"><?= htmlspecialchars($m->phone ?: '—') ?></td>
                        <td class="td-muted"><?= formatDate($m->join_date) ?></td>
                        <td>
                            <span class="badge badge-suspended">
                                <?= formatDate($m->deleted_at) ?>
                            </span>
                        </td>
                        <td>
                            <a href="restore.php?id=<?= $m->id ?>"
                               class="btn btn-sm btn-primary"
                               onclick="return confirm('Restore <?= htmlspecialchars(addslashes($m->full_name)) ?>? They will reappear in the members list.')">
                                <i class="fas fa-rotate-left"></i> Restore
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($archived)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-box-archive"></i></div>
                            <h3><?= $search ? 'No matches found' : 'No archived members' ?></h3>
                            <p><?= $search ? 'Try adjusting your search.' : 'Archived members will appear here.' ?></p>
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
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>"><i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
