<?php
// Start session if not already started
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
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
