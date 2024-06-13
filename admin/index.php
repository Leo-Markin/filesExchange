<?php
    require_once('../check_backup.php');
    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == false) {
        header("Location: ../login");
        exit();
    }
    if ($_SESSION["ID"] != 2) {
        header("Location: ../userpage");
        exit();
    }
    require_once('../db.php');
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['file_id'])) {
        $file_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['file_id'])));
        $sql = "SELECT file_url FROM files WHERE id=$file_id";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if ($_SESSION["ID"] == 2) {
                $link = $row["file_url"];
                $sql = "DELETE FROM files WHERE id=$file_id";
                mysqli_query($conn, $sql);
                unlink("../filesuploaded/$link");
                $action = "delete";
                $stmt = $conn->prepare("INSERT INTO file_activity_log (file_id, user_id, action_type) VALUES (?, ?, ?)");
                $stmt->bind_param("iis", $file_id, $_SESSION["ID"], $action);
                $stmt->execute();
                header("Location: {$_SERVER['PHP_SELF']}");
                exit();
            } else {
                $_SESSION['error_message'] = "Ошибка удаления.";
                header("Location: {$_SERVER['PHP_SELF']}");
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Ошибка удаления.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete_user_id'])) {
        $user_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['delete_user_id'])));
        if ($user_id == 2) {
            $_SESSION['error_message'] = "Этого пользователя нельзя удалить.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
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
                $stmt->bind_param("iis", $file_id, $_SESSION["ID"], $action);
                $stmt->execute();
            }
        }
        $sql = "DELETE FROM `users` WHERE id=$user_id";
        mysqli_query($conn, $sql);
        $action = "delete";
        $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $action);
        $stmt->execute();
        header("Location: {$_SERVER['PHP_SELF']}");
        exit();
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["newUsername"]) && !empty($_POST["newPassword"])) {
        $username = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['newUsername'])));
        $password = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['newPassword'])));
        if (is_null($password)) {
            $_SESSION['error_message'] = "Ошибка создания пользователя.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
        $sql = "SELECT * FROM `users` WHERE username = '$username'";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $_SESSION['error_message'] = "Пользователь с таким именем уже существует.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
        $password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO `users` (username, `password`) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $password);
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $action = "create";
            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $action);
            $stmt->execute();
            header("Location: /admin");
            exit();
        } else {
            $_SESSION['error_message'] = "Ошибка создания пользователя";
            header("Location: /admin");
            exit();
        }
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["ChangePassword"]) && !empty($_POST["userId"])) {
        $user_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['userId'])));
        if ($user_id == 2) {
            $_SESSION['error_message'] = "Этому пользователю нельзя изменить пароль.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
        $password = mysqli_real_escape_string($conn, htmlspecialchars(trim($_POST['ChangePassword'])));
        if (is_null($password)) {
            $_SESSION['error_message'] = "Ошибка изменения пароля.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
        $password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        $stmt->bind_param("si", $password, $user_id);
        if ($stmt->execute()) {
            $action = "changepassword";
            $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $action);
            $stmt->execute();
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        } else {
            $_SESSION['error_message'] = "Ошибка изменения пароля.";
            header("Location: {$_SERVER['PHP_SELF']}");
            exit();
        }
    }
?>
<!DOCTYPE html>
<html>
<head>
    <title>FX - Панель администратора</title>
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
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
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

        .delete-button {
            background-color: #dc3545;
        }

        .delete-button:hover {
            background-color: #c82333;
        }

        .user-info {
            text-align: right;
            margin-bottom: 20px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }

        #createUserForm {
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        #createUserForm h3 {
            margin-top: 0;
        }

        #createUserForm label {
            display: block;
            margin: 10px 0 5px;
        }

        #createUserForm input[type="text"], #createUserForm input[type="password"], #password-form input[type="password"]{
            width: calc(100% - 22px);
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            box-sizing: border-box;
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

        <div style="display: inline-block; margin-left: 20px;">
            <form action="../backup.php">
            <button class="button delete-button" type="submit">Резервное копирование данных</button>
            </form>
            <?php
                $sql = "SELECT action_time FROM user_activity_log WHERE action_type = 'backup' ORDER BY action_time DESC LIMIT 1";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                $lastBackupTime = $row['action_time'];
                echo "<p>Последнее резервное копирование: $lastBackupTime</p>";
            ?>
        </div>
        
        <h2>FX - Панель администратора</h2>
        <?php
            if (isset($_SESSION['error_message'])) {
                echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
                unset($_SESSION['error_message']);
            }
        ?>
        <h3>Файлы</h3>
        <input type="text" id="searchInput" placeholder="Поиск по имени файла">
        <table id="filesTable">
            <thead>
                <tr>
                    <th onclick="sortTable(0, 'filesTable')">Файл <span id="file-sort-icon">▲▼</span></th>
                    <th onclick="sortTable(1, 'filesTable')">Размер <span id="size-sort-icon">▲▼</span></th>
                    <th onclick="sortTable(2, 'filesTable')">Дата загрузки <span id="date-sort-icon">▲▼</span></th>
                    <th onclick="sortTable(3, 'filesTable')">Создатель <span id="creator-sort-icon">▲▼</span></th>
                    <th>Переходы</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT * FROM `files`";
                    $result = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $file_name = $row["file_name"];
                            $file_size = $row["file_size"];
                            $file_id = $row["id"];
                            $owner_id = $row["owner_id"];
                            if ($file_size < 1024) $file_size = $file_size . ' B';
                            elseif ($file_size < 1048576) $file_size = round($file_size / 1024, 2) . ' KB';
                            else $file_size = round($file_size / 1048576, 2) . ' MB';
                            $file_url = '/files/' . $row["file_url"];
                            $file_id = $row["id"];
                            $file_upload_date = $row["upload_date"];
                            $sql = "SELECT username FROM `users` WHERE id = $owner_id";
                            $fileresult = mysqli_query($conn, $sql);
                            $filerow = mysqli_fetch_assoc($fileresult);
                            $file_username = $filerow["username"];
                            $sql = "SELECT COUNT(*) AS count_views FROM `file_activity_log` WHERE action_type = 'view' AND file_id = $file_id";
                            $fileresult = mysqli_query($conn, $sql);
                            $filerow = mysqli_fetch_assoc($fileresult);
                            $count_views = $filerow["count_views"];
                            echo "<tr><td><a href='$file_url'>$file_name</a></td>";
                            echo "<td>$file_size</td>";
                            echo "<td>$file_upload_date</td>";
                            echo "<td>$file_username</td>";
                            echo "<td>$count_views</td>";
                            echo "<td><button onclick='confirmDeleteFile($file_id)' class='button delete-button'>Удалить</button></td></tr>";
                        }
                    }
                ?>
            </tbody>
        </table>

        <br>

        <h3>Пользователи</h3>
        <input type="text" id="userSearchInput" placeholder="Поиск по имени пользователя">
        <table id="usersTable">
            <thead>
                <tr>
                <th onclick="sortTable(0, 'usersTable')">Пользователь <span id="user-sort-icon">▲▼</span></th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sql = "SELECT * from `users`";
                    $result = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $user_id = $row["id"];
                            if ($user_id == 2) continue;
                            $username = $row["username"];
                            echo "<tr><td>$username</td>";
                            echo "<td><button class='button change-password' data-user-id='$user_id'>Изменить пароль</button> ";
                            echo "<button onclick='confirmDeleteUser($user_id)' class='button delete-button'>Удалить</button>";
                            echo "<form class='password-form' id='password-form' data-user-id='$user_id' method='POST' action='' style='display:none;'>
                                    <input type='hidden' name='userId' value='$user_id'>
                                    <input type='password' name='ChangePassword' placeholder='Новый пароль' required>
                                    <button type='submit' class='button'>Подтвердить</button>
                                    <button type='button' class='button cancel-button delete-button'>Отмена</button>
                                    </form></td></tr>";
                        }
                    }
                ?>
            </tbody>
        </table>
        <br>
        <form id="createUserForm" style="display: none;" method="POST" action="">
            <h3>Создать нового пользователя</h3>
            <label for="newUsername">Имя пользователя:</label>
            <input type="text" id="newUsername" name="newUsername" placeholder="Введите имя пользователя">
            <label for="newPassword">Пароль:</label>
            <input type="password" id="newPassword" name="newPassword" placeholder="Введите пароль">
            <button type="submit" class="button">Подтвердить</button>
        </form>
        <br>
        <button class="button create-user" id="button_create_user">Создать пользователя </button>
        <button class="button delete-button" id="button_cancel" onclick="cancelCreateUser()" style="display:none">Отмена </button>
    </div>
    <script>
        const searchInput = document.getElementById('searchInput');
        const filesTable = document.getElementById('filesTable');
        let fileRows = Array.from(filesTable.querySelectorAll('tbody tr'));
        let sortOrder = { columnIndex: -1, ascending: true };

        searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            fileRows.forEach(row => {
                const fileNameCell = row.cells[0];
                const fileName = fileNameCell.textContent.toLowerCase();
                if (fileName.includes(searchText)) row.style.display = '';
                else row.style.display = 'none';
            });
        });

        function sortTable(columnIndex, tableId) {
            const table = document.getElementById(tableId);
            const tbody = table.querySelector('tbody');
            let rows = Array.from(tbody.querySelectorAll('tr'));
            const icons = {
                'filesTable': ['file-sort-icon', 'size-sort-icon', 'date-sort-icon', 'creator-sort-icon'],
                'usersTable': ['user-sort-icon']
            };
            const icon = document.getElementById(icons[tableId][columnIndex]);
            if (sortOrder.columnIndex === columnIndex) {
                sortOrder.ascending = !sortOrder.ascending;
            } else {
                sortOrder.columnIndex = columnIndex;
                sortOrder.ascending = true;
            }
            let sortedRows = rows.sort((a, b) => {
                let aText = a.cells[columnIndex].textContent.trim();
                let bText = b.cells[columnIndex].textContent.trim();
                if (tableId === 'filesTable' && columnIndex === 1) {
                    aText = parseFileSize(aText);
                    bText = parseFileSize(bText);
                } else if (tableId === 'filesTable' && columnIndex === 2) {
                    aText = new Date(aText);
                    bText = new Date(bText);
                } 
                if (aText > bText) return sortOrder.ascending ? 1 : -1;
                if (aText < bText) return sortOrder.ascending ? -1 : 1;
                return 0;
            });
            tbody.innerHTML = '';
            sortedRows.forEach(row => tbody.appendChild(row));
            icons[tableId].forEach(id => {
                const element = document.getElementById(id);
                if (id === icon.id) {
                    element.textContent = sortOrder.ascending ? '▲' : '▼';
                } else {
                    element.textContent = '▲▼';
                }
            });
        }

        function parseFileSize(sizeText) {
            const units = { 'B': 1, 'KB': 1024, 'MB': 1048576 };
            const unit = sizeText.match(/[A-Za-z]+/)[0];
            const size = parseFloat(sizeText.replace(unit, '').trim());
            return size * units[unit];
        }

        document.querySelector('.button.create-user').addEventListener('click', function() {
            document.getElementById('createUserForm').style.display = 'block';
            document.getElementById('button_cancel').style.display = 'block';
            document.getElementById('button_create_user').style.display = 'none';
        });

        function cancelCreateUser() {
            document.getElementById('createUserForm').style.display = 'none';
            document.getElementById('button_create_user').style.display = 'block';
            document.getElementById('button_cancel').style.display = 'none';
            document.getElementById('newUsername').value = '';
            document.getElementById('newPassword').value = '';
        }

        document.querySelectorAll('.button.change-password').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                document.querySelector(`form[data-user-id="${userId}"]`).style.display = 'block';
                this.style.display = 'none';
                this.nextElementSibling.style.display = 'none';
            });
        });

        document.querySelectorAll('.password-form .cancel-button').forEach(button => {
            button.addEventListener('click', function() {
                const form = this.closest('.password-form');
                const userId = form.getAttribute('data-user-id');
                form.style.display = 'none';
                form.previousElementSibling.style.display = 'inline-block';
                form.previousElementSibling.previousElementSibling.style.display = 'inline-block';
            });
        });

        function confirmDeleteUser(userId) {
            if (confirm('Вы уверены, что хотите удалить этого пользователя? Удалятся и все файлы этого пользователя.')) {
                var form = document.createElement("form");
                form.method = "GET";
                form.action = "";
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "delete_user_id";
                input.value = userId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function confirmDeleteFile(fileId) {
            if (confirm('Вы уверены, что хотите удалить этот файл?')) {
                var form = document.createElement("form");
                form.method = "GET";
                form.action = "";
                var input = document.createElement("input");
                input.type = "hidden";
                input.name = "file_id";
                input.value = fileId;
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        const userSearchInput = document.getElementById('userSearchInput');
        const usersTable = document.getElementById('usersTable');
        let userRows = Array.from(usersTable.querySelectorAll('tbody tr'));

        userSearchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            userRows.forEach(row => {
                const userNameCell = row.cells[0];
                const userName = userNameCell.textContent.toLowerCase();
                if (userName.includes(searchText)) row.style.display = '';
                else row.style.display = 'none';
            });
        });
    </script>
</body>
</html>