<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
    mysqli_query($conn, "UPDATE scholarships SET status = IF(status='active','inactive','active') WHERE scholarship_id=$id");
}

header("Location: manage_scholarships.php");
exit();
?>