<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "e waste"; 
$port = 3306; 

// Define SMTP constants only if not already defined
if (!defined('SMTP_USER')) {
    define('SMTP_USER', 'rajivsinghrajput146@gmail.com');
}

if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', 'ftxw xmjm grax lkvv');
}

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>