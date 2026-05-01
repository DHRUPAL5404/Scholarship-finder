<?php
session_start();
include "db.php";
require_once "includes/csrf.php";

$login_error  = '';
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error   = $_SESSION['flash_error']   ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if(isset($_POST['login'])){

    // ── CSRF verification ──────────────────────────────────────────────────
    csrf_verify();

    // ── Server-side input validation ───────────────────────────────────────
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $login_error = "Please enter a valid email address.";
    } elseif(empty($password)){
        $login_error = "Password is required.";
    } else {

        // ── Prepared statement login query ─────────────────────────────────
        $stmt = $conn->prepare("SELECT user_id, name, password, role FROM users WHERE email = ?");
        if (!$stmt) {
            $login_error = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if($result && $result->num_rows === 1){
                $row = $result->fetch_assoc();

                if(password_verify($password, $row['password'])){
                    // Regenerate session ID on login to prevent session fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id']   = $row['user_id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['role']      = $row['role'];
                    $_SESSION['flash_success'] = "Login successful.";

                    if($row['role'] === 'student'){
                        header("Location: student_dashboard.php");
                    } else {
                        header("Location: admin_dashboard.php");
                    }
                    exit();
                } else {
                    $login_error = "Invalid email or password.";
                }
            } else {
                $login_error = "Invalid email or password.";
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <main>
        <h2>Login to Scholar Match</h2>

        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        <?php if($login_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($login_error); ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" id="login-form">
            <!-- ── CSRF token ── -->
            <?php csrf_token(); ?>

            <div class="input-wrapper">
                <input type="email" name="email" id="login-email"
                       placeholder="Email" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="input-wrapper">
                <input type="password" name="password" id="login-password"
                       placeholder="Password" required>
            </div>
            <button type="submit" name="login" id="login-submit-btn">Login</button>
        </form>

        <p style="margin-top:1rem; text-align:center;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </main>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>
</body>
</html>
