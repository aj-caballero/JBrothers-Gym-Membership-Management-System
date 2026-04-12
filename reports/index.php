<?php
// C:/Users/Kyle/GYM MEMBERSHIP/reports/index.php
$pageTitle = 'Reports & Analytics';
require_once '../includes/header.php';

// Base monthly revenue chart data
$months = [];
$revenues = [];

// Last 6 months 
for ($i = 5; $i >= 0; $i--) {
    $monthDate = date('Y-m', strtotime("-$i months"));
    $monthLabel = date('M Y', strtotime("-$i months"));
    
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?");
    $stmt->execute([$monthDate]);
    $result = $stmt->fetch();
    
    $months[] = $monthLabel;
    $revenues[] = $result->total ?? 0;
}

$monthsJson = json_encode($months);
$revenuesJson = json_encode($revenues);

// Quick Stats
$totalMembers = $pdo->query("SELECT COUNT(*) as total FROM members")->fetch()->total;
$activeMembers = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status='Active'")->fetch()->total;
$expiredMembers = $pdo->query("SELECT COUNT(*) as total FROM members WHERE status='Expired'")->fetch()->total;

?>

<div class="stats-grid">
    <div class="stat-card" style="background: linear-gradient(145deg, #1e1e2e, #1a2a44);">
        <div class="stat-icon" style="color: #007bff; background: rgba(0, 123, 255, 0.1);"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3>Total Members</h3>
            <p class="stat-value"><?= number_format($totalMembers) ?></p>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(145deg, #1e1e2e, #143324);">
        <div class="stat-icon" style="color: #28a745; background: rgba(40, 167, 69, 0.1);"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <h3>Active Members</h3>
            <p class="stat-value"><?= number_format($activeMembers) ?></p>
        </div>
    </div>
    <div class="stat-card" style="background: linear-gradient(145deg, #1e1e2e, #441a1a);">
        <div class="stat-icon" style="color: #dc3545; background: rgba(220, 53, 69, 0.1);"><i class="fas fa-user-times"></i></div>
        <div class="stat-info">
            <h3>Expired Members</h3>
            <p class="stat-value"><?= number_format($expiredMembers) ?></p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Revenue Overview (Last 6 Months)</h3>
    </div>
    <div style="height: 350px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<div class="card" style="margin-top: 20px;">
    <div class="card-header">
        <h3 class="card-title">Export Data</h3>
    </div>
    <p style="color: var(--text-muted); margin-bottom: 20px;">Use your database management tool (e.g., phpMyAdmin) directly for extensive CSV exports until the dedicated export module is integrated.</p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Create gradient
    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(0, 255, 204, 0.5)');
    gradient.addColorStop(1, 'rgba(0, 255, 204, 0.05)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= $monthsJson ?>,
            datasets: [{
                label: 'Revenue (PHP)',
                data: <?= $revenuesJson ?>,
                borderColor: '#00ffcc',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: '#00ffcc',
                pointBorderColor: '#fff',
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#a0a0b0' }
                },
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#a0a0b0' }
                }
            }
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
