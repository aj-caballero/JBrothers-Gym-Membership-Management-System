<?php
// C:/Users/Kyle/GYM MEMBERSHIP/payments/index.php
$pageTitle = 'Payments History';
require_once '../includes/header.php';

// Pagination and Search
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$filter_status = $_GET['status'] ?? '';
$filter_method = $_GET['method'] ?? '';

$hasRequestedPlanColumn = dbHasColumn($pdo, 'payments', 'requested_plan_id');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(m.full_name LIKE ? OR p.id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter_status !== '') {
    $where[] = "p.status = ?";
    $params[] = $filter_status;
}
if ($filter_method !== '') {
    $where[] = "p.payment_method = ?";
    $params[] = $filter_method;
}

$whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "SELECT p.*, m.full_name as member_name, u.full_name as processed_by_name, "
    . ($hasRequestedPlanColumn
        ? "COALESCE(mp.plan_name, mp_req.plan_name) AS plan_name "
        : "mp.plan_name AS plan_name ")
    . "FROM payments p
       JOIN members m ON p.member_id = m.id
       LEFT JOIN memberships ms ON p.membership_id = ms.id
       LEFT JOIN membership_plans mp ON ms.plan_id = mp.id "
    . ($hasRequestedPlanColumn ? "LEFT JOIN membership_plans mp_req ON p.requested_plan_id = mp_req.id " : "")
    . "LEFT JOIN users u ON p.processed_by = u.id "
    . "$whereClause ORDER BY p.payment_date DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Total
$totalSql = "SELECT COUNT(*) as total FROM payments p JOIN members m ON p.member_id = m.id $whereClause";
$totalStmt = $pdo->prepare($totalSql);
$totalStmt->execute($params);
$total = $totalStmt->fetch()->total;
$totalPages = ceil($total / $limit);
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Payments</h3>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Record Payment</a>
    </div>

    <!-- Filter Form -->
    <div style="padding: 16px 22px; border-bottom: 1px solid var(--border);">
        <form method="GET" action="index.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-size:12px; color:var(--text-muted); margin-bottom:4px;">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Receipt No. or Member Name" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div style="width:140px;">
                <label style="display:block; font-size:12px; color:var(--text-muted); margin-bottom:4px;">Status</label>
                <select name="status" class="form-control">
                    <option value="">All</option>
                    <option value="Paid" <?= $filter_status === 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Cancelled" <?= $filter_status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div style="width:140px;">
                <label style="display:block; font-size:12px; color:var(--text-muted); margin-bottom:4px;">Method</label>
                <select name="method" class="form-control">
                    <option value="">All</option>
                    <option value="Cash" <?= $filter_method === 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="GCash" <?= $filter_method === 'GCash' ? 'selected' : '' ?>>GCash</option>
                    <option value="Card" <?= $filter_method === 'Card' ? 'selected' : '' ?>>Card</option>
                    <option value="PayMongo" <?= $filter_method === 'PayMongo' ? 'selected' : '' ?>>PayMongo</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-secondary" style="height:38px;"><i class="fas fa-search"></i> Filter</button>
                <?php if ($search || $filter_status || $filter_method): ?>
                    <a href="index.php" class="btn btn-ghost" style="height:38px;">Clear</a>
                <?php endif; ?>
            </div>
        </form>
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
                    <th>Processed By</th>
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
                        <td><?= htmlspecialchars($pay->processed_by_name ?? '—') ?></td>
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
                    <tr><td colspan="9">No payments found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Basic Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>&method=<?= urlencode($filter_method) ?>" class="btn btn-sm <?= ($page == $i) ? 'btn-primary' : '' ?>" style="border: 1px solid var(--border);"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
