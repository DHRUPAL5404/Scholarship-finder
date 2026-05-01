<?php
include 'db.php';

$stmt = mysqli_prepare($conn, "SELECT state_id, state_name FROM states ORDER BY state_name");

if ($stmt) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)){
        echo "<option value='{$row['state_id']}'>" . htmlspecialchars($row['state_name']) . "</option>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "<option value=''>Error loading states</option>";
}
?>
