<?php
require_once('check_backup.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once('../db.php');
$response = array();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {
        $username = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['username'])));
        $password = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['password'])));
        $sql = "SELECT * FROM `users` WHERE username = '$username'";
        $result = mysqli_query($conn, $sql);
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                $token_api = $row['token_api'];
                $action = "login";
                $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
                $stmt->bind_param("is", $row['id'], $action);
                $stmt->execute();
                http_response_code(200);
                $response['token_api'] = $token_api;
                if ($row['id'] == 2) $response['role'] = "admin";
                else $response['role'] = "user";
                $response['status'] = 'success';
            } else {
                http_response_code(201);
                $response['status'] = 'invalid auth_data';
            }
        } else {
            http_response_code(201);
            $response['status'] = 'invalid auth_data';
        }
    } else {
        http_response_code(201);
        $response['status'] = 'invalid args';
    }
    echo json_encode($response);
}