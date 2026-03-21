<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";

$sch = mysqli_query($conn,"SELECT * FROM scholarships");
$selected_scholarship = isset($_POST['scholarship']) ? intval($_POST['scholarship']) : 0;
$eligible_students = array();
$query_error = '';

if(isset($_POST['check'])){
    $sid = $selected_scholarship;

    $stmt_rules = mysqli_prepare($conn, "SELECT * FROM eligibility_rules WHERE scholarship_id=?");
    mysqli_stmt_bind_param($stmt_rules, "i", $sid);
    mysqli_stmt_execute($stmt_rules);
    $rules = mysqli_stmt_get_result($stmt_rules);
    $conditions = [];

    $allowed_operators = array('=', '>=', '<=', '>', '<');
    $valid_fields = array();

    $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM student_profile");
    while($col = mysqli_fetch_assoc($columns_result)){
        $valid_fields[$col['Field']] = true;
    }

    while($r=mysqli_fetch_assoc($rules)){
        $field = trim($r['field_name']);
        $operator = trim($r['operator']);
        $raw_value = trim($r['value']);
        $value = mysqli_real_escape_string($conn, $raw_value);

        // Skip rules whose field does not exist in current student_profile schema
        if(!isset($valid_fields[$field])){
            continue;
        }

        // Fallback to '=' if operator is missing/invalid
        if(!in_array($operator, $allowed_operators, true)){
            $operator = '=';
        }

        // 'All' style values are non-restrictive
        if(strcasecmp($raw_value, 'All') === 0 || strcasecmp($raw_value, 'All India') === 0){
            continue;
        }

        // Comma-separated values with '=' should behave like IN (...)
        if($operator === '=' && strpos($raw_value, ',') !== false){
            $parts = array_filter(array_map('trim', explode(',', $raw_value)), function($v){ return $v !== ''; });
            if(count($parts) > 0){
                $escaped_parts = array_map(function($p) use ($conn){
                    return "'" . mysqli_real_escape_string($conn, $p) . "'";
                }, $parts);
                $conditions[] = "sp.`$field` IN (" . implode(',', $escaped_parts) . ")";
            }
            continue;
        }

        $conditions[] = "sp.`$field` $operator '$value'";
    }

    $sql="SELECT u.full_name, u.email, sp.* FROM student_profile sp 
          JOIN users u ON u.user_id=sp.user_id";

    if($conditions){
        $sql.=" WHERE ".implode(" AND ",$conditions);
    }

    $result = mysqli_query($conn,$sql);
    $query_error = !$result ? mysqli_error($conn) : '';

    if($result){
        while($row = mysqli_fetch_assoc($result)){
            $eligible_students[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligible Students - ScholarMatch</title>
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
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <h2>Eligible Students</h2>

        <form method="post">
        <select name="scholarship">
        <?php while($s=mysqli_fetch_assoc($sch)){ ?>
        <option value="<?= $s['scholarship_id'] ?>" <?= ($selected_scholarship === intval($s['scholarship_id'])) ? 'selected' : '' ?>>
            <?= $s['title'] ?>
        </option>
        <?php } ?>
        </select>
        <button name="check">Check</button>
        </form>

        <?php if(!empty($query_error)): ?>
            <p style="color:#c0392b;">Error loading eligible students: <?php echo htmlspecialchars($query_error); ?></p>
        <?php endif; ?>

        <?php if(isset($_POST['check']) && empty($query_error)): ?>
            <?php if(count($eligible_students) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Marks</th>
                            <th>Category</th>
                            <th>Education</th>
                            <th>Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($eligible_students as $st): ?>
                            <tr>
                                <td><?= htmlspecialchars($st['full_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($st['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($st['marks'] ?? '') ?>%</td>
                                <td><?= htmlspecialchars($st['category'] ?? '') ?></td>
                                <td><?= htmlspecialchars($st['education_level'] ?? '') ?></td>
                                <td><?= htmlspecialchars($st['family_income'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert warning">No students are eligible for the selected scholarship.</div>
            <?php endif; ?>
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
                <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                <li><a href="manage_scholarships.php">Manage Scholarships</a></li>
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
