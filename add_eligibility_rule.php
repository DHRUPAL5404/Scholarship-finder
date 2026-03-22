<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";

$flash_success = '';
$flash_error = '';

if(isset($_POST['add_all'])) {
    $scholarship_id = intval($_POST['scholarship'] ?? 0);
    $rules = $_POST['rules'] ?? [];

    if($scholarship_id <= 0) {
        $flash_error = "Please select a scholarship.";
    } else {
        $inserted = 0;
        $allowed_ops = ['=', '>=', '<=', '>', '<'];

        foreach($rules as $field => $data) {
            $operator = trim($data['operator'] ?? '=');
            $value = trim($data['value'] ?? '');

            if($value === '') {
                continue; // skip empty rule
            }

            if(!in_array($operator, $allowed_ops, true)) {
                $operator = '=';
            }

            $field_db = mysqli_real_escape_string($conn, $field);
            $operator_db = mysqli_real_escape_string($conn, $operator);
            $value_db = mysqli_real_escape_string($conn, $value);

            $sql = "INSERT INTO eligibility_rules (scholarship_id, field_name, operator, value)
                    VALUES ($scholarship_id, '$field_db', '$operator_db', '$value_db')";
            if(mysqli_query($conn, $sql)) {
                $inserted++;
            }
        }

        if($inserted > 0) {
            $_SESSION['flash_success'] = "Added $inserted rule(s) successfully.";
            header("Location: admin_dashboard.php");
            exit();
        } else {
            $flash_error = "No rules were added. Please fill at least one rule value.";
        }
    }
}

$selected_scholarship = intval($_POST['scholarship'] ?? ($_GET['scholarship'] ?? 0));
$sch = mysqli_query($conn, "SELECT * FROM scholarships ORDER BY title ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Eligibility Rule - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="eligibility-container">
        <h1>Add Eligibility Rule</h1>

        <?php if($flash_success): ?>
            <div class="success-message"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="error-message"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="scholarship-selector">
                <label><strong>Select Scholarship:</strong></label>
                <select name="scholarship" required>
                    <option value="">-- Select Scholarship --</option>
                    <?php while($s = mysqli_fetch_assoc($sch)): ?>
                        <option value="<?= intval($s['scholarship_id']) ?>" <?= $selected_scholarship === intval($s['scholarship_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="rule-section">
                <strong>Gender</strong>
                <input type="hidden" name="rules[gender][operator]" value="=">
                <select name="rules[gender][value]">
                    <option value="">-- Skip --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                    <option value="All">All</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Age</strong>
                <select name="rules[age][operator]">
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                    <option value="=">=</option>
                </select>
                <input type="number" name="rules[age][value]" placeholder="e.g., 18">
            </div>

            <div class="rule-section">
                <strong>Education Level</strong>
                <input type="hidden" name="rules[education_level][operator]" value="=">
                <select name="rules[education_level][value]">
                    <option value="">-- Skip --</option>
                    <option value="Below 10th">Below 10th</option>
                    <option value="10th Pass(SSC)">10th Pass</option>
                    <option value="Undergraduate">Undergraduate</option>
                    <option value="Postgraduate">Postgraduate</option>
                    <option value="PhD">PhD</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Marks</strong>
                <select name="rules[marks][operator]">
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                    <option value="=">=</option>
                </select>
                <input type="number" name="rules[marks][value]" placeholder="e.g., 75" step="0.01">
            </div>

            <div class="rule-section">
                <strong>Institution Type</strong>
                <input type="hidden" name="rules[institution_type][operator]" value="=">
                <select name="rules[institution_type][value]">
                    <option value="">-- Skip --</option>
                    <option value="Government">Government</option>
                    <option value="Private">Private</option>
                    <option value="Government-Aided">Government-Aided</option>
                    <option value="Autonomous">Autonomous</option>
                    <option value="University">University</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Family Income</strong>
                <select name="rules[family_income][operator]">
                    <option value="<=">&le;</option>
                    <option value=">=">&ge;</option>
                    <option value="=">=</option>
                </select>
                <input type="number" name="rules[family_income][value]" placeholder="e.g., 500000">
            </div>

            <div class="rule-section">
                <strong>Category</strong>
                <input type="hidden" name="rules[category][operator]" value="=">
                <select name="rules[category][value]">
                    <option value="">-- Skip --</option>
                    <option value="General (GEN / UR)">General (GEN / UR)</option>
                    <option value="Other Backward Class (OBC)">OBC</option>
                    <option value="Scheduled Caste (SC)">SC</option>
                    <option value="Scheduled Tribe (ST)">ST</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Minority Status</strong>
                <input type="hidden" name="rules[minority_status][operator]" value="=">
                <select name="rules[minority_status][value]">
                    <option value="">-- Skip --</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Disability Percent</strong>
                <select name="rules[disability_percent][operator]">
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                    <option value=">">&gt;</option>
                </select>
                <input type="number" name="rules[disability_percent][value]" placeholder="e.g., 40" min="0" max="100">
            </div>

            <div class="rule-section">
                <button name="add_all" type="submit">Add Selected Rules</button>
            </div>
        </form>
    </div>
</body>
</html>
