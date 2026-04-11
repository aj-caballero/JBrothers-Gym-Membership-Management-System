<?php
// C:/Users/Kyle/GYM MEMBERSHIP/plans/index.php
$pageTitle = 'Membership Plans';
require_once '../includes/header.php';

$stmt = $pdo->query("SELECT * FROM membership_plans ORDER BY id ASC");
$plans = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Available Plans</h3>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Plan</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Plan Name</th>
                    <th>Duration (Days)</th>
                    <th>Price</th>
                    <th>Description</th>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                    <tr>
                        <td><?= $plan->id ?></td>
                        <td><strong><?= htmlspecialchars($plan->plan_name) ?></strong></td>
                        <td><?= $plan->duration_days ?> Days</td>
                        <td><?= formatCurrency($plan->price) ?></td>
                        <td><?= htmlspecialchars($plan->description) ?></td>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <td>
                                <a href="edit.php?id=<?= $plan->id ?>" class="btn btn-sm" style="background:#ffc107; color:#000;"><i class="fas fa-edit"></i> Edit</a>
                                <a href="delete.php?id=<?= $plan->id ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? Members using this plan may be affected.');"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($plans)): ?>
                    <tr><td colspan="<?= ($_SESSION['user_role'] === 'admin') ? 6 : 5 ?>">No plans created yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
