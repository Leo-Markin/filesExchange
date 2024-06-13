<?php
session_start();
require_once('../check_backup.php');
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == false) {
    header("Location: ../login");
    exit();
}
require_once("../db.php");
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['link'])) {
    $link = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['link'])));
    $sql = "SELECT * FROM files WHERE file_url = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $link);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $file_path = "../filesuploaded/$link";
        $file_name = $row["file_name"];
        $file_id = $row["id"];
        if (file_exists($file_path)) {
            $action = "download";
            $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $file_id, $_SESSION["ID"], $action);
            $stmt->execute();
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_name) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit();
        } else {
            $_SESSION['error_message'] = "Файл не найден. $file_path";
            header("Location: /userpage");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Файла не существует.";
        header("Location: /userpage");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Некорректный запрос.";
    header("Location: /userpage");
    exit();
}
?>