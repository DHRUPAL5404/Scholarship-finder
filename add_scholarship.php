<?php
session_start();
include "db.php";

if($_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

if(isset($_POST['add'])){
    $title = $_POST['title'];
    $desc = $_POST['description'];
    $deadline = $_POST['deadline'];

    mysqli_query($conn,"INSERT INTO scholarships(title,description,deadline,status)
    VALUES('$title','$desc','$deadline','active')");
    
    echo "Scholarship Added ✅";
}
?>

<h2>Add Scholarship</h2>
<form method="post">
    <input type="text" name="title" placeholder="Title" required><br><br>
    <textarea name="description" placeholder="Description" required></textarea><br><br>
    <input type="date" name="deadline" required><br><br>
    <button name="add">Add</button>
</form>