<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FX - Авторизация</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 350px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="text"], input[type="password"] {
            width: 91%;
            padding: 15px;
            margin: 5px 0 20px 0;
            border: none;
            background: #eee;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            background: #e8e8e8;
        }

        input[type="submit"] {
            background-color: #008CBA;
            color: white;
            padding: 15px 20px;
            border: none;
            cursor: pointer;
            width: 100%;
            opacity: 0.9;
        }

        input[type="submit"]:hover {
            opacity: 1;
        }

        a {
            color: #008CBA;
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>FX - Авторизация</h2>
        <?php
        require_once('check_backup.php');
        session_start();
        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] == true) {
            if ($_SESSION["ID"] == 2) {
                header("Location: ../admin");
                exit();
            } else {
                header("Location: ../userpage");
                exit();
            }
        }
        if (isset($_SESSION['error_message'])) {
            echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
            unset($_SESSION['error_message']);
        }
        ?>
        <form method="POST" action="">
            <div>
                <label for="username">Имя пользователя:</label>
                <input type="text" id="username" name="username" placeholder="Введите имя пользователя">
            </div>
            <div>
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" placeholder="Введите пароль">
            </div>
            <input type="submit" value="Войти">
        </form>
    </div>
</body>
</html>
<?php
    require_once('../db.php');
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!empty($_POST["username"]) && !empty($_POST["password"])) {
            $username = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['username'])));
            $password = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['password'])));
            $sql = "SELECT * FROM `users` WHERE username = '$username'";
            $result = mysqli_query($conn, $sql);
            if ($result->num_rows > 0) {
                $row = mysqli_fetch_assoc($result);
                if (password_verify($password, $row['password'])) {
                    session_start();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    $_SESSION['ID'] = $row['id'];
                    $action = "login";
                    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
                    $stmt->bind_param("is", $_SESSION["ID"], $action);
                    $stmt->execute();
                    if ($_SESSION['ID'] == 2) {
                        header("Location: ../admin");
                        exit();
                    }
                    else {
                        header("Location: ../userpage");
                        exit();
                    }
                } else {
                    session_start();
                    $_SESSION['error_message'] = "Неправильное имя пользователя или пароль";
                    header("Location: {$_SERVER['PHP_SELF']}");
                    exit();
                }
            }
            else {
                session_start();
                $_SESSION['error_message'] = "Неправильное имя пользователя или пароль";
                header("Location: {$_SERVER['PHP_SELF']}");
                exit();
            } 
        } else {
            session_start();
            $_SESSION['error_message'] = "Введите данные для авторизации";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
    } 
?>