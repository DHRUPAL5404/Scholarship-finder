<?php
// Note: Remove error display in production — never expose PHP errors to users
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

include "db.php";
session_start();
require_once "includes/csrf.php";

$register_error = '';

// ── Server-side validation function ───────────────────────────────────────────
function validateRegistration(string $name, string $email, string $mobile, string $password): array {
    $errors = [];

    // Full name
    if(empty($name)){
        $errors[] = "Full name is required.";
    } elseif(strlen($name) < 2 || strlen($name) > 100){
        $errors[] = "Full name must be between 2 and 100 characters.";
    } elseif(!preg_match('/^[\p{L}\s\'\-\.]+$/u', $name)){
        $errors[] = "Full name contains invalid characters.";
    }

    // Email
    if(empty($email)){
        $errors[] = "Email is required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errors[] = "Please enter a valid email address.";
    } elseif(strlen($email) > 255){
        $errors[] = "Email address is too long.";
    }

    // Mobile — must be exactly 10 digits
    if(empty($mobile)){
        $errors[] = "Mobile number is required.";
    } elseif(!preg_match('/^\d{10}$/', $mobile)){
        $errors[] = "Mobile number must be exactly 10 digits (numbers only).";
    }

    // Password
    if(empty($password)){
        $errors[] = "Password is required.";
    } elseif(strlen($password) < 8){
        $errors[] = "Password must be at least 8 characters long.";
    } elseif(!preg_match('/[0-9]/', $password)){
        $errors[] = "Password must contain at least one number.";
    } elseif(!preg_match('/[A-Za-z]/', $password)){
        $errors[] = "Password must contain at least one letter.";
    }

    return $errors;
}

if(isset($_POST['register'])){

    // ── CSRF verification ──────────────────────────────────────────────────
    csrf_verify();

    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $mobile   = trim($_POST['mobile']   ?? '');
    $password = $_POST['password']      ?? '';   // don't trim passwords

    // ── Run server-side validation ─────────────────────────────────────────
    $validation_errors = validateRegistration($name, $email, $mobile, $password);

    if(!empty($validation_errors)){
        $register_error = implode(" ", $validation_errors);
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ── Check if email already exists ──────────────────────────────────
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if(!$check_stmt){
            $register_error = "Database error. Please try again later.";
        } else {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if($check_result && $check_result->num_rows > 0){
                $register_error = "This email is already registered. Please login instead.";
                $check_stmt->close();
            } else {
                $check_stmt->close();

                // ── Check if mobile already exists ─────────────────────────
                $mobile_stmt = $conn->prepare("SELECT user_id FROM users WHERE mobile = ?");
                if(!$mobile_stmt){
                    $register_error = "Database error. Please try again later.";
                } else {
                    $mobile_stmt->bind_param("s", $mobile);
                    $mobile_stmt->execute();
                    $mobile_result = $mobile_stmt->get_result();

                    if($mobile_result && $mobile_result->num_rows > 0){
                        $register_error = "This mobile number is already registered.";
                        $mobile_stmt->close();
                    } else {
                        $mobile_stmt->close();

                        // ── Insert new user ────────────────────────────────
                        $role = 'student';
                        $stmt = $conn->prepare("INSERT INTO users (name, email, mobile, password, role) VALUES (?, ?, ?, ?, ?)");
                        if(!$stmt){
                            $register_error = "Database error. Please try again later.";
                        } else {
                            $stmt->bind_param("sssss", $name, $email, $mobile, $hashed_password, $role);

                            if($stmt->execute()){
                                $user_id = $stmt->insert_id;
                                $stmt->close();

                                // ── Create empty student profile ───────────
                                $profile_stmt = $conn->prepare("INSERT INTO student_profile (user_id, full_name) VALUES (?, ?)");
                                if($profile_stmt){
                                    $profile_stmt->bind_param("is", $user_id, $name);
                                    $profile_stmt->execute();
                                    $profile_stmt->close();
                                }

                                $_SESSION['flash_success'] = "Registration successful! Please login.";
                                header("Location: login.php");
                                exit();
                            } else {
                                $register_error = "Registration failed. Please try again.";
                                $stmt->close();
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Scholar Match</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <main>
        <h2>Create Your Account</h2>

        <?php if($register_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($register_error); ?></div>
        <?php endif; ?>

        <form method="post" action="register.php" id="register-form">
            <!-- ── CSRF token ── -->
            <?php csrf_token(); ?>

            <div class="input-wrapper">
                <input type="text" name="name" id="reg-name"
                       placeholder="Full Name" required maxlength="100"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>
            <div class="input-wrapper">
                <input type="email" name="email" id="reg-email"
                       placeholder="Email" required maxlength="255"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            <div class="input-wrapper">
                <input type="text" name="mobile" id="reg-mobile"
                       placeholder="Mobile Number (10 digits)" required
                       pattern="\d{10}" maxlength="10"
                       value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
            </div>
            <div class="input-wrapper">
                <input type="password" name="password" id="reg-password"
                       placeholder="Password (Min 8 chars, at least 1 number)" required minlength="8">
            </div>
            <button type="submit" name="register" id="register-submit-btn">Register</button>
        </form>

        <p style="margin-top:1rem; text-align:center;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </main>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>

</body>
</html>
