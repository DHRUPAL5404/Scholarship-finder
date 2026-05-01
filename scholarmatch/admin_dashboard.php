<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .charts-container { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 30px; }
        .chart-card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex: 1; min-width: 300px; text-align: center; }
        .chart-card h3 { color: #333; margin-bottom: 15px; }
    </style>
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container admin-home">
        <h2>Welcome Admin</h2>
        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        
        <ul class="admin-links" style="display: flex; gap: 10px; list-style: none; padding: 0; flex-wrap: wrap;">
            <li><a href="add_scholarship.php" class="btn">Add Scholarship</a></li>
            <li><a href="manage_scholarships.php" class="btn">Manage Scholarships</a></li>
            <li><a href="add_eligibility_rule.php" class="btn">Add Eligibility Rules</a></li>
            <li><a href="view_applications.php" class="btn">Manage Applications</a></li>
        </ul>

        <div class="charts-container">
            <div class="chart-card">
                <h3>Applications by Category</h3>
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Application Status</h3>
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

    <script>
        fetch('api_chart_data.php')
            .then(res => res.json())
            .then(data => {
                const catLabels = data.category_data.map(d => d.label);
                const catValues = data.category_data.map(d => d.value);

                if (catLabels.length > 0) {
                    new Chart(document.getElementById('categoryChart'), {
                        type: 'pie',
                        data: {
                            labels: catLabels,
                            datasets: [{
                                data: catValues,
                                backgroundColor: ['#667eea', '#764ba2', '#a78bfa', '#c4b5fd', '#818cf8']
                            }]
                        }
                    });
                } else {
                    document.getElementById('categoryChart').parentElement.innerHTML += '<p style="color:#888;margin-top:20px;">No category data available yet.</p>';
                }

                const statLabels = data.status_data.map(d => d.label);
                const statValues = data.status_data.map(d => d.value);

                if (statLabels.length > 0) {
                    new Chart(document.getElementById('statusChart'), {
                        type: 'bar',
                        data: {
                            labels: statLabels,
                            datasets: [{
                                label: 'Applications',
                                data: statValues,
                                backgroundColor: ['#f59e0b', '#10b981', '#ef4444', '#667eea'],
                                borderRadius: 6
                            }]
                        },
                        options: {
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                        }
                    });
                } else {
                    document.getElementById('statusChart').parentElement.innerHTML += '<p style="color:#888;margin-top:20px;">No status data available yet.</p>';
                }
            })
            .catch(err => console.error("Error loading chart data:", err));
    </script>
</body>
</html>
