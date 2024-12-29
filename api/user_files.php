<?php
require_once('check_backup.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once("../db.php");
$response = array();
$files = array();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST["token_api"])) {
    $token_api = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['token_api'])));
    $sql = "SELECT * FROM `users` WHERE token_api = '$token_api'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_id = $row['id'];
        if ($user_id == 2) $sql = "SELECT * FROM `files`";
        else $sql = "SELECT * FROM `files` WHERE owner_id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_assoc($result)) {
            $file = array();
            $file["id"] = $row["id"];
            $file["file_name"] = $row["file_name"];
            $file_size = $row["file_size"];
            $owner_id = $row["owner_id"];
            $sql = "SELECT username FROM `users` WHERE id = $owner_id";
            $resultfile = mysqli_query($conn, $sql);
            $rowfile = mysqli_fetch_assoc($resultfile);
            $file['owner_name'] = $rowfile["username"];
            if ($file_size < 1024) $file_size = $file_size . ' B';
            elseif ($file_size < 1048576) $file_size = round($file_size / 1024, 2) . ' KB';
            else $file_size = round($file_size / 1048576, 2) . ' MB';
            $file["file_size"] = $file_size;
            $file["file_url"] = 'direct-capital-scorpion.ngrok-free.app/files/' . $row["file_url"];
            $file["upload_date"] = $row["upload_date"];
            $sql = "SELECT COUNT(*) AS count_views FROM `file_activity_log` WHERE action_type = 'view' AND file_id = $file[id]";
            $fileresult = mysqli_query($conn, $sql);
            $filerow = mysqli_fetch_assoc($fileresult);
            $file["count_views"] = $filerow["count_views"];
            $files[] = $file;
        }
        $response["files"] = $files;
        $response["status"] = "success";
        http_response_code(200);
    } else {
        $response["status"] = "invalid token";
        http_response_code(201);
    }
} else {
    $response["status"] = "invalid args";
    http_response_code(201);
}
echo json_encode($response);