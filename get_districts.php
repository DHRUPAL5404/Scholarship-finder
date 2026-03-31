<?php
include 'db.php';

$state_id = isset($_GET['state_id']) ? intval($_GET['state_id']) : 0;

if ($state_id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT district_id, district_name FROM districts WHERE state_id = ? ORDER BY district_name");
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $state_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while($row = mysqli_fetch_assoc($result)){
            echo "<option value='{$row['district_id']}'>" . htmlspecialchars($row['district_name']) . "</option>";
        }
        mysqli_stmt_close($stmt);
    } else {
        echo "<option value=''>Error loading districts</option>";
    }
}
?>
