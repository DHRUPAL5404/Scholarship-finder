<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";
require_once "includes/csrf.php";

$flash_success = '';
$flash_error   = '';

if(isset($_POST['add_rule'])){

    csrf_verify();

    $scholarship_id = intval($_POST['scholarship'] ?? 0);
    $field          = $_POST['field'] ?? '';
    $operator       = $_POST['operator'] ?? '=';
    $value          = trim($_POST['value'] ?? '');

    $allowed_ops   = ['=', '>=', '<=', '>', '<'];
    $allowed_fields = ['family_income', 'category', 'education_level', 'gender', 'marks'];

    if($scholarship_id <= 0){
        $flash_error = "Please select a valid scholarship.";
    } elseif(!in_array($field, $allowed_fields)){
        $flash_error = "Invalid field selected.";
    } elseif($value === ''){
        $flash_error = "Please enter a value.";
    } else {
        if(!in_array($operator, $allowed_ops, true)){
            $operator = '=';
        }

        $sch_check = $conn->prepare("SELECT scholarship_id FROM scholarships WHERE scholarship_id = ?");
        $sch_check->bind_param("i", $scholarship_id);
        $sch_check->execute();
        $sch_result = $sch_check->get_result();
        $sch_check->close();

        if($sch_result->num_rows === 0){
            $flash_error = "Selected scholarship does not exist.";
        } else {
            $insert_stmt = $conn->prepare(
                "INSERT INTO eligibility_rules (scholarship_id, field_name, operator, value)
                 VALUES (?, ?, ?, ?)"
            );

            if(!$insert_stmt){
                $flash_error = "Database error. Please try again.";
            } else {
                $insert_stmt->bind_param("isss", $scholarship_id, $field, $operator, $value);
                if($insert_stmt->execute()){
                    $_SESSION['flash_success'] = "Rule added successfully.";
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $flash_error = "Failed to add rule.";
                }
                $insert_stmt->close();
            }
        }
    }
}

$selected_scholarship = intval($_POST['scholarship'] ?? ($_GET['scholarship'] ?? 0));
$sch_stmt = $conn->prepare("SELECT scholarship_id, title FROM scholarships ORDER BY title ASC");
$sch_stmt->execute();
$sch = $sch_stmt->get_result();
$sch_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Eligibility Rule - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .rule-form { background: #fff; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 600px; margin: 2rem auto; }
        .rule-form .input-wrapper { margin-bottom: 1.5rem; }
        .rule-form label { font-weight: bold; display: block; margin-bottom: 0.5rem; }
        .rule-form select, .rule-form input { width: 100%; padding: 0.8rem; border-radius: 6px; border: 1px solid #ccc; }
        .btn-row { display: flex; gap: 1rem; }
    </style>
</head>
<body>
    <?php include "includes/navbar.php"; ?>
    <div class="container">
        <div class="rule-form">
            <h2>Add Eligibility Rule</h2>

            <?php if($flash_success): ?>
                <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
            <?php endif; ?>
            <?php if($flash_error): ?>
                <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
            <?php endif; ?>

            <form method="post" id="add-eligibility-form">
                <?php csrf_token(); ?>

                <div class="input-wrapper">
                    <label>Scholarship <span style="color:red">*</span></label>
                    <select name="scholarship" required>
                        <option value="">-- Select Scholarship --</option>
                        <?php while($s = $sch->fetch_assoc()): ?>
                            <option value="<?= intval($s['scholarship_id']) ?>"
                                <?= $selected_scholarship === intval($s['scholarship_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="input-wrapper">
                    <label>Field <span style="color:red">*</span></label>
                    <select name="field" required>
                        <option value="">-- Select Field --</option>
                        <option value="family_income">Family Income</option>
                        <option value="category">Category</option>
                        <option value="education_level">Education Level</option>
                        <option value="gender">Gender</option>
                        <option value="marks">Percentage / Marks</option>
                    </select>
                </div>

                <div class="input-wrapper">
                    <label>Operator <span style="color:red">*</span></label>
                    <select name="operator" required>
                        <option value="=">Equals (=)</option>
                        <option value="<=">Less than or equal (<=)</option>
                        <option value=">=">Greater than or equal (>=)</option>
                        <option value="<">Less than (<)</option>
                        <option value=">">Greater than (>)</option>
                    </select>
                </div>

                <div class="input-wrapper">
                    <label>Value <span style="color:red">*</span></label>
                    <input type="text" name="value" placeholder="Enter required value" required>
                    <small style="color: #666; display: block; margin-top: 5px;">For category, use: General (GEN / UR), OBC, SC, ST</small>
                </div>

                <div class="btn-row">
                    <button type="submit" name="add_rule" style="flex: 1; padding: 0.8rem; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Add Rule</button>
                    <a href="admin_dashboard.php" style="flex: 1; text-align: center; padding: 0.8rem; background: #f0f0f0; color: #333; text-decoration: none; border-radius: 6px; border: 1px solid #ccc; font-weight: bold;">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <?php include "includes/footer.php"; ?>
</body>
</html>
