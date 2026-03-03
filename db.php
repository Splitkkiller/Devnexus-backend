<?php
// Database connection for XAMPP
$host = "localhost";
$user = "root";
$pass = ""; // default XAMPP password is empty
$dbname = "devnexus";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}
?>
