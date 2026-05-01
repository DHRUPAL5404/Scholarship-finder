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
    $allowed_fields = ['family_income', 'category', 'education_level', 'gender', 'marks', 'state_id', 'institution_type'];

    if($scholarship_id <= 0){
        $flash_error = "Please select a valid scholarship.";
    } elseif(!in_array($field, $allowed_fields)){
        $flash_error = "Invalid field selected.";
    } elseif($value === ''){
        $flash_error = "Please enter or select a value.";
    } else {
        if(!in_array($operator, $allowed_ops, true)) $operator = '=';

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
                    $_SESSION['flash_success'] = "✅ Eligibility rule added successfully.";
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

$selected_scholarship = intval($_REQUEST['scholarship'] ?? 0);
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
    <title>Set Eligibility - ScholarMatch Admin</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .premium-form-card {
            background: #fff;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 700px;
            margin: 3rem auto;
            border: 1px solid #f1f5f9;
        }
        .premium-form-card h2 {
            margin-top: 0;
            margin-bottom: 2rem;
            font-size: 1.75rem;
            color: #1e293b;
            text-align: center;
            font-weight: 800;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: #475569;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 0.9rem 1.1rem;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            font-size: 1rem;
            transition: all 0.2s ease;
            background-color: #f8fafc;
        }
        .form-group select:focus, .form-group input:focus {
            outline: none;
            border-color: #4f46e5;
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(79, 70, 229, 0.3);
            opacity: 0.95;
        }
        .btn-cancel {
            display: block;
            text-align: center;
            margin-top: 1.25rem;
            color: #64748b;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .btn-cancel:hover { color: #1e293b; }
        
        .hint-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <?php include "includes/navbar.php"; ?>
    
    <div class="container">
        <div class="premium-form-card">
            <h2>🎯 Set Eligibility Rule</h2>

            <?php if($flash_error): ?>
                <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
            <?php endif; ?>

            <form method="post" id="eligibility-rule-form">
                <?php csrf_token(); ?>

                <div class="form-group">
                    <label>Target Scholarship</label>
                    <select name="scholarship" required>
                        <option value="">-- Choose a scholarship --</option>
                        <?php while($s = $sch->fetch_assoc()): ?>
                            <option value="<?= intval($s['scholarship_id']) ?>"
                                <?= $selected_scholarship === intval($s['scholarship_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['title']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Criteria Field</label>
                    <select name="field" id="field-select" required onchange="updateValueInput()">
                        <option value="">-- Select student attribute --</option>
                        <option value="category">Student Category (Caste)</option>
                        <option value="education_level">Current Education Level</option>
                        <option value="gender">Gender</option>
                        <option value="institution_type">Institution Type</option>
                        <option value="marks">Minimum Academic Score (%)</option>
                        <option value="family_income">Maximum Family Income (₹)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Matching Condition</label>
                    <select name="operator" id="operator-select" required>
                        <option value="=">Exactly Matches (=)</option>
                        <option value=">=">At Least (>=)</option>
                        <option value="<=">Up To (<=)</option>
                        <option value=">">Greater Than (>)</option>
                        <option value="<">Less Than (<)</option>
                    </select>
                </div>

                <div class="form-group" id="value-group">
                    <label>Required Value</label>
                    <div id="value-container">
                        <input type="text" name="value" id="value-input" placeholder="Select a field first..." required>
                    </div>
                    <span class="hint-text" id="value-hint">Select a field above to see available options.</span>
                </div>

                <button type="submit" name="add_rule" class="btn-submit">🚀 Add Eligibility Requirement</button>
                <a href="admin_dashboard.php" class="btn-cancel">Cancel and return</a>
            </form>
        </div>
    </div>

    <script>
        const fieldOptions = {
            category: {
                type: 'dropdown',
                options: ['General', 'OBC', 'SC', 'ST', 'Minority'],
                hint: 'Choose the student category required for this scholarship.',
                defaultOp: '='
            },
            education_level: {
                type: 'dropdown',
                options: ['Below 10th', '10th Passed', '12th Passed', 'Diploma', 'Undergraduate', 'Postgraduate', 'PhD'],
                hint: 'Minimum education level required.',
                defaultOp: '>='
            },
            gender: {
                type: 'dropdown',
                options: ['Male', 'Female', 'Other', 'Transgender'],
                hint: 'Gender requirement (if any).',
                defaultOp: '='
            },
            institution_type: {
                type: 'dropdown',
                options: ['Government', 'Semi-Government', 'Private'],
                hint: 'Type of college/school.',
                defaultOp: '='
            },
            marks: {
                type: 'number',
                placeholder: 'e.g. 60 (for 60%)',
                hint: 'Minimum percentage required in previous exams.',
                defaultOp: '>='
            },
            family_income: {
                type: 'number',
                placeholder: 'e.g. 250000',
                hint: 'Maximum annual family income allowed.',
                defaultOp: '<='
            }
        };

        function updateValueInput() {
            const field = document.getElementById('field-select').value;
            const container = document.getElementById('value-container');
            const hint = document.getElementById('value-hint');
            const opSelect = document.getElementById('operator-select');

            if (!field || !fieldOptions[field]) {
                container.innerHTML = '<input type="text" name="value" placeholder="Select a field first..." required disabled>';
                hint.innerText = 'Select a field above to see available options.';
                return;
            }

            const config = fieldOptions[field];
            hint.innerText = config.hint;
            
            // Auto-select common operator
            if (config.defaultOp) {
                opSelect.value = config.defaultOp;
            }

            if (config.type === 'dropdown') {
                let html = `<select name="value" required>`;
                html += `<option value="">-- Select Option --</option>`;
                config.options.forEach(opt => {
                    html += `<option value="${opt}">${opt}</option>`;
                });
                html += `</select>`;
                container.innerHTML = html;
            } else if (config.type === 'number') {
                container.innerHTML = `<input type="number" name="value" placeholder="${config.placeholder}" required min="0">`;
            }
        }
    </script>

    <?php include "includes/footer.php"; ?>
</body>
</html>
