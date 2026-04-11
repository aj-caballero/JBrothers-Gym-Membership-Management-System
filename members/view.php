<?php
// C:/Users/Kyle/GYM MEMBERSHIP/members/view.php
$pageTitle = 'Member Profile';
require_once '../includes/header.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    redirect('/members/index.php');
}

// Get active membership
$stmtMs = $pdo->prepare("SELECT ms.*, mp.plan_name FROM memberships ms JOIN membership_plans mp ON ms.plan_id = mp.id WHERE ms.member_id = ? ORDER BY ms.id DESC LIMIT 1");
$stmtMs->execute([$id]);
$membership = $stmtMs->fetch();

// Get payment history
$stmtPay = $pdo->prepare("SELECT * FROM payments WHERE member_id = ? ORDER BY payment_date DESC");
$stmtPay->execute([$id]);
$payments = $stmtPay->fetchAll();
?>

<div class="form-row">
    <!-- Profile Info -->
    <div class="form-group" style="flex:1;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Profile Details</h3>
                <a href="edit.php?id=<?= $member->id ?>" class="btn btn-sm" style="background:#ffc107; color:#000;"><i class="fas fa-edit"></i> Edit</a>
            </div>
            <div style="line-height:2;">
                <p><strong>Name:</strong> <?= htmlspecialchars($member->full_name) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($member->email) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($member->phone) ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?= strtolower($member->status) ?>"><?= $member->status ?></span></p>
                <p><strong>Join Date:</strong> <?= formatDate($member->join_date) ?></p>
            </div>
        </div>
    </div>

    <!-- Active Membership -->
    <div class="form-group" style="flex:1;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Current Membership</h3>
            </div>
            <?php if ($membership): ?>
                <div style="line-height:2;">
                    <p><strong>Plan:</strong> <?= htmlspecialchars($membership->plan_name) ?></p>
                    <p><strong>Valid From:</strong> <?= formatDate($membership->start_date) ?></p>
                    <p><strong>Valid Until:</strong> <?= formatDate($membership->end_date) ?></p>
                    <p><strong>Status:</strong> <span class="badge badge-<?= strtolower($membership->status) ?>"><?= $membership->status ?></span></p>
                </div>
            <?php else: ?>
                <p>No active membership. <a href="../payments/add.php?member_id=<?= $member->id ?>">Add one now</a>.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Payment History</h3>
    </div>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td><?= formatDate($pay->payment_date) ?></td>
                        <td><?= formatCurrency($pay->amount) ?></td>
                        <td><?= $pay->payment_method ?></td>
                        <td><span class="badge badge-<?= strtolower($pay->status) ?>"><?= $pay->status ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="4">No payments recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
