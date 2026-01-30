<<<<<<< HEAD
<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$result = mysqli_query($conn,"SELECT * FROM scholarships");
?>

<h2>Manage Scholarships</h2>

<?php while($s=mysqli_fetch_assoc($result)){ ?>
<div style="border:1px solid #ccc;padding:10px;margin:10px">
    <b><?= $s['title'] ?></b><br>
    Status: <?= $s['status'] ?><br>
    <a href="toggle_scholarship.php?id=<?= $s['scholarship_id'] ?>">Toggle Status</a>
</div>
=======
<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$result = mysqli_query($conn,"SELECT * FROM scholarships");
?>

<h2>Manage Scholarships</h2>

<?php while($s=mysqli_fetch_assoc($result)){ ?>
<div style="border:1px solid #ccc;padding:10px;margin:10px">
    <b><?= $s['title'] ?></b><br>
    Status: <?= $s['status'] ?><br>
    <a href="toggle_scholarship.php?id=<?= $s['scholarship_id'] ?>">Toggle Status</a>
</div>
>>>>>>> dhruti
<?php } ?>