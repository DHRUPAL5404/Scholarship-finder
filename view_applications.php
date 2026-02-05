<<<<<<< HEAD
<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$res=mysqli_query($conn,"
SELECT a.*,u.name,s.title 
FROM applications a
JOIN users u ON a.student_id=u.user_id
JOIN scholarships s ON s.scholarship_id=a.scholarship_id
");
?>

<h2>Applications</h2>
<?php while($a=mysqli_fetch_assoc($res)){ ?>
<div>
<?= $a['name'] ?> applied for <?= $a['title'] ?> | Status: <?= $a['status'] ?>
</div>
=======
<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$res=mysqli_query($conn,"
SELECT a.*,u.name,s.title 
FROM applications a
JOIN users u ON a.student_id=u.user_id
JOIN scholarships s ON s.scholarship_id=a.scholarship_id
");
?>

<h2>Applications</h2>
<?php while($a=mysqli_fetch_assoc($res)){ ?>
<div>
<?= $a['name'] ?> applied for <?= $a['title'] ?> | Status: <?= $a['status'] ?>
</div>
>>>>>>> dhruti
<?php } ?>