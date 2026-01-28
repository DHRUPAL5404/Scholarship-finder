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

// Get filter values
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$education_filter = isset($_GET['education']) ? $_GET['education'] : '';
$state_filter = isset($_GET['state']) ? $_GET['state'] : '';
$search_query = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'deadline'; // deadline or title
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build scholarship query
$where_conditions = array("status = 'active'");

if($search_query) {
    $where_conditions[] = "(title LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

if($category_filter) {
    $where_conditions[] = "(category LIKE '%$category_filter%' OR category = 'General')";
}

if($education_filter) {
    $where_conditions[] = "education_level LIKE '%$education_filter%'";
}

if($state_filter) {
    $where_conditions[] = "state_id = '$state_filter'";
}

$where_clause = implode(" AND ", $where_conditions);

// Count total scholarships
$count_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM scholarships WHERE $where_clause");
$count_row = mysqli_fetch_assoc($count_result);
$total_scholarships = $count_row['total'];
$total_pages = ceil($total_scholarships / $per_page);

// Sort options
$order_clause = "deadline ASC";
if($sort_by == 'title') {
    $order_clause = "title ASC";
} elseif($sort_by == 'newest') {
    $order_clause = "created_date DESC";
}

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

// Fetch states for filter
$states_result = mysqli_query($conn, "SELECT * FROM states ORDER BY state_name");

// Get unique categories - with try-catch error handling
try {
    $categories_result = mysqli_query($conn, "SELECT DISTINCT category FROM scholarships WHERE status='active'");
    if(!$categories_result || mysqli_num_rows($categories_result) == 0) {
        throw new Exception("No categories found");
    }
} catch (Exception $e) {
    // Fallback if table or column doesn't exist
    $categories_result = mysqli_query($conn, "SELECT 'General' as category UNION SELECT 'SC' UNION SELECT 'ST' UNION SELECT 'OBC'");
}

// Get unique education levels - with try-catch error handling
try {
    $education_result = mysqli_query($conn, "SELECT DISTINCT education_level FROM scholarships WHERE status='active'");
    if(!$education_result || mysqli_num_rows($education_result) == 0) {
        throw new Exception("No education levels found");
    }
} catch (Exception $e) {
    // Fallback if table or column doesn't exist
    $education_result = mysqli_query($conn, "SELECT 'Undergraduate' as education_level UNION SELECT 'Postgraduate' UNION SELECT 'PhD'");
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
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div>
            <h1>📚 Scholarship Dashboard</h1>
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
        ⏰ <strong><?php echo $expiring_count; ?> scholarships</strong> are expiring within the next 7 days!
    </div>
    <?php endif; ?>
    
    <?php if(!$profile): ?>
    <div class="alert">
        ⚠️ Please <a href="profile.php" style="color: inherit; text-decoration: underline;">complete your profile</a> to see accurate eligibility.
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="filters-section">
        <h3>🔍 Search & Filter Scholarships</h3>
        <form method="GET" action="">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>Search by Title/Description</label>
                    <input type="text" name="search" placeholder="Search scholarships..." value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                
                <div class="filter-group">
                    <label>Category</label>
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category_filter == $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Education Level</label>
                    <select name="education">
                        <option value="">All Levels</option>
                        <?php while($edu = mysqli_fetch_assoc($education_result)): ?>
                        <option value="<?php echo htmlspecialchars($edu['education_level']); ?>" <?php echo $education_filter == $edu['education_level'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($edu['education_level']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>State</label>
                    <select name="state">
                        <option value="">All States</option>
                        <?php while($state = mysqli_fetch_assoc($states_result)): ?>
                        <option value="<?php echo $state['state_id']; ?>" <?php echo $state_filter == $state['state_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($state['state_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Sort By</label>
                    <select name="sort">
                        <option value="deadline" <?php echo $sort_by == 'deadline' ? 'selected' : ''; ?>>Deadline (Nearest First)</option>
                        <option value="title" <?php echo $sort_by == 'title' ? 'selected' : ''; ?>>Title (A-Z)</option>
                        <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    </select>
                </div>
            </div>
            
            <div class="filter-buttons">
                <button type="submit" class="btn btn-primary">🔍 Search</button>
                <a href="student_dashboard.php" class="btn btn-secondary" style="text-decoration: none; display: inline-block;">↺ Clear Filters</a>
            </div>
        </form>
    </div>
    
    <!-- Results Info -->
    <div class="results-info">
        Showing <strong><?php echo ($offset + 1); ?> - <?php echo min($offset + $per_page, $total_scholarships); ?></strong> of <strong><?php echo $total_scholarships; ?></strong> scholarships
    </div>
    
    <!-- Setup Alert -->
    <?php if(isset($setup_needed) && $setup_needed): ?>
    <div class="alert" style="background: #ffebee; border-color: #ef5350; color: #c62828;">
        ⚠️ <strong>Database Setup Required</strong><br>
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
                        📅 Deadline: <?php echo date('d M, Y', strtotime($scholarship['deadline'])); ?>
                        <?php if($is_urgent && $days_left >= 0): ?>
                        <br>⏰ <strong><?php echo $days_left; ?> days left</strong>
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
            😕 No scholarships found matching your filters. Try adjusting your search.
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php if($page > 1): ?>
        <a href="?page=1&category=<?php echo urlencode($category_filter); ?>&education=<?php echo urlencode($education_filter); ?>&state=<?php echo urlencode($state_filter); ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo urlencode($sort_by); ?>">« First</a>
        <a href="?page=<?php echo $page-1; ?>&category=<?php echo urlencode($category_filter); ?>&education=<?php echo urlencode($education_filter); ?>&state=<?php echo urlencode($state_filter); ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo urlencode($sort_by); ?>">‹ Previous</a>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <?php if($i == $page): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category_filter); ?>&education=<?php echo urlencode($education_filter); ?>&state=<?php echo urlencode($state_filter); ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo urlencode($sort_by); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if($page < $total_pages): ?>
        <a href="?page=<?php echo $page+1; ?>&category=<?php echo urlencode($category_filter); ?>&education=<?php echo urlencode($education_filter); ?>&state=<?php echo urlencode($state_filter); ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo urlencode($sort_by); ?>">Next ›</a>
        <a href="?page=<?php echo $total_pages; ?>&category=<?php echo urlencode($category_filter); ?>&education=<?php echo urlencode($education_filter); ?>&state=<?php echo urlencode($state_filter); ?>&search=<?php echo urlencode($search_query); ?>&sort=<?php echo urlencode($sort_by); ?>">Last »</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>