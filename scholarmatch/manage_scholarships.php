<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

include "db.php";

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ── Search & Filter inputs (GET so they persist across page links) ─────────────
$search     = trim($_GET['search']  ?? '');
$filter_status = trim($_GET['status'] ?? '');          // 'active' | 'inactive' | ''

// ── Pagination config ─────────────────────────────────────────────────────────
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset   = ($page - 1) * $per_page;

// ── Build WHERE clause dynamically (safe values only) ─────────────────────────
$where_parts  = [];
$bind_types   = '';
$bind_values  = [];

if($search !== ''){
    $where_parts[] = "title LIKE ?";
    $bind_types   .= 's';
    $bind_values[] = '%' . $search . '%';
}

$allowed_statuses = ['active', 'inactive'];
if(in_array($filter_status, $allowed_statuses, true)){
    $where_parts[] = "status = ?";
    $bind_types   .= 's';
    $bind_values[] = $filter_status;
}

$where_sql = $where_parts ? ('WHERE ' . implode(' AND ', $where_parts)) : '';

// ── Get total count for pagination ─────────────────────────────────────────────
$count_sql  = "SELECT COUNT(*) AS total FROM scholarships $where_sql";
$count_stmt = $conn->prepare($count_sql);

if($count_stmt){
    if($bind_values){
        $count_stmt->bind_param($bind_types, ...$bind_values);
    }
    $count_stmt->execute();
    $count_row   = $count_stmt->get_result()->fetch_assoc();
    $total       = (int)($count_row['total'] ?? 0);
    $count_stmt->close();
} else {
    $total = 0;
}

$total_pages = max(1, (int)ceil($total / $per_page));
if($page > $total_pages) {
    $page   = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// ── Fetch paginated results ────────────────────────────────────────────────────
$data_sql  = "SELECT * FROM scholarships $where_sql ORDER BY scholarship_id DESC LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);

$scholarships = [];
if($data_stmt){
    $paged_types  = $bind_types . 'ii';
    $paged_values = array_merge($bind_values, [$per_page, $offset]);
    $data_stmt->bind_param($paged_types, ...$paged_values);
    $data_stmt->execute();
    $data_result = $data_stmt->get_result();
    while($row = $data_result->fetch_assoc()){
        $scholarships[] = $row;
    }
    $data_stmt->close();
}

// ── Helper: build query string preserving current filters ─────────────────────
function page_url(int $p, string $search, string $status): string {
    $params = ['page' => $p];
    if($search  !== '') $params['search'] = $search;
    if($status  !== '') $params['status'] = $status;
    return '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Scholarships - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* ── Search / filter bar ── */
        .filter-bar {
            display: flex;
            gap: .75rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        .filter-bar input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: .55rem .9rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: .95rem;
        }
        .filter-bar select {
            padding: .55rem .9rem;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: .95rem;
        }
        .filter-bar button {
            padding: .55rem 1.2rem;
            border-radius: 6px;
            font-size: .95rem;
            cursor: pointer;
        }
        .filter-bar a.clear-btn {
            padding: .55rem 1rem;
            border: 1px solid #bbb;
            border-radius: 6px;
            font-size: .9rem;
            color: #555;
            text-decoration: none;
            background: #f5f5f5;
        }
        .filter-bar a.clear-btn:hover { background:#e0e0e0; }

        /* ── Scholarship card ── */
        .manage-scholarship-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: .85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: .5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .manage-scholarship-card .card-info { flex: 1; }
        .manage-scholarship-card .card-info b { font-size: 1.05rem; }
        .manage-scholarship-card .card-info small { color:#777; display:block; margin-top:.2rem; }
        .manage-scholarship-card .card-actions { display:flex; gap:.6rem; align-items:center; }
        .status-chip { padding: .25rem .65rem; border-radius: 12px; font-size: .8rem; font-weight: 600; }
        .status-chip.success  { background:#d4edda; color:#155724; }
        .status-chip.muted    { background:#f0f0f0; color:#555; }

        /* ── Results summary ── */
        .results-summary { color:#666; font-size:.9rem; margin-bottom:.75rem; }

        /* ── No results ── */
        .no-results { text-align:center; padding:3rem; color:#999; }
    </style>
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container">
        <h2>Manage Scholarships</h2>

        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>

        <!-- ── Search & Filter bar ── -->
        <form method="get" action="manage_scholarships.php" id="manage-filter-form">
            <div class="filter-bar">
                <input type="text" name="search" id="manage-search"
                       placeholder="🔍 Search by title..."
                       value="<?php echo htmlspecialchars($search); ?>">

                <select name="status" id="manage-status-filter">
                    <option value="">All Statuses</option>
                    <option value="active"   <?= $filter_status === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>

                <button type="submit" id="manage-filter-btn">Filter</button>

                <?php if($search !== '' || $filter_status !== ''): ?>
                    <a href="manage_scholarships.php" class="clear-btn" id="clear-filters-btn">✕ Clear</a>
                <?php endif; ?>

                <a href="add_scholarship.php" class="btn" style="margin-left:auto;" id="add-scholarship-link">+ Add New</a>
            </div>
        </form>

        <!-- ── Results summary ── -->
        <?php if($total > 0): ?>
            <p class="results-summary">
                Showing <strong><?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?></strong>
                of <strong><?= $total ?></strong> scholarship(s)
                <?php if($search !== ''): ?>— matching "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
                <?php if($filter_status !== ''): ?>— status: <strong><?= htmlspecialchars($filter_status) ?></strong><?php endif; ?>
            </p>
        <?php endif; ?>

        <!-- ── Scholarship cards ── -->
        <?php if(empty($scholarships)): ?>
            <div class="no-results">
                😕 No scholarships found<?php echo $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : ''; ?>.
                <br><a href="add_scholarship.php">Add the first scholarship →</a>
            </div>
        <?php else: ?>

            <?php foreach($scholarships as $s): ?>
            <div class="manage-scholarship-card">
                <div class="card-info">
                    <b><?= htmlspecialchars($s['title']) ?></b>
                    <small>
                        <?php if(!empty($s['start_date']) || !empty($s['end_date'])): ?>
                            📅 <?= htmlspecialchars($s['start_date'] ?? '?') ?>
                            → <?= htmlspecialchars($s['end_date']   ?? '?') ?>
                        <?php endif; ?>
                    </small>
                </div>
                <div class="card-actions">
                    <span class="status-chip <?= $s['status'] === 'active' ? 'success' : 'muted' ?>">
                        <?= htmlspecialchars($s['status']) ?>
                    </span>
                    <a href="toggle_scholarship.php?id=<?= intval($s['scholarship_id']) ?>"
                       id="toggle-sch-<?= intval($s['scholarship_id']) ?>"
                       onclick="return confirm('Toggle status for this scholarship?');"
                       title="Toggle active / inactive">
                       <?= $s['status'] === 'active' ? '⏸ Deactivate' : '▶ Activate' ?>
                    </a>
                    <a href="add_eligibility_rule.php?scholarship=<?= intval($s['scholarship_id']) ?>"
                       id="rules-sch-<?= intval($s['scholarship_id']) ?>"
                       title="Add eligibility rules">⚙ Rules</a>
                </div>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>

        <!-- ── Pagination ── -->
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="<?= page_url(1, $search, $filter_status) ?>">&laquo; First</a>
                <a href="<?= page_url($page - 1, $search, $filter_status) ?>">&lsaquo; Prev</a>
            <?php endif; ?>

            <?php
            $start_p = max(1, $page - 2);
            $end_p   = min($total_pages, $page + 2);
            for($i = $start_p; $i <= $end_p; $i++):
            ?>
                <?php if($i === $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= page_url($i, $search, $filter_status) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if($page < $total_pages): ?>
                <a href="<?= page_url($page + 1, $search, $filter_status) ?>">Next &rsaquo;</a>
                <a href="<?= page_url($total_pages, $search, $filter_status) ?>">Last &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
