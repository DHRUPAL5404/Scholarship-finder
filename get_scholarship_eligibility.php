<?php
session_start();
include "db.php";
header('Content-Type: application/json');

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch student profile
$profile = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=$user_id")
);

if(!$profile) {
    echo json_encode(['error' => 'Student profile not found']);
    exit();
}

// Get scholarship ID from request
$scholarship_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($scholarship_id == 0) {
    echo json_encode(['error' => 'Scholarship ID required']);
    exit();
}

// Fetch scholarship details
$scholarship = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM scholarships WHERE scholarship_id=$scholarship_id")
);

if(!$scholarship) {
    echo json_encode(['error' => 'Scholarship not found']);
    exit();
}

// Check eligibility
function checkEligibility($scholarship, $student_profile) {
    $eligibility = array(
        'status' => 'eligible',
        'issues' => array(),
        'percentage' => 100,
        'details' => array()
    );
    
    // Check category eligibility
    if($scholarship['category'] != 'General' && $scholarship['category'] != $student_profile['category']) {
        if($scholarship['category'] == 'OBC' || $scholarship['category'] == 'SC' || $scholarship['category'] == 'ST') {
            if(strpos($student_profile['category'], 'General') === false) {
                $eligibility['issues'][] = "Category mismatch: Required {$scholarship['category']}, You have {$student_profile['category']}";
                $eligibility['percentage'] -= 20;
            } else {
                $eligibility['details'][] = "✓ Category eligible";
            }
        }
    } else {
        $eligibility['details'][] = "✓ Category eligible";
    }
    
    // Check marks eligibility
    if($scholarship['min_marks'] > 0) {
        if($student_profile['marks'] < $scholarship['min_marks']) {
            $eligibility['issues'][] = "Marks below minimum: Required {$scholarship['min_marks']}%, You have {$student_profile['marks']}%";
            $eligibility['percentage'] -= 30;
        } else {
            $eligibility['details'][] = "✓ Marks eligible ({$student_profile['marks']}%)";
        }
    }
    
    // Check family income eligibility
    if($scholarship['max_family_income'] > 0) {
        if($student_profile['family_income'] > $scholarship['max_family_income']) {
            $eligibility['issues'][] = "Family income exceeds limit: Max ₹{$scholarship['max_family_income']}, You have ₹{$student_profile['family_income']}";
            $eligibility['percentage'] -= 25;
        } else {
            $eligibility['details'][] = "✓ Income eligible";
        }
    }
    
    // Check education level eligibility
    if($scholarship['education_level'] && strpos($student_profile['education_level'], $scholarship['education_level']) === false) {
        $eligibility['issues'][] = "Education level mismatch: Required {$scholarship['education_level']}, You have {$student_profile['education_level']}";
        $eligibility['percentage'] -= 20;
    } else {
        $eligibility['details'][] = "✓ Education level eligible";
    }
    
    // Check state eligibility
    if($scholarship['state_id'] && $scholarship['state_id'] != $student_profile['state_id']) {
        $eligibility['issues'][] = "Not available in your state";
        $eligibility['percentage'] -= 50;
    } else {
        $eligibility['details'][] = "✓ State eligible";
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

$eligibility = checkEligibility($scholarship, $profile);

echo json_encode([
    'scholarship_id' => $scholarship['scholarship_id'],
    'title' => $scholarship['title'],
    'eligibility' => $eligibility
]);
?>
