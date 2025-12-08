<?php
session_start();
header('Content-Type: application/json');

// Check if the session variables we created during login exist
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    // If logged in, send back the user's name and role in a JSON format
    echo json_encode([
        'success' => true,
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role']
    ]);
} else {
    // If not logged in, send back a failure message
    echo json_encode(['success' => false]);
}
?>