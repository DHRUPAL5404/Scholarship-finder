<?php
include 'db.php';

$district_id = $_GET['district_id'];
$result = mysqli_query($conn, "SELECT sub_district_id, sub_district_name FROM sub_districts WHERE district_id = $district_id ORDER BY sub_district_name");

while($row = mysqli_fetch_assoc($result)){
    echo "<option value='{$row['sub_district_id']}'>{$row['sub_district_name']}</option>";
}
?>
