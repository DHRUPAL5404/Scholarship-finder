<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch student profile */
$profile_q = mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=$user_id");
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
    <link rel="stylesheet" href="assets/css/common.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/student.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/validation.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

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
                
                $rules = mysqli_query(
                    $conn,
                    "SELECT * FROM eligibility_rules WHERE scholarship_id=$sid"
                );
                
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

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
