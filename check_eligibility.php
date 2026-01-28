<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Fetch student profile */
$profile_q = mysqli_query($conn, "SELECT * FROM student_profile WHERE user_id=$user_id");
$student = mysqli_fetch_assoc($profile_q);

if(!$student){
    echo "⚠️ Please complete your profile first.";
    exit();
}

/* Fetch active scholarships */
$scholarships = mysqli_query($conn, "SELECT * FROM scholarships WHERE status='active'");

echo "<h2>Eligible Scholarships 🎯</h2>";

$found = false;

while($sch = mysqli_fetch_assoc($scholarships)){
    $sid = $sch['scholarship_id'];

    $rules = mysqli_query(
        $conn,
        "SELECT * FROM eligibility_rules WHERE scholarship_id=$sid"
    );

    $eligible = true;

    while($rule = mysqli_fetch_assoc($rules)){
        $field = $rule['field_name'];
        $operator = $rule['operator'];
        $value = $rule['value'];

        $student_value = $student[$field];

        switch($operator){
            case '=':
                if($student_value != $value) $eligible = false;
                break;
            case '>=':
                if($student_value < $value) $eligible = false;
                break;
            case '<=':
                if($student_value > $value) $eligible = false;
                break;
            case '>':
                if($student_value <= $value) $eligible = false;
                break;
            case '<':
                if($student_value >= $value) $eligible = false;
                break;
        }

        if(!$eligible) break;
    }

    if($eligible){
        $found = true;
        echo "
        <div style='border:1px solid #ccc; padding:15px; margin:15px;'>
            <h3>{$sch['title']}</h3>
            <p>{$sch['description']}</p>
            <p><b>Deadline:</b> {$sch['deadline']}</p>
            <a href='apply.php?sid={$sid}'>Apply Now</a>
        </div>
        ";
    }
}

if(!$found){
    echo "<p>😕 No scholarships matched your profile.</p>";
}
?>