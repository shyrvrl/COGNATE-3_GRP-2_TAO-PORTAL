<?php
header("Content-Type: application/json"); // Set the content type for all API responses
header("Access-Control-Allow-Origin: *"); // Allow requests from any origin (for development)

$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$dbname = "university_admission";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}
?>