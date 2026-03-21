<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch student profile */
$stmt = mysqli_prepare($conn, "SELECT * FROM student_profile WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$profile_q = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($profile_q);

if(!$student){
    $error = "âš ï¸ Please complete your profile first.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Eligibility - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
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
        <?php if(isset($error)): ?>
            <p><?php echo $error; ?></p>
        <?php else: ?>
            <h2>Eligible Scholarships ðŸŽ¯</h2>
            
            <?php
            /* Fetch active scholarships */
            $scholarships = mysqli_query($conn, "SELECT * FROM scholarships WHERE status='active'");
            
            $found = false;
            
            while($sch = mysqli_fetch_assoc($scholarships)){
                $sid = $sch['scholarship_id'];
                
                $stmt_rules = mysqli_prepare($conn, "SELECT * FROM eligibility_rules WHERE scholarship_id=?");
                mysqli_stmt_bind_param($stmt_rules, "i", $sid);
                mysqli_stmt_execute($stmt_rules);
                $rules = mysqli_stmt_get_result($stmt_rules);
                
                $eligible = true;
                
                while($rule = mysqli_fetch_assoc($rules)){
                    $field = $rule['field_name'];
                    $operator = $rule['operator'];
                    $value = $rule['value'];

                    if(!array_key_exists($field, $student)){
                        continue;
                    }

                    $student_value = trim((string)$student[$field]);
                    $rule_value = trim((string)$value);

                    // Non-restrictive values
                    if(strcasecmp($rule_value, 'All') === 0 || strcasecmp($rule_value, 'All India') === 0){
                        continue;
                    }

                    // Comma-separated equality values behave like set membership
                    if($operator === '=' && strpos($rule_value, ',') !== false){
                        $allowed_values = array_map('trim', explode(',', $rule_value));
                        if(!in_array($student_value, $allowed_values, true)) $eligible = false;
                        if(!$eligible) break;
                        continue;
                    }
                    
                    switch($operator){
                        case '=':
                            if($student_value != $rule_value) $eligible = false;
                            break;
                        case '>=':
                            if($student_value < $rule_value) $eligible = false;
                            break;
                        case '<=':
                            if($student_value > $rule_value) $eligible = false;
                            break;
                        case '>':
                            if($student_value <= $rule_value) $eligible = false;
                            break;
                        case '<':
                            if($student_value >= $rule_value) $eligible = false;
                            break;
                    }
                    
                    if(!$eligible) break;
                }
                
                if($eligible){
                    $found = true;
                    echo "
                    <div class='eligible-card'>
                        <h3>{$sch['title']}</h3>
                        <p>{$sch['description']}</p>
                        <p><b>Deadline:</b> {$sch['deadline']}</p>
                        <a href='apply.php?sid={$sid}' class='btn'>Apply Now</a>
                    </div>
                    ";
                }
            }
            
            if(!$found){
                echo "<p>ðŸ˜• No scholarships matched your profile.</p>";
            }
            ?>
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
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="profile.php">My Profile</a></li>
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
