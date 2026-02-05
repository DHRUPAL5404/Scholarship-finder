<<<<<<< HEAD
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

    // Check if email already exists
    $check = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $check);

    if(mysqli_num_rows($result) > 0){
        echo "Email already registered!";
    } else {
        // Insert user
        $query = "INSERT INTO users (name,email,mobile,password) 
                  VALUES ('$name','$email','$mobile','$password')";

        if(mysqli_query($conn, $query)){
            echo "Registration successful!";
           
            header("Location: login.php"); exit();
        } else {
            echo "Error: ".mysqli_error($conn);
        }
    }
}
?>

<form method="post" action="register.php">
  <input type="text" name="name" placeholder="Full Name" required><br><br>
  <input type="email" name="email" placeholder="Email" required><br><br>
  <input type="text" name="mobile" placeholder="Mobile Number" required><br><br>
  <input type="password" name="password" placeholder="Password" required><br><br>
  <button type="submit" name="register">Register</button>
=======
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

    // Check if email already exists
    $check = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $check);

    if(mysqli_num_rows($result) > 0){
        echo "Email already registered!";
    } else {
        // Insert user
        $query = "INSERT INTO users (name,email,mobile,password) 
                  VALUES ('$name','$email','$mobile','$password')";

        if(mysqli_query($conn, $query)){
            echo "Registration successful!";
           
            header("Location: login.php"); exit();
        } else {
            echo "Error: ".mysqli_error($conn);
        }
    }
}
?>

<form method="post" action="register.php">
  <input type="text" name="name" placeholder="Full Name" required><br><br>
  <input type="email" name="email" placeholder="Email" required><br><br>
  <input type="text" name="mobile" placeholder="Mobile Number" required><br><br>
  <input type="password" name="password" placeholder="Password" required><br><br>
  <button type="submit" name="register">Register</button>
>>>>>>> dhruti
</form>