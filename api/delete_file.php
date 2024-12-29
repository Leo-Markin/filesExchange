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
        $user_id = $row['id'];
        if (!empty($_POST["file_id"])) {
            $file_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['file_id'])));
            $sql = "SELECT owner_id, file_url FROM `files` WHERE id = '$file_id'";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $owner_id = $row['owner_id'];
                $file_url = $row['file_url'];
                if ($owner_id == $user_id || $user_id == 2) {
                    $sql = "DELETE FROM `files` WHERE id = '$file_id'";
                    $result = mysqli_query($conn, $sql);
                    unlink("../filesuploaded/$file_url");
                    $action = "delete";
                    $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $file_id, $user_id, $action);
                    $stmt->execute();
                    $response['status'] = "success";
                    http_response_code(200);
                } else {
                    $response['status'] = 'not owner';
                    http_response_code(201);
                }
            } else {
                $response['status'] = 'invalid file';
                http_response_code(201);
            }
        } else {
            $response['status'] = 'invalid file';
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