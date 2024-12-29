<?php
require_once('check_backup.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("../db.php");
$response = array();
error_log(print_r($_POST, true));
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST["token_api"])) {
    $token_api = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['token_api'])));
    $sql = "SELECT * FROM `users` WHERE token_api = '$token_api'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_id = $row['id'];
        if (!empty($_FILES["file"])) {
            $target_dir = "../filesuploaded/";
            $original_file_name = $_FILES["file"]["name"];
            $unique_file_name = uniqid($user_id);
            $target_file = $target_dir . $unique_file_name;
            if ($_FILES["file"]["size"] <= 1073741824) {
                $sql = "SELECT (SUM(file_size)) AS summary_size FROM `files` WHERE owner_id = $user_id";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                if (10737418240 - $_FILES["file"]["size"] >= 0) {
                    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                        $file_name = htmlspecialchars($original_file_name);
                        $file_size = $_FILES["file"]["size"];
                        $file_url = $unique_file_name;
                        $stmt = $conn->prepare("INSERT INTO files (owner_id, file_name, file_url, file_size) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $user_id, $file_name, $file_url, $file_size);
                        if ($stmt->execute()) {
                            $file_id = $stmt->insert_id;
                            $action = "upload";
                            $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                            $stmt->bind_param("iis", $file_id, $user_id, $action);
                            $stmt->execute();
                            $response['status'] = 'success';
                            http_response_code(200);
                        } else {
                            $response['status'] = 'error save';
                            http_response_code(201);
                        }
                    } else {
                        $response['status'] = 'error save';
                        http_response_code(201);
                    }
                } else {
                    $response['status'] = 'no space';
                    http_response_code(201);
                }
            } else {
                $response['status'] = 'max size';
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