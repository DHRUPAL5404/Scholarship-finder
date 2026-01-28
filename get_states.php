<?php
include 'db.php';

$result = mysqli_query($conn, "SELECT state_id, state_name FROM states ORDER BY state_name");

while($row = mysqli_fetch_assoc($result)){
    echo "<option value='{$row['state_id']}'>{$row['state_name']}</option>";
}
?>
