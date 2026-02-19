<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit();
}
include "db.php";

// fetch scholarships for dropdown
$sch = mysqli_query($conn,"SELECT * FROM scholarships");

// if a scholarship is selected via GET, load its existing rules to display
$existing_rules = [];
$selected_sch_id = isset($_GET['scholarship']) ? intval($_GET['scholarship']) : 0;
if($selected_sch_id) {
    $res = mysqli_query($conn, "SELECT * FROM eligibility_rules WHERE scholarship_id=$selected_sch_id");
    while($r = mysqli_fetch_assoc($res)){
        $existing_rules[] = $r;
    }
}

if(isset($_POST['add'])){
    $scholarship_id = mysqli_real_escape_string($conn, $_POST['scholarship']);
    $field_name = mysqli_real_escape_string($conn, $_POST['field']);
    $operator = mysqli_real_escape_string($conn, $_POST['operator']);
    $value = mysqli_real_escape_string($conn, $_POST['value']);
    
    // Separate handling for education_level and courses
    if($field_name === 'education_level' && isset($_POST['is_course']) && $_POST['is_course'] == '1'){
        $field_name = 'courses'; // Save courses with specific field name
    }

    // fetch scholarship title for convenience
    $title_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT title FROM scholarships WHERE scholarship_id=$scholarship_id"));
    $scholarship_title = $title_row ? mysqli_real_escape_string($conn, $title_row['title']) : '';
    
    mysqli_query($conn,"INSERT INTO eligibility_rules
    (scholarship_id,scholarship_title,field_name,operator,value)
    VALUES
    ('$scholarship_id','$scholarship_title','$field_name','$operator','$value')");
    echo "✅ Rule Added Successfully!";
    // reload page so that existing rules list updates
    header("Location: add_eligibility_rule.php?scholarship=$scholarship_id");
    exit();
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Eligibility Rule - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/navbar-footer.css">
<script>
function showOtherInput(selectElement, fieldId) {
    const otherInput = document.getElementById(fieldId);
    if(selectElement.value === 'Other' || selectElement.value === 'Others') {
        otherInput.style.display = 'block';
    } else {
        otherInput.style.display = 'none';
        otherInput.querySelector('input').value = '';
    }
}
</script>
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
        <h2>Add Eligibility Rule</h2>

        <label><strong>Select Scholarship:</strong></label>
        <form method="post" style="display:inline;">
        <select name="scholarship" onchange="document.location='?scholarship='+this.value;" required>
        <option value="">-- Select Scholarship --</option>
        <?php // re-use results already fetched earlier
        mysqli_data_seek($sch, 0);
        while($s=mysqli_fetch_assoc($sch)){ ?>
        <option value="<?= $s['scholarship_id'] ?>" <?= ($selected_sch_id && $selected_sch_id == $s['scholarship_id'])?'selected':'' ?>><?= $s['title'] ?></option>
        <?php } ?>
        </select>
        </form>
        <hr style="margin: 15px 0;">

        <strong>Gender</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="gender">
        <select name="value" required><option value="">-- Select Gender --</option><option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option><option value="All">All</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Age</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="age">
        <select name="operator" required><option value=">=">&ge;</option><option value="<=">&le;</option><option value="=">=</option></select>
        <input type="number" name="value" placeholder="e.g., 18" required>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Education Level</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="education_level">
        <select name="value" required onchange="showOtherInput(this, 'edu_other')"><option value="">-- Select --</option><option value="Below 10th">Below 10th</option><option value="Below 10th - Primary School (Std 1–8)">Below 10th - Primary</option><option value="Below 10th - Secondary School – Appearing (Std 9–10)">Below 10th - Secondary</option><option value="10th Pass(SSC)">10th Pass</option><option value="Undergraduate">Undergraduate</option><option value="Postgraduate">Postgraduate</option><option value="PhD">PhD</option><option value="Other">Others</option></select>
        <div id="edu_other" style="display:none;"><input type="text" name="value" placeholder="Specify" required></div>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>10th Pass - Stream & Diploma Courses</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="education_level">
        <input type="hidden" name="is_course" value="1">
        <input type="hidden" name="operator" value="=">
        <select name="value" required><option value="">-- Select --</option><option value="10th Pass(SSC) - Science">Science</option><option value="10th Pass(SSC) - Commerce">Commerce</option><option value="10th Pass(SSC) - Arts">Arts</option><option value="10th Pass(SSC) - Diploma">Diploma</option><option value="10th Pass(SSC) - Diploma - Diploma in Engineering (Polytechnic)">Diploma in Engineering</option><option value="10th Pass(SSC) - Diploma - Diploma in Computer Engineering / IT">Diploma in IT</option><option value="10th Pass(SSC) - Diploma - Diploma in Mechanical Engineering">Diploma in Mechanical</option><option value="10th Pass(SSC) - Diploma - Diploma in Electrical Engineering">Diploma in Electrical</option><option value="10th Pass(SSC) - Diploma - Diploma in Civil Engineering">Diploma in Civil</option><option value="10th Pass(SSC) - Diploma - Diploma in Electronics / EC">Diploma in Electronics</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Undergraduate Courses</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="education_level">
        <input type="hidden" name="is_course" value="1">
        <input type="hidden" name="operator" value="=">
        <select name="value" required><option value="">-- Select --</option><option value="Undergraduate - Science - Group A">B.Sc (PCM)</option><option value="Undergraduate - Science - Group B">B.Sc (PCB)</option><option value="Undergraduate - Commerce">B.Com</option><option value="Undergraduate - Arts">B.A</option><option value="Undergraduate - Science">B.Tech/Engineering</option><option value="Undergraduate - Medical">Medical</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Postgraduate Courses</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="education_level">
        <input type="hidden" name="is_course" value="1">
        <input type="hidden" name="operator" value="=">
        <select name="value" required><option value="">-- Select --</option><option value="Postgraduate - M.E. / M.Tech (Engineering)">M.Tech</option><option value="Postgraduate - M.Sc (Science)">M.Sc</option><option value="Postgraduate - M.Com (Commerce)">M.Com</option><option value="Postgraduate - M.A (Arts / Humanities)">M.A</option><option value="Postgraduate - MCA (Computer Applications)">MCA</option><option value="Postgraduate - MBA (Master of Business Administration)">MBA</option><option value="Postgraduate - M.Voc (Vocational Master's)">M.Voc</option><option value="Postgraduate - M.Ed (Education)">M.Ed</option><option value="Postgraduate - LLM (Law)">LLM</option><option value="Postgraduate - M.Pharm (Pharmacy)">M.Pharm</option><option value="Postgraduate - M.Sc Nursing">M.Sc Nursing</option><option value="Postgraduate - MS (Medical / Clinical)">MS Medical</option><option value="Postgraduate - MPH (Public Health)">MPH</option><option value="Postgraduate - MHA (Hospital Administration)">MHA</option><option value="Postgraduate - MSW (Social Work)">MSW</option><option value="Postgraduate - M.Des (Design)">M.Des</option><option value="Postgraduate - M.Phil">M.Phil</option><option value="Postgraduate - M.Tech (AI / ML / Data Science / Cyber Security)">M.Tech (AI/ML)</option><option value="Postgraduate - M.Sc (Data Science / AI / Analytics)">M.Sc (Data Science)</option><option value="Postgraduate - PG Diploma">PG Diploma</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>PhD Courses</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="education_level">
        <input type="hidden" name="is_course" value="1">
        <input type="hidden" name="operator" value="=">
        <select name="value" required><option value="">-- Select --</option><option value="PhD - Computer Science / IT">PhD (CS/IT)</option><option value="PhD - Engineering">PhD (Engineering)</option><option value="PhD - Mathematics">PhD (Math)</option><option value="PhD - Physics">PhD (Physics)</option><option value="PhD - Chemistry">PhD (Chemistry)</option><option value="PhD - Biology / Life Sciences">PhD (Biology)</option><option value="PhD - Environmental Science">PhD (Environment)</option><option value="PhD - Statistics">PhD (Statistics)</option><option value="PhD - English">PhD (English)</option><option value="PhD - Economics">PhD (Economics)</option><option value="PhD - History">PhD (History)</option><option value="PhD - Political Science">PhD (Political)</option><option value="PhD - Sociology">PhD (Sociology)</option><option value="PhD - Psychology">PhD (Psychology)</option><option value="PhD - Philosophy">PhD (Philosophy)</option><option value="PhD - Education">PhD (Education)</option><option value="PhD - Commerce">PhD (Commerce)</option><option value="PhD - Management / Business Administration">PhD (Management)</option><option value="PhD - Finance">PhD (Finance)</option><option value="PhD - Marketing">PhD (Marketing)</option><option value="PhD - Human Resource Management">PhD (HR)</option><option value="PhD - Law">PhD (Law)</option><option value="PhD - Medical Sciences">PhD (Medical)</option><option value="PhD - Pharmacy">PhD (Pharmacy)</option><option value="PhD - Nursing">PhD (Nursing)</option><option value="PhD - Public Health">PhD (PH)</option><option value="PhD - Agriculture">PhD (Agriculture)</option><option value="PhD - Veterinary Science">PhD (Veterinary)</option><option value="PhD - Design">PhD (Design)</option><option value="PhD - Data Science / AI / ML">PhD (Data/AI)</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <hr style="margin: 15px 0;">

        <strong>Marks / Percentage / GPA</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <select name="field" required><option value="">-- Select --</option><option value="marks">Marks</option><option value="percentage">Percentage</option><option value="gpa">GPA</option></select>
        <select name="operator" required><option value=">=">&ge;</option><option value="<=">&le;</option><option value="=">=</option></select>
        <input type="number" name="value" placeholder="e.g., 75" step="0.01" required>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Institution Type</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="institution_type">
        <select name="value" required><option value="">-- Select --</option><option value="Government">Government</option><option value="Private">Private</option><option value="Autonomous">Autonomous</option><option value="University">University</option><option value="Polytechnic">Polytechnic</option><option value="College">College</option><option value="School">School</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>State</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="state">
        <input type="hidden" name="operator" value="=">
        <select name="value" required><option value="">-- Select --</option><option value="Andhra Pradesh">Andhra Pradesh</option><option value="Arunachal Pradesh">Arunachal Pradesh</option><option value="Assam">Assam</option><option value="Bihar">Bihar</option><option value="Chhattisgarh">Chhattisgarh</option><option value="Goa">Goa</option><option value="Gujarat">Gujarat</option><option value="Haryana">Haryana</option><option value="Himachal Pradesh">Himachal Pradesh</option><option value="Jharkhand">Jharkhand</option><option value="Karnataka">Karnataka</option><option value="Kerala">Kerala</option><option value="Madhya Pradesh">Madhya Pradesh</option><option value="Maharashtra">Maharashtra</option><option value="Manipur">Manipur</option><option value="Meghalaya">Meghalaya</option><option value="Mizoram">Mizoram</option><option value="Nagaland">Nagaland</option><option value="Odisha">Odisha</option><option value="Punjab">Punjab</option><option value="Rajasthan">Rajasthan</option><option value="Sikkim">Sikkim</option><option value="Tamil Nadu">Tamil Nadu</option><option value="Telangana">Telangana</option><option value="Tripura">Tripura</option><option value="Uttar Pradesh">Uttar Pradesh</option><option value="Uttarakhand">Uttarakhand</option><option value="West Bengal">West Bengal</option><option value="Ladakh">Ladakh</option><option value="Jammu and Kashmir">Jammu and Kashmir</option><option value="Puducherry">Puducherry</option><option value="Lakshadweep">Lakshadweep</option><option value="Daman and Diu">Daman and Diu</option><option value="Dadra and Nagar Haveli">Dadra and Nagar Haveli</option><option value="Chandigarh">Chandigarh</option><option value="Delhi">Delhi</option><option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Family Income</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="family_income">
        <select name="operator" required><option value="<=">&le;</option><option value=">=">&ge;</option><option value="=">=</option></select>
        <input type="number" name="value" placeholder="e.g., 500000" required>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Category</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="category">
        <select name="value" required><option value="">-- Select --</option><option value="General (GEN / UR)">General (GEN / UR)</option><option value="Other Backward Class (OBC)">OBC</option><option value="Scheduled Caste (SC)">SC</option><option value="Scheduled Tribe (ST)">ST</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Minority Status</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="minority_status">
        <select name="value" required><option value="">-- Select --</option><option value="Yes">Yes</option><option value="No">No</option></select>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Disability Percent</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="disability_percent">
        <select name="operator" required><option value=">=">&ge;</option><option value="<=">&le;</option><option value=">">&gt;</option></select>
        <input type="number" name="value" placeholder="e.g., 40" min="0" max="100" required>
        <button name="add" type="submit">Add</button>
        </form>

        <strong>Scholarships Already Applied</strong>
        <form method="post" style="margin: 5px 0;">
        <input type="hidden" name="scholarship" value="<?= $_GET['scholarship'] ?? '' ?>" required>
        <input type="hidden" name="field" value="scholarships_applied">
        <select name="operator" required><option value="<=">&le;</option><option value=">=">&ge;</option></select>
        <input type="number" name="value" placeholder="e.g., 5" min="0" required>
        <button name="add" type="submit">Add</button>
        </form>
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
