<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
    $ok = mysqli_query($conn, "UPDATE scholarships SET status = IF(status='active','inactive','active') WHERE scholarship_id=$id");
    if($ok){
        $_SESSION['flash_success'] = "Scholarship status updated successfully.";
    } else {
        $_SESSION['flash_error'] = "Failed to update scholarship status.";
    }
} else {
    $_SESSION['flash_error'] = "Invalid scholarship selected.";
}

header("Location: manage_scholarships.php");
exit();
?>
