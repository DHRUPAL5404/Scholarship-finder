<?php
session_start();
include "db.php";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn,$query);

    if(!$result){
        die("SQL Error: " . mysqli_error($conn));
    }

    if(mysqli_num_rows($result)==1){
        $row = mysqli_fetch_assoc($result);

        echo "User Found<br>";
        $_SESSION['student_id'] = $row['user_id'];
        $_SESSION['student_name'] = $row['name'];
        echo "Session set: "; print_r($_SESSION); echo "<br>";

        header("Location: dashboard.php");
        
echo "<p>Welcome, ".$_SESSION['student_name']." 🎓</p>";
        exit();
    } else {
        echo "Invalid email or password";
    }
} else {
    echo "Form not submitted";
}
?>
<form method="post" action="login.php">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="login">Login</button>
</form>