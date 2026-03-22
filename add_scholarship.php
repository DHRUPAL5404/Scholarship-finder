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

function ensureScholarshipDateColumns($conn) {
    $needed = [
        'start_date' => 'DATE NULL',
        'end_date' => 'DATE NULL'
    ];

    foreach($needed as $col => $def) {
        $check = mysqli_query($conn, "SHOW COLUMNS FROM scholarships LIKE '$col'");
        if($check && mysqli_num_rows($check) === 0) {
            mysqli_query($conn, "ALTER TABLE scholarships ADD COLUMN $col $def");
        }
    }
}

ensureScholarshipDateColumns($conn);

if(isset($_POST['add'])){
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    if($start_date === '' || $end_date === '') {
        $_SESSION['flash_error'] = "Please select both Start Date and End Date.";
        header("Location: add_scholarship.php");
        exit();
    }

    if(strtotime($start_date) > strtotime($end_date)) {
        $_SESSION['flash_error'] = "Start Date cannot be after End Date.";
        header("Location: add_scholarship.php");
        exit();
    }

    // Use prepared statement to prevent SQL injection
    // Keep deadline mapped to end_date for backward compatibility with existing pages
    $stmt = $conn->prepare("INSERT INTO scholarships (title, description, start_date, end_date, deadline, status) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $_SESSION['flash_error'] = "Database error: " . $conn->error;
    } else {
        $status = 'active';
        $deadline = $end_date;
        $stmt->bind_param("ssssss", $title, $desc, $start_date, $end_date, $deadline, $status);
        
        if($stmt->execute()){
            $_SESSION['flash_success'] = "Scholarship added successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to add scholarship: " . $stmt->error;
        }
        $stmt->close();
    }
    header("Location: admin_dashboard.php");
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
            <label>Start Date:</label><br>
            <input type="date" name="start_date" required><br><br>
            <label>End Date:</label><br>
            <input type="date" name="end_date" required><br><br>
            <button name="add">Add</button>
        </form>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
