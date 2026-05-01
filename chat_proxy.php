<?php
session_start();
include "db.php";
require_once "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');

if (empty($message)) {
    echo json_encode(['error' => 'Message is empty']);
    exit();
}

// Fetch student profile
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM student_profile WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_context = "Student Profile Context:\n";
if ($profile) {
    foreach ($profile as $key => $value) {
        if (!empty($value) && $key !== 'profile_id' && $key !== 'user_id' && $key !== 'uploaded_doc') {
            $profile_context .= "- " . htmlspecialchars($key) . ": " . htmlspecialchars($value) . "\n";
        }
    }
} else {
    $profile_context .= "The student has not completed their profile yet.\n";
}

$system_prompt = "You are a helpful, friendly AI assistant for the ScholarMatch scholarship portal. " .
                 "Your goal is to answer the student's questions about scholarships, eligibility, and the portal. " .
                 "Use the provided student profile context to give personalized answers. Keep answers concise and helpful.\n\n" .
                 $profile_context;

// Anthropic API request
$ch = curl_init('https://api.anthropic.com/v1/messages');

$data = [
    'model' => 'claude-sonnet-4-20250514', // Using requested model name
    'max_tokens' => 500,
    'system' => $system_prompt,
    'messages' => [
        ['role' => 'user', 'content' => $message]
    ]
];

$headers = [
    'x-api-key: ' . CLAUDE_API_KEY,
    'anthropic-version: 2023-06-01',
    'content-type: application/json'
];

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
// For local XAMPP testing, bypass SSL check if needed, but better to leave true
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit();
}
curl_close($ch);

$responseData = json_decode($response, true);

if ($http_code == 200 && isset($responseData['content'][0]['text'])) {
    $ai_reply = $responseData['content'][0]['text'];
    echo json_encode(['reply' => htmlspecialchars($ai_reply)]);
} else {
    $err = $responseData['error']['message'] ?? 'Unknown API error';
    echo json_encode(['error' => htmlspecialchars($err)]);
}
