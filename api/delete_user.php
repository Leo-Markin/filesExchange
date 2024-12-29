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
            if (!empty($_POST['user_id'])) {
                $user_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['user_id'])));
                if ($user_id != 2) {
                    $sql = "SELECT id, file_url FROM files WHERE owner_id = $user_id";
                    $result = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $link = $row["file_url"];
                            $file_id = $row["id"];
                            $file_sql = "DELETE FROM files WHERE id=$file_id";
                            mysqli_query($conn, $file_sql);
                            unlink("../filesuploaded/$link");
                            $action = "delete";
                            $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                            $stmt->bind_param("iis", $file_id, $admin_id, $action);
                            $stmt->execute();
                        }
                    }
                    $sql = "DELETE FROM `users` WHERE id=$user_id";
                    mysqli_query($conn, $sql);
                    $action = "delete";
                    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
                    $stmt->bind_param("is", $user_id, $action);
                    $stmt->execute();
                    http_response_code(200);
                    $response['status'] = 'success';
                } else {
                    http_response_code(201);
                    $response['status'] = 'invalid user_id';
                }
            } else {
                http_response_code(201);
                $response['status'] = 'invalid user_id';
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