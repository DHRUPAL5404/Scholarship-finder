<?php
include "db.php";
$id=$_GET['id'];

mysqli_query($conn,"
UPDATE scholarships 
SET status = IF(status='active','inactive','active')
WHERE scholarship_id=$id
");

header("Location: manage_scholarships.php");