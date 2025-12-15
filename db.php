<?php
$host = "localhost";
$user = "root";       // default in XAMPP
$pass = "";           // default is empty
$db   = "web";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
4e