<?php
include 'db.php';

$state_id = $_GET['state_id'];
$result = mysqli_query($conn, "SELECT district_id, district_name FROM districts WHERE state_id = $state_id ORDER BY district_name");

while($row = mysqli_fetch_assoc($result)){
    echo "<option value='{$row['district_id']}'>{$row['district_name']}</option>";
}
?>
