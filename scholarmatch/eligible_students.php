<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";

// ── Fetch all scholarships for the dropdown
$sch = mysqli_query($conn, "SELECT scholarship_id, title FROM scholarships ORDER BY title ASC");

$selected_scholarship = isset($_REQUEST['scholarship']) ? intval($_REQUEST['scholarship']) : 0;
$eligible_students    = [];
$query_error          = '';

// ── Pagination config
$per_page    = 10;
$page        = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

if($selected_scholarship > 0){
    $sid = $selected_scholarship;

    // Fetch eligibility rules
    $stmt_rules = mysqli_prepare($conn, "SELECT * FROM eligibility_rules WHERE scholarship_id = ?");
    if (!$stmt_rules) {
        $query_error = "DB error preparing rules query.";
    } else {
        mysqli_stmt_bind_param($stmt_rules, "i", $sid);
        mysqli_stmt_execute($stmt_rules);
        $rules = mysqli_stmt_get_result($stmt_rules);

        $conditions = [];
        $valid_fields = [];
        $allowed_operators = ['=', '>=', '<=', '>', '<'];

        $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM student_profile");
        while($col = mysqli_fetch_assoc($columns_result)){
            $valid_fields[$col['Field']] = true;
        }

        while($r = mysqli_fetch_assoc($rules)){
            $field     = trim($r['field_name']);
            $operator  = trim($r['operator']);
            $raw_value = trim($r['value']);

            if(!isset($valid_fields[$field])) continue;
            if(!in_array($operator, $allowed_operators, true)) $operator = '=';
            if(strcasecmp($raw_value, 'All') === 0 || strcasecmp($raw_value, 'All India') === 0) continue;

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

        // Update query to include name, email, and mobile
        $sql = "SELECT u.name, u.email, u.mobile, sp.* FROM student_profile sp
                JOIN users u ON u.user_id = sp.user_id";

        if($conditions){
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $result = mysqli_query($conn, $sql);
        $query_error = !$result ? mysqli_error($conn) : '';

        if($result){
            while($row = mysqli_fetch_assoc($result)){
                $eligible_students[] = $row;
            }
        }
    }
}

// ── Pagination calculations
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
    <title>Eligible Students - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .filter-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-form select {
            flex: 1;
            min-width: 300px;
            margin-bottom: 0;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
        }
        .filter-form button {
            padding: 0.75rem 2rem;
            background: #4f46e5;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-form button:hover { background: #4338ca; transform: translateY(-1px); }

        .student-table-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
        }
        table { margin: 0; box-shadow: none; width: 100%; border-collapse: collapse; }
        th { background: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 1.25rem 1rem; border-bottom: 2px solid #f1f5f9; }
        td { padding: 1.25rem 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover { background: #fbfcfe; }

        .student-main { display: flex; flex-direction: column; gap: 0.25rem; }
        .student-name { font-weight: 700; color: #1e293b; font-size: 1rem; }
        .student-meta { font-size: 0.85rem; color: #64748b; }
        
        .badge-education { background: #eff6ff; color: #1d4ed8; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .marks-text { font-weight: 700; color: #059669; }
        
        .empty-state { text-align: center; padding: 4rem 2rem; color: #94a3b8; }
        .empty-state h3 { color: #64748b; margin-bottom: 0.5rem; }
    </style>
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container" style="max-width: 1200px; margin-top: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0;">Eligible Students Search</h2>
            <a href="admin_dashboard.php" class="btn" style="background: #64748b;">← Back</a>
        </div>

        <!-- ── Filter Header ── -->
        <div class="filter-header">
            <form method="get" class="filter-form" action="eligible_students.php">
                <select name="scholarship" id="scholarship-select" required>
                    <option value="">-- Select Scholarship to Check Eligibility --</option>
                    <?php mysqli_data_seek($sch, 0); while($s = mysqli_fetch_assoc($sch)): ?>
                    <option value="<?= intval($s['scholarship_id']) ?>"
                        <?= ($selected_scholarship === intval($s['scholarship_id'])) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['title']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit">🔍 Run Eligibility Check</button>
            </form>
        </div>

        <?php if(!empty($query_error)): ?>
            <div class="alert danger">Error: <?= htmlspecialchars($query_error) ?></div>
        <?php endif; ?>

        <?php if($selected_scholarship > 0): ?>
            <?php if($total_students > 0): ?>
                <div style="margin-bottom: 1rem; color: #64748b; font-size: 0.95rem;">
                    Found <strong><?= $total_students ?></strong> eligible students matching the scholarship criteria.
                </div>

                <div class="student-table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student Details</th>
                                <th>Contact Information</th>
                                <th>Academic Info</th>
                                <th>Profile Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paged_students as $st): ?>
                            <tr>
                                <td>
                                    <div class="student-main">
                                        <div class="student-name"><?= htmlspecialchars($st['name'] ?? 'Unknown') ?></div>
                                        <div><span class="badge-education"><?= htmlspecialchars($st['education_level'] ?? 'N/A') ?></span></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="student-meta">
                                        📧 <?= htmlspecialchars($st['email'] ?? 'N/A') ?><br>
                                        📱 <?= htmlspecialchars($st['mobile'] ?? 'N/A') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="student-meta">
                                        Marks: <span class="marks-text"><?= htmlspecialchars($st['marks'] ?? '0') ?>%</span><br>
                                        Income: ₹<?= number_format(intval($st['family_income'] ?? 0)) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="student-meta">
                                        Cat: <?= htmlspecialchars($st['category'] ?? 'General') ?><br>
                                        Loc: <?= htmlspecialchars($st['state_id'] ? 'State ID: '.$st['state_id'] : 'N/A') ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&scholarship=<?= $selected_scholarship ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <h3>No eligible students found</h3>
                    <p>Try adjusting the eligibility rules for this scholarship.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <p>Please select a scholarship from the dropdown above to view eligible students.</p>
            </div>
        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>
</body>
</html>
