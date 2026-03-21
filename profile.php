<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission
$success_message = '';
$error_message   = '';

if(isset($_POST['save_profile'])){
    $stmt = $conn->prepare("SELECT profile_id FROM student_profile WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    $full_name        = $_POST['full_name'];
    $email            = $_POST['email'];
    $education_level  = $_POST['education_level'];
    $marks            = intval($_POST['marks']);
    $family_income    = intval($_POST['family_income']);
    $category         = $_POST['category'];
    $gender           = $_POST['gender'];
    $state_id         = intval($_POST['state_id']);
    $district_id      = intval($_POST['district_id']);
    $institution_type = $_POST['institution_type'];
    $age              = intval($_POST['age']);
    $disability_type  = $_POST['disability_type'] ?? 'None';
    $disability_percent = intval($_POST['disability_percent'] ?? 0);
    $minority_status  = $_POST['minority_status'];
    $parent_name      = $_POST['parent_name'] ?? '';
    $parent_occupation= $_POST['parent_occupation'] ?? '';
    $parent_contact   = $_POST['parent_contact'] ?? '';
    $course           = $_POST['course'] ?? '';
    $current_year     = $_POST['current_year'] ?? '';

    // Build education_final string
    $education_final = $education_level;
    if($education_level === 'Below 10th' && !empty($_POST['below_10th_level'])) {
        $education_final = $_POST['below_10th_level'];
    } elseif($education_level === '10th Pass(SSC)' && !empty($_POST['tenth_stream'])) {
        $tenth_stream = $_POST['tenth_stream'];
        if($tenth_stream === 'Diploma' && !empty($_POST['diploma_course'])) {
            $education_final = '10th Pass - Diploma - ' . $_POST['diploma_course'];
        } else {
            $education_final = '10th Pass - ' . $tenth_stream;
        }
    } elseif($education_level === 'Undergraduate') {
        $ustream = $_POST['undergrad_stream'] ?? '';
        $sgroup  = $_POST['science_group'] ?? '';
        $ucourse = $_POST['undergrad_course'] ?? '';
        if($ustream === 'Science' && $sgroup && $ucourse) {
            $education_final = "Undergraduate - Science - $sgroup - $ucourse";
        } elseif($ustream && $ucourse) {
            $education_final = "Undergraduate - $ustream - $ucourse";
        } elseif($ustream) {
            $education_final = "Undergraduate - $ustream";
        }
    } elseif($education_level === 'Postgraduate' && !empty($_POST['postgrad_course'])) {
        $education_final = $_POST['postgrad_course'];
    } elseif($education_level === 'PhD' && !empty($_POST['phd_course'])) {
        $education_final = $_POST['phd_course'];
    }

    if($exists) {
        $stmt = $conn->prepare("UPDATE student_profile SET
            full_name=?, email=?, education_level=?, marks=?, family_income=?,
            category=?, gender=?, state_id=?, district_id=?, institution_type=?,
            age=?, disability_type=?, disability_percent=?, minority_status=?,
            parent_name=?, parent_occupation=?, parent_contact=?, course=?, current_year=?
            WHERE user_id=?");
        $stmt->bind_param("sssiissiisisssssssssi",
            $full_name, $email, $education_final, $marks, $family_income,
            $category, $gender, $state_id, $district_id, $institution_type,
            $age, $disability_type, $disability_percent, $minority_status,
            $parent_name, $parent_occupation, $parent_contact, $course, $current_year,
            $user_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO student_profile
            (user_id, full_name, email, education_level, marks, family_income,
             category, gender, state_id, district_id, institution_type, age,
             disability_type, disability_percent, minority_status,
             parent_name, parent_occupation, parent_contact, course, current_year)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isssiissiisissssssss",
            $user_id, $full_name, $email, $education_final, $marks, $family_income,
            $category, $gender, $state_id, $district_id, $institution_type, $age,
            $disability_type, $disability_percent, $minority_status,
            $parent_name, $parent_occupation, $parent_contact, $course, $current_year);
    }

    if($stmt->execute()){
        $_SESSION['profile_success'] = $exists ? "Profile updated successfully!" : "Profile saved successfully!";
        header("Location: student_dashboard.php");
        exit();
    } else {
        $error_message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch student profile
// handle form submission for profile update/insert
if(isset($_POST['save_profile'])){
    // sanitize inputs
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $education_level = mysqli_real_escape_string($conn, $_POST['education_level']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $current_year = mysqli_real_escape_string($conn, $_POST['current_year']);
    $marks = intval($_POST['marks']);
    $family_income = intval($_POST['family_income']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $state_id = intval($_POST['state_id']);
    $district_id = intval($_POST['district_id']);
    $institution_type = mysqli_real_escape_string($conn, $_POST['institution_type']);
    $age = intval($_POST['age']);
    $disability_type = mysqli_real_escape_string($conn, $_POST['disability_type']);
    $disability_percent = intval($_POST['disability_percent']);
    $minority_status = mysqli_real_escape_string($conn, $_POST['minority_status']);
    $parent_name = mysqli_real_escape_string($conn, $_POST['parent_name']);
    $parent_occupation = mysqli_real_escape_string($conn, $_POST['parent_occupation']);
    $parent_contact = mysqli_real_escape_string($conn, $_POST['parent_contact']);
    
    $success = true;
    $exists = mysqli_query($conn, "SELECT profile_id FROM student_profile WHERE user_id=$user_id");
    if(mysqli_num_rows($exists) > 0){
        // update
        if(!mysqli_query($conn, "UPDATE student_profile SET 
            full_name='$full_name',
            education_level='$education_level',
            course='$course',
            current_year='$current_year',
            marks=$marks,
            family_income=$family_income,
            category='$category',
            gender='$gender',
            state_id=$state_id,
            district_id=$district_id,
            institution_type='$institution_type',
            age=$age,
            disability_type='$disability_type',
            disability_percent=$disability_percent,
            minority_status='$minority_status',
            parent_name='$parent_name',
            parent_occupation='$parent_occupation',
            parent_contact='$parent_contact'
            WHERE user_id=$user_id")){
            $success = false;
            $message = "❌ Update failed: " . mysqli_error($conn);
        }
        // also update email in users table
        if($success && !mysqli_query($conn, "UPDATE users SET email='$email' WHERE user_id=$user_id")){
            $success = false;
            $message = "❌ Email update failed: " . mysqli_error($conn);
        }
    } else {
        // insert new
        if(!mysqli_query($conn, "INSERT INTO student_profile 
            (user_id, full_name, education_level, course, current_year, marks, family_income, category, gender, state_id, district_id, institution_type, age, disability_type, disability_percent, minority_status, parent_name, parent_occupation, parent_contact)
            VALUES 
            ($user_id,'$full_name','$education_level','$course','$current_year',$marks,$family_income,'$category','$gender',$state_id,$district_id,'$institution_type',$age,'$disability_type',$disability_percent,'$minority_status','$parent_name','$parent_occupation','$parent_contact')")){
            $success = false;
            $message = "❌ Insert failed: " . mysqli_error($conn);
        }
        // store email in users table as well
        if($success && !mysqli_query($conn, "UPDATE users SET email='$email' WHERE user_id=$user_id")){
            $success = false;
            $message = "❌ Email update failed: " . mysqli_error($conn);
        }
    }
    if($success){
        $message = "✅ Profile saved successfully!";
    }
}

// when retrieving profile include email from users for convenience
$profile = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT sp.*, u.email FROM student_profile sp 
        JOIN users u ON sp.user_id=u.user_id 
        WHERE sp.user_id=$user_id")
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Create' ?> Profile - ScholarMatch</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<nav>
    <ul>
        <li><a href="index.php">Home</a></li>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="student_dashboard.php">Dashboard</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

    <div class="container profile-page">
        <h2>Update Your Profile</h2>
        
        <?php if($success_message): ?>
            <div style="color: green; background-color: #d4edda; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div style="color: red; background-color: #f8d7da; padding: 10px; margin-bottom: 20px; border-radius: 5px;">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <script>
function toggleBelow10thDropdown() {
    var educationLevel = document.getElementById('education_level').value;
    var below10thDiv = document.getElementById('below_10th_dropdown');
    var tenthStreamDiv = document.getElementById('tenth_stream_dropdown');
    var undergradStreamDiv = document.getElementById('undergrad_stream_dropdown');
    var postgradCourseDiv = document.getElementById('postgrad_course_dropdown');
    var phdCourseDiv = document.getElementById('phd_course_dropdown');
    var courseYearDiv = document.getElementById('course_year_fields');
    var below10thSelect = document.querySelector('select[name="below_10th_level"]');
    var tenthStreamSelect = document.getElementById('tenth_stream');
    var undergradStreamSelect = document.getElementById('undergrad_stream');
    var scienceGroupSelect = document.getElementById('science_group');
    var undergradCourseSelect = document.getElementById('undergrad_course');
    var postgradCourseSelect = document.getElementById('postgrad_course');
    var phdCourseSelect = document.getElementById('phd_course');
    var courseInput = document.getElementById('course');
    var currentYearInput = document.getElementById('current_year');

    below10thSelect.removeAttribute('required');
    tenthStreamSelect.removeAttribute('required');
    undergradStreamSelect.removeAttribute('required');
    scienceGroupSelect.removeAttribute('required');
    undergradCourseSelect.removeAttribute('required');
    postgradCourseSelect.removeAttribute('required');
    phdCourseSelect.removeAttribute('required');
    courseInput.removeAttribute('required');
    currentYearInput.removeAttribute('required');
    
    if(educationLevel === '') {
        below10thDiv.style.display = 'none';
        tenthStreamDiv.style.display = 'none';
        undergradStreamDiv.style.display = 'none';
        postgradCourseDiv.style.display = 'none';
        phdCourseDiv.style.display = 'none';
        courseYearDiv.style.display = 'none';
    } else if(educationLevel === 'Below 10th') {
        below10thDiv.style.display = 'block';
        tenthStreamDiv.style.display = 'none';
        undergradStreamDiv.style.display = 'none';
        postgradCourseDiv.style.display = 'none';
        phdCourseDiv.style.display = 'none';
        courseYearDiv.style.display = 'none';
        below10thSelect.setAttribute('required', 'required');
    } else if(educationLevel === '10th Pass(SSC)') {
        below10thDiv.style.display = 'none';
        tenthStreamDiv.style.display = 'block';
        undergradStreamDiv.style.display = 'none';
        postgradCourseDiv.style.display = 'none';
        phdCourseDiv.style.display = 'none';
        courseYearDiv.style.display = 'none';
        tenthStreamSelect.setAttribute('required', 'required');
    } else if(educationLevel === 'Undergraduate') {
        below10thDiv.style.display = 'none';
        tenthStreamDiv.style.display = 'none';
        undergradStreamDiv.style.display = 'block';
        postgradCourseDiv.style.display = 'none';
        phdCourseDiv.style.display = 'none';
        courseYearDiv.style.display = 'none';
        undergradStreamSelect.setAttribute('required', 'required');
    } else if(educationLevel === 'Postgraduate') {
        below10thDiv.style.display = 'none';
        tenthStreamDiv.style.display = 'none';
        undergradStreamDiv.style.display = 'none';
        postgradCourseDiv.style.display = 'block';
        phdCourseDiv.style.display = 'none';
        courseYearDiv.style.display = 'none';
        postgradCourseSelect.setAttribute('required', 'required');
    } else if(educationLevel === 'PhD') {
        below10thDiv.style.display = 'none';
        tenthStreamDiv.style.display = 'none';
        undergradStreamDiv.style.display = 'none';
        postgradCourseDiv.style.display = 'none';
        phdCourseDiv.style.display = 'block';
        courseYearDiv.style.display = 'none';
        phdCourseSelect.setAttribute('required', 'required');
    } else {
        below10thDiv.style.display = 'none';
        tenthStreamDiv.style.display = 'none';
        undergradStreamDiv.style.display = 'none';
        postgradCourseDiv.style.display = 'none';
        phdCourseDiv.style.display = 'none';
        courseYearDiv.style.display = 'block';
        courseInput.setAttribute('required', 'required');
        currentYearInput.setAttribute('required', 'required');
    }

    toggleDiplomaDropdown();
    toggleScienceGroupDropdown();
    toggleTwelfthCourseDropdown();
}

function toggleDiplomaDropdown() {
    var tenthStream = document.getElementById('tenth_stream').value;
    var diplomaDiv = document.getElementById('diploma_course_dropdown');
    
    if(tenthStream === 'Diploma') {
        diplomaDiv.style.display = 'block';
        document.getElementById('diploma_course').setAttribute('required', 'required');
    } else {
        diplomaDiv.style.display = 'none';
        document.getElementById('diploma_course').removeAttribute('required');
    }
}

function toggleScienceGroupDropdown() {
    var undergradStream = document.getElementById('undergrad_stream').value;
    var scienceGroupDiv = document.getElementById('science_group_dropdown');
    var undergradCourseDiv = document.getElementById('undergrad_course_dropdown');
    
    if(undergradStream === 'Science') {
        scienceGroupDiv.style.display = 'block';
        document.getElementById('science_group').setAttribute('required', 'required');
        undergradCourseDiv.style.display = 'none';
        document.getElementById('undergrad_course').removeAttribute('required');
    } else if(undergradStream === 'Commerce' || undergradStream === 'Arts') {
        scienceGroupDiv.style.display = 'none';
        document.getElementById('science_group').removeAttribute('required');
        undergradCourseDiv.style.display = 'block';
        document.getElementById('undergrad_course').setAttribute('required', 'required');
        updateCoursesForStream(undergradStream);
    } else {
        scienceGroupDiv.style.display = 'none';
        undergradCourseDiv.style.display = 'none';
        document.getElementById('science_group').removeAttribute('required');
        document.getElementById('undergrad_course').removeAttribute('required');
    }
}

function toggleTwelfthCourseDropdown() {
    var undergradStream = document.getElementById('undergrad_stream').value;
    var scienceGroup = document.getElementById('science_group').value;
    var undergradCourseDiv = document.getElementById('undergrad_course_dropdown');
    var undergradCourseSelect = document.getElementById('undergrad_course');
    
    if(undergradStream === 'Science' && scienceGroup) {
        undergradCourseDiv.style.display = 'block';
        updateCoursesForScienceGroup(scienceGroup);
        undergradCourseSelect.setAttribute('required', 'required');
    } else if(undergradStream && undergradStream !== 'Science') {
        undergradCourseDiv.style.display = 'block';
        updateCoursesForStream(undergradStream);
        undergradCourseSelect.setAttribute('required', 'required');
    } else {
        undergradCourseDiv.style.display = 'none';
        undergradCourseSelect.removeAttribute('required');
    }
}

function updateCoursesForScienceGroup(group) {
    var courseSelect = document.getElementById('undergrad_course');
    courseSelect.innerHTML = '<option value="">Select Course</option>';
    
    var courses = {};
    courses['Group A'] = [
        'B.E. / B.Tech (Engineering)',
        'BCA (Computer Applications)',
        'B.Sc (Science)'
    ];
    courses['Group B'] = [
        'MBBS / BDS / BAMS / BHMS (Medical)',
        'B.Sc Nursing',
        'B.Pharm (Pharmacy)',
        'B.Sc (Science)'
    ];
    
    if(courses[group]) {
        courses[group].forEach(function(course) {
            var option = document.createElement('option');
            option.value = course;
            option.textContent = course;
            courseSelect.appendChild(option);
        });
    }
}

function updateCoursesForStream(stream) {
    var courseSelect = document.getElementById('undergrad_course');
    courseSelect.innerHTML = '<option value="">Select Course</option>';
    
    var courses = {};
    courses['Commerce'] = [
        'B.Com (Commerce)',
        'BBA (Business Administration)',
        'Hotel Management / Tourism',
        'B.Ed (Integrated)'
    ];
    courses['Arts'] = [
        'B.A (Arts / Humanities)',
        'Fashion Designing / Fine Arts',
        'LLB (Law â€“ 5 Year)',
        'Hotel Management / Tourism',
        'B.Ed (Integrated)'
    ];
    
    if(courses[stream]) {
        courses[stream].forEach(function(course) {
            var option = document.createElement('option');
            option.value = course;
            option.textContent = course;
            courseSelect.appendChild(option);
        });
    }
}

function toggleOtherField(dropdownId, otherFieldId) {
    var dropdown = document.getElementById(dropdownId);
    var otherField = document.getElementById(otherFieldId);
    
    if(dropdown.value === 'Other') {
        otherField.style.display = 'block';
        otherField.querySelector('input').setAttribute('required', 'required');
    } else {
        otherField.style.display = 'none';
        otherField.querySelector('input').removeAttribute('required');
    }
}

// Cascade dropdowns for State, District
document.addEventListener('DOMContentLoaded', function() {
    const stateSelect = document.getElementById('state');
    const districtSelect = document.getElementById('district');
    
    // Load states on page load
    fetch('get_states.php')
        .then(res => res.text())
        .then(data => {
            stateSelect.innerHTML += data;
        });
    
    // Load districts when state changes
    stateSelect.addEventListener('change', function() {
        if(this.value) {
            fetch('get_districts.php?state_id=' + this.value)
                .then(res => res.text())
                .then(data => {
                    districtSelect.innerHTML = '<option value="">Select District</option>' + data;
                });
        } else {
            districtSelect.innerHTML = '<option value="">Select District</option>';
            districtSelect.value = '';
        }
    });

    toggleBelow10thDropdown();
});
</script>

<form method="post" action="profile.php" class="profile-form">
    Full Name:
    <input type="text" name="full_name" placeholder="Full Name" value="<?= $profile['full_name'] ?? '' ?>" required><br><br>
    
    Email:
    <input type="email" name="email" placeholder="Email" value="<?= $profile['email'] ?? '' ?>" required><br><br>
    
    Education Level:
    <select id="education_level" name="education_level" onchange="toggleBelow10thDropdown()" required>
        <option value="">Select</option>
        <option value="Below 10th" <?= ($profile && strpos($profile['education_level'], 'Below 10th') !== false)?'selected':'' ?>>Below 10th</option>
        <option value="10th Pass(SSC)" <?= ($profile && $profile['education_level']=='10th Pass')?'selected':'' ?>>10th Pass</option>
        <option value="Undergraduate" <?= ($profile && strpos($profile['education_level'], 'Undergraduate') !== false)?'selected':'' ?>>Undergraduate</option>
        <option value="Postgraduate" <?= ($profile && $profile['education_level']=='Postgraduate')?'selected':'' ?>>Postgraduate</option>
        <option value="PhD" <?= ($profile && $profile['education_level']=='PhD')?'selected':'' ?>>PhD</option>
        <option value="Other" <?= ($profile && $profile['education_level']=='Other')?'selected':'' ?>>Other</option>
    </select><br><br>
    
    <!-- Conditional dropdown for Below 10th -->
    <div id="below_10th_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], 'Below 10th') !== false) ? 'block' : 'none' ?>;">
        <label>Select Standard:</label>
        <select name="below_10th_level">
            <option value="">Select Standard</option>
            <option value="Primary School (Std 1â€“8)" <?= ($profile && strpos($profile['education_level'], 'Primary School') !== false)?'selected':'' ?>>Primary School (Std 1â€“8)</option>
            <option value="Secondary School â€“ Appearing (Std 9â€“10)" <?= ($profile && strpos($profile['education_level'], 'Secondary School') !== false)?'selected':'' ?>>Secondary School â€“ Appearing (Std 9â€“10)</option>
        </select><br><br>
    </div>
    
    <!-- Conditional dropdown for 10th Pass Stream -->
    <div id="tenth_stream_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], '10th Pass') !== false) ? 'block' : 'none' ?>;">
        <label>Select Stream:</label>
        <select id="tenth_stream" name="tenth_stream" onchange="toggleDiplomaDropdown()">
            <option value="">Select Stream</option>
            <option value="Science" <?= ($profile && strpos($profile['education_level'], 'Science') !== false)?'selected':'' ?>>Science</option>
            <option value="Commerce" <?= ($profile && strpos($profile['education_level'], 'Commerce') !== false)?'selected':'' ?>>Commerce</option>
            <option value="Arts" <?= ($profile && strpos($profile['education_level'], 'Arts') !== false)?'selected':'' ?>>Arts</option>
            <option value="Diploma" <?= ($profile && strpos($profile['education_level'], '10th Pass - Diploma') !== false)?'selected':'' ?>>Diploma</option>
        </select><br><br>
        
        <!-- Conditional dropdown for Diploma Courses -->
        <div id="diploma_course_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], '10th Pass - Diploma') !== false) ? 'block' : 'none' ?>;">
            <label>Select Diploma Course:</label>
            <select id="diploma_course" name="diploma_course" onchange="toggleOtherField('diploma_course', 'diploma_course_other_field')">
                <option value="">Select Diploma Course</option>
                <option value="Diploma in Engineering (Polytechnic)" <?= ($profile && strpos($profile['education_level'], 'Diploma in Engineering (Polytechnic)') !== false)?'selected':'' ?>>Diploma in Engineering (Polytechnic)</option>
                <option value="Diploma in Computer Engineering / IT" <?= ($profile && strpos($profile['education_level'], 'Diploma in Computer Engineering / IT') !== false)?'selected':'' ?>>Diploma in Computer Engineering / IT</option>
                <option value="Diploma in Mechanical Engineering" <?= ($profile && strpos($profile['education_level'], 'Diploma in Mechanical Engineering') !== false)?'selected':'' ?>>Diploma in Mechanical Engineering</option>
                <option value="Diploma in Electrical Engineering" <?= ($profile && strpos($profile['education_level'], 'Diploma in Electrical Engineering') !== false)?'selected':'' ?>>Diploma in Electrical Engineering</option>
                <option value="Diploma in Civil Engineering" <?= ($profile && strpos($profile['education_level'], 'Diploma in Civil Engineering') !== false)?'selected':'' ?>>Diploma in Civil Engineering</option>
                <option value="Diploma in Electronics / EC" <?= ($profile && strpos($profile['education_level'], 'Diploma in Electronics / EC') !== false)?'selected':'' ?>>Diploma in Electronics / EC</option>
                <option value="Other" <?= ($profile && strpos($profile['education_level'], 'Diploma') !== false && !in_array($profile['education_level'], ['Diploma in Engineering (Polytechnic)', 'Diploma in Computer Engineering / IT', 'Diploma in Mechanical Engineering', 'Diploma in Electrical Engineering', 'Diploma in Civil Engineering', 'Diploma in Electronics / EC']))?'selected':'' ?>>Other</option>
            </select><br><br>
            
            <!-- Other Diploma Course Text Field -->
            <div id="diploma_course_other_field" style="display:none;">
                <input type="text" name="diploma_course_other" placeholder="Enter Your Diploma Course" value=""><br><br>
            </div>
        </div>
    </div>
    
    <!-- Conditional dropdown for Undergraduate Stream -->
    <div id="undergrad_stream_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], 'Undergraduate') !== false) ? 'block' : 'none' ?>;">
        <label>Select Stream:</label>
        <select id="undergrad_stream" name="undergrad_stream" onchange="toggleScienceGroupDropdown()">
            <option value="">Select Stream</option>
            <option value="Science" <?= ($profile && strpos($profile['education_level'], 'Undergraduate - Science') !== false)?'selected':'' ?>>Science</option>
            <option value="Commerce" <?= ($profile && strpos($profile['education_level'], 'Undergraduate - Commerce') !== false)?'selected':'' ?>>Commerce</option>
            <option value="Arts" <?= ($profile && strpos($profile['education_level'], 'Undergraduate - Arts') !== false)?'selected':'' ?>>Arts</option>
        </select><br><br>
        
        <!-- Conditional dropdown for Science Group -->
        <div id="science_group_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], 'Undergraduate - Science') !== false) ? 'block' : 'none' ?>;">
            <label>Select Science Group:</label>
            <select id="science_group" name="science_group" onchange="toggleTwelfthCourseDropdown()">
                <option value="">Select Group</option>
                <option value="Group A" <?= ($profile && strpos($profile['education_level'], 'Group A') !== false)?'selected':'' ?>>Group A (Physics, Chemistry, Mathematics)</option>
                <option value="Group B" <?= ($profile && strpos($profile['education_level'], 'Group B') !== false)?'selected':'' ?>>Group B (Physics, Chemistry, Biology)</option>
            </select><br><br>
        </div>
        
        <!-- Conditional dropdown for Undergraduate Courses -->
        <div id="undergrad_course_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], 'Undergraduate') !== false && (strpos($profile['education_level'], 'Science') !== false || strpos($profile['education_level'], 'Commerce') !== false || strpos($profile['education_level'], 'Arts') !== false)) ? 'block' : 'none' ?>;">
            <label>Select Course:</label>
            <select id="undergrad_course" name="undergrad_course" onchange="toggleOtherField('undergrad_course', 'undergrad_course_other_field')">
                <option value="">Select Course</option>
            </select><br><br>
            
            <!-- Other Undergraduate Course Text Field -->
            <div id="undergrad_course_other_field" style="display:none;">
                <input type="text" name="undergrad_course_other" placeholder="Enter Your Course" value=""><br><br>
            </div>
        </div>
    </div>
    
    <!-- Conditional dropdown for Postgraduate Course -->
    <div id="postgrad_course_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], 'Postgraduate') !== false) ? 'block' : 'none' ?>;">
        <label>Select Postgraduate Course:</label>
        <select id="postgrad_course" name="postgrad_course" onchange="toggleOtherField('postgrad_course', 'postgrad_course_other_field')">
            <option value="">Select Course</option>
            <option value="M.E. / M.Tech (Engineering)" <?= ($profile && strpos($profile['education_level'], 'M.E. / M.Tech (Engineering)') !== false)?'selected':'' ?>>M.E. / M.Tech (Engineering)</option>
            <option value="M.Sc (Science)" <?= ($profile && strpos($profile['education_level'], 'M.Sc (Science)') !== false)?'selected':'' ?>>M.Sc (Science)</option>
            <option value="M.Com (Commerce)" <?= ($profile && strpos($profile['education_level'], 'M.Com (Commerce)') !== false)?'selected':'' ?>>M.Com (Commerce)</option>
            <option value="M.A (Arts / Humanities)" <?= ($profile && strpos($profile['education_level'], 'M.A (Arts / Humanities)') !== false)?'selected':'' ?>>M.A (Arts / Humanities)</option>
            <option value="MCA (Computer Applications)" <?= ($profile && strpos($profile['education_level'], 'MCA (Computer Applications)') !== false)?'selected':'' ?>>MCA (Computer Applications)</option>
            <option value="MBA (Master of Business Administration)" <?= ($profile && strpos($profile['education_level'], 'MBA (Master of Business Administration)') !== false)?'selected':'' ?>>MBA (Master of Business Administration)</option>
            <option value="M.Voc (Vocational Master's)" <?= ($profile && strpos($profile['education_level'], "M.Voc (Vocational Master's)") !== false)?'selected':'' ?>>M.Voc (Vocational Master's)</option>
            <option value="M.Ed (Education)" <?= ($profile && strpos($profile['education_level'], 'M.Ed (Education)') !== false)?'selected':'' ?>>M.Ed (Education)</option>
            <option value="LLM (Law)" <?= ($profile && strpos($profile['education_level'], 'LLM (Law)') !== false)?'selected':'' ?>>LLM (Law)</option>
            <option value="M.Pharm (Pharmacy)" <?= ($profile && strpos($profile['education_level'], 'M.Pharm (Pharmacy)') !== false)?'selected':'' ?>>M.Pharm (Pharmacy)</option>
            <option value="M.Sc Nursing" <?= ($profile && strpos($profile['education_level'], 'M.Sc Nursing') !== false)?'selected':'' ?>>M.Sc Nursing</option>
            <option value="MS (Medical / Clinical)" <?= ($profile && strpos($profile['education_level'], 'MS (Medical / Clinical)') !== false)?'selected':'' ?>>MS (Medical / Clinical)</option>
            <option value="MPH (Public Health)" <?= ($profile && strpos($profile['education_level'], 'MPH (Public Health)') !== false)?'selected':'' ?>>MPH (Public Health)</option>
            <option value="MHA (Hospital Administration)" <?= ($profile && strpos($profile['education_level'], 'MHA (Hospital Administration)') !== false)?'selected':'' ?>>MHA (Hospital Administration)</option>
            <option value="MSW (Social Work)" <?= ($profile && strpos($profile['education_level'], 'MSW (Social Work)') !== false)?'selected':'' ?>>MSW (Social Work)</option>
            <option value="M.Des (Design)" <?= ($profile && strpos($profile['education_level'], 'M.Des (Design)') !== false)?'selected':'' ?>>M.Des (Design)</option>
            <option value="M.Phil" <?= ($profile && strpos($profile['education_level'], 'M.Phil') !== false)?'selected':'' ?>>M.Phil</option>
            <option value="Integrated Master's Program" <?= ($profile && strpos($profile['education_level'], "Integrated Master's Program") !== false)?'selected':'' ?>>Integrated Master's Program</option>
            <option value="M.Tech (AI / ML / Data Science / Cyber Security)" <?= ($profile && strpos($profile['education_level'], 'M.Tech (AI / ML / Data Science / Cyber Security)') !== false)?'selected':'' ?>>M.Tech (AI / ML / Data Science / Cyber Security)</option>
            <option value="M.Sc (Data Science / AI / Analytics)" <?= ($profile && strpos($profile['education_level'], 'M.Sc (Data Science / AI / Analytics)') !== false)?'selected':'' ?>>M.Sc (Data Science / AI / Analytics)</option>
            <option value="PG Diploma" <?= ($profile && strpos($profile['education_level'], 'PG Diploma') !== false)?'selected':'' ?>>PG Diploma</option>
            <option value="Distance / Online Master's Degree" <?= ($profile && strpos($profile['education_level'], "Distance / Online Master's Degree") !== false)?'selected':'' ?>>Distance / Online Master's Degree</option>
            <option value="Other" <?= ($profile && strpos($profile['education_level'], 'Postgraduate') !== false && strpos($profile['education_level'], 'Other') !== false)?'selected':'' ?>>Other</option>
        </select><br><br>
        
        <!-- Other Postgraduate Course Text Field -->
        <div id="postgrad_course_other_field" style="display:none;">
            <input type="text" name="postgrad_course_other" placeholder="Enter Your Postgraduate Course" value=""><br><br>
        </div>
    </div>
    
    <!-- Conditional dropdown for PhD Course -->
    <div id="phd_course_dropdown" style="display:<?= ($profile && strpos($profile['education_level'], 'PhD') !== false) ? 'block' : 'none' ?>;">
        <label>Select PhD Course:</label>
        <select id="phd_course" name="phd_course" onchange="toggleOtherField('phd_course', 'phd_course_other_field')">
            <option value="">Select Course</option>
            <option value="PhD in Computer Science / IT" <?= ($profile && strpos($profile['education_level'], 'PhD in Computer Science / IT') !== false)?'selected':'' ?>>PhD in Computer Science / IT</option>
            <option value="PhD in Mechanical Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Mechanical Engineering') !== false)?'selected':'' ?>>PhD in Mechanical Engineering</option>
            <option value="PhD in Civil Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Civil Engineering') !== false)?'selected':'' ?>>PhD in Civil Engineering</option>
            <option value="PhD in Electrical Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Electrical Engineering') !== false)?'selected':'' ?>>PhD in Electrical Engineering</option>
            <option value="PhD in Electronics & Communication" <?= ($profile && strpos($profile['education_level'], 'PhD in Electronics & Communication') !== false)?'selected':'' ?>>PhD in Electronics & Communication</option>
            <option value="PhD in Chemical Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Chemical Engineering') !== false)?'selected':'' ?>>PhD in Chemical Engineering</option>
            <option value="PhD in Biotechnology Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Biotechnology Engineering') !== false)?'selected':'' ?>>PhD in Biotechnology Engineering</option>
            <option value="PhD in Environmental Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Environmental Engineering') !== false)?'selected':'' ?>>PhD in Environmental Engineering</option>
            <option value="PhD in Aerospace / Aeronautical Engineering" <?= ($profile && strpos($profile['education_level'], 'PhD in Aerospace / Aeronautical Engineering') !== false)?'selected':'' ?>>PhD in Aerospace / Aeronautical Engineering</option>
            <option value="PhD in Data Science / AI / ML" <?= ($profile && strpos($profile['education_level'], 'PhD in Data Science / AI / ML') !== false)?'selected':'' ?>>PhD in Data Science / AI / ML</option>
            <option value="PhD in Mathematics" <?= ($profile && strpos($profile['education_level'], 'PhD in Mathematics') !== false)?'selected':'' ?>>PhD in Mathematics</option>
            <option value="PhD in Physics" <?= ($profile && strpos($profile['education_level'], 'PhD in Physics') !== false)?'selected':'' ?>>PhD in Physics</option>
            <option value="PhD in Chemistry" <?= ($profile && strpos($profile['education_level'], 'PhD in Chemistry') !== false)?'selected':'' ?>>PhD in Chemistry</option>
            <option value="PhD in Biology / Life Sciences" <?= ($profile && strpos($profile['education_level'], 'PhD in Biology / Life Sciences') !== false)?'selected':'' ?>>PhD in Biology / Life Sciences</option>
            <option value="PhD in Environmental Science" <?= ($profile && strpos($profile['education_level'], 'PhD in Environmental Science') !== false)?'selected':'' ?>>PhD in Environmental Science</option>
            <option value="PhD in Economics" <?= ($profile && strpos($profile['education_level'], 'PhD in Economics') !== false)?'selected':'' ?>>PhD in Economics</option>
            <option value="PhD in Management / Business Administration" <?= ($profile && strpos($profile['education_level'], 'PhD in Management / Business Administration') !== false)?'selected':'' ?>>PhD in Management / Business Administration</option>
            <option value="PhD in Commerce" <?= ($profile && strpos($profile['education_level'], 'PhD in Commerce') !== false)?'selected':'' ?>>PhD in Commerce</option>
            <option value="Other" <?= ($profile && strpos($profile['education_level'], 'Other') !== false && strpos($profile['education_level'], 'PhD') !== false)?'selected':'' ?>>Other</option>
        </select><br><br>
        
        <!-- Other PhD Course Text Field -->
        <div id="phd_course_other_field" style="display:none;">
            <input type="text" name="phd_course_other" placeholder="Enter Your PhD Course" value=""><br><br>
        </div>
    </div>
    
    <!-- Fields for students above 10th -->
    <div id="course_year_fields" style="display:<?= ($profile && strpos($profile['education_level'], 'Below 10th') === false && strpos($profile['education_level'], '10th Pass') === false && strpos($profile['education_level'], 'Undergraduate') === false && strpos($profile['education_level'], 'Postgraduate') === false && strpos($profile['education_level'], 'PhD') === false) ? 'block' : 'none' ?>;">
        <input type="text" id="course" name="course" placeholder="Course" value="<?= $profile['course'] ?? '' ?>" required><br><br>
        <input type="text" id="current_year" name="current_year" placeholder="Current Year" value="<?= $profile['current_year'] ?? '' ?>" required><br><br>
    </div>
    
    Marks (%):
    <input type="number" name="marks" placeholder="Marks (%)" value="<?= $profile['marks'] ?? '' ?>" required><br><br>
    
    Family Income:
    <input type="number" name="family_income" placeholder="Family Income" value="<?= $profile['family_income'] ?? '' ?>" required><br><br>
    
    Category:
    <select name="category" required>
        <option value="">Select Category</option>
        <option value="General (GEN / UR)" <?= ($profile && $profile['category']=='General (GEN / UR)')?'selected':'' ?>>General (GEN / UR)</option>
        <option value="Other Backward Class (OBC)" <?= ($profile && $profile['category']=='Other Backward Class (OBC)')?'selected':'' ?>>Other Backward Class (OBC)</option>
        <option value="Scheduled Caste (SC)" <?= ($profile && $profile['category']=='Scheduled Caste (SC)')?'selected':'' ?>>Scheduled Caste (SC)</option>
        <option value="Scheduled Tribe (ST)" <?= ($profile && $profile['category']=='Scheduled Tribe (ST)')?'selected':'' ?>>Scheduled Tribe (ST)</option>
    </select><br><br>
    
    Gender: 
    <select name="gender" required>
        <option value="">Select</option>
        <option value="Male" <?= ($profile && $profile['gender']=='Male')?'selected':'' ?>>Male</option>
        <option value="Female" <?= ($profile && $profile['gender']=='Female')?'selected':'' ?>>Female</option>
        <option value="Other" <?= ($profile && $profile['gender']=='Other')?'selected':'' ?>>Other</option>
    </select><br><br>
    
    State:
    <select id="state" name="state_id" required>
        <option value="">Select State</option>
    </select><br><br>
    
    District:
    <select id="district" name="district_id" required>
        <option value="">Select District</option>
    </select><br><br>
    
    Institution Type:
    <select name="institution_type" required>
        <option value="">Select Institution Type</option>
        <option value="Government" <?= ($profile && $profile['institution_type']=='Government')?'selected':'' ?>>Government</option>
        <option value="Private" <?= ($profile && $profile['institution_type']=='Private')?'selected':'' ?>>Private</option>
        <option value="Government-Aided" <?= ($profile && $profile['institution_type']=='Government-Aided')?'selected':'' ?>>Government-Aided</option>
        <option value="Autonomous" <?= ($profile && $profile['institution_type']=='Autonomous')?'selected':'' ?>>Autonomous</option>
        <option value="University" <?= ($profile && $profile['institution_type']=='University')?'selected':'' ?>>University</option>
    </select><br><br>
    
    Age:
    <input type="number" name="age" placeholder="Age" value="<?= $profile['age'] ?? '' ?>" required><br><br>
    
    Disability Type:
    <select name="disability_type">
        <option value="">Select Disability Type (if any)</option>
        <option value="None" <?= ($profile && $profile['disability_type']=='None')?'selected':'' ?>>None</option>
        <option value="Physical Disability" <?= ($profile && $profile['disability_type']=='Physical Disability')?'selected':'' ?>>Physical Disability</option>
        <option value="Visual Impairment" <?= ($profile && $profile['disability_type']=='Visual Impairment')?'selected':'' ?>>Visual Impairment</option>
        <option value="Hearing Impairment" <?= ($profile && $profile['disability_type']=='Hearing Impairment')?'selected':'' ?>>Hearing Impairment</option>
        <option value="Speech & Language Disability" <?= ($profile && $profile['disability_type']=='Speech & Language Disability')?'selected':'' ?>>Speech & Language Disability</option>
        <option value="Intellectual Disability" <?= ($profile && $profile['disability_type']=='Intellectual Disability')?'selected':'' ?>>Intellectual Disability</option>
        <option value="Mental Illness" <?= ($profile && $profile['disability_type']=='Mental Illness')?'selected':'' ?>>Mental Illness</option>
        <option value="Multiple Disabilities" <?= ($profile && $profile['disability_type']=='Multiple Disabilities')?'selected':'' ?>>Multiple Disabilities</option>
        <option value="Specific Learning Disability" <?= ($profile && $profile['disability_type']=='Specific Learning Disability')?'selected':'' ?>>Specific Learning Disability</option>
        <option value="Other" <?= ($profile && $profile['disability_type']=='Other')?'selected':'' ?>>Other</option>
    </select><br><br>
    
    Disability Percent (if any):
    <input type="number" name="disability_percent" placeholder="Disability Percent (0-100)" value="<?= $profile['disability_percent'] ?? '' ?>" min="0" max="100"><br><br>
    
    Minority Status: 
    <select name="minority_status" required>
        <option value="">Select</option>
        <option value="Yes" <?= ($profile && $profile['minority_status']=='Yes')?'selected':'' ?>>Yes</option>
        <option value="No" <?= ($profile && $profile['minority_status']=='No')?'selected':'' ?>>No</option>
    </select><br><br>
    
    Parent / Guardian Name:
    <input type="text" name="parent_name" placeholder="Parent / Guardian Name" value="<?= $profile['parent_name'] ?? '' ?>"><br><br>
    
    Parent / Guardian Occupation:
    <input type="text" name="parent_occupation" placeholder="Parent / Guardian Occupation" value="<?= $profile['parent_occupation'] ?? '' ?>"><br><br>
    
    Parent / Guardian Contact:
    <input type="tel" name="parent_contact" placeholder="Parent / Guardian Contact (Mobile / Phone)" value="<?= $profile['parent_contact'] ?? '' ?>"><br><br>
    
    <button type="submit" name="save_profile">Save Profile</button>
</form>
    </div>

<footer id="footer">
    <div>
        <h4>ScholarMatch</h4>
        <p>&copy; <?php echo date('Y'); ?> ScholarMatch. All rights reserved.</p>
    </div>
    <div>
        <h4>Quick Links</h4>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="student_dashboard.php">Dashboard</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </div>
    <div>
        <h4>Contact</h4>
        <p>Email: info@scholarmatch.com</p>
    </div>
</footer>

<script>
// Saved values from PHP for auto-selecting state/district
const SAVED_STATE_ID    = <?= intval($saved_state_id) ?>;
const SAVED_DISTRICT_ID = <?= intval($saved_district_id) ?>;

document.addEventListener('DOMContentLoaded', function() {
    // ── State dropdown: load all states, then auto-select saved one ──
    fetch('get_states.php')
        .then(res => res.text())
        .then(html => {
            document.getElementById('state').innerHTML += html;
            if(SAVED_STATE_ID > 0) {
                document.getElementById('state').value = SAVED_STATE_ID;
                // After selecting state, load its districts then auto-select saved district
                loadDistricts(SAVED_STATE_ID, SAVED_DISTRICT_ID);
            }
        });

    // ── State change by user ──
    document.getElementById('state').addEventListener('change', function() {
        loadDistricts(this.value, 0);
    });

    // ── Trigger education dropdowns on load ──
    toggleBelow10thDropdown();
});

function loadDistricts(stateId, autoSelectDistrictId) {
    if(!stateId) {
        document.getElementById('district').innerHTML = '<option value="">Select District</option>';
        return;
    }
    fetch('get_districts.php?state_id=' + stateId)
        .then(res => res.text())
        .then(html => {
            document.getElementById('district').innerHTML =
                '<option value="">Select District</option>' + html;
            // Auto-select the saved district if provided
            if(autoSelectDistrictId > 0) {
                document.getElementById('district').value = autoSelectDistrictId;
            }
        });
}

function toggleBelow10thDropdown() {
    const edu = document.getElementById('education_level').value;
    const divs = {
        'below_10th':    ['Below 10th'],
        'tenth_stream':  ['10th Pass(SSC)'],
        'undergrad_stream': ['Undergraduate'],
        'postgrad_course':  ['Postgraduate'],
        'phd_course':       ['PhD'],
        'course_year':      ['Other']
    };

    hide('below_10th_dropdown');
    hide('tenth_stream_dropdown');
    hide('undergrad_stream_dropdown');
    hide('postgrad_course_dropdown');
    hide('phd_course_dropdown');
    hide('course_year_fields');

    if(edu === 'Below 10th')      show('below_10th_dropdown');
    else if(edu === '10th Pass(SSC)') { show('tenth_stream_dropdown'); toggleDiplomaDropdown(); }
    else if(edu === 'Undergraduate')  { show('undergrad_stream_dropdown'); toggleScienceGroupDropdown(); }
    else if(edu === 'Postgraduate')   show('postgrad_course_dropdown');
    else if(edu === 'PhD')            show('phd_course_dropdown');
    else if(edu === 'Other')          show('course_year_fields');
}

function toggleDiplomaDropdown() {
    const val = document.getElementById('tenth_stream').value;
    val === 'Diploma' ? show('diploma_course_dropdown') : hide('diploma_course_dropdown');
}

function toggleScienceGroupDropdown() {
    const stream = document.getElementById('undergrad_stream').value;
    hide('science_group_dropdown');
    hide('undergrad_course_dropdown');

    if(stream === 'Science') {
        show('science_group_dropdown');
        toggleTwelfthCourseDropdown();
    } else if(stream === 'Commerce' || stream === 'Arts') {
        show('undergrad_course_dropdown');
        updateCoursesForStream(stream);
    }
}

function toggleTwelfthCourseDropdown() {
    const stream = document.getElementById('undergrad_stream').value;
    const group  = document.getElementById('science_group').value;
    if(stream === 'Science' && group) {
        show('undergrad_course_dropdown');
        updateCoursesForScienceGroup(group);
    }
}

function updateCoursesForScienceGroup(group) {
    const map = {
        'Group A': ['B.E. / B.Tech (Engineering)','BCA (Computer Applications)','B.Sc (Science)'],
        'Group B': ['MBBS / BDS / BAMS / BHMS (Medical)','B.Sc Nursing','B.Pharm (Pharmacy)','B.Sc (Science)']
    };
    fillCourseSelect('undergrad_course', map[group] || []);
}

function updateCoursesForStream(stream) {
    const map = {
        'Commerce': ['B.Com (Commerce)','BBA (Business Administration)','Hotel Management / Tourism','B.Ed (Integrated)'],
        'Arts':     ['B.A (Arts / Humanities)','Fashion Designing / Fine Arts','LLB (Law - 5 Year)','Hotel Management / Tourism','B.Ed (Integrated)']
    };
    fillCourseSelect('undergrad_course', map[stream] || []);
}

function fillCourseSelect(id, courses) {
    // Preserve previously saved value if it exists
    const saved = document.getElementById(id).dataset.saved || '';
    let html = '<option value="">Select Course</option>';
    courses.forEach(c => {
        html += `<option value="${c}" ${c === saved ? 'selected' : ''}>${c}</option>`;
    });
    document.getElementById(id).innerHTML = html;
}

function show(id) { const el = document.getElementById(id); if(el) el.style.display = 'block'; }
function hide(id) { const el = document.getElementById(id); if(el) el.style.display = 'none'; }
</script>

</body>
</html>