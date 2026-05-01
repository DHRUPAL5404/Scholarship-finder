<?php
session_start();
include "db.php";
require_once "includes/csrf.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role']!='admin'){
    header("Location: login.php");
    exit();
}

$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    csrf_verify();
    $app_id = intval($_POST['application_id']);
    $new_status = $_POST['status'];
    
    $allowed_statuses = ['pending', 'approved', 'rejected'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $conn->prepare("UPDATE scholarship_applications SET status = ? WHERE application_id = ?");
        $stmt->bind_param("si", $new_status, $app_id);
        if ($stmt->execute()) {
            $flash_success = "Application #{$app_id} status updated to " . ucfirst($new_status);
        } else {
            $flash_error = "Error updating status.";
        }
        $stmt->close();
    } else {
        $flash_error = "Invalid status selected.";
    }
}

$res = @mysqli_query($conn,"
SELECT a.*, u.full_name, s.title, sp.uploaded_doc 
FROM scholarship_applications a
JOIN users u ON a.user_id = u.user_id
JOIN scholarships s ON s.scholarship_id = a.scholarship_id
LEFT JOIN student_profile sp ON a.user_id = sp.user_id
ORDER BY a.application_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        .badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: #fff; }
        .bg-pending { background-color: #f59e0b; }
        .bg-approved { background-color: #10b981; }
        .bg-rejected { background-color: #ef4444; }
        
        .update-form { display: flex; gap: 5px; align-items: center; }
        .update-form select { padding: 4px; border-radius: 4px; border: 1px solid #ccc; }
        .update-form button { padding: 4px 8px; border: none; background: #667eea; color: white; border-radius: 4px; cursor: pointer; }
        .update-form button:hover { background: #5a67d8; }
        
        .doc-link { color: #2563eb; text-decoration: none; font-weight: bold; }
        .doc-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container" style="max-width: 1000px;">
        <h2>Manage Applications</h2>
        
        <?php if($flash_success): ?>
            <div class="alert success"><?= htmlspecialchars($flash_success) ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?= htmlspecialchars($flash_error) ?></div>
        <?php endif; ?>

        <?php if(!$res): ?>
            <div class="alert danger">Applications table is unavailable. Please check database setup.</div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Scholarship</th>
                            <th>Applied Date</th>
                            <th>Document</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($a=mysqli_fetch_assoc($res)){ 
                            $status_class = 'bg-pending';
                            if ($a['status'] === 'approved') $status_class = 'bg-approved';
                            elseif ($a['status'] === 'rejected') $status_class = 'bg-rejected';
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($a['full_name']) ?></td>
                            <td><?= htmlspecialchars($a['title']) ?></td>
                            <td><?= date('d M Y', strtotime($a['application_date'])) ?></td>
                            <td>
                                <?php if(!empty($a['uploaded_doc'])): ?>
                                    <a href="<?= htmlspecialchars($a['uploaded_doc']) ?>" target="_blank" class="doc-link">View Doc</a>
                                <?php else: ?>
                                    <span style="color: #999;">No Doc</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $status_class ?>"><?= ucfirst(htmlspecialchars($a['status'])) ?></span></td>
                            <td>
                                <form method="post" class="update-form">
                                    <?php csrf_token(); ?>
                                    <input type="hidden" name="application_id" value="<?= $a['application_id'] ?>">
                                    <select name="status">
                                        <option value="pending" <?= $a['status']=='pending'?'selected':'' ?>>Pending</option>
                                        <option value="approved" <?= $a['status']=='approved'?'selected':'' ?>>Approve</option>
                                        <option value="rejected" <?= $a['status']=='rejected'?'selected':'' ?>>Reject</option>
                                    </select>
                                    <button type="submit" name="update_status">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
