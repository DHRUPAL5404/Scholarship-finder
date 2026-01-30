<?php
session_start();
include "db.php";

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    echo "Access Denied. Admin only.";
    exit();
}

// Create scholarships table
$scholarships_table = "CREATE TABLE IF NOT EXISTS scholarships (
    scholarship_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT,
    amount INT DEFAULT 0,
    category VARCHAR(100) DEFAULT 'General',
    education_level VARCHAR(255) DEFAULT '',
    min_marks DECIMAL(5,2) DEFAULT 0,
    max_family_income INT DEFAULT 0,
    state_id INT DEFAULT NULL,
    deadline DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(state_id) ON DELETE SET NULL
)";

// Create scholarship_applications table
$applications_table = "CREATE TABLE IF NOT EXISTS scholarship_applications (
    application_id INT PRIMARY KEY AUTO_INCREMENT,
    scholarship_id INT NOT NULL,
    user_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes LONGTEXT,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(scholarship_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
)";

$results = array();

// Execute scholarships table creation
if(mysqli_query($conn, $scholarships_table)) {
    $results[] = "✓ Scholarships table created/verified";
} else {
    $results[] = "✗ Error creating scholarships table: " . mysqli_error($conn);
}

// Execute applications table creation
if(mysqli_query($conn, $applications_table)) {
    $results[] = "✓ Scholarship applications table created/verified";
} else {
    $results[] = "✗ Error creating applications table: " . mysqli_error($conn);
}

// Check scholarships table structure and add missing columns
$alter_table = "ALTER TABLE scholarships ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'General'";
if(mysqli_query($conn, $alter_table)) {
    $results[] = "✓ Category column verified";
} else {
    $results[] = "⚠ Category column check: " . mysqli_error($conn);
}

// Create indexes for performance
$indexes = array(
    "CREATE INDEX IF NOT EXISTS idx_scholarship_status ON scholarships(status)",
    "CREATE INDEX IF NOT EXISTS idx_scholarship_deadline ON scholarships(deadline)",
    "CREATE INDEX IF NOT EXISTS idx_scholarship_category ON scholarships(category)",
    "CREATE INDEX IF NOT EXISTS idx_scholarship_education ON scholarships(education_level)",
    "CREATE INDEX IF NOT EXISTS idx_scholarship_state ON scholarships(state_id)",
    "CREATE INDEX IF NOT EXISTS idx_applications_user ON scholarship_applications(user_id)"
);

foreach($indexes as $index_query) {
    if(mysqli_query($conn, $index_query)) {
        $results[] = "✓ Index verified";
    }
}

// Insert sample scholarships if table is empty
$count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM scholarships"));

if($count['count'] == 0) {
    $sample_data = "INSERT INTO scholarships (title, description, amount, category, education_level, min_marks, max_family_income, state_id, deadline, status) VALUES
    ('Merit Scholarship 2025', 'For students with excellent academic performance', 50000, 'General', 'Undergraduate', 80, 500000, NULL, '2026-06-30', 'active'),
    ('SC Category Scholarship', 'Special scholarship for SC category students', 75000, 'SC', 'Undergraduate', 60, 300000, NULL, '2026-05-15', 'active'),
    ('ST Category Scholarship', 'Special scholarship for ST category students', 75000, 'ST', 'Postgraduate', 65, 400000, NULL, '2026-07-31', 'active'),
    ('OBC Category Scholarship', 'Special scholarship for OBC category students', 60000, 'OBC', 'Undergraduate', 70, 350000, NULL, '2026-04-30', 'active'),
    ('Women Empowerment Scholarship', 'Special scholarship for girl students', 45000, 'General', 'Undergraduate', 75, 600000, NULL, '2026-05-31', 'active'),
    ('Minority Scholarship', 'Scholarship for minority community students', 55000, 'General', 'Undergraduate', 65, 400000, NULL, '2026-06-15', 'active'),
    ('Sports Excellence Award', 'For students excelling in sports', 80000, 'General', 'Undergraduate', 50, 700000, NULL, '2026-03-31', 'active'),
    ('Science & Tech Scholarship', 'For Science and Technology students', 70000, 'General', 'Undergraduate', 85, 550000, NULL, '2026-08-31', 'active')";
    
    if(mysqli_query($conn, $sample_data)) {
        $results[] = "✓ Sample scholarships inserted";
    } else {
        $results[] = "⚠ Sample data: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Database Setup - ScholarMatch</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-bottom: 20px;
        }
        .results {
            list-style: none;
            padding: 0;
        }
        .results li {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            background: #f0f0f0;
            border-left: 4px solid #667eea;
        }
        .results li:before {
            content: attr(data-icon);
            margin-right: 10px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #667eea;
            color: #0c5460;
        }
        .button-group {
            margin-top: 30px;
            display: flex;
            gap: 10px;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s;
        }
        a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>🗄️ Database Setup Results</h1>
    <p>The following operations have been performed:</p>
    
    <ul class="results">
        <?php foreach($results as $result): 
            $class = 'info';
            if(strpos($result, '✓') === 0) $class = 'success';
            elseif(strpos($result, '✗') === 0) $class = 'error';
            elseif(strpos($result, '⚠') === 0) $class = 'warning';
        ?>
        <li class="<?php echo $class; ?>"><?php echo htmlspecialchars($result); ?></li>
        <?php endforeach; ?>
    </ul>
    
    <div class="button-group">
        <a href="student_dashboard.php">Go to Student Dashboard</a>
        <a href="manage_scholarships.php">Manage Scholarships</a>
    </div>
</div>

</body>
</html>
