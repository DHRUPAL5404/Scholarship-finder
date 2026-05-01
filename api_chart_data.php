<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$response = [
    'category_data' => [],
    'status_data' => []
];

// Applications by Category (joining with student_profile)
$cat_q = "SELECT sp.category, COUNT(*) as count 
          FROM scholarship_applications a 
          JOIN student_profile sp ON a.user_id = sp.user_id 
          GROUP BY sp.category";
$cat_res = mysqli_query($conn, $cat_q);
while ($row = mysqli_fetch_assoc($cat_res)) {
    $cat = empty($row['category']) ? 'Unspecified' : $row['category'];
    $response['category_data'][] = [
        'label' => $cat,
        'value' => (int)$row['count']
    ];
}

// Applications by Status
$stat_q = "SELECT status, COUNT(*) as count 
           FROM scholarship_applications 
           GROUP BY status";
$stat_res = mysqli_query($conn, $stat_q);
while ($row = mysqli_fetch_assoc($stat_res)) {
    $response['status_data'][] = [
        'label' => ucfirst($row['status']),
        'value' => (int)$row['count']
    ];
}

echo json_encode($response);
?>
