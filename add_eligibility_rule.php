<?php
session_start();
include "db.php";
if($_SESSION['role']!='admin'){ header("Location: login.php"); exit(); }

$sch = mysqli_query($conn,"SELECT * FROM scholarships");

if(isset($_POST['add'])){
    mysqli_query($conn,"INSERT INTO eligibility_rules
    (scholarship_id,field_name,operator,value)
    VALUES
    ('$_POST[scholarship]','$_POST[field]','$_POST[operator]','$_POST[value]')");
    echo "Rule Added ✅";
}
?>

<h2>Add Eligibility Rule</h2>

<form method="post">
<select name="scholarship">
<?php while($s=mysqli_fetch_assoc($sch)){ ?>
<option value="<?= $s['scholarship_id'] ?>"><?= $s['title'] ?></option>
<?php } ?>
</select><br><br>

<select name="field">
<option value="marks">Marks</option>
<option value="family_income">Income</option>
<option value="category">Category</option>
<option value="gender">Gender</option>
<option value="state">State</option>

</select>

<select name="operator">
<option>=</option>
<option>>=</option>
<option><=</option>
</select>

<input type="text" name="value" required>
<button name="add">Add Rule</button>
</form>