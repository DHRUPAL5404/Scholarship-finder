<?php
session_start();
include "db.php"; // Database connection

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student'){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch existing profile (if any)
$profile = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=$user_id")
);

// Handle form submission
if(isset($_POST['save_profile'])){
    $education_level = mysqli_real_escape_string($conn, $_POST['education_level']);
    $course = mysqli_real_escape_string($conn, $_POST['course']);
    $current_year = mysqli_real_escape_string($conn, $_POST['current_year']);
    $marks = mysqli_real_escape_string($conn, $_POST['marks']);
    $family_income = mysqli_real_escape_string($conn, $_POST['family_income']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $institution_type = mysqli_real_escape_string($conn, $_POST['institution_type']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $disability_percent = mysqli_real_escape_string($conn, $_POST['disability_percent']);
    $minority_status = mysqli_real_escape_string($conn, $_POST['minority_status']);

    if($profile){
        // Update existing profile
        $query = "UPDATE student_profile SET 
                    education_level='$education_level',
                    course='$course',
                    current_year='$current_year',
                    marks='$marks',
                    family_income='$family_income',
                    category='$category',
                    gender='$gender',
                    state='$state',
                    institution_type='$institution_type',
                    age='$age',
                    disability_percent='$disability_percent',
                    minority_status='$minority_status'
                  WHERE user_id=$user_id";
    } else {
        // Insert new profile
        $query = "INSERT INTO student_profile
                    (user_id, education_level, course, current_year, marks, family_income, category, gender, state, institution_type, age, disability_percent, minority_status)
                  VALUES
                    ($user_id, '$education_level', '$course', '$current_year', $marks, $family_income, '$category', '$gender', '$state', '$institution_type', $age, $disability_percent, '$minority_status')";
    }

    if(mysqli_query($conn, $query)){
        echo "<p style='color:green;'>Profile saved successfully!</p>";
        // Refresh profile
        $profile = mysqli_fetch_assoc(
            mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=$user_id")
        );
    } else {
        echo "<p style='color:red;'>Error: ".mysqli_error($conn)."</p>";
    }
}
?>

<h2>Student Profile</h2>
<form method="post" action="profile.php">
    <input type="text" name="education_level" placeholder="Education Level" value="<?= $profile['education_level'] ?? '' ?>" required><br><br>
    <input type="text" name="course" placeholder="Course" value="<?= $profile['course'] ?? '' ?>" required><br><br>
    <input type="text" name="current_year" placeholder="Current Year" value="<?= $profile['current_year'] ?? '' ?>" required><br><br>
    <input type="number" name="marks" placeholder="Marks (%)" value="<?= $profile['marks'] ?? '' ?>" required><br><br>
    <input type="number" name="family_income" placeholder="Family Income" value="<?= $profile['family_income'] ?? '' ?>" required><br><br>
    <input type="text" name="category" placeholder="Category" value="<?= $profile['category'] ?? '' ?>" required><br><br>
    
    Gender: 
    <select name="gender" required>
        <option value="">Select</option>
        <option value="Male" <?= ($profile['gender']=='Male')?'selected':'' ?>>Male</option>
        <option value="Female" <?= ($profile['gender']=='Female')?'selected':'' ?>>Female</option>
        <option value="Other" <?= ($profile['gender']=='Other')?'selected':'' ?>>Other</option>
    </select><br><br>
    
    <input type="text" name="state" placeholder="State" value="<?= $profile['state'] ?? '' ?>" required><br><br>
    <input type="text" name="institution_type" placeholder="Institution Type" value="<?= $profile['institution_type'] ?? '' ?>" required><br><br>
    <input type="number" name="age" placeholder="Age" value="<?= $profile['age'] ?? '' ?>" required><br><br>
    <input type="number" name="disability_percent" placeholder="Disability Percent" value="<?= $profile['disability_percent'] ?? '' ?>" required><br><br>
    
    Minority Status: 
    <select name="minority_status" required>
        <option value="">Select</option>
        <option value="Yes" <?= ($profile['minority_status']=='Yes')?'selected':'' ?>>Yes</option>
        <option value="No" <?= ($profile['minority_status']=='No')?'selected':'' ?>>No</option>
    </select><br><br>
    
    <button type="submit" name="save_profile">Save Profile</button>
</form>