<?php
session_start();
include "db.php";

// Check database setup
function checkDatabaseSetup() {
    global $conn;
    
    $checks = array(
        'scholarships_table' => false,
        'has_category' => false,
        'has_education_level' => false,
        'has_sample_data' => false
    );
    
    // Check if scholarships table exists
    $result = mysqli_query($conn, "SELECT 1 FROM scholarships LIMIT 1");
    if($result) {
        $checks['scholarships_table'] = true;
        
        // Check for category column
        $result = mysqli_query($conn, "SELECT category FROM scholarships LIMIT 1");
        if($result) {
            $checks['has_category'] = true;
        }
        
        // Check for education_level column
        $result = mysqli_query($conn, "SELECT education_level FROM scholarships LIMIT 1");
        if($result) {
            $checks['has_education_level'] = true;
        }
        
        // Check if has data
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM scholarships");
        if($result) {
            $row = mysqli_fetch_assoc($result);
            $checks['has_sample_data'] = $row['count'] > 0;
        }
    }
    
    return $checks;
}

$checks = checkDatabaseSetup();
$all_good = $checks['scholarships_table'] && $checks['has_category'] && $checks['has_education_level'] && $checks['has_sample_data'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Status Check - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">
    <h1> Database Setup Status</h1>
    
    <div class="status-item <?php echo $checks['scholarships_table'] ? 'pass' : 'fail'; ?>">
        <span>Scholarships Table Exists</span>
        <span class="badge <?php echo $checks['scholarships_table'] ? 'pass' : 'fail'; ?>">
            <?php echo $checks['scholarships_table'] ? '✓ PASS' : '✗ FAIL'; ?>
        </span>
    </div>
    
    <div class="status-item <?php echo $checks['has_category'] ? 'pass' : 'fail'; ?>">
        <span>Category Column Exists</span>
        <span class="badge <?php echo $checks['has_category'] ? 'pass' : 'fail'; ?>">
            <?php echo $checks['has_category'] ? '✓ PASS' : '✗ FAIL'; ?>
        </span>
    </div>
    
    <div class="status-item <?php echo $checks['has_education_level'] ? 'pass' : 'fail'; ?>">
        <span>Education Level Column Exists</span>
        <span class="badge <?php echo $checks['has_education_level'] ? 'pass' : 'fail'; ?>">
            <?php echo $checks['has_education_level'] ? '✓ PASS' : '✗ FAIL'; ?>
        </span>
    </div>
    
    <div class="status-item <?php echo $checks['has_sample_data'] ? 'pass' : 'fail'; ?>">
        <span>Sample Scholarships Exist</span>
        <span class="badge <?php echo $checks['has_sample_data'] ? 'pass' : 'fail'; ?>">
            <?php echo $checks['has_sample_data'] ? '✓ PASS' : '✗ FAIL'; ?>
        </span>
    </div>
    
    <div class="summary <?php echo $all_good ? 'ready' : 'not-ready'; ?>">
        <?php if($all_good): ?>
             Database is fully configured and ready to use!
        <?php else: ?>
             Database needs to be set up
        <?php endif; ?>
    </div>
    
    <div class="action-buttons">
        <?php if(!$all_good): ?>
        <a href="setup_database.php" class="btn-primary">🔧 Run Database Setup</a>
        <?php endif; ?>
        <a href="student_dashboard.php" class="btn-<?php echo $all_good ? 'success' : 'secondary'; ?>">📊 Go to Dashboard</a>
    </div>
</div>

</body>
</html>
