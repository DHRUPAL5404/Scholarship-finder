<<<<<<< HEAD
<?php
include "db.php";
$id=$_GET['id'];

mysqli_query($conn,"
UPDATE scholarships 
SET status = IF(status='active','inactive','active')
WHERE scholarship_id=$id
");

=======
<?php
include "db.php";
$id=$_GET['id'];

mysqli_query($conn,"
UPDATE scholarships 
SET status = IF(status='active','inactive','active')
WHERE scholarship_id=$id
");

>>>>>>> dhruti
header("Location: manage_scholarships.php");