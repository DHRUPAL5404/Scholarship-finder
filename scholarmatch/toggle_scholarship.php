<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id > 0) {
    $stmt = mysqli_prepare($conn, "UPDATE scholarships SET status = IF(status='active','inactive','active') WHERE scholarship_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        $ok = mysqli_stmt_execute($stmt);
        if($ok){
            $_SESSION['flash_success'] = "Scholarship status updated successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to update scholarship status.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['flash_error'] = "Database error: Failed to update.";
    }
} else {
    $_SESSION['flash_error'] = "Invalid scholarship selected.";
}

header("Location: manage_scholarships.php");
exit();
?>
