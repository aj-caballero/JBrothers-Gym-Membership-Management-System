<?php
// C:/Users/Kyle/GYM MEMBERSHIP/payments/index.php
$pageTitle = 'Payments History';
require_once '../includes/header.php';

// Pagination and Search
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$hasRequestedPlanColumn = dbHasColumn($pdo, 'payments', 'requested_plan_id');

$sql = "SELECT p.*, m.full_name as member_name, "
    . ($hasRequestedPlanColumn
        ? "COALESCE(mp.plan_name, mp_req.plan_name) AS plan_name "
        : "mp.plan_name AS plan_name ")
    . "FROM payments p
       JOIN members m ON p.member_id = m.id
       LEFT JOIN memberships ms ON p.membership_id = ms.id
       LEFT JOIN membership_plans mp ON ms.plan_id = mp.id "
    . ($hasRequestedPlanColumn ? "LEFT JOIN membership_plans mp_req ON p.requested_plan_id = mp_req.id " : "")
    . "ORDER BY p.payment_date DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->query($sql);
$payments = $stmt->fetchAll();

// Total
$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM payments");
$total = $totalStmt->fetch()->total;
$totalPages = ceil($total / $limit);
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Payments</h3>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Record Payment</a>
    </div>

    <?php if (!empty($_GET['cancelled'])): ?>
        <div class="alert alert-success" style="margin:16px 22px 0;">
            Pending payment was cancelled successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['error']) && $_GET['error'] !== 'unauthorized'): ?>
        <div class="alert alert-danger" style="margin:16px 22px 0;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Receipt No.</th>
                    <th>Date</th>
                    <th>Member</th>
                    <th>Plan/Desc</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): ?>
                    <tr>
                        <td>#<?= str_pad($pay->id, 6, '0', STR_PAD_LEFT) ?></td>
                        <td><?= formatDate($pay->payment_date) ?></td>
                        <td><?= htmlspecialchars($pay->member_name) ?></td>
                        <td><?= htmlspecialchars($pay->plan_name ?? 'Misc') ?></td>
                        <td><?= formatCurrency($pay->amount) ?></td>
                        <td>
                            <div><?= htmlspecialchars($pay->payment_method) ?></div>
                            <?php if (property_exists($pay, 'gateway_transaction_id') && !empty($pay->gateway_transaction_id)): ?>
                                <small style="color:var(--text-muted);display:block;margin-top:2px;">
                                    Ref: <?= htmlspecialchars($pay->gateway_transaction_id) ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= strtolower($pay->status) ?>"><?= $pay->status ?></span></td>
                        <td>
                            <a href="receipt.php?id=<?= $pay->id ?>" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-print"></i> Receipt</a>
                            <?php if ($hasRequestedPlanColumn && $pay->status === 'Pending'): ?>
                                <form method="POST" action="settle.php" style="display:inline-block;margin-left:6px;" onsubmit="return confirm('Settle this pending payment as Paid and activate membership now?');">
                                    <input type="hidden" name="id" value="<?= (int)$pay->id ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary"><i class="fas fa-check"></i> Settle</button>
                                </form>
                                <form method="POST" action="cancel.php" style="display:inline-block;margin-left:6px;" onsubmit="return confirm('Cancel this pending payment? This will not activate membership.');">
                                    <input type="hidden" name="id" value="<?= (int)$pay->id ?>">
                                    <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-ban"></i> Cancel</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="8">No payments recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Basic Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="btn btn-sm <?= ($page == $i) ? 'btn-primary' : '' ?>" style="border: 1px solid var(--border);"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
