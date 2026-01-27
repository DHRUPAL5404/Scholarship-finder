<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
</head>
<body>

<h2>Welcome Admin 👑</h2>

<ul>
    <li><a href="add_scholarship.php">Add Scholarship</a></li>
    <li><a href="manage_scholarships.php">Manage Scholarships</a></li>
    <li><a href="add_eligibility_rule.php">Add Eligibility Rules</a></li>
    <li><a href="eligible_students.php">Eligible Students</a></li>
    <li><a href="view_applications.php">Applications</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

</body>
</html>