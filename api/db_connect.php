<?php
// 1. Set PHP Timezone to Manila
// This ensures php functions like date() return Manila time.
date_default_timezone_set('Asia/Manila');

header("Content-Type: application/json"); // Set the content type for all API responses
header("Access-Control-Allow-Origin: *"); // Allow requests from any origin (for development)

$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$dbname = "university_admission2";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
$conn->query("SET time_zone = '+08:00'");
?>