<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";

// ── Fetch all scholarships for the dropdown (no user input used here)
$sch = mysqli_query($conn, "SELECT scholarship_id, title FROM scholarships ORDER BY title ASC");

$selected_scholarship = isset($_POST['scholarship']) ? intval($_POST['scholarship']) : 0;
$eligible_students    = [];
$query_error          = '';

// ── Pagination config
$per_page    = 10;
$page        = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

if(isset($_POST['check'])){
    $sid = $selected_scholarship;

    // Fetch eligibility rules using prepared statement
    $stmt_rules = mysqli_prepare($conn, "SELECT * FROM eligibility_rules WHERE scholarship_id = ?");
    if (!$stmt_rules) {
        $query_error = "DB error preparing rules query.";
    } else {
        mysqli_stmt_bind_param($stmt_rules, "i", $sid);
        mysqli_stmt_execute($stmt_rules);
        $rules = mysqli_stmt_get_result($stmt_rules);

        $conditions = [];

        // ── Validate against actual DB columns (no user input in column names)
        $allowed_operators = ['=', '>=', '<=', '>', '<'];
        $valid_fields      = [];

        $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM student_profile");
        while($col = mysqli_fetch_assoc($columns_result)){
            $valid_fields[$col['Field']] = true;
        }

        while($r = mysqli_fetch_assoc($rules)){
            $field     = trim($r['field_name']);
            $operator  = trim($r['operator']);
            $raw_value = trim($r['value']);

            // Skip rules whose field does not exist in the schema
            if(!isset($valid_fields[$field])) continue;

            // Normalise operator
            if(!in_array($operator, $allowed_operators, true)){
                $operator = '=';
            }

            // Non-restrictive values — skip
            if(strcasecmp($raw_value, 'All') === 0 || strcasecmp($raw_value, 'All India') === 0){
                continue;
            }

            // Comma-separated values with '=' → IN (...)
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

            $value        = mysqli_real_escape_string($conn, $raw_value);
            $conditions[] = "sp.`$field` $operator '$value'";
        }
        mysqli_stmt_close($stmt_rules);

        $sql = "SELECT u.name AS full_name, u.email, sp.* FROM student_profile sp
                JOIN users u ON u.user_id = sp.user_id";

        if($conditions){
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $result      = mysqli_query($conn, $sql);
        $query_error = !$result ? mysqli_error($conn) : '';

        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $eligible_students[] = $row;
            }
        }
    }
}

// ── Pagination calculations (applied after results are fetched)
$total_students = count($eligible_students);
$total_pages    = max(1, (int)ceil($total_students / $per_page));
if($page > $total_pages) $page = $total_pages;
$offset         = ($page - 1) * $per_page;
$paged_students = array_slice($eligible_students, $offset, $per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligible Students - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <h2>Eligible Students</h2>

        <!-- ── Scholarship selector form ── -->
        <form method="post" id="eligible-students-form">
            <select name="scholarship" id="scholarship-select">
                <option value="">-- Select Scholarship --</option>
                <?php while($s = mysqli_fetch_assoc($sch)): ?>
                <option value="<?= intval($s['scholarship_id']) ?>"
                    <?= ($selected_scholarship === intval($s['scholarship_id'])) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['title']) /* ← XSS fix */ ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button name="check" type="submit" id="check-eligibility-btn">Check</button>
        </form>

        <!-- ── Error ── -->
        <?php if(!empty($query_error)): ?>
            <p style="color:#c0392b;">Error loading eligible students: <?php echo htmlspecialchars($query_error); ?></p>
        <?php endif; ?>

        <!-- ── Results table with pagination ── -->
        <?php if(isset($_POST['check']) && empty($query_error)): ?>

            <?php if($total_students > 0): ?>
                <p>Showing <strong><?= $offset + 1 ?>–<?= min($offset + $per_page, $total_students) ?></strong>
                   of <strong><?= $total_students ?></strong> eligible student(s).</p>

                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Marks</th>
                            <th>Category</th>
                            <th>Education</th>
                            <th>Income</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($paged_students as $i => $st): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($st['full_name']      ?? '') ?></td>
                                <td><?= htmlspecialchars($st['email']          ?? '') ?></td>
                                <td><?= htmlspecialchars($st['marks']          ?? '') ?>%</td>
                                <td><?= htmlspecialchars($st['category']       ?? '') ?></td>
                                <td><?= htmlspecialchars($st['education_level'] ?? '') ?></td>
                                <td><?= htmlspecialchars($st['family_income']  ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- ── Pagination controls ── -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=1&scholarship=<?= $selected_scholarship ?>">&laquo; First</a>
                        <a href="?page=<?= $page - 1 ?>&scholarship=<?= $selected_scholarship ?>">&lsaquo; Prev</a>
                    <?php endif; ?>

                    <?php
                    // Show at most 5 page links centered on current page
                    $start_p = max(1, $page - 2);
                    $end_p   = min($total_pages, $page + 2);
                    for($i = $start_p; $i <= $end_p; $i++):
                    ?>
                        <?php if($i === $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&scholarship=<?= $selected_scholarship ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&scholarship=<?= $selected_scholarship ?>">Next &rsaquo;</a>
                        <a href="?page=<?= $total_pages ?>&scholarship=<?= $selected_scholarship ?>">Last &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert warning">No students are eligible for the selected scholarship.</div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
