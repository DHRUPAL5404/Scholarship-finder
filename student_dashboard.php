<?php
session_start();
include "db.php";
mysqli_report(MYSQLI_REPORT_OFF);

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id      = $_SESSION['user_id'];
$user_name    = $_SESSION['user_name'];
$flash_success  = $_SESSION['flash_success']  ?? '';
$flash_error    = $_SESSION['flash_error']    ?? '';
$profile_success= $_SESSION['profile_success']?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['profile_success']);

// ── Fetch student profile (prepared statement) ────────────────────────────────
$p_stmt = $conn->prepare("SELECT * FROM student_profile WHERE user_id = ?");
$profile = null;
if($p_stmt){
    $p_stmt->bind_param("i", $user_id);
    $p_stmt->execute();
    $profile = $p_stmt->get_result()->fetch_assoc();
    $p_stmt->close();
}
$has_profile = !empty($profile);
if(!$has_profile){
    $profile = ['category'=>null,'marks'=>0,'family_income'=>0,'education_level'=>null,'state_id'=>null];
}

// ── Search & filter from GET ──────────────────────────────────────────────────
$search        = trim($_GET['search']   ?? '');
$filter_expiry = trim($_GET['expiry']   ?? '');   // 'soon' | 'open'

// ── Check scholarships table exists ──────────────────────────────────────────
$setup_needed = !mysqli_query($conn, "SELECT 1 FROM scholarships LIMIT 1");

$all_scholarships = [];
if(!$setup_needed){
    $sch_result = mysqli_query($conn, "SELECT * FROM scholarships WHERE status='active' ORDER BY deadline ASC");
    if($sch_result){
        while($row = mysqli_fetch_assoc($sch_result)) $all_scholarships[] = $row;
    }
}

// ── Fetch valid profile columns once ─────────────────────────────────────────
$profile_columns = [];
$col_res = mysqli_query($conn, "SHOW COLUMNS FROM student_profile");
if($col_res){ while($c = mysqli_fetch_assoc($col_res)) $profile_columns[$c['Field']] = true; }

// ── Eligibility engine — returns status, issues[], percent ───────────────────
function checkEligibilityByRules(mysqli $conn, array $scholarship, array $student): array {
    static $rules_cache  = [];
    static $table_exists = null;
    global $profile_columns;

    $sid = intval($scholarship['scholarship_id'] ?? 0);
    if($sid <= 0) return ['status'=>'not_eligible','issues'=>[],'percent'=>0];

    if($table_exists === null){
        $table_exists = (bool)mysqli_query($conn, "SELECT 1 FROM eligibility_rules LIMIT 1");
    }

    if(!isset($rules_cache[$sid])){
        $rules_cache[$sid] = [];
        if($table_exists){
            $stmt = mysqli_prepare($conn, "SELECT field_name, operator, value FROM eligibility_rules WHERE scholarship_id = ?");
            if($stmt){
                mysqli_stmt_bind_param($stmt, "i", $sid);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while($r = mysqli_fetch_assoc($res)) $rules_cache[$sid][] = $r;
                mysqli_stmt_close($stmt);
            }
        }
    }

    $rules = $rules_cache[$sid];
    if(empty($rules)) return ['status'=>'eligible','issues'=>[],'percent'=>100];

    $allowed_ops = ['=','>=','<=','>','<'];
    $issues      = [];
    $total_rules = 0;

    foreach($rules as $r){
        $field     = trim($r['field_name'] ?? '');
        $operator  = trim($r['operator']   ?? '=');
        $raw_value = trim($r['value']       ?? '');

        if($field === '' || !isset($profile_columns[$field])) continue;
        if(!in_array($operator, $allowed_ops, true)) $operator = '=';
        if(strcasecmp($raw_value,'All')===0 || strcasecmp($raw_value,'All India')===0) continue;

        $total_rules++;
        $student_val = trim((string)($student[$field] ?? ''));

        // Comma-separated set membership
        if($operator === '=' && strpos($raw_value,',') !== false){
            $parts   = array_filter(array_map('trim', explode(',', $raw_value)), fn($v)=>$v!=='');
            $matched = false;
            foreach($parts as $p){ if(strcasecmp($student_val, $p)===0){ $matched=true; break; } }
            if(!$matched) $issues[] = "$field mismatch";
            continue;
        }

        // Numeric comparisons
        if(in_array($operator,['>=','<=','>','<'],true)){
            $sn = is_numeric($student_val) ? floatval($student_val) : 0;
            $rn = is_numeric($raw_value)   ? floatval($raw_value)   : 0;
            $ok = match($operator){
                '>=' => $sn >= $rn,
                '<=' => $sn <= $rn,
                '>'  => $sn >  $rn,
                '<'  => $sn <  $rn,
                default => true,
            };
            if(!$ok) $issues[] = "$field condition not met";
            continue;
        }

        // Exact equality
        if(strcasecmp($student_val, $raw_value) !== 0) $issues[] = "$field mismatch";
    }

    $failed  = count($issues);
    $percent = $total_rules > 0 ? max(0, round((($total_rules - $failed) / $total_rules) * 100)) : 100;

    return [
        'status'  => $failed === 0 ? 'eligible' : 'not_eligible',
        'issues'  => $issues,
        'percent' => $percent,
    ];
}

// ── Attach eligibility to every scholarship ───────────────────────────────────
$enriched = [];
foreach($all_scholarships as $s){
    $elig              = checkEligibilityByRules($conn, $s, $profile);
    $s['_eligibility'] = $elig;
    $days_left = $s['deadline'] ? (int)floor((strtotime($s['deadline']) - time()) / 86400) : null;
    $s['_days_left']   = $days_left;

    // ── Apply search filter ────────────────────────────────────────────────
    if($search !== '' && stripos($s['title'], $search) === false) continue;

    // ── Apply expiry filter ────────────────────────────────────────────────
    if($filter_expiry === 'soon' && ($days_left === null || $days_left > 7 || $days_left < 0)) continue;
    if($filter_expiry === 'open' && $days_left !== null && $days_left < 0) continue;

    $enriched[] = $s;
}

// Sort: eligible first, then by days_left ASC
usort($enriched, function($a,$b){
    $ae = $a['_eligibility']['status'] === 'eligible' ? 0 : 1;
    $be = $b['_eligibility']['status'] === 'eligible' ? 0 : 1;
    if($ae !== $be) return $ae - $be;
    return ($a['_days_left'] ?? 999) - ($b['_days_left'] ?? 999);
});

// ── Pagination ────────────────────────────────────────────────────────────────
$per_page    = 10;
$page        = max(1, intval($_GET['page'] ?? 1));
$total       = count($enriched);
$total_pages = max(1, (int)ceil($total / $per_page));
if($page > $total_pages){ $page = $total_pages; }
$offset      = ($page - 1) * $per_page;
$scholarships = array_slice($enriched, $offset, $per_page);

// ── Expiring soon count ───────────────────────────────────────────────────────
$expiring_count = 0;
$expiring_res   = mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM scholarships
     WHERE status='active' AND deadline >= CURDATE() AND deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
if($expiring_res) $expiring_count = (int)(mysqli_fetch_assoc($expiring_res)['c'] ?? 0);

// ── Helpers ───────────────────────────────────────────────────────────────────
function page_url_dash(int $p, string $search, string $expiry): string {
    $params = ['page' => $p];
    if($search !== '') $params['search'] = $search;
    if($expiry !== '') $params['expiry'] = $expiry;
    return '?' . http_build_query($params);
}

function badge_color(int $pct): string {
    if($pct >= 80) return '#28a745';
    if($pct >= 50) return '#ffc107';
    return '#dc3545';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        /* ── Dashboard layout ── */
        .dash-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:1rem; margin-bottom:1.5rem; }
        .dash-header h1 { margin:0; font-size:1.6rem; }
        .dash-header p  { margin:.2rem 0 0; color:#888; font-size:.95rem; }
        .header-actions { display:flex; gap:.6rem; flex-wrap:wrap; }
        .header-actions a { padding:.45rem 1rem; border-radius:6px; font-size:.88rem; text-decoration:none;
                            background:#f0f0f0; color:#333; border:1px solid #ddd; }
        .header-actions a:hover { background:#e0e0e0; }
        .header-actions a.logout { background:#fee2e2; color:#b91c1c; border-color:#fca5a5; }

        /* ── Filter bar ── */
        .filter-bar { display:flex; gap:.7rem; flex-wrap:wrap; align-items:center; margin-bottom:1.25rem; }
        .filter-bar input[type="text"] {
            flex:1; min-width:210px; padding:.55rem .9rem;
            border:1px solid #ccc; border-radius:8px; font-size:.95rem;
        }
        .filter-bar select { padding:.55rem .9rem; border:1px solid #ccc; border-radius:8px; font-size:.95rem; }
        .filter-bar button { padding:.55rem 1.2rem; border-radius:8px; font-size:.95rem; cursor:pointer; }
        .filter-bar a.clear-btn { padding:.5rem .9rem; border:1px solid #ccc; border-radius:8px;
                                   font-size:.88rem; color:#555; background:#f5f5f5; text-decoration:none; }
        .filter-bar a.clear-btn:hover { background:#e0e0e0; }

        /* ── Scholarship grid ── */
        .scholarships-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(310px,1fr)); gap:1.25rem; }

        /* ── Scholarship card ── */
        .scholarship-card {
            background:#fff; border:1px solid #e5e7eb; border-radius:14px;
            box-shadow:0 2px 8px rgba(0,0,0,.07); display:flex; flex-direction:column;
            overflow:hidden; transition:transform .2s,box-shadow .2s;
        }
        .scholarship-card:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(0,0,0,.12); }
        .scholarship-card.eligible   { border-top:4px solid #22c55e; }
        .scholarship-card.not_eligible{ border-top:4px solid #f87171; }

        .card-header { padding:1rem 1.1rem .6rem; }
        .card-header h3 { margin:0; font-size:1.05rem; line-height:1.35; }

        .card-body { padding:.4rem 1.1rem; flex:1; }
        .card-desc { font-size:.88rem; color:#555; margin:.35rem 0 .75rem; line-height:1.5; }

        /* ── Eligibility % badge ── */
        .eligibility-badge {
            display:inline-flex; align-items:center; gap:.4rem;
            padding:.3rem .7rem; border-radius:20px; font-size:.82rem;
            font-weight:700; color:#fff; margin-bottom:.65rem;
        }
        .badge-ring {
            width:36px; height:36px; border-radius:50%; display:flex;
            align-items:center; justify-content:center;
            font-size:.78rem; font-weight:800; color:#fff;
            flex-shrink:0;
        }

        /* ── Deadline countdown ── */
        .deadline-strip {
            display:flex; align-items:center; gap:.5rem;
            padding:.42rem .75rem; border-radius:8px;
            background:#f8fafc; border:1px solid #e2e8f0;
            font-size:.84rem; margin-bottom:.6rem;
        }
        .deadline-strip .days-badge {
            padding:.18rem .55rem; border-radius:10px;
            font-weight:700; font-size:.8rem; color:#fff;
        }
        .deadline-strip .days-badge.urgent { background:#ef4444; animation:pulse 1.5s infinite; }
        .deadline-strip .days-badge.warning{ background:#f59e0b; }
        .deadline-strip .days-badge.ok     { background:#22c55e; }
        .deadline-strip .days-badge.expired{ background:#9ca3af; }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.6} }

        /* ── Issues list ── */
        .issues-list { font-size:.82rem; color:#b91c1c; margin:.4rem 0; }
        .issues-list ul { margin:.2rem 0 0 1rem; padding:0; }

        /* ── Card footer / buttons ── */
        .card-footer { padding:.75rem 1.1rem; border-top:1px solid #f0f0f0;
                       display:flex; gap:.6rem; flex-wrap:wrap; }
        .btn-details {
            flex:1; text-align:center; padding:.45rem .7rem; border-radius:7px;
            background:#f3f4f6; color:#374151; font-size:.85rem; text-decoration:none;
            border:1px solid #d1d5db; font-weight:600;
        }
        .btn-details:hover { background:#e5e7eb; }
        .btn-apply {
            flex:1; text-align:center; padding:.45rem .7rem; border-radius:7px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:#fff; font-size:.85rem; text-decoration:none; font-weight:700;
            border:none; box-shadow:0 2px 6px rgba(102,126,234,.4);
        }
        .btn-apply:hover { opacity:.9; }

        /* ── Stats row ── */
        .stats-row { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .stat-card {
            flex:1; min-width:140px; background:#fff; border:1px solid #e5e7eb;
            border-radius:12px; padding:.9rem 1.1rem; text-align:center;
            box-shadow:0 1px 4px rgba(0,0,0,.06);
        }
        .stat-card .stat-num { font-size:1.8rem; font-weight:800; line-height:1; }
        .stat-card .stat-label { font-size:.8rem; color:#888; margin-top:.3rem; }

        /* ── No results ── */
        .no-scholarships { text-align:center; padding:3rem 1rem; color:#9ca3af; font-size:1rem; }
        .results-summary { color:#666; font-size:.9rem; margin-bottom:.75rem; }
    </style>
</head>
<body>

<?php include "includes/navbar.php"; ?>

<div class="container">

    <!-- ── Header ── -->
    <div class="dash-header">
        <div>
            <h1>🎓 Scholarship Dashboard</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
        </div>
        <div class="header-actions">
            <a href="profile.php" id="update-profile-link">✏ Profile</a>
            <a href="check_eligibility.php" id="check-eligibility-link">🎯 Check Eligibility</a>
            <a href="logout.php" class="logout" id="logout-link">Logout</a>
        </div>
    </div>

    <!-- ── Flash messages ── -->
    <?php if($flash_success): ?><div class="alert success"><?= htmlspecialchars($flash_success) ?></div><?php endif; ?>
    <?php if($profile_success): ?><div class="alert success"><?= htmlspecialchars($profile_success) ?></div><?php endif; ?>
    <?php if($flash_error): ?><div class="alert danger"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

    <?php if($expiring_count > 0): ?>
    <div class="alert" style="background:#fff7ed;border-color:#fb923c;color:#9a3412;">
        ⏰ <strong><?= $expiring_count ?> scholarship<?= $expiring_count > 1 ? 's' : '' ?></strong>
        expiring within 7 days — apply soon!
    </div>
    <?php endif; ?>

    <?php if(!$has_profile): ?>
    <div class="alert" style="background:#eff6ff;border-color:#93c5fd;color:#1e40af;">
        📋 Please <a href="profile.php" style="color:#1d4ed8;font-weight:600;">complete your profile</a>
        to see accurate eligibility results.
    </div>
    <?php endif; ?>

    <?php if($setup_needed): ?>
    <div class="alert" style="background:#fef2f2;border-color:#fca5a5;color:#991b1b;">
        ⚠ <strong>Database Setup Required</strong> —
        <a href="setup_database.php" style="color:#991b1b;font-weight:700;">Click here to set up now</a>
    </div>
    <?php endif; ?>

    <!-- ── Stats row ── -->
    <?php
    $eligible_count    = count(array_filter($enriched, fn($s) => $s['_eligibility']['status'] === 'eligible'));
    $not_eligible_count= $total - $eligible_count;
    $avg_pct = $total > 0 ? round(array_sum(array_column(array_column($enriched,'_eligibility'),'percent')) / $total) : 0;
    ?>
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-num" style="color:#667eea"><?= $total ?></div>
            <div class="stat-label">Active Scholarships</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#22c55e"><?= $eligible_count ?></div>
            <div class="stat-label">You're Eligible</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#f59e0b"><?= $expiring_count ?></div>
            <div class="stat-label">Expiring Soon</div>
        </div>
        <div class="stat-card">
            <div class="stat-num" style="color:#764ba2"><?= $avg_pct ?>%</div>
            <div class="stat-label">Avg. Match Score</div>
        </div>
    </div>

    <!-- ── Search & Filter bar ── -->
    <form method="get" action="student_dashboard.php" id="dashboard-filter-form">
        <div class="filter-bar">
            <input type="text" name="search" id="dash-search"
                   placeholder="🔍 Search scholarships..."
                   value="<?= htmlspecialchars($search) ?>">
            <select name="expiry" id="dash-expiry-filter">
                <option value="">All Deadlines</option>
                <option value="soon"  <?= $filter_expiry==='soon'  ? 'selected':'' ?>>⏰ Expiring in 7 Days</option>
                <option value="open"  <?= $filter_expiry==='open'  ? 'selected':'' ?>>✅ Still Open</option>
            </select>
            <button type="submit" id="dash-filter-btn">Filter</button>
            <?php if($search !== '' || $filter_expiry !== ''): ?>
                <a href="student_dashboard.php" class="clear-btn" id="dash-clear-btn">✕ Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- ── Results summary ── -->
    <?php if($total > 0): ?>
    <p class="results-summary">
        Showing <strong><?= $offset+1 ?>–<?= min($offset+$per_page,$total) ?></strong>
        of <strong><?= $total ?></strong> scholarship(s) —
        <span style="color:#22c55e; font-weight:600;"><?= $eligible_count ?> eligible</span>
        (sorted: eligible first, nearest deadline)
    </p>
    <?php endif; ?>

    <!-- ── Scholarship cards grid ── -->
    <?php if(empty($scholarships)): ?>
        <div class="no-scholarships">
            😕 No scholarships found<?= $search !== '' ? ' for "<strong>'.htmlspecialchars($search).'</strong>"' : '' ?>.<br>
            <a href="student_dashboard.php" style="color:#667eea;">Clear filters</a>
        </div>
    <?php else: ?>
    <div class="scholarships-grid" id="cardView">
        <?php foreach($scholarships as $sch): ?>
        <?php
            $elig      = $sch['_eligibility'];
            $pct       = $elig['percent'];
            $is_elig   = $elig['status'] === 'eligible';
            $days_left = $sch['_days_left'];
            $is_urgent = $days_left !== null && $days_left >= 0 && $days_left <= 7;
            $expired   = $days_left !== null && $days_left < 0;
            $bg_color  = badge_color($pct);

            // Deadline chip class
            if($expired)        $chip_class = 'expired';
            elseif($is_urgent)  $chip_class = 'urgent';
            elseif($days_left !== null && $days_left <= 30) $chip_class = 'warning';
            else                $chip_class = 'ok';
        ?>
        <div class="scholarship-card <?= $is_elig ? 'eligible' : 'not_eligible' ?>">

            <!-- Card header -->
            <div class="card-header">
                <h3><?= htmlspecialchars($sch['title']) ?></h3>
            </div>

            <div class="card-body">

                <!-- ── Eligibility % badge ── -->
                <div style="display:flex; align-items:center; gap:.6rem; margin-bottom:.7rem;">
                    <div class="badge-ring"
                         style="background:<?= $bg_color ?>;
                                background:conic-gradient(<?= $bg_color ?> <?= $pct * 3.6 ?>deg, #e5e7eb 0deg);">
                        <span style="background:#fff; border-radius:50%; width:26px; height:26px;
                                     display:flex; align-items:center; justify-content:center;
                                     font-size:.65rem; font-weight:800; color:<?= $bg_color ?>;">
                            <?= $pct ?>%
                        </span>
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:.88rem; color:<?= $bg_color ?>">
                            <?= $is_elig ? '✅ Eligible' : '❌ Not Eligible' ?>
                        </div>
                        <div style="font-size:.78rem; color:#888;">Match score: <?= $pct ?>%</div>
                    </div>
                </div>

                <!-- Description -->
                <p class="card-desc">
                    <?= htmlspecialchars(mb_substr($sch['description'] ?? '', 0, 110)) ?>…
                </p>

                <!-- ── Deadline countdown strip ── -->
                <div class="deadline-strip">
                    📅 <span style="color:#555;">
                        <?= $sch['deadline'] ? htmlspecialchars(date('d M Y', strtotime($sch['deadline']))) : 'No deadline' ?>
                    </span>
                    <?php if($days_left !== null): ?>
                    <span class="days-badge <?= $chip_class ?>">
                        <?php if($expired): ?>
                            Expired
                        <?php elseif($days_left === 0): ?>
                            Today!
                        <?php else: ?>
                            <?= $days_left ?> day<?= $days_left !== 1 ? 's' : '' ?> left
                        <?php endif; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Issues (if not eligible) -->
                <?php if(!$is_elig && !empty($elig['issues'])): ?>
                <div class="issues-list">
                    <strong>Why not eligible:</strong>
                    <ul>
                        <?php foreach(array_slice($elig['issues'],0,3) as $issue): ?>
                        <li><?= htmlspecialchars($issue) ?></li>
                        <?php endforeach; ?>
                        <?php if(count($elig['issues']) > 3): ?>
                        <li>…and <?= count($elig['issues'])-3 ?> more</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <!-- Card footer: action buttons -->
            <div class="card-footer">
                <a href="scholarship_details.php?id=<?= intval($sch['scholarship_id']) ?>"
                   class="btn-details"
                   id="details-<?= intval($sch['scholarship_id']) ?>">View Details</a>
                <?php if($is_elig && !$expired): ?>
                <a href="apply_scholarship.php?id=<?= intval($sch['scholarship_id']) ?>"
                   class="btn-apply"
                   id="apply-<?= intval($sch['scholarship_id']) ?>">Apply Now →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div><!-- /.scholarships-grid -->

    <!-- ── Pagination ── -->
    <?php if($total_pages > 1): ?>
    <div class="pagination" style="margin-top:1.5rem;">
        <?php if($page > 1): ?>
            <a href="<?= page_url_dash(1, $search, $filter_expiry) ?>">&laquo;</a>
            <a href="<?= page_url_dash($page-1, $search, $filter_expiry) ?>">&lsaquo;</a>
        <?php endif; ?>
        <?php for($i=max(1,$page-2); $i<=min($total_pages,$page+2); $i++): ?>
            <?php if($i===$page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= page_url_dash($i, $search, $filter_expiry) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if($page < $total_pages): ?>
            <a href="<?= page_url_dash($page+1, $search, $filter_expiry) ?>">&rsaquo;</a>
            <a href="<?= page_url_dash($total_pages, $search, $filter_expiry) ?>">&raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; /* end if scholarships */ ?>

</div><!-- /.container -->

<?php include "includes/footer.php"; ?>
<script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
