<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
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

if (!$student) {
    $error = "Please complete your profile first.";
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
    <style>
        .page-title {
            text-align: center;
            color: #2d3748;
            font-size: 2.2rem;
            margin-bottom: 30px;
            position: relative;
        }
        .page-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            margin: 10px auto 0;
            border-radius: 2px;
        }
        .eligible-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .eligible-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(102, 126, 234, 0.15);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .eligible-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }
        .eligible-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.2);
            border-color: rgba(102, 126, 234, 0.4);
        }
        .eligible-card h3 {
            margin-top: 0;
            color: #1a202c;
            font-size: 1.35rem;
            margin-bottom: 12px;
            font-weight: 800;
        }
        .eligible-card p {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .deadline-badge {
            display: inline-block;
            background: #edf2f7;
            color: #2d3748;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .eligible-card .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-align: center;
            padding: 14px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.35);
        }
        .eligible-card .btn:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .empty-state h3 { color: #4a5568; margin-bottom: 10px; }
        .empty-state p { color: #718096; }
    </style>
</head>

<body>

    <!-- Navbar -->
    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="empty-state">
                <h3>Profile Incomplete</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="profile.php" class="btn" style="display:inline-block; margin-top:15px; padding:10px 20px; background:#667eea; color:#fff; border-radius:8px; text-decoration:none;">Go to Profile</a>
            </div>
        <?php else: ?>
            <h2 class="page-title">Eligible Scholarships</h2>

            <?php
            /* Fetch active scholarships and their rules in a single query */
            $status = 'active';
            $stmt_sch = mysqli_prepare($conn, "
                SELECT 
                    s.scholarship_id, s.title, s.description, s.deadline, 
                    e.rule_id, e.field_name, e.operator, e.value 
                FROM scholarships s
                LEFT JOIN eligibility_rules e ON s.scholarship_id = e.scholarship_id
                WHERE s.status = ?
            ");
            mysqli_stmt_bind_param($stmt_sch, "s", $status);
            mysqli_stmt_execute($stmt_sch);
            $result = mysqli_stmt_get_result($stmt_sch);

            $scholarships_data = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $sid = $row['scholarship_id'];
                
                if (!isset($scholarships_data[$sid])) {
                    $scholarships_data[$sid] = [
                        'scholarship_id' => $sid,
                        'title' => $row['title'],
                        'description' => $row['description'],
                        'deadline' => $row['deadline'],
                        'rules' => []
                    ];
                }
                
                if (!empty($row['rule_id'])) {
                    $scholarships_data[$sid]['rules'][] = [
                        'field_name' => $row['field_name'],
                        'operator' => $row['operator'],
                        'value' => $row['value']
                    ];
                }
            }
            mysqli_stmt_close($stmt_sch);

            $found = false;
            $output_html = "<div class='eligible-grid'>";

            foreach ($scholarships_data as $sid => $sch) {
                $eligible = true;

                foreach ($sch['rules'] as $rule) {
                    $field = $rule['field_name'];
                    $operator = $rule['operator'];
                    $value = $rule['value'];

                    if (!array_key_exists($field, $student)) {
                        continue;
                    }

                    $student_value = trim((string) $student[$field]);
                    $rule_value = trim((string) $value);

                    // Non-restrictive values
                    if (strcasecmp($rule_value, 'All') === 0 || strcasecmp($rule_value, 'All India') === 0) {
                        continue;
                    }

                    // Comma-separated equality values behave like set membership
                    if ($operator === '=' && strpos($rule_value, ',') !== false) {
                        $allowed_values = array_map('trim', explode(',', $rule_value));
                        if (!in_array($student_value, $allowed_values, true))
                            $eligible = false;
                        if (!$eligible)
                            break;
                        continue;
                    }

                    switch ($operator) {
                        case '=':
                            if ($student_value != $rule_value)
                                $eligible = false;
                            break;
                        case '>=':
                            if ($student_value < $rule_value)
                                $eligible = false;
                            break;
                        case '<=':
                            if ($student_value > $rule_value)
                                $eligible = false;
                            break;
                        case '>':
                            if ($student_value <= $rule_value)
                                $eligible = false;
                            break;
                        case '<':
                            if ($student_value >= $rule_value)
                                $eligible = false;
                            break;
                    }

                    if (!$eligible)
                        break;
                }

                if ($eligible) {
                    $found = true;
                    $safe_title       = htmlspecialchars($sch['title']);
                    $safe_description = htmlspecialchars(mb_substr($sch['description'], 0, 150)) . '...';
                    $safe_deadline    = htmlspecialchars(date('d M Y', strtotime($sch['deadline'])));
                    $safe_sid         = intval($sid);
                    
                    $output_html .= "
                    <div class='eligible-card'>
                        <h3>{$safe_title}</h3>
                        <p>{$safe_description}</p>
                        <div><span class='deadline-badge'>Deadline: {$safe_deadline}</span></div>
                        <a href='apply_scholarship.php?id={$safe_sid}' class='btn'>Apply Now &rarr;</a>
                    </div>
                    ";
                }
            }

            $output_html .= "</div>";

            if ($found) {
                echo $output_html;
            } else {
                echo "
                <div class='empty-state'>
                    <h3>No Matches Found</h3>
                    <p>We couldn't find any active scholarships that match your current profile details.</p>
                </div>";
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