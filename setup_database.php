<?php
session_start();
include "db.php";

// Disable mysqli exceptions to prevent fatal errors
mysqli_report(MYSQLI_REPORT_OFF);

// Create users table
$users_table = "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mobile VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Create states table
$states_table = "CREATE TABLE IF NOT EXISTS states (
    state_id INT PRIMARY KEY AUTO_INCREMENT,
    state_name VARCHAR(255) NOT NULL UNIQUE
)";

// Create districts table
$districts_table = "CREATE TABLE IF NOT EXISTS districts (
    district_id INT PRIMARY KEY AUTO_INCREMENT,
    state_id INT NOT NULL,
    district_name VARCHAR(255) NOT NULL,
    FOREIGN KEY (state_id) REFERENCES states(state_id) ON DELETE CASCADE,
    UNIQUE KEY unique_district (state_id, district_name)
)";

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
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    deadline DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(state_id) ON DELETE SET NULL
)";

// Create student_profile table
$student_profile_table = "CREATE TABLE IF NOT EXISTS student_profile (
    profile_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(255),
    email VARCHAR(255),
    education_level VARCHAR(255),
    marks DECIMAL(5,2),
    family_income INT,
    category VARCHAR(100),
    gender VARCHAR(50),
    state_id INT,
    district_id INT,
    institution_type VARCHAR(100),
    age INT,
    disability_type VARCHAR(100) DEFAULT 'None',
    disability_percent INT DEFAULT 0,
    minority_status VARCHAR(10),
    parent_name VARCHAR(255),
    parent_occupation VARCHAR(255),
    parent_contact VARCHAR(20),
    course VARCHAR(255),
    current_year VARCHAR(50),
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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

// Execute users table creation
if(mysqli_query($conn, $users_table)) {
    $results[] = "✓ Users table created/verified";
} else {
    $results[] = "✗ Error creating users table: " . mysqli_error($conn);
}

// Execute states table creation
if(mysqli_query($conn, $states_table)) {
    $results[] = "✓ States table created/verified";
} else {
    $results[] = "✗ Error creating states table: " . mysqli_error($conn);
}

// Execute districts table creation
if(mysqli_query($conn, $districts_table)) {
    $results[] = "✓ Districts table created/verified";
} else {
    $results[] = "✗ Error creating districts table: " . mysqli_error($conn);
}

// Execute scholarships table creation
if(mysqli_query($conn, $scholarships_table)) {
    $results[] = "✓ Scholarships table created/verified";
} else {
    $results[] = "✗ Error creating scholarships table: " . mysqli_error($conn);
}

// Execute student profile table creation
if(mysqli_query($conn, $student_profile_table)) {
    $results[] = "✓ Student profile table created/verified";
} else {
    $results[] = "✗ Error creating student profile table: " . mysqli_error($conn);
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
    "CREATE INDEX IF NOT EXISTS idx_scholarship_state ON scholarships(state_id)",
    "CREATE INDEX IF NOT EXISTS idx_applications_user ON scholarship_applications(user_id)"
);

foreach($indexes as $index_query) {
    @mysqli_query($conn, $index_query);
}

// Insert sample states if table is empty
$state_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM states"));
if($state_count['count'] == 0) {
    $states_data = "INSERT INTO states (state_name) VALUES 
    ('Andhra Pradesh'), ('Arunachal Pradesh'), ('Assam'), ('Bihar'), ('Chhattisgarh'),
    ('Goa'), ('Gujarat'), ('Haryana'), ('Himachal Pradesh'), ('Jharkhand'),
    ('Karnataka'), ('Kerala'), ('Madhya Pradesh'), ('Maharashtra'), ('Manipur'),
    ('Meghalaya'), ('Mizoram'), ('Nagaland'), ('Odisha'), ('Punjab'),
    ('Rajasthan'), ('Sikkim'), ('Tamil Nadu'), ('Telangana'), ('Tripura'),
    ('Uttar Pradesh'), ('Uttarakhand'), ('West Bengal'), ('Delhi'), ('Puducherry')";
    
    if(mysqli_query($conn, $states_data)) {
        $results[] = "✓ Sample states inserted";
    }
}

// Insert sample districts if table is empty
$district_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM districts"));
if($district_count['count'] == 0) {
    $districts_data = "INSERT INTO districts (state_id, district_name) VALUES 
    (1, 'Visakhapatnam'), (1, 'Krishna'), (1, 'Guntur'), (1, 'Nellore'),
    (11, 'Bangalore'), (11, 'Mysore'), (11, 'Belgaum'), (11, 'Mangalore'),
    (12, 'Thiruvananthapuram'), (12, 'Ernakulam'), (12, 'Kottayam'), (12, 'Kozhikode'),
    (14, 'Mumbai'), (14, 'Pune'), (14, 'Nagpur'), (14, 'Aurangabad'),
    (27, 'Lucknow'), (27, 'Varanasi'), (27, 'Kanpur'), (27, 'Agra'),
    (28, 'Dehradun'), (28, 'Haridwar'), (28, 'Nainital'),
    (29, 'Kolkata'), (29, 'Howrah'), (29, 'Darjeeling'), (29, 'Asansol'),
    (30, 'New Delhi'), (30, 'North Delhi')";
    
    if(mysqli_query($conn, $districts_data)) {
        $results[] = "✓ Sample districts inserted";
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
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
