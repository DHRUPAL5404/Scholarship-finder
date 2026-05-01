<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --admin-primary: #4f46e5;
            --admin-secondary: #818cf8;
            --admin-bg: #f3f4f6;
        }

        .admin-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3rem 2rem;
            border-radius: 16px;
            margin-bottom: 2.5rem;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .admin-hero::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
            filter: blur(50px);
        }

        .admin-hero h2 {
            color: white;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .admin-hero p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .admin-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }

        .admin-card:hover {
            transform: translateY(-5px);
            border-color: var(--admin-primary);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.1);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            background: rgba(79, 70, 229, 0.1);
            color: var(--admin-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .admin-card:hover .card-icon {
            background: var(--admin-primary);
            color: white;
            transform: rotate(-5deg) scale(1.1);
        }

        .card-info h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #1e293b;
            font-weight: 700;
        }

        .card-info p {
            margin: 0.5rem 0 0;
            color: #64748b;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .btn-view {
            margin-top: 1rem;
            color: var(--admin-primary);
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view::after {
            content: '→';
            transition: transform 0.3s ease;
        }

        .admin-card:hover .btn-view::after {
            transform: translateX(5px);
        }

        @media (max-width: 640px) {
            .admin-hero { padding: 2rem 1.5rem; }
            .admin-hero h2 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <?php include "includes/navbar.php"; ?>

    <div class="container mt-4">
        
        <div class="admin-hero">
            <h2>Admin Dashboard</h2>
            <p>Welcome back! Manage scholarships, rules, and student applications from here.</p>
        </div>

        <?php if($flash_success): ?>
            <div class="alert success"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        <?php if($flash_error): ?>
            <div class="alert danger"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        
        <div class="admin-grid">
            <a href="add_scholarship.php" class="admin-card">
                <div class="card-icon">🎓</div>
                <div class="card-info">
                    <h3>Add Scholarship</h3>
                    <p>Create new scholarship opportunities for students to apply.</p>
                </div>
                <div class="btn-view">Configure Now</div>
            </a>

            <a href="manage_scholarships.php" class="admin-card">
                <div class="card-icon">📝</div>
                <div class="card-info">
                    <h3>Manage Scholarships</h3>
                    <p>View, edit, or remove existing scholarship listings.</p>
                </div>
                <div class="btn-view">Manage List</div>
            </a>

            <a href="add_eligibility_rule.php" class="admin-card">
                <div class="card-icon">🎯</div>
                <div class="card-info">
                    <h3>Eligibility Rules</h3>
                    <p>Define automated criteria for scholarship matching.</p>
                </div>
                <div class="btn-view">Set Criteria</div>
            </a>

            <a href="eligible_students.php" class="admin-card">
                <div class="card-icon">👥</div>
                <div class="card-info">
                    <h3>Eligible Students</h3>
                    <p>Identify all students matching scholarship criteria for outreach.</p>
                </div>
                <div class="btn-view">View Matches</div>
            </a>
        </div>
    </div>

    <?php include "includes/footer.php"; ?>

</body>
</html>
