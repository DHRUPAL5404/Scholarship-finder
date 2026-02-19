<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
include "db.php"; // database connection
session_start();
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

    <!-- Navbar -->
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#how-it-works">How It Works</a></li>
            <li><a href="index.php#features">Features</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php
    if(isset($_POST['register'])){

        // Fetch form data
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // secure

        // Check if email already exists
        $check = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($conn, $check);

        if(mysqli_num_rows($result) > 0){
            echo "Email already registered!";
        } else {
            // Insert user with student role
            $query = "INSERT INTO users (name,email,mobile,password,role) 
                      VALUES ('$name','$email','$mobile','$password','student')";

            if(mysqli_query($conn, $query)){
                // Get the inserted user ID
                $user_id = mysqli_insert_id($conn);
                
                // Create empty student profile
                $profile_query = "INSERT INTO student_profile (user_id, full_name, email) 
                                VALUES ($user_id, '$name', '$email')";
                
                if(mysqli_query($conn, $profile_query)){
                    echo "Registration successful!";
                    header("Location: login.php"); exit();
                } else {
                    echo "Registration successful but profile creation failed: ".mysqli_error($conn);
                    header("Location: login.php"); exit();
                }
            } else {
                echo "Error: ".mysqli_error($conn);
            }
        }
    }
    ?>

    <main>
        <h2>Create Your Account</h2>
        <form method="post" action="register.php">
          <input type="text" name="name" placeholder="Full Name" required><br><br>
          <input type="email" name="email" placeholder="Email" required><br><br>
          <input type="text" name="mobile" placeholder="Mobile Number" required><br><br>
          <input type="password" name="password" placeholder="Password" required><br><br>
          <button type="submit" name="register">Register</button>
        </form>
    </main>

    <!-- Footer -->
    <footer id="footer">
        <div>
            <h4>ScholarMatch</h4>
            <p>&copy; <?php echo date('Y'); ?> ScholarMatch. All rights reserved.</p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="index.php#how-it-works">How It Works</a></li>
                <li><a href="index.php#features">Features</a></li>
                <li><a href="login.php">Login</a></li>
            </ul>
        </div>
        <div>
            <h4>Contact</h4>
            <p>Email: info@scholarmatch.com</p>
            <p>Phone: (555) 123-4567</p>
        </div>
        <div>
            <h4>Follow Us</h4>
            <p>Facebook | Twitter | LinkedIn | Instagram</p>
        </div>
    </footer>

</body>
</html>
