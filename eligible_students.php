<<<<<<< HEAD
<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$sch = mysqli_query($conn,"SELECT * FROM scholarships");

if(isset($_POST['check'])){
    $sid=$_POST['scholarship'];

    $rules = mysqli_query($conn,"SELECT * FROM eligibility_rules WHERE scholarship_id=$sid");
    $conditions=[];

    while($r=mysqli_fetch_assoc($rules)){
        $conditions[]="sp.{$r['field_name']} {$r['operator']} '{$r['value']}'";
    }

    $sql="SELECT u.name,sp.* FROM student_profile sp 
          JOIN users u ON u.user_id=sp.user_id";

    if($conditions){
        $sql.=" WHERE ".implode(" AND ",$conditions);
    }

    $result=mysqli_query($conn,$sql);
}
?>

<h2>Eligible Students</h2>

<form method="post">
<select name="scholarship">
<?php while($s=mysqli_fetch_assoc($sch)){ ?>
<option value="<?= $s['scholarship_id'] ?>"><?= $s['title'] ?></option>
<?php } ?>
</select>
<button name="check">Check</button>
</form>

<?php if(isset($result)){ 
while($st=mysqli_fetch_assoc($result)){
echo "<p>{$st['name']} ({$st['marks']}%)</p>";
=======
<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$sch = mysqli_query($conn,"SELECT * FROM scholarships");

if(isset($_POST['check'])){
    $sid=$_POST['scholarship'];

    $rules = mysqli_query($conn,"SELECT * FROM eligibility_rules WHERE scholarship_id=$sid");
    $conditions=[];

    while($r=mysqli_fetch_assoc($rules)){
        $conditions[]="sp.{$r['field_name']} {$r['operator']} '{$r['value']}'";
    }

    $sql="SELECT u.name,sp.* FROM student_profile sp 
          JOIN users u ON u.user_id=sp.user_id";

    if($conditions){
        $sql.=" WHERE ".implode(" AND ",$conditions);
    }

    $result=mysqli_query($conn,$sql);
}
?>

<h2>Eligible Students</h2>

<form method="post">
<select name="scholarship">
<?php while($s=mysqli_fetch_assoc($sch)){ ?>
<option value="<?= $s['scholarship_id'] ?>"><?= $s['title'] ?></option>
<?php } ?>
</select>
<button name="check">Check</button>
</form>

<?php if(isset($result)){ 
while($st=mysqli_fetch_assoc($result)){
echo "<p>{$st['name']} ({$st['marks']}%)</p>";
>>>>>>> dhruti
}} ?>