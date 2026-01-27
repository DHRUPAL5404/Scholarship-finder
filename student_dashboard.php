<?php
session_start();
include "db.php";

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>




<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard - ScholarMatch</title>
    <style>
        body { font-family: Arial; background: #f4f6f8; text-align:center; }
        .card { background: #fff; width: 500px; margin: 50px auto; padding: 30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        a { display:block; margin: 10px 0; padding: 10px; background: #007bff; color:white; text-decoration:none; border-radius:5px; }
        a.logout { background:#dc3545; }
    </style>
</head>
<body>

<div class="card">
    <h2>Welcome, <?php echo $user_name; ?> 🎓</h2>

    <a href="profile.php">View / Update Profile</a>
    <a href="check_eligibility.php">Check Scholarship Eligibility</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

</body>
</html>