<?php
// Start session if not already started
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Navbar -->
<nav>
    <div class="nav-container">
        <a href="index.php" class="nav-logo-link">
            <?php include __DIR__ . "/brand.php"; ?>
        </a>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="index.php#how-it-works">How It Works</a></li>
            <li><a href="index.php#features">Features</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                <?php else: ?>
                    <li><a href="student_dashboard.php">Dashboard</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
