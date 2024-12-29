<?php
require_once('check_backup.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("../db.php");
$response = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST["token_api"])) {
    $token_api = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['token_api'])));
    $sql = "SELECT * FROM `users` WHERE token_api = '$token_api'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        if ($row['id'] == 2) {
            $output = array();
            $command = "php backup.php";
            exec($command, $output, $return_var);
            $response['status'] = 'success';
            http_response_code(200);
        } else {
            $response['status'] = 'invalid token';
            http_response_code(201);
        }
    } else {
        $response['status'] = 'invalid token';
        http_response_code(201);
    }
} else {
    $response['status'] = 'invalid token';
    http_response_code(201);
}
echo json_encode($response);