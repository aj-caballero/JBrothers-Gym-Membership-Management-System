<?php
// admin/login_logs.php
$pageTitle = 'Login Logs';
require_once '../includes/header.php';

if ($_SESSION['user_role'] !== 'admin') {
    redirect('/dashboard.php?error=unauthorized');
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$sql = "SELECT l.*, u.full_name 
        FROM login_logs l 
        LEFT JOIN users u ON l.user_id = u.id 
        ORDER BY l.login_time DESC 
        LIMIT $limit OFFSET $offset";
$stmt = $pdo->query($sql);
$logs = $stmt->fetchAll();

$totalStmt = $pdo->query("SELECT COUNT(*) as total FROM login_logs");
$total = $totalStmt->fetch()->total;
$totalPages = ceil($total / $limit);
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">System Login Logs</h3>
    </div>
    
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Email Attempt</th>
                    <th>User Name</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space: nowrap;"><?= date('M d, Y h:i A', strtotime($log->login_time)) ?></td>
                        <td><?= htmlspecialchars($log->email_attempt) ?></td>
                        <td><?= htmlspecialchars($log->full_name ?? '—') ?></td>
                        <td><?= htmlspecialchars($log->ip_address) ?></td>
                        <td>
                            <?php if ($log->status === 'Success'): ?>
                                <span class="badge badge-active">Success</span>
                            <?php else: ?>
                                <span class="badge badge-inactive">Failed</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($log->user_agent) ?>">
                            <small class="text-muted"><?= htmlspecialchars($log->user_agent) ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding: 30px;">
                            <div class="empty-state">
                                <div class="empty-icon"><i class="fas fa-history"></i></div>
                                <h3>No login logs found.</h3>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="btn btn-sm <?= ($page == $i) ? 'btn-primary' : '' ?>" style="border: 1px solid var(--border);"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
