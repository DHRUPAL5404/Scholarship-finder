# ScholarMatch - Intelligent Scholarship Finder

ScholarMatch is a web-based platform designed to simplify the scholarship application process for students. It features a dynamic eligibility engine that matches students with scholarships based on their specific profile criteria (marks, income, category, gender, etc.).

## 🚀 Key Features

- **Smart Eligibility Engine**: Automatically calculates a match percentage for every scholarship based on complex rules.
- **Role-Based Access**: Separate dashboards for Students and Administrators.
- **Dynamic Scholarship Management**: Admins can add, edit, and define granular eligibility rules for scholarships.
- **Student Profiles**: Comprehensive profile management including academic details, financial status, and category information.
- **Expiring Alerts**: Visual indicators and alerts for scholarships with approaching deadlines.

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB
- **Frontend**: Vanilla CSS, JavaScript, HTML5
- **Icons/UI**: Modern CSS Gradients, Box Shadows, and Micro-animations.

## 📋 Installation

1. Clone the repository to your local server (e.g., XAMPP `htdocs` folder).
2. Start Apache and MySQL in your XAMPP Control Panel.
3. Open a browser and navigate to `http://localhost/project/scholarmatch/setup_database.php` to initialize the database.
4. The system will create the necessary tables and seed initial data.

## 👤 User Credentials (for Testing)

### Student Account
- **Email**: `student_new@example.com`
- **Password**: `Student@123`
- *Profile configured for 5 specific eligible scholarships.*

### Admin Account
- **Email**: `admin_new@example.com`
- **Password**: `Admin@123`

## 📂 Project Structure

- `student_dashboard.php`: Main portal for students to find and filter scholarships.
- `admin_dashboard.php`: Management interface for scholarship administrators.
- `profile.php`: Student profile configuration page.
- `eligible_students.php`: Admin tool to see which students match a specific scholarship.
- `assets/`: Contains CSS and JS validation scripts.
- `includes/`: Common components like navbar, footer, and database connection.

---
Built with ❤️ for Student Success.
