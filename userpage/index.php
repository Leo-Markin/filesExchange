<?php
    require_once('../check_backup.php');
    session_start();
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] == false) {
        header("Location: ../login");
        exit();
    }
    if ($_SESSION["ID"] == 2) {
        header("Location: ../admin");
        exit();
    }
    require_once('../db.php');
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['file_id'])) {
        $file_id = mysqli_real_escape_string($conn, htmlspecialchars(trim($_GET['file_id'])));
        $id = $_SESSION["ID"];
        $sql = "SELECT owner_id, file_url FROM files WHERE id=$file_id";
        $result = mysqli_query($conn, $sql);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if ($id == $row["owner_id"]) {
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>FX - Мои файлы</title>
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

        .progress-bar-container {
            width: 100%;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
            height: 20px;
            display: none;
        }

        .progress-bar {
            width: 0;
            height: 100%;
            background-color: #007bff;
            border-radius: 4px;
        }

        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            color: #007bff;
            margin-top: 20px;
            cursor: pointer;
        }

        .drop-zone.dragover {
            background-color: #e0e0e0;
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
        <h2>FX - Мои файлы</h2>
        <input type="text" id="searchInput" placeholder="Поиск по имени файла">
        <table>
            <thead>
                <tr>
                    <th onclick="sortTable(0)">Файл <span id="file-sort-icon">▲▼</span></th>
                    <th onclick="sortTable(1)">Размер <span id="size-sort-icon">▲▼</span></th>
                    <th onclick="sortTable(2)">Дата загрузки <span id="date-sort-icon">▲▼</span></th>
                    <th>Ссылка</th>
                    <th>Переходы</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $id = $_SESSION['ID'];
                    $sql = "SELECT * FROM `files` WHERE owner_id = '$id'";
                    $result = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            $file_name = $row["file_name"];
                            $file_size = $row["file_size"];
                            $file_id = $row["id"];
                            if ($file_size < 1024) $file_size = $file_size . ' B';
                            elseif ($file_size < 1048576) $file_size = round($file_size / 1024, 2) . ' KB';
                            else $file_size = round($file_size / 1048576, 2) . ' MB';
                            $file_url = '/files/' . $row["file_url"];
                            $file_id = $row["id"];
                            $file_upload_date = $row["upload_date"];
                            $sql = "SELECT COUNT(*) AS count_views FROM `file_activity_log` WHERE action_type = 'view' AND file_id = $file_id";
                            $fileresult = mysqli_query($conn, $sql);
                            $filerow = mysqli_fetch_assoc($fileresult);
                            $count_views = $filerow["count_views"];
                            echo "<tr><td><a href='$file_url'>$file_name</a></td>";
                            echo "<td>$file_size</td>";
                            echo "<td>$file_upload_date</td>";
                            echo "<td><button class='button' onclick='window.navigator.clipboard.writeText(\"direct-capital-scorpion.ngrok-free.app$file_url\")'>Скопировать</button></td>";
                            echo "<td>$count_views</td>";
                            echo "<td><button onclick='confirmDeleteFile($file_id)' class='button delete-button'>Удалить</button></td></tr>";
                        }
                    }
                ?>
            </tbody>
        </table>
        <br>
        <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
            <input type="file" id="fileToUpload" name="fileToUpload" style="display:none">
        </form>
        <div class="drop-zone" id="dropZone">
            Перетащите загружаемый файл сюда или нажмите для выбора
        </div>
        <div class="progress-bar-container" id="progressBarContainer">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <?php
            if (isset($_SESSION['error_message'])) {
                echo '<p class="error">' . $_SESSION['error_message'] . '</p>';
                unset($_SESSION['error_message']);
            }
        ?>
    </div>
    <script>
        const MAX_FILE_SIZE = 1024 * 1048576;
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('fileToUpload');
        const progressBarContainer = document.getElementById('progressBarContainer');
        const progressBar = document.getElementById('progressBar');
        const dropZone = document.getElementById('dropZone');
        const searchInput = document.getElementById('searchInput');
        let tableRows = Array.from(document.querySelectorAll('tbody tr'));
        let sortOrder = { columnIndex: -1, ascending: true };

        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        dropZone.addEventListener('dragover', (event) => {
            event.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (event) => {
            event.preventDefault();
            dropZone.classList.remove('dragover');
            const files = event.dataTransfer.files;
            if (files.length) {
                handleFileUpload(files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                handleFileUpload(this.files[0]);
            }
        });

        function handleFileUpload(file) {
            if (file.size > MAX_FILE_SIZE) {
                alert("Файл слишком велик.");
                fileInput.value = "";
            } else {
                const formData = new FormData(form);
                formData.append('fileToUpload', file);
                const xhr = new XMLHttpRequest();
                xhr.open("POST", form.action, true);
                xhr.upload.onprogress = function(event) {
                    if (event.lengthComputable) {
                        const percentComplete = (event.loaded / event.total) * 100;
                        progressBar.style.width = percentComplete + '%';
                    }
                };
                xhr.onloadstart = function() {
                    progressBarContainer.style.display = 'block';
                };
                xhr.onloadend = function() {
                    progressBarContainer.style.display = 'none';
                    window.location.reload();
                };
                xhr.send(formData);
            }
        }

        searchInput.addEventListener('input', function() {
            const searchText = this.value.toLowerCase();
            tableRows.forEach(row => {
                const fileNameCell = row.cells[0];
                const fileName = fileNameCell.textContent.toLowerCase();
                if (fileName.includes(searchText)) row.style.display = '';
                else row.style.display = 'none';
            });
        });

        function sortTable(columnIndex) {
            const tbody = document.querySelector('tbody');
            const icons = ['file-sort-icon', 'size-sort-icon', 'date-sort-icon'];
            const icon = document.getElementById(icons[columnIndex]);
            if (sortOrder.columnIndex === columnIndex) {
                sortOrder.ascending = !sortOrder.ascending;
            } else {
                sortOrder.columnIndex = columnIndex;
                sortOrder.ascending = true;
            }
            let sortedRows = tableRows.sort((a, b) => {
                let aText = a.cells[columnIndex].textContent.trim();
                let bText = b.cells[columnIndex].textContent.trim();
                if (columnIndex === 1) {
                    aText = parseFileSize(aText);
                    bText = parseFileSize(bText);
                } else if (columnIndex === 2) {
                    aText = new Date(aText);
                    bText = new Date(bText);
                } 
                if (aText > bText) return sortOrder.ascending ? 1 : -1;
                if (aText < bText) return sortOrder.ascending ? -1 : 1;
                return 0;
            });
            tbody.innerHTML = '';
            sortedRows.forEach(row => tbody.appendChild(row));
            icons.forEach(id => {
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
    </script>

</body>
</html>