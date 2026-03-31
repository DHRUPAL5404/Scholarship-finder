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
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
$profile_success = $_SESSION['profile_success'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['profile_success']);

// Fetch student profile (safer)
$profile_query = mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=" . intval($user_id));
$profile = $profile_query ? mysqli_fetch_assoc($profile_query) : null;
$has_profile = !empty($profile);
if (!$has_profile) {
    $profile = [
        'category' => null,
        'marks' => 0,
        'family_income' => 0,
        'education_level' => null,
        'state_id' => null
    ];
}

// Get filter values
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Sort options
$order_clause = "deadline ASC";

// Check if scholarships table exists
$table_check = mysqli_query($conn, "SELECT 1 FROM scholarships LIMIT 1");
$setup_needed = false;

if(!$table_check) {
    $setup_needed = true;
    $total_scholarships = 0;
    $total_pages = 1;
    $scholarships = [];
} else {
    // Fetch all active scholarships first; eligibility filter is applied in PHP
    $scholarships_query = "SELECT * FROM scholarships WHERE status='active' ORDER BY $order_clause";
    $scholarships_result = mysqli_query($conn, $scholarships_query);

    if(!$scholarships_result) {
        $total_pages = 1;
        $scholarships = [];
    } else {
        $scholarships = [];
        while($row = mysqli_fetch_assoc($scholarships_result)) {
            $scholarships[] = $row;
        }
    }
}

// Function to determine eligibility (fallback checks based on scholarship table columns)
function checkEligibility($scholarship, $student_profile) {
    $eligibility = ['status' => 'eligible', 'issues' => []];

    $s_cat = $scholarship['category'] ?? null;
    $p_cat = $student_profile['category'] ?? null;
    if ($s_cat && $s_cat !== 'General' && $p_cat !== $s_cat) {
        $eligibility['issues'][] = 'Category mismatch';
    }

    $min_marks = intval($scholarship['min_marks'] ?? 0);
    $stu_marks = intval($student_profile['marks'] ?? 0);
    if ($min_marks > 0 && $stu_marks < $min_marks) {
        $eligibility['issues'][] = 'Insufficient marks';
    }

    $max_income = intval($scholarship['max_family_income'] ?? 0);
    $stu_income = intval($student_profile['family_income'] ?? 0);
    if ($max_income > 0 && $stu_income > $max_income) {
        $eligibility['issues'][] = 'Family income exceeds limit';
    }

    $req_edu = $scholarship['education_level'] ?? null;
    $stu_edu = $student_profile['education_level'] ?? null;
    if ($req_edu && $stu_edu && stripos($req_edu, $stu_edu) === false) {
        $eligibility['issues'][] = 'Education level mismatch';
    }

    $req_state = $scholarship['state_id'] ?? null;
    if ($req_state && $req_state != ($student_profile['state_id'] ?? null)) {
        $eligibility['issues'][] = 'State mismatch';
    }

    if (count($eligibility['issues']) > 0) {
        $eligibility['status'] = 'not_eligible';
    }

    return $eligibility;
}

// Rule-based eligibility using eligibility_rules table
function checkEligibilityByRules($conn, $scholarship, $student_profile) {
    static $rules_cache = [];
    static $profile_columns = null;
    static $rules_table_exists = null;

    $sid = intval($scholarship['scholarship_id'] ?? 0);
    if($sid <= 0) {
        return checkEligibility($scholarship, $student_profile);
    }

    if($rules_table_exists === null) {
        $rules_table_exists = mysqli_query($conn, "SELECT 1 FROM eligibility_rules LIMIT 1") ? true : false;
    }

    if($profile_columns === null) {
        $profile_columns = [];
        $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM student_profile");
        if($columns_result) {
            while($col = mysqli_fetch_assoc($columns_result)) {
                $profile_columns[$col['Field']] = true;
            }
        }
    }

    if(!$rules_table_exists) {
        return checkEligibility($scholarship, $student_profile);
    }

    if(!isset($rules_cache[$sid])) {
        $rules_cache[$sid] = [];
        $rules_q = mysqli_query($conn, "SELECT field_name, operator, value FROM eligibility_rules WHERE scholarship_id=$sid");
        if($rules_q) {
            while($r = mysqli_fetch_assoc($rules_q)) {
                $rules_cache[$sid][] = $r;
            }
        }
    }

    $rules = $rules_cache[$sid];
    if(empty($rules)) {
        return checkEligibility($scholarship, $student_profile);
    }

    $eligibility = ['status' => 'eligible', 'issues' => []];
    $allowed_operators = ['=', '>=', '<=', '>', '<'];

    foreach($rules as $r) {
        $field = trim($r['field_name'] ?? '');
        $operator = trim($r['operator'] ?? '=');
        $raw_value = trim($r['value'] ?? '');

        if($field === '' || !isset($profile_columns[$field])) {
            continue;
        }

        if(!in_array($operator, $allowed_operators, true)) {
            $operator = '=';
        }

        // Non-restrictive rules
        if(strcasecmp($raw_value, 'All') === 0 || strcasecmp($raw_value, 'All India') === 0) {
            continue;
        }

        $student_value = trim((string)($student_profile[$field] ?? ''));

        // '=' with comma list => IN behavior
        if($operator === '=' && strpos($raw_value, ',') !== false) {
            $parts = array_filter(array_map('trim', explode(',', $raw_value)), function($v){ return $v !== ''; });
            $matched = false;
            foreach($parts as $p) {
                if(strcasecmp($student_value, $p) === 0) {
                    $matched = true;
                    break;
                }
            }
            if(!$matched) {
                $eligibility['issues'][] = "$field mismatch";
            }
            continue;
        }

        if(in_array($operator, ['>=', '<=', '>', '<'], true)) {
            $student_num = is_numeric($student_value) ? floatval($student_value) : 0;
            $rule_num = is_numeric($raw_value) ? floatval($raw_value) : 0;

            $ok = true;
            if($operator === '>=') $ok = $student_num >= $rule_num;
            if($operator === '<=') $ok = $student_num <= $rule_num;
            if($operator === '>')  $ok = $student_num > $rule_num;
            if($operator === '<')  $ok = $student_num < $rule_num;

            if(!$ok) {
                $eligibility['issues'][] = "$field condition failed";
            }
            continue;
        }

        // '=' exact compare (case-insensitive)
        if(strcasecmp($student_value, $raw_value) !== 0) {
            $eligibility['issues'][] = "$field mismatch";
        }
    }

    if(!empty($eligibility['issues'])) {
        $eligibility['status'] = 'not_eligible';
    }

    return $eligibility;
}

// Apply eligibility filter for student dashboard (show only eligible)
$eligible_scholarships = [];
if(!empty($scholarships) && $has_profile) {
    foreach($scholarships as $s) {
        $elig = checkEligibilityByRules($conn, $s, $profile);
        if(($elig['status'] ?? 'not_eligible') === 'eligible') {
            $s['_eligibility'] = $elig;
            $eligible_scholarships[] = $s;
        }
    }
}

$total_scholarships = count($eligible_scholarships);
$total_pages = max(1, (int)ceil($total_scholarships / $per_page));
if($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}
$scholarships = array_slice($eligible_scholarships, $offset, $per_page);

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
    <title>Student Dashboard - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div>
            <h1> Scholarship Dashboard</h1>
            <p style="color: #999; margin-top: 5px;">Welcome, <?php echo htmlspecialchars($user_name); ?></p>
        </div>
        <div class="header-actions">
            <a href="profile.php">Update Profile</a>
            <a href="check_eligibility.php">Check Eligibility</a>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <!-- Alerts -->
    <?php if($flash_success): ?>
    <div class="alert success">
        <?php echo htmlspecialchars($flash_success); ?>
    </div>
    <?php endif; ?>

    <?php if($profile_success): ?>
    <div class="alert success">
        <?php echo htmlspecialchars($profile_success); ?>
    </div>
    <?php endif; ?>

    <?php if($flash_error): ?>
    <div class="alert danger">
        <?php echo htmlspecialchars($flash_error); ?>
    </div>
    <?php endif; ?>

    <?php if($expiring_count > 0): ?>
    <div class="alert">
         <strong><?php echo $expiring_count; ?> scholarships</strong> are expiring within the next 7 days!
    </div>
    <?php endif; ?>
    
    <?php if(!$has_profile): ?>
    <div class="alert">
         Please <a href="profile.php" style="color: inherit; text-decoration: underline;">complete your profile</a> to see accurate eligibility.
    </div>
    <?php endif; ?>
    
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
        <?php if($total_scholarships > 0 && !empty($scholarships)): ?>
        <div class="scholarships-grid">
            <?php foreach($scholarships as $scholarship): ?>
            <?php 
                $eligibility = $scholarship['_eligibility'] ?? checkEligibilityByRules($conn, $scholarship, $profile);
                $days_left = round((strtotime($scholarship['deadline']) - time()) / (60 * 60 * 24));
                $is_urgent = $days_left <= 7 && $days_left >= 0;
            ?>
            <div class="scholarship-card <?php echo $eligibility['status']; ?>">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($scholarship['title']); ?></h3>
                </div>
                
                <div class="card-body">
                   
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
                    <a href="scholarship_details.php?id=<?php echo $scholarship['scholarship_id']; ?>" class="btn-details">View Details</a>
                    <?php if($eligibility['status'] == 'eligible'): ?>
                    <a href="apply_scholarship.php?id=<?php echo $scholarship['scholarship_id']; ?>" class="btn-apply">Apply Now</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
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

<?php include "includes/footer.php"; ?>

<script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
