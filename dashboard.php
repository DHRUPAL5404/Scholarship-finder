<?php
session_start();

// login check
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - ScholarMatch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            text-align: center;
        }
        .card {
            background: white;
            width: 400px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        a {
            display: block;
            margin: 15px 0;
            padding: 10px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a.logout {
            background: #dc3545;
        }
    </style>
</head>
<body>

<div class="card">
    <h2>Welcome to ScholarMatch 🎓</h2>
    <p>You are successfully logged in.</p>

    <a href="check_eligibility.php">Check Scholarship Eligibility</a>
    <a href="logout.php" class="logout">Logout</a>
</div>

</body>
</html>