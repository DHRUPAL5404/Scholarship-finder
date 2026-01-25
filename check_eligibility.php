<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "db.php";

$student_id = 1; // test student id

// student profile
$student = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM student_profile WHERE student_id=$student_id")
);

// scholarships
$scholarships = mysqli_query($conn,"SELECT * FROM scholarships WHERE status='active'");

while($sch = mysqli_fetch_assoc($scholarships)){
    $eligible = true;

    $rules = mysqli_query($conn,
        "SELECT * FROM eligibility_rules WHERE scholarship_id=".$sch['scholarship_id']
    );

    while($rule = mysqli_fetch_assoc($rules)){
        $field = $rule['field_name'];
        $op = $rule['operator'];
        $value = $rule['value'];

        if($op=="<=" && $student[$field] > $value) $eligible=false;
        if($op==">=" && $student[$field] < $value) $eligible=false;
        if($op=="=" && $student[$field] != $value) $eligible=false;
    }

    echo "<h3>".$sch['title']."</h3>";
    echo $eligible ? " Eligible" : " Not Eligible";
    echo "<hr>";
}
?>