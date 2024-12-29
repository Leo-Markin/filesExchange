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
            $admin_id = $row['id'];
            if (!empty($_POST['username']) && !empty($_POST['password'])) {
                $username = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['username'])));
                $password = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['password'])));
                $sql = "SELECT * FROM `users` WHERE username = '$username'";
                $result = mysqli_query($conn, $sql);
                if (mysqli_num_rows($result) == 0) {
                    $password = password_hash($password, PASSWORD_BCRYPT);
                    $token_api = md5(microtime() . $password . time());
                    $stmt = $conn->prepare("INSERT INTO `users` (username, `password`, token_api) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $password, $token_api);
                    if ($stmt->execute()) {
                        $user_id = $stmt->insert_id;
                        $action = "create";
                        $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
                        $stmt->bind_param("is", $user_id, $action);
                        $stmt->execute();
                        http_response_code(200);
                        $response['status'] = 'success';
                    }
                } else {
                    http_response_code(201);
                    $response['status'] = 'invalid username';
                }
            } else {
                http_response_code(201);
                $response['status'] = 'invalid args';
            }
        } else {
            http_response_code(201);
            $response['status'] = 'invalid token';
        }
    } else {
        http_response_code(201);
        $response['status'] = 'invalid token';
    }
} else {
    http_response_code(201);
    $response['status'] = 'invalid token';
}
echo json_encode($response);