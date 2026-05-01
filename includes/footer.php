<?php
// Start session if not already started
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!-- Footer -->
<footer id="footer">
    <div>
        <a href="index.php" class="footer-logo-link">
            <?php include __DIR__ . "/brand.php"; ?>
        </a>
        <p>&copy; <?php echo date('Y'); ?> Scholar Match. All rights reserved.</p>
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
