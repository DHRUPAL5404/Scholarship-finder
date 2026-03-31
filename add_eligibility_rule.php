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

if(isset($_POST['add_all'])){

    // ── CSRF verification ──────────────────────────────────────────────────
    csrf_verify();

    $scholarship_id = intval($_POST['scholarship'] ?? 0);
    $rules          = $_POST['rules'] ?? [];

    // ── Server-side validation ─────────────────────────────────────────────
    if($scholarship_id <= 0){
        $flash_error = "Please select a valid scholarship.";
    } elseif(!is_array($rules) || empty($rules)){
        $flash_error = "No rules were submitted.";
    } else {
        $allowed_ops   = ['=', '>=', '<=', '>', '<'];
        $inserted      = 0;
        $skipped       = 0;

        // ── Validate scholarship exists before inserting rules ─────────────
        $sch_check = $conn->prepare("SELECT scholarship_id FROM scholarships WHERE scholarship_id = ?");
        if(!$sch_check){
            $flash_error = "Database error. Please try again.";
        } else {
            $sch_check->bind_param("i", $scholarship_id);
            $sch_check->execute();
            $sch_result = $sch_check->get_result();
            $sch_check->close();

            if($sch_result->num_rows === 0){
                $flash_error = "Selected scholarship does not exist.";
            } else {

                // ── Prepare the INSERT statement once, reuse in loop ───────
                $insert_stmt = $conn->prepare(
                    "INSERT INTO eligibility_rules (scholarship_id, field_name, operator, value)
                     VALUES (?, ?, ?, ?)"
                );

                if(!$insert_stmt){
                    $flash_error = "Database error. Please try again.";
                } else {
                    foreach($rules as $field => $data){
                        $operator  = trim($data['operator'] ?? '=');
                        $value     = trim($data['value']    ?? '');

                        // Skip empty rule values
                        if($value === ''){
                            $skipped++;
                            continue;
                        }

                        // Normalise operator
                        if(!in_array($operator, $allowed_ops, true)){
                            $operator = '=';
                        }

                        // Sanitise field name — only allow alphanumeric + underscore
                        $field_clean = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
                        if(empty($field_clean)){
                            $skipped++;
                            continue;
                        }

                        // ── Bind and execute prepared insert ───────────────
                        $insert_stmt->bind_param("isss", $scholarship_id, $field_clean, $operator, $value);

                        if($insert_stmt->execute()){
                            $inserted++;
                        }
                    }

                    $insert_stmt->close();

                    if($inserted > 0){
                        $_SESSION['flash_success'] = "Added {$inserted} rule(s) successfully."
                            . ($skipped > 0 ? " {$skipped} empty rule(s) were skipped." : "");
                        header("Location: admin_dashboard.php");
                        exit();
                    } else {
                        $flash_error = "No rules were added. Please fill in at least one rule value.";
                    }
                }
            }
        }
    }
}

$selected_scholarship = intval($_POST['scholarship'] ?? ($_GET['scholarship'] ?? 0));

// Fetch scholarships using prepared statement (no user input, but consistent style)
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

        <form method="post" id="add-eligibility-form">
            <!-- ── CSRF token ── -->
            <?php csrf_token(); ?>

            <div class="scholarship-selector">
                <label><strong>Select Scholarship: <span style="color:red">*</span></strong></label>
                <select name="scholarship" id="eligibility-scholarship-select" required>
                    <option value="">-- Select Scholarship --</option>
                    <?php while($s = $sch->fetch_assoc()): ?>
                        <option value="<?= intval($s['scholarship_id']) ?>"
                            <?= $selected_scholarship === intval($s['scholarship_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['title']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="rule-section">
                <strong>Gender</strong>
                <input type="hidden" name="rules[gender][operator]" value="=">
                <select name="rules[gender][value]" id="rule-gender">
                    <option value="">-- Skip --</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                    <option value="All">All</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Age</strong>
                <select name="rules[age][operator]" id="rule-age-op">
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                    <option value="=">=</option>
                </select>
                <input type="number" name="rules[age][value]" id="rule-age-val"
                       placeholder="e.g., 18" min="1" max="100">
            </div>

            <div class="rule-section">
                <strong>Education Level</strong>
                <input type="hidden" name="rules[education_level][operator]" value="=">
                <select name="rules[education_level][value]" id="rule-edu">
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
                <strong>Marks (%)</strong>
                <select name="rules[marks][operator]" id="rule-marks-op">
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                    <option value="=">=</option>
                </select>
                <input type="number" name="rules[marks][value]" id="rule-marks-val"
                       placeholder="e.g., 75" step="0.01" min="0" max="100">
            </div>

            <div class="rule-section">
                <strong>Institution Type</strong>
                <input type="hidden" name="rules[institution_type][operator]" value="=">
                <select name="rules[institution_type][value]" id="rule-inst">
                    <option value="">-- Skip --</option>
                    <option value="Government">Government</option>
                    <option value="Private">Private</option>
                    <option value="Government-Aided">Government-Aided</option>
                    <option value="Autonomous">Autonomous</option>
                    <option value="University">University</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Family Income (₹)</strong>
                <select name="rules[family_income][operator]" id="rule-income-op">
                    <option value="<=">&le;</option>
                    <option value=">=">&ge;</option>
                    <option value="=">=</option>
                </select>
                <input type="number" name="rules[family_income][value]" id="rule-income-val"
                       placeholder="e.g., 500000" min="0">
            </div>

            <div class="rule-section">
                <strong>Category</strong>
                <input type="hidden" name="rules[category][operator]" value="=">
                <select name="rules[category][value]" id="rule-category">
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
                <select name="rules[minority_status][value]" id="rule-minority">
                    <option value="">-- Skip --</option>
                    <option value="Yes">Yes</option>
                    <option value="No">No</option>
                </select>
            </div>

            <div class="rule-section">
                <strong>Disability Percent (%)</strong>
                <select name="rules[disability_percent][operator]" id="rule-disability-op">
                    <option value=">=">&ge;</option>
                    <option value="<=">&le;</option>
                    <option value=">">&gt;</option>
                </select>
                <input type="number" name="rules[disability_percent][value]" id="rule-disability-val"
                       placeholder="e.g., 40" min="0" max="100">
            </div>

            <div class="rule-section">
                <button name="add_all" type="submit" id="add-rules-btn">Add Selected Rules</button>
                <a href="admin_dashboard.php" class="btn-secondary" style="margin-left:1rem;">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>
