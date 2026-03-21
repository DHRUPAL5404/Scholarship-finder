<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";
session_start();

$register_error = '';

// Password validation function
function validatePassword($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least 1 number";
    }
    
    return $errors;
}

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $password = $_POST['password'];

    // Validate password on server side
    $password_errors = validatePassword($password);
    if (!empty($password_errors)) {
        $register_error = implode(", ", $password_errors);
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);

        // Use prepared statement to check if email exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$check_stmt) {
            $register_error = "Database error: " . $conn->error;
        } else {
            $check_stmt->bind_param("s", $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if($result && $result->num_rows > 0){
                $register_error = "Email already registered!";
            } else {
                // Use prepared statement for user registration
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, mobile, password, role) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $register_error = "Database error: " . $conn->error;
                } else {
                    $role = 'student';
                    $stmt->bind_param("sssss", $name, $email, $mobile, $password, $role);

                    if($stmt->execute()){
                        $user_id = $stmt->insert_id;
                        $stmt->close();

                        // Use prepared statement for profile creation
                        $profile_stmt = $conn->prepare("INSERT INTO student_profile (user_id, full_name) VALUES (?, ?)");
                        if ($profile_stmt) {
                            $profile_stmt->bind_param("is", $user_id, $name);
                            if($profile_stmt->execute()){
                                $_SESSION['flash_success'] = "Registration successful. Please login.";
                            } else {
                                $_SESSION['flash_success'] = "Registration successful. Please login to complete profile.";
                            }
                            $profile_stmt->close();
                        }
                        header("Location: login.php");
                        exit();
                    } else {
                        $register_error = "Error: " . $stmt->error;
                        $stmt->close();
                    }
                }
            }
            $check_stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ScholarMatch</title>
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
          <div class="input-wrapper">
            <input type="text" name="name" placeholder="Full Name" required>
          </div>
          <div class="input-wrapper">
            <input type="email" name="email" placeholder="Email" required>
          </div>
          <div class="input-wrapper">
            <input type="text" name="mobile" placeholder="Mobile Number" required>
          </div>
          <div class="input-wrapper">
            <input type="password" name="password" placeholder="Password (Min 8 chars, at least 1 number)" required>
          </div>
          <button type="submit" name="register">Register</button>
        </form>
    </main>

    <?php include "includes/footer.php"; ?>

    <script src="assets/js/validation.js?v=<?php echo time(); ?>"></script>

</body>
</html>
