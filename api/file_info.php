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
        if (!empty($_POST['file_url'])) {
            $row = mysqli_fetch_assoc($result);
            $user_id = $row['id'];
            $file_url = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['file_url'])));
            $sql = "SELECT * FROM files WHERE file_url = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $file_url);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $response['file_id'] = $row["id"];
                $response["file_name"] = $row["file_name"];
                $file_size = $row["file_size"];
                if ($file_size < 1024) $file_size = $file_size . ' B';
                elseif ($file_size < 1048576) $file_size = round($file_size / 1024, 2) . ' KB';
                else $file_size = round($file_size / 1048576, 2) . ' MB';
                $response["file_size"] = $file_size;
                $response["upload_date"] = $row["upload_date"];
                $owner_id = $row["owner_id"];
                $sql = "SELECT COUNT(*) AS count_views FROM `file_activity_log` WHERE action_type = 'view' AND file_id = $response[file_id]";
                $fileresult = mysqli_query($conn, $sql);
                $filerow = mysqli_fetch_assoc($fileresult);
                $response["count_views"] = $filerow["count_views"];
                $sql = "SELECT username FROM `users` WHERE id = $owner_id";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                $response['owner_name'] = $row["username"];
                $action = "view";
                $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $response['file_id'], $user_id, $action);
                $stmt->execute();
                $sql = "SELECT COUNT(*) AS count_downloads FROM `file_activity_log` WHERE action_type = 'download' AND file_id = $response[file_id]";
                $fileresult = mysqli_query($conn, $sql);
                $filerow = mysqli_fetch_assoc($fileresult);
                $response["count_downloads"] = $filerow["count_downloads"];
                $response["access_to_del"] = $user_id == 2 || $user_id == $owner_id ? 1 : 0;
                $http_response_code = 200;
                $response['status'] = "success";
            } else {
                $response['status'] = 'invalid url';
                $http_response_code = 201;
            }
        } else {
            $response['status'] = 'invalid url';
            $http_response_code = 201;
        }
    } else {
        $response['status'] = 'invalid token';
        $http_response_code = 201;
    }
} else {
    $response['status'] = 'invalid args';
    $http_response_code = 201;
}
echo json_encode($response);