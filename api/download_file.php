<?php
require_once('check_backup.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("../db.php");
$response = array();
if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET["token_api"])) {
    $token_api = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['token_api'])));
    $sql = "SELECT * FROM `users` WHERE token_api = '$token_api'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $user_id = mysqli_fetch_assoc($result)["id"];
        if (!empty($_GET['file_id'])) {
            $file_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['file_id'])));
            $sql = "SELECT * FROM `files` WHERE id = $file_id";
            $result = mysqli_query($conn, $sql);
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $file_path = "../filesuploaded/$row[file_url]";
                $file_name = $row["file_name"];
                if (file_exists($file_path)) {
                    $action = "download";
                    $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $file_id, $user_id, $action);
                    $stmt->execute();
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($file_path));
                    readfile($file_path);
                    http_response_code(200);
                } else {
                    $response['status'] = 'error file';
                    http_response_code(201);
                }
            } else {
                $response['status'] = 'invalid file_id';
                http_response_code(201);
            }
        } else {
            $response['status'] = 'invalid file_id';
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
if (http_response_code() == 201) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($response);
}
