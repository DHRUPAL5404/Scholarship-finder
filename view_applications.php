<?php
session_start();
include "db.php";
if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin'){
    header("Location: login.php");
    exit();
}

$res = @mysqli_query($conn,"
SELECT a.*,u.full_name,s.title 
FROM applications a
JOIN users u ON a.student_id=u.user_id
JOIN scholarships s ON s.scholarship_id=a.scholarship_id
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/validation.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <h2>Applications</h2>

        <?php if(!$res): ?>
            <div class="alert danger">Applications table is unavailable. Please check database setup.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Scholarship</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($a=mysqli_fetch_assoc($res)){ ?>
                    <tr>
                        <td><?= htmlspecialchars($a['full_name']) ?></td>
                        <td><?= htmlspecialchars($a['title']) ?></td>
                        <td><?= htmlspecialchars($a['status']) ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
