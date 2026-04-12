<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access.");
}
include "db.php";

echo "<h2>Database Test</h2>";

// Check tables
echo "<h3>Tables in Database:</h3>";
$result = mysqli_query($conn, "SHOW TABLES");
if($result) {
    while($row = mysqli_fetch_array($result)) {
        echo $row[0] . "<br>";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check student_profile structure
echo "<h3>Student Profile Table Structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE student_profile");
if($result) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . mysqli_error($conn);
}

// Test INSERT
if(isset($_POST['test_insert'])) {
    echo "<h3>Testing INSERT:</h3>";
    $test_query = "INSERT INTO student_profile (user_id, full_name, email) VALUES (999, 'Test User', 'test@example.com')";
    if(mysqli_query($conn, $test_query)) {
        echo "INSERT successful!";
    } else {
        echo "INSERT Error: " . mysqli_error($conn);
    }
}

// Test UPDATE
if(isset($_POST['test_update'])) {
    echo "<h3>Testing UPDATE:</h3>";
    $test_query = "UPDATE student_profile SET full_name='Updated Test' WHERE user_id=999";
    if(mysqli_query($conn, $test_query)) {
        echo "UPDATE successful!";
    } else {
        echo "UPDATE Error: " . mysqli_error($conn);
    }
}
?>

<form method="POST">
    <button type="submit" name="test_insert">Test INSERT</button>
    <button type="submit" name="test_update">Test UPDATE</button>
</form>
