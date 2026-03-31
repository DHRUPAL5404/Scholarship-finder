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
    <title>Manage Scholarships - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <h2>Manage Scholarships</h2>
        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        <?php
        include "db.php";
        $result = mysqli_query($conn,"SELECT * FROM scholarships");
        ?>
        <?php while($s=mysqli_fetch_assoc($result)){ ?>
        <div class="manage-scholarship-card">
            <b><?= $s['title'] ?></b><br>
            Status: <span class="status-chip <?= $s['status'] === 'active' ? 'success' : 'muted' ?>"><?= $s['status'] ?></span><br>
            <?php if(!empty($s['start_date']) || !empty($s['end_date'])): ?>
            Start Date: <?= htmlspecialchars($s['start_date'] ?? '-') ?><br>
            End Date: <?= htmlspecialchars($s['end_date'] ?? '-') ?><br>
            <?php endif; ?>
            <a href="toggle_scholarship.php?id=<?= $s['scholarship_id'] ?>">Toggle Status</a>
        </div>
        <?php } ?>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
