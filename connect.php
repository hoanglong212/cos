<?php
$servername = "sql12.freesqldatabase.com";
$username = "sql12802109";
$password = "QM8dUEyvi2";
$dbname = "sql12802109";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("❌ Kết nối thất bại: " . $conn->connect_error);
}
?>
