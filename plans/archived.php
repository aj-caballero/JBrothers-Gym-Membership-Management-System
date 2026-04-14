<?php
// plans/archived.php — View archived (soft-deleted) membership plans
$pageTitle = 'Archived Plans';
require_once '../includes/header.php';
require_admin();

$stmt = $pdo->query("SELECT * FROM membership_plans WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
$archived = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><i class="fas fa-box-archive" style="color:var(--warning);margin-right:8px;"></i>Archived Plans</h1>
        <p>These membership plans have been archived. Existing subscriptions tied to them are unaffected.</p>
    </div>
    <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Plans</a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'restored'): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> Plan restored successfully.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <span class="card-title">Archived Plans <span style="color:var(--text-muted);font-weight:400;font-size:13px;">(<?= count($archived) ?>)</span></span>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Plan Name</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Archived On</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($archived as $plan): ?>
                    <tr>
                        <td class="td-muted"><?= $plan->id ?></td>
                        <td><strong><?= htmlspecialchars($plan->plan_name) ?></strong></td>
                        <td class="td-muted"><?= $plan->duration_days ?> Days</td>
                        <td><?= formatCurrency($plan->price) ?></td>
                        <td>
                            <span class="badge badge-suspended">
                                <?= formatDate($plan->deleted_at) ?>
                            </span>
                        </td>
                        <td>
                            <a href="restore.php?id=<?= $plan->id ?>"
                               class="btn btn-sm btn-primary"
                               onclick="return confirm('Restore \"<?= htmlspecialchars(addslashes($plan->plan_name)) ?>\"? It will reappear in the active plans list.')">
                                <i class="fas fa-rotate-left"></i> Restore
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($archived)): ?>
                    <tr><td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fas fa-box-archive"></i></div>
                            <h3>No archived plans</h3>
                            <p>Archived plans will appear here.</p>
                        </div>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
