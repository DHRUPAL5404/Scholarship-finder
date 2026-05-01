<?php
session_start();
include "db.php";
require_once "includes/csrf.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Ensure start_date / end_date columns exist (idempotent) ───────────────────
function ensureScholarshipDateColumns($conn): void {
    $needed = [
        'start_date' => 'DATE NULL',
        'end_date'   => 'DATE NULL',
    ];
    foreach($needed as $col => $def){
        $check = mysqli_query($conn, "SHOW COLUMNS FROM scholarships LIKE '$col'");
        if($check && mysqli_num_rows($check) === 0){
            mysqli_query($conn, "ALTER TABLE scholarships ADD COLUMN $col $def");
        }
    }
}
ensureScholarshipDateColumns($conn);

if(isset($_POST['add'])){

    // ── CSRF verification ──────────────────────────────────────────────────
    csrf_verify();

    // ── Collect & sanitise inputs ──────────────────────────────────────────
    $title      = trim($_POST['title']       ?? '');
    $desc       = trim($_POST['description'] ?? '');
    $amount     = intval($_POST['amount'] ?? 0);
    $max_applicants = intval($_POST['max_applicants'] ?? 0);
    $start_date = trim($_POST['start_date']  ?? '');
    $end_date   = trim($_POST['end_date']    ?? '');

    // ── Server-side validation ─────────────────────────────────────────────
    $errors = [];

    if(empty($title)){
        $errors[] = "Scholarship title is required.";
    } elseif(strlen($title) > 255){
        $errors[] = "Title must not exceed 255 characters.";
    }

    if(empty($desc)){
        $errors[] = "Description is required.";
    } elseif(strlen($desc) < 20){
        $errors[] = "Description must be at least 20 characters.";
    }

    if($amount <= 0){
        $errors[] = "Amount must be greater than 0.";
    }

    if($max_applicants <= 0){
        $errors[] = "Max applicants must be greater than 0.";
    }

    if(empty($start_date)){
        $errors[] = "Start date is required.";
    } elseif(!strtotime($start_date)){
        $errors[] = "Start date is not a valid date.";
    }

    if(empty($end_date)){
        $errors[] = "End date is required.";
    } elseif(!strtotime($end_date)){
        $errors[] = "End date is not a valid date.";
    }

    if(empty($errors) && strtotime($start_date) > strtotime($end_date)){
        $errors[] = "Start date cannot be after end date.";
    }

    if(!empty($errors)){
        $_SESSION['flash_error'] = implode(" ", $errors);
        header("Location: add_scholarship.php");
        exit();
    }

    // ── Insert using prepared statement ────────────────────────────────────
    // deadline mirrors end_date for backward compatibility
    $status   = 'active';
    $deadline = $end_date;

    $stmt = $conn->prepare(
        "INSERT INTO scholarships (title, description, amount, max_applicants, start_date, end_date, deadline, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if(!$stmt){
        $_SESSION['flash_error'] = "Database error. Please try again later.";
    } else {
        $stmt->bind_param("ssiissss", $title, $desc, $amount, $max_applicants, $start_date, $end_date, $deadline, $status);

        if($stmt->execute()){
            $_SESSION['flash_success'] = "Scholarship \"" . htmlspecialchars($title) . "\" added successfully.";
        } else {
            $_SESSION['flash_error'] = "Failed to add scholarship. Please try again.";
        }
        $stmt->close();
    }

    header("Location: admin_dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Scholarship - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <h2>Add New Scholarship</h2>

        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>

        <form method="post" action="add_scholarship.php" id="add-scholarship-form">
            <!-- ── CSRF token ── -->
            <?php csrf_token(); ?>

            <div class="input-wrapper">
                <label for="sch-title">Title <span style="color:red">*</span></label>
                <input type="text" name="title" id="sch-title"
                       placeholder="e.g. Merit Scholarship 2026"
                       maxlength="255" required
                       value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="input-wrapper">
                <label for="sch-description">Description <span style="color:red">*</span></label>
                <textarea name="description" id="sch-description"
                          placeholder="Describe the scholarship criteria and benefits (min 20 characters)"
                          rows="5" required minlength="20"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="input-wrapper">
                <label for="sch-amount">Amount (₹) <span style="color:red">*</span></label>
                <input type="number" name="amount" id="sch-amount" placeholder="e.g. 50000" required min="1"
                       value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>">
            </div>

            <div class="input-wrapper">
                <label for="sch-max">Max Applicants <span style="color:red">*</span></label>
                <input type="number" name="max_applicants" id="sch-max" placeholder="e.g. 100" required min="1"
                       value="<?php echo htmlspecialchars($_POST['max_applicants'] ?? ''); ?>">
            </div>

            <div class="input-wrapper">
                <label for="sch-start">Start Date <span style="color:red">*</span></label>
                <input type="date" name="start_date" id="sch-start" required
                       value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
            </div>

            <div class="input-wrapper">
                <label for="sch-end">End Date / Deadline <span style="color:red">*</span></label>
                <input type="date" name="end_date" id="sch-end" required
                       value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
            </div>

            <button type="submit" name="add" id="add-scholarship-btn">Add Scholarship</button>
            <a href="admin_dashboard.php" class="btn-secondary" style="margin-left:1rem;">Cancel</a>
        </form>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>

    <script>
        // Client-side: enforce end_date >= start_date for better UX
        const startInput = document.getElementById('sch-start');
        const endInput   = document.getElementById('sch-end');

        startInput.addEventListener('change', function(){
            if(endInput.value && endInput.value < this.value){
                endInput.value = '';
            }
            endInput.min = this.value;
        });
    </script>
</body>
</html>
