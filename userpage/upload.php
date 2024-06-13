<?php
    require_once('../check_backup.php');
    session_start();
    require_once('../db.php');
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == false) {
        header("Location: login");
        exit();
    }
    $username = $_SESSION["username"];
    $target_dir = "../filesuploaded/";
    $original_file_name = $_FILES["fileToUpload"]["name"];
    $unique_file_name = uniqid($_SESSION["ID"]);
    $target_file = $target_dir . $unique_file_name;
    if ($_FILES["fileToUpload"]["size"] > 1073741824) {
        $_SESSION['error_message'] = "Файл слишком велик";
        header("Location: /userpage");
        exit();
    }
    $owner_id = $_SESSION["ID"];
    $sql = "SELECT (SUM(file_size)) AS summary_size FROM `files` WHERE owner_id = $owner_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    if (10737418240 - $_FILES["fileToUpload"]["size"] < 0) {
        $_SESSION['error_message'] = "Превышен лимит на размер всех файлов: 10 ГБ";
        header("Location: /userpage");
        exit();
    }
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $file_name = htmlspecialchars($original_file_name);
        $file_size = $_FILES["fileToUpload"]["size"];
        $file_url = $unique_file_name;
        $stmt = $conn->prepare("INSERT INTO files (owner_id, file_name, file_url, file_size) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $owner_id, $file_name, $file_url, $file_size);
        if ($stmt->execute()) {
            $file_id = $stmt->insert_id;
            $action = "upload";
            $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $file_id, $owner_id, $action);
            $stmt->execute();
            header("Location: /userpage");
            exit();
        } else {
            $_SESSION['error_message'] = "Ошибка при сохранении информации о файле в базе данных.";
            header("Location: /userpage");
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Ошибка при загрузке файла.";
        header("Location: /userpage");
        exit();
    }
?>
