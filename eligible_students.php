<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";

$sch = mysqli_query($conn,"SELECT * FROM scholarships");

if(isset($_POST['check'])){
    $sid=$_POST['scholarship'];

    $rules = mysqli_query($conn,"SELECT * FROM eligibility_rules WHERE scholarship_id=$sid");
    $conditions=[];

    while($r=mysqli_fetch_assoc($rules)){
        $conditions[]="sp.{$r['field_name']} {$r['operator']} '{$r['value']}'";
    }

    // use full_name from profile when available, otherwise fallback to users table
    $sql="SELECT COALESCE(NULLIF(sp.full_name, ''), u.name) AS student_name, sp.* 
          FROM student_profile sp 
          LEFT JOIN users u ON u.user_id=sp.user_id";

    if($conditions){
        $sql.=" WHERE ".implode(" AND ",$conditions);
    }

    $result=mysqli_query($conn,$sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligible Students - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/navbar-footer.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Navbar -->
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#how-it-works">How It Works</a></li>
            <li><a href="index.php#features">Features</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <h2>Eligible Students</h2>

        <form method="post">
        <select name="scholarship">
        <?php while($s=mysqli_fetch_assoc($sch)){ ?>
        <option value="<?= $s['scholarship_id'] ?>"><?= $s['title'] ?></option>
        <?php } ?>
        </select>
        <button name="check">Check</button>
        </form>

        <?php if(isset($result)){ 
        while($st=mysqli_fetch_assoc($result)){
        echo "<p>{$st['student_name']} ({$st['marks']}%)</p>";
        }} ?>
    </div>

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
                <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                <li><a href="manage_scholarships.php">Manage Scholarships</a></li>
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