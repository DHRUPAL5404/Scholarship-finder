<?php
session_start();
include "db.php";

// Set error handling to not throw exceptions initially, we'll handle them
mysqli_report(MYSQLI_REPORT_OFF);

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch student profile
$profile = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=$user_id")
);

// Pagination setup
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build scholarship query - show all active scholarships
$where_clause = "status = 'active'";

// Count total scholarships
$count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM scholarships WHERE $where_clause");
$count_row = mysqli_fetch_assoc($count_result);
$total_scholarships = $count_row['total'];
$total_pages = ceil($total_scholarships / $per_page);

// Default sort by deadline
$order_clause = "deadline ASC";

// Check if scholarships table exists
$table_check = mysqli_query($conn, "SELECT 1 FROM scholarships LIMIT 1");
$setup_needed = false;

if(!$table_check) {
    $setup_needed = true;
    $total_scholarships = 0;
    $total_pages = 1;
    $scholarships_result = false;
} else {
    // Fetch scholarships
    $scholarships_query = "SELECT * FROM scholarships 
                           WHERE $where_clause 
                           ORDER BY $order_clause 
                           LIMIT $offset, $per_page";
    $scholarships_result = mysqli_query($conn, $scholarships_query);

    if(!$scholarships_result) {
        // If query fails
        $setup_needed = true;
        $scholarships_result = false;
        $total_scholarships = 0;
        $total_pages = 1;
    } else {
        $setup_needed = false;
    }
}

// Function to determine eligibility
function checkEligibility($scholarship, $student_profile) {
    $eligibility = array(
        'status' => 'eligible', // eligible, not_eligible, partial
        'issues' => array(),
        'percentage' => 100
    );
    
    // Check category eligibility
    if($scholarship['category'] != 'General' && $scholarship['category'] != $student_profile['category']) {
        if($scholarship['category'] == 'OBC' || $scholarship['category'] == 'SC' || $scholarship['category'] == 'ST') {
            // These can include General category
            if(strpos($student_profile['category'], 'General') === false) {
                $eligibility['issues'][] = "Category mismatch";
                $eligibility['percentage'] -= 20;
            }
        }
    }
    
    // Check marks eligibility
    if($scholarship['min_marks'] > 0 && $student_profile['marks'] < $scholarship['min_marks']) {
        $eligibility['issues'][] = "Marks below minimum ({$scholarship['min_marks']}%)";
        $eligibility['percentage'] -= 30;
    }
    
    // Check family income eligibility
    if($scholarship['max_family_income'] > 0 && $student_profile['family_income'] > $scholarship['max_family_income']) {
        $eligibility['issues'][] = "Family income exceeds limit";
        $eligibility['percentage'] -= 25;
    }
    
    // Check education level eligibility
    if($scholarship['education_level'] && strpos($student_profile['education_level'], $scholarship['education_level']) === false) {
        $eligibility['issues'][] = "Education level mismatch";
        $eligibility['percentage'] -= 20;
    }
    
    // Check state eligibility
    if($scholarship['state_id'] && $scholarship['state_id'] != $student_profile['state_id']) {
        $eligibility['issues'][] = "Not available in your state";
        $eligibility['percentage'] -= 50;
    }
    
    // Determine status based on percentage
    if($eligibility['percentage'] >= 80) {
        $eligibility['status'] = 'eligible';
    } elseif($eligibility['percentage'] >= 50) {
        $eligibility['status'] = 'partial';
    } else {
        $eligibility['status'] = 'not_eligible';
    }
    
    return $eligibility;
}

// Check for expiring scholarships (within 7 days) - with error handling
try {
    $expiring_query = "SELECT COUNT(*) as count FROM scholarships 
                       WHERE status='active' AND deadline <= DATE_ADD(NOW(), INTERVAL 7 DAY) 
                       AND deadline >= NOW()";
    $expiring_result = mysqli_query($conn, $expiring_query);
    $expiring_row = mysqli_fetch_assoc($expiring_result);
    $expiring_count = $expiring_row['count'];
} catch (Exception $e) {
    $expiring_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/navbar-footer.css">
</head>
<body>

    <!-- Navbar -->
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#how-it-works">How It Works</a></li>
            <li><a href="index.php#features">Features</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <!-- Header -->
        <style>
            .results-info { display: none; }
        </style>
        <div class="header">
            <div>
                <h1> Student Dashboard</h1>
                <p style="color: #999; margin-top: 5px;">Welcome, <?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <div class="header-actions">
                <a href="profile.php">Update Profile</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
        
        <!-- Alerts -->
        <?php if($expiring_count > 0): ?>
        <div class="alert">
            <strong><?php echo $expiring_count; ?> scholarships</strong> are expiring within the next 7 days!
        </div>
        <?php endif; ?>
        
        <?php if(!$profile): ?>
        <div class="alert">
             Please <a href="profile.php" style="color: inherit; text-decoration: underline;">complete your profile</a> to see accurate eligibility.
        </div>
        <?php endif; ?>
        
        <!-- Results Info -->
        <div class="results-info">
            Showing <strong><?php echo ($offset + 1); ?> - <?php echo min($offset + $per_page, $total_scholarships); ?></strong> of <strong><?php echo $total_scholarships; ?></strong> scholarships
        </div>
        
        <!-- Setup Alert -->
        <?php if(isset($setup_needed) && $setup_needed): ?>
        <div class="alert" style="background: #ffebee; border-color: #ef5350; color: #c62828;">
             <strong>Database Setup Required</strong><br>
            The scholarships table needs to be created. 
            <a href="setup_database.php" style="color: #c62828; text-decoration: underline; font-weight: bold;">Click here to set up the database automatically</a>
        </div>
        <?php endif; ?>
        
        <!-- Scholarships Display -->
        <div id="cardView">
            <?php if($total_scholarships > 0 && $scholarships_result): ?>
            <div class="scholarships-grid">
                <?php while($scholarship = mysqli_fetch_assoc($scholarships_result)): ?>
                <?php 
                    $eligibility = checkEligibility($scholarship, $profile);
                    $days_left = round((strtotime($scholarship['deadline']) - time()) / (60 * 60 * 24));
                    $is_urgent = $days_left <= 7 && $days_left >= 0;
                ?>
                <div class="scholarship-card <?php echo $eligibility['status']; ?>">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($scholarship['title']); ?></h3>
                    </div>
                    
                    <div class="card-body">
                        <p><strong>Amount:</strong> ₹<?php echo number_format($scholarship['amount']); ?></p>
                        <p><?php echo htmlspecialchars(substr($scholarship['description'], 0, 100)); ?>...</p>
                        
                        <div class="deadline-badge <?php echo $is_urgent ? 'urgent' : ''; ?>">
                             Deadline: <?php echo date('d M, Y', strtotime($scholarship['deadline'])); ?>
                            <?php if($is_urgent && $days_left >= 0): ?>
                            <br> <strong><?php echo $days_left; ?> days left</strong>
                            <?php endif; ?>
                        </div>
                        
                        <div class="eligibility-status">
                            <span>Eligibility:</span>
                            <span class="status-badge <?php echo $eligibility['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $eligibility['status'])); ?>
                            </span>
                        </div>
                        
                        <?php if(count($eligibility['issues']) > 0): ?>
                        <div class="issues-list">
                            <strong>Issues:</strong>
                            <ul>
                                <?php foreach($eligibility['issues'] as $issue): ?>
                                <li><?php echo htmlspecialchars($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer">
                        <a href="view_applications.php?id=<?php echo $scholarship['scholarship_id']; ?>" class="btn-details">View Details</a>
                        <?php if($eligibility['status'] == 'eligible'): ?>
                        <a href="apply_scholarship.php?id=<?php echo $scholarship['scholarship_id']; ?>" class="btn-apply">Apply Now</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="no-scholarships">
                 No scholarships found. Please check back later.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
            <a href="?page=1">« First</a>
            <a href="?page=<?php echo $page-1; ?>">‹ Previous</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <?php if($i == $page): ?>
                <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
            <a href="?page=<?php echo $page+1; ?>">Next ›</a>
            <a href="?page=<?php echo $total_pages; ?>">Last »</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer id="footer">
        <div>
            <h4>ScholarMatch</h4>
            <p>&copy; <?php echo date('Y'); ?> ScholarMatch. All rights reserved.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#how-it-works">How It Works</a></li>
                <li><a href="index.php#features">Features</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
        <div>
            <h4>Contact</h4>
            <p>Email: info@scholarmatch.com</p>
            <p>Phone: (555) 123-4567</p>
        </div>
        <div>
            <h4>Follow Us</h4>
            <p>Facebook | Twitter | LinkedIn | Instagram</p>
        </div>
    </footer>

</body>
</html>