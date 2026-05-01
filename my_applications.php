<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch applications
$stmt = $conn->prepare("
    SELECT a.*, s.title, s.amount, s.deadline 
    FROM scholarship_applications a
    JOIN scholarships s ON a.scholarship_id = s.scholarship_id
    WHERE a.user_id = ?
    ORDER BY a.application_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .applications-container { max-width: 900px; margin: 30px auto; padding: 20px; background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-bottom: 20px; }
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f1f5f9; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; color: #fff; display: inline-block; text-align: center; }
        .badge-pending { background-color: #f59e0b; }
        .badge-approved { background-color: #10b981; }
        .badge-rejected { background-color: #ef4444; }
        
        .btn-back { display: inline-block; margin-bottom: 20px; padding: 8px 15px; background: #e2e8f0; color: #334155; text-decoration: none; border-radius: 5px; font-weight: 600; }
        .btn-back:hover { background: #cbd5e1; }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="applications-container">
    <a href="student_dashboard.php" class="btn-back">← Back to Dashboard</a>
    <h2>📝 My Scholarship Applications</h2>
    
    <?php if ($applications->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Scholarship Name</th>
                        <th>Amount</th>
                        <th>Applied On</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($app = $applications->fetch_assoc()): 
                        $status = strtolower($app['status']);
                        $badge_class = 'badge-pending';
                        if ($status === 'approved') $badge_class = 'badge-approved';
                        elseif ($status === 'rejected') $badge_class = 'badge-rejected';
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($app['title']) ?></strong></td>
                        <td>₹<?= number_format($app['amount']) ?></td>
                        <td><?= date('d M Y, h:i A', strtotime($app['application_date'])) ?></td>
                        <td><span class="badge <?= $badge_class ?>"><?= ucfirst(htmlspecialchars($app['status'])) ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #64748b;">
            <p style="font-size: 1.1rem; margin-bottom: 10px;">You haven't applied for any scholarships yet.</p>
            <a href="student_dashboard.php" style="color: #667eea; text-decoration: underline;">Browse Scholarships</a>
        </div>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>

<script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
