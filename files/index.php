<?php
    require_once('check_backup.php');
    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == false) {
        header("Location: ../login");
        exit();
    }
    require_once("../db.php");
?>
<!DOCTYPE html>
<html>
<head>
    <?php
        if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['link'])) {
            $link = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['link'])));
            $sql = "SELECT * FROM files WHERE file_url = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $link);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $file_name = $row["file_name"];
                $file_size = $row["file_size"];
                $file_upload_date = $row["upload_date"];
                $owner_id = $row["owner_id"];
                $file_id = $row["id"];
                $sql = "SELECT username FROM `users` WHERE id = $owner_id";
                $result = mysqli_query($conn, $sql);
                $row = mysqli_fetch_assoc($result);
                $username = $row["username"];
                echo "<title>FX - $file_name</title>";
                $action = "view";
                $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $file_id, $_SESSION["ID"], $action);
                $stmt->execute();
            } else {
                $_SESSION['error_message'] = "Файла не существует.";
                header("Location: /userpage");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Файла не существует.";
            header("Location: /userpage");
            exit();
        }
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            font-weight: bold;
        }
        .button {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .user-info {
            text-align: right;
            margin-bottom: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        ul li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
            <div class="user-info">
            <form action="../logout.php" method="post">
                <span><?php echo htmlspecialchars($_SESSION['username']);?> </span>
                <button class="button" type="submit">Выйти</button>
            </form>
        </div>
        <?php echo "<h2>FX - $file_name</h2>"?>
        <table>
            <tbody>
                <tr>
                    <th>Файл:</th>
                    <?php echo "<td>$file_name</td>";?>
                </tr>
                <tr>
                    <th>Размер:</th>
                    <?php
                        if ($file_size < 1024) $file_size = $file_size . ' B';
                        elseif ($file_size < 1048576) $file_size = round($file_size / 1024, 2) . ' KB';
                        else $file_size = round($file_size / 1048576, 2) . ' MB';
                        echo "<td>$file_size</td>";
                    ?>
                </tr>
                <tr>
                    <th>Дата загрузки:</th>
                    <?php echo "<td>$file_upload_date</td>";?>
                </tr>
                <tr>
                    <th>Создатель:</th>
                    <?php echo "<td>$username</td>";?>
                </tr>
            </tbody>
        </table>
        <br>
        <button class="button" onclick="window.location.href='/files/download.php?link=<?php echo $link; ?>'">Скачать файл</button>

        <?php 
            $sql = "SELECT COUNT(*) AS count_views FROM `file_activity_log` WHERE action_type = 'view' AND file_id = $file_id";
            $fileresult = mysqli_query($conn, $sql);
            $filerow = mysqli_fetch_assoc($fileresult);
            $count_views = $filerow["count_views"];
            echo "<h3>Количество посещений: $count_views</h3>"
        ?>
        <h3>Скачивания:</h3>
        <ul>
            <?php
                $sql = "SELECT * FROM `file_activity_log` WHERE action_type = 'download' AND file_id = $file_id";
                $result = mysqli_query($conn, $sql);
                while ($row = mysqli_fetch_assoc($result)) {
                    $date = $row["action_time"];
                    $user_id = $row["user_id"];
                    $sql = "SELECT username FROM `users` WHERE id = $user_id";
                    $user_result = mysqli_query($conn, $sql);
                    $user_row = mysqli_fetch_assoc($user_result);
                    $username = $user_row["username"];
                    echo "<li>$date - $username</li>";
                }
            ?>
        </ul>

    </div>
</body>
</html>