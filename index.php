<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScholarMatch - Find Your Perfect Scholarship</title>
    <link rel="stylesheet" href="assets/css/navbar-footer.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Navbar -->
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#features">Features</a></li>
            <?php if(isset($_SESSION['user_id'])): ?>
                <li><a href="student_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Hero Section -->
    <section id="hero">
        <h1>Welcome to ScholarMatch</h1>
        <p>Discover scholarships tailored for you</p>
        <p>Connect with educational opportunities that match your goals and aspirations</p>
        
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php else: ?>
            <p>Welcome back, <?php echo $_SESSION['user_name']; ?></p>
            <?php if($_SESSION['role'] == 'student'): ?>
                <a href="student_dashboard.php">Go to Dashboard</a>
            <?php elseif($_SESSION['role'] == 'admin'): ?>
                <a href="admin_dashboard.php">Go to Admin Panel</a>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <!-- How It Works -->
    <section id="how-it-works">
        <h2>How It Works</h2>
        <div>
            <h3>Step 1: Create Account</h3>
            <p>Sign up and tell us about yourself</p>
        </div>
        <div>
            <h3>Step 2: Complete Profile</h3>
            <p>Add your academic details and interests</p>
        </div>
        <div>
            <h3>Step 3: Discover Scholarships</h3>
            <p>Browse scholarships matched to your profile</p>
        </div>
        <div>
            <h3>Step 4: Apply</h3>
            <p>Submit applications directly through our platform</p>
        </div>
    </section>

    <!-- Who It's For -->
    

    <!-- Features -->
    <section id="features">
        <h2>Our Features</h2>
        <div>
            <h3>Profile-Based Scholarship Matching</h3>
            <p>Get personalized scholarship recommendations based on your unique profile</p>
        </div>
        <div>
            <h3>Eligibility Checker</h3>
            <p>Instantly check if you qualify for scholarships with our rule-based logic</p>
        </div>
        <div>
            <h3>Scholarship Recommendations</h3>
            <p>Receive tailored scholarship suggestions matched to your goals and qualifications</p>
        </div>
       
        <div>
            <h3>Verified Listings</h3>
            <p>All scholarships are verified and legitimate</p>
        </div>
        <div>
            <h3>100% Free</h3>
            <p>No hidden fees or charges</p>
        </div>
    </section>

    <!-- Call to Action -->
    <section id="cta">
        <h2>Ready to Find Your Scholarship?</h2>
        <p>Join thousands of students who have found their perfect match</p>
        <?php if(!isset($_SESSION['user_id'])): ?>
            <a href="register.php">Get Started Today</a>
        <?php endif; ?>
    </section>

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
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#features">Features</a></li>
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