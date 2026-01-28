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
    <title>Database Status Check - ScholarMatch</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }
        .status-item {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 5px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-item.pass {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .status-item.fail {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        .badge.pass {
            background: #28a745;
            color: white;
        }
        .badge.fail {
            background: #dc3545;
            color: white;
        }
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        a, button {
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #764ba2;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .summary {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
        }
        .summary.ready {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
        }
        .summary.not-ready {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🔍 Database Setup Status</h1>
    
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
            ✅ Database is fully configured and ready to use!
        <?php else: ?>
            ⚠️ Database needs to be set up
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
