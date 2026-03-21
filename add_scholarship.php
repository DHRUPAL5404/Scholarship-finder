<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if(isset($_POST['add'])){
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO scholarships (title, description, deadline, status) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        $_SESSION['flash_error'] = "Database error: " . $conn->error;
    } else {
        $status = 'active';
        $stmt->bind_param("ssss", $title, $desc, $deadline, $status);
        
        if($stmt->execute()){
            $_SESSION['flash_success'] = "Scholarship added successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to add scholarship: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: add_scholarship.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Scholarship - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/admin.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/validation.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <h2>Add New Scholarship</h2>
        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="title" placeholder="Title" required><br><br>
            <textarea name="description" placeholder="Description" required></textarea><br><br>
            <input type="date" name="deadline" required><br><br>
            <button name="add">Add</button>
        </form>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
