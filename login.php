<<<<<<< HEAD
<?php
session_start();
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

<form method="post" action="login.php">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="login">Login</button>
=======
<?php
session_start();
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

<form method="post" action="login.php">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="login">Login</button>
>>>>>>> dhruti
</form>