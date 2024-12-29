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
            $sql = "SELECT id, username from `users`";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                $users = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $user = array();
                    if ($row['id'] == 2) continue;
                    $user['id'] = $row['id'];
                    $user['username'] = $row["username"];
                    $users[] = $user;
                }
                $response["users"] = $users;
            }
            http_response_code(200);
            $response['status'] = 'success';
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