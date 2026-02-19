<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ScholarMatch</title>
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

    <!-- Login Form Content -->
    <main>
        <h2>Login to ScholarMatch</h2>
        <form method="post" action="login.php">
            <input type="email" name="email" placeholder="Email" required><br><br>
            <input type="password" name="password" placeholder="Password" required><br><br>
            <button type="submit" name="login">Login</button>
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
            <p>Email: support@scholarmatch.com</p>
            <p>Phone: +1 (555) 123-4567</p>
        </div>
    </footer>

</body>
</html>

<?php
include "db.php";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Fetch user by email
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn,$query);

    if(!$result){
        die("SQL Error: " . mysqli_error($conn));
    }

    if(mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);

        // Verify hashed password
        if(password_verify($password, $row['password'])){
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            // Redirect based on role
            if($row['role'] == 'student'){
                header("Location: student_dashboard.php");
            } else if($row['role'] == 'admin'){
                header("Location: admin_dashboard.php");
            }
            exit();
        } else {
            echo "Invalid email or password";
        }
    } else {
        echo "User not found";
    }
}
?>
