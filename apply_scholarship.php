<?php
session_start();
include "db.php";
require_once "includes/csrf.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id        = $_SESSION['user_id'];
$scholarship_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error          = '';
$success        = '';

if ($scholarship_id <= 0) {
    header("Location: student_dashboard.php");
    exit();
}

// ── Fetch scholarship ─────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM scholarships WHERE scholarship_id = ? AND status = 'active'");
$stmt->bind_param("i", $scholarship_id);
$stmt->execute();
$scholarship = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$scholarship) {
    $_SESSION['flash_error'] = "Scholarship not found or is no longer active.";
    header("Location: student_dashboard.php");
    exit();
}

// ── Check if already applied ──────────────────────────────────────────────────
$chk = $conn->prepare("SELECT application_id, status FROM scholarship_applications WHERE scholarship_id = ? AND user_id = ?");
$chk->bind_param("ii", $scholarship_id, $user_id);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
$chk->close();

// ── Handle form submission ────────────────────────────────────────────────────
if (isset($_POST['apply'])) {
    csrf_verify();

    if ($existing) {
        $error = "You have already applied for this scholarship.";
    } else {
        $ins = $conn->prepare("INSERT INTO scholarship_applications (scholarship_id, user_id, status) VALUES (?, ?, 'pending')");
        $ins->bind_param("ii", $scholarship_id, $user_id);
        if ($ins->execute()) {
            $ins->close();
            $_SESSION['flash_success'] = "✅ Application submitted successfully for \"" . htmlspecialchars($scholarship['title']) . "\"!";
            header("Location: student_dashboard.php");
            exit();
        } else {
            $error = "Something went wrong. Please try again.";
            $ins->close();
        }
    }
}

// ── Fetch eligibility rules ───────────────────────────────────────────────────
$rules = [];
$rq = $conn->prepare("SELECT field_name, operator, value FROM eligibility_rules WHERE scholarship_id = ?");
$rq->bind_param("i", $scholarship_id);
$rq->execute();
$rr = $rq->get_result();
while ($row = $rr->fetch_assoc()) $rules[] = $row;
$rq->close();

$deadline_str = !empty($scholarship['deadline'])
    ? date('d M Y', strtotime($scholarship['deadline']))
    : 'N/A';
$days_left = !empty($scholarship['deadline'])
    ? (int)floor((strtotime($scholarship['deadline']) - time()) / 86400)
    : null;
$expired = $days_left !== null && $days_left < 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply — <?php echo htmlspecialchars($scholarship['title']); ?> | ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        * { box-sizing: border-box; }
        .apply-wrapper { max-width: 760px; margin: 2rem auto; padding: 0 1rem; }
        .apply-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 4px 24px rgba(0,0,0,.1);
        }
        .apply-hero {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 2rem 2rem 1.5rem; color: #fff;
        }
        .apply-hero h1 { margin: 0 0 .4rem; font-size: 1.4rem; line-height: 1.35; }
        .apply-hero .meta { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .88rem; opacity: .9; margin-top: .5rem; }
        .apply-hero .meta span { background: rgba(255,255,255,.2); padding: .2rem .7rem; border-radius: 20px; }

        .apply-body { padding: 1.8rem 2rem; }
        .section-title { font-size: 1rem; font-weight: 700; color: #374151; margin: 1.2rem 0 .6rem; border-bottom: 2px solid #f0f0f0; padding-bottom: .4rem; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: .75rem; margin-bottom: 1.2rem; }
        .info-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: .75rem 1rem; }
        .info-item .label { font-size: .75rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; }
        .info-item .value { font-size: 1.05rem; font-weight: 700; color: #1f2937; margin-top: .15rem; }

        .desc-block { background: #f9fafb; border-left: 4px solid #667eea; border-radius: 0 8px 8px 0; padding: 1rem 1.2rem; font-size: .92rem; color: #374151; line-height: 1.65; margin-bottom: 1.2rem; }

        .rules-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
        .rules-table th { background: #f3f4f6; text-align: left; padding: .6rem 1rem; color: #6b7280; font-weight: 600; }
        .rules-table td { padding: .55rem 1rem; border-bottom: 1px solid #f0f0f0; }
        .rules-table tr:last-child td { border-bottom: none; }

        .already-applied { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 10px; padding: 1rem 1.2rem; color: #065f46; font-weight: 600; margin-bottom: 1rem; }
        .expired-notice  { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 10px; padding: 1rem 1.2rem; color: #991b1b; font-weight: 600; margin-bottom: 1rem; }

        .btn-row { display: flex; gap: .8rem; flex-wrap: wrap; margin-top: 1.8rem; }
        .btn-back  { flex: 1; text-align: center; padding: .7rem 1rem; background: #f3f4f6; color: #374151; border-radius: 10px; text-decoration: none; font-weight: 600; border: 1px solid #d1d5db; }
        .btn-back:hover { background: #e5e7eb; }
        .btn-submit { flex: 2; padding: .7rem 1.5rem; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; border: none; border-radius: 10px; font-size: 1rem; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(102,126,234,.4); transition: opacity .2s; }
        .btn-submit:hover { opacity: .88; }
        .btn-submit:disabled { opacity: .5; cursor: not-allowed; }

        .alert { padding: .8rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .92rem; }
        .alert.danger  { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
        .alert.success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    </style>
</head>
<body>
<?php include "includes/navbar.php"; ?>

<div class="apply-wrapper">

    <?php if ($error): ?>
        <div class="alert danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="apply-card">
        <!-- Hero header -->
        <div class="apply-hero">
            <h1><?= htmlspecialchars($scholarship['title']) ?></h1>
            <div class="meta">
                <span>💰 ₹<?= number_format(intval($scholarship['amount'])) ?></span>
                <span>📂 <?= htmlspecialchars($scholarship['category'] ?? 'General') ?></span>
                <span>🎓 <?= htmlspecialchars($scholarship['education_level'] ?? 'All levels') ?></span>
                <?php if ($days_left !== null): ?>
                <span style="<?= $expired ? 'background:rgba(239,68,68,.4);' : ($days_left <= 7 ? 'background:rgba(245,158,11,.4);' : '') ?>">
                    📅 <?= $expired ? 'Expired' : "$days_left days left" ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="apply-body">

            <!-- Info grid -->
            <div class="info-grid">
                <div class="info-item">
                    <div class="label">Amount</div>
                    <div class="value">₹<?= number_format(intval($scholarship['amount'])) ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Category</div>
                    <div class="value"><?= htmlspecialchars($scholarship['category'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Education Level</div>
                    <div class="value"><?= htmlspecialchars($scholarship['education_level'] ?? '—') ?></div>
                </div>
                <div class="info-item">
                    <div class="label">Deadline</div>
                    <div class="value" style="<?= $expired ? 'color:#dc2626' : '' ?>"><?= $deadline_str ?></div>
                </div>
                <?php if ($scholarship['min_marks'] > 0): ?>
                <div class="info-item">
                    <div class="label">Min. Marks</div>
                    <div class="value"><?= $scholarship['min_marks'] ?>%</div>
                </div>
                <?php endif; ?>
                <?php if ($scholarship['max_family_income'] > 0): ?>
                <div class="info-item">
                    <div class="label">Max. Family Income</div>
                    <div class="value">₹<?= number_format(intval($scholarship['max_family_income'])) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="section-title">About this Scholarship</div>
            <div class="desc-block"><?= nl2br(htmlspecialchars($scholarship['description'] ?? '')) ?></div>

            <!-- Eligibility rules -->
            <?php if (!empty($rules)): ?>
            <div class="section-title">Eligibility Criteria</div>
            <table class="rules-table">
                <thead>
                    <tr>
                        <th>Criterion</th>
                        <th>Condition</th>
                        <th>Required Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['field_name']))) ?></td>
                        <td><code><?= htmlspecialchars($r['operator']) ?></code></td>
                        <td><?= htmlspecialchars($r['value']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- Apply / status section -->
            <?php if ($existing): ?>
                <div class="already-applied">
                    ✅ You have already applied for this scholarship.
                    Status: <strong><?= ucfirst(htmlspecialchars($existing['status'])) ?></strong>
                </div>
            <?php elseif ($expired): ?>
                <div class="expired-notice">
                    ❌ This scholarship's deadline has passed. Applications are closed.
                </div>
            <?php else: ?>
                <form method="post" id="apply-form">
                    <?php csrf_token(); ?>
                    <p style="font-size:.9rem; color:#6b7280; margin-bottom:1rem;">
                        By clicking <strong>Submit Application</strong>, you confirm that all information
                        in your profile is accurate and you meet the eligibility criteria above.
                    </p>
                    <div class="btn-row">
                        <a href="student_dashboard.php" class="btn-back">← Back</a>
                        <button type="submit" name="apply" id="apply-submit-btn" class="btn-submit">
                            🚀 Submit Application
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if ($existing || $expired): ?>
            <div class="btn-row" style="margin-top:1rem;">
                <a href="student_dashboard.php" class="btn-back">← Back to Dashboard</a>
            </div>
            <?php endif; ?>

        </div><!-- /.apply-body -->
    </div><!-- /.apply-card -->
</div>

<?php include "includes/footer.php"; ?>
<script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
