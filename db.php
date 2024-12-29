<?php

header('Content-Type: text/html; charset=utf-8');


$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "filesexchange";

$conn = mysqli_connect($servername, $username, $password, $dbname);
mysqli_set_charset($conn, "utf8");

if(!$conn) {
    die("Connection error" . mysqli_connect_error());
}
?>
