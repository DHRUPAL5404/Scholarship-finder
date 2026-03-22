<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$scholarship_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$scholarship = null;
$rules = [];

if($scholarship_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM scholarships WHERE scholarship_id = ?");
    $stmt->bind_param("i", $scholarship_id);
    $stmt->execute();
    $scholarship = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if($scholarship) {
        $rules_q = $conn->prepare("SELECT field_name, operator, value FROM eligibility_rules WHERE scholarship_id = ? ORDER BY rule_id ASC");
        $rules_q->bind_param("i", $scholarship_id);
        $rules_q->execute();
        $res = $rules_q->get_result();
        while($row = $res->fetch_assoc()) {
            $rules[] = $row;
        }
        $rules_q->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarship Details - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/student.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/validation.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="container">
    <?php if(!$scholarship): ?>
        <div class="alert danger">Scholarship not found.</div>
        <a href="student_dashboard.php" class="btn-details">Back to Dashboard</a>
    <?php else: ?>
        <h2><?php echo htmlspecialchars($scholarship['title'] ?? 'Scholarship'); ?></h2>

        <div class="scholarship-card eligible" style="max-width: 900px; margin: 0 auto;">
            <div class="card-body">
                <p><strong>Description:</strong><br><?php echo nl2br(htmlspecialchars($scholarship['description'] ?? '')); ?></p>
                <p><strong>Deadline:</strong> <?php echo !empty($scholarship['deadline']) ? date('d M, Y', strtotime($scholarship['deadline'])) : 'N/A'; ?></p>
                <?php if(!empty($scholarship['status'])): ?>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($scholarship['status']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <h3 style="margin-top: 24px;">Eligibility Rules</h3>
        <?php if(!empty($rules)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Operator</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($rules as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['field_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['operator']); ?></td>
                            <td><?php echo htmlspecialchars($r['value']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert warning">No eligibility rules found for this scholarship.</div>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="student_dashboard.php" class="btn-details">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>

<script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>

