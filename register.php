<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
include "db.php"; // database connection

if(isset($_POST['register'])){

    // Fetch form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // secure

    // Check if email or mobile already exists (use prepared statements)
    $check_sql = "SELECT email, mobile FROM users WHERE email = ? OR mobile = ? LIMIT 1";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $email, $mobile);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $existing_email, $existing_mobile);
        if (mysqli_stmt_fetch($stmt)) {
            $errors = [];
            if ($existing_email === $email) $errors[] = "Email already registered";
            if ($existing_mobile === $mobile) $errors[] = "Mobile number already registered";
            mysqli_stmt_close($stmt);
            foreach ($errors as $e) {
                echo htmlspecialchars($e) . "!<br>";
            }
        } else {
            mysqli_stmt_close($stmt);

            // Insert user using prepared statement
            $ins_sql = "INSERT INTO users (name,email,mobile,password) VALUES (?,?,?,?)";
            if ($ins = mysqli_prepare($conn, $ins_sql)) {
                mysqli_stmt_bind_param($ins, "ssss", $name, $email, $mobile, $password);
                if (mysqli_stmt_execute($ins)) {
                    mysqli_stmt_close($ins);
                    header("Location: login.php");
                    exit();
                } else {
                    $err = mysqli_stmt_error($ins);
                    echo "Error: " . htmlspecialchars($err);
                    mysqli_stmt_close($ins);
                }
            } else {
                echo "Error preparing statement: " . htmlspecialchars(mysqli_error($conn));
            }
        }
    } else {
        echo "Error preparing check: " . htmlspecialchars(mysqli_error($conn));
    }
}
?>

<form method="post" action="register.php">
  <input type="text" name="name" placeholder="Full Name" required><br><br>
  <input type="email" name="email" placeholder="Email" required><br><br>
  <input type="text" name="mobile" placeholder="Mobile Number" required><br><br>
  <input type="password" name="password" placeholder="Password" required><br><br>
  <button type="submit" name="register">Register</button>
</form>