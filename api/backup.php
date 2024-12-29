<?php
require_once('check_backup.php');
file_put_contents('../maintenance.flag', '');
function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

$sourceDir = '../filesuploaded';
$backupDir = '../filesbackup';
require_once('../db.php');
$stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action_type) VALUES (?, ?)");
$user_id = 2;
$action = "backup";
$stmt->bind_param("is", $user_id, $action);
$stmt->execute();
@rmdir($backupDir);
copyDirectory($sourceDir, $backupDir);

$backupFile = '../filesbackup/db_backup.sql';
$output = array();
$command = "mysqldump --host=$servername --user=$username --password=$password $dbname > $backupFile";
exec($command, $output, $return_var);
if($return_var !== 0) {
    print_r($output);
    echo "Ошибка при создании резервной копии базы данных $return_var.";
} else {
    echo "Резервная копия успешно создана.";
}

// Удаление флага обслуживания
unlink('../maintenance.flag');
?>
