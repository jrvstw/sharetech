<?php
$uploadfile = '/var/www/html/3/' . basename($_FILES['import']['name']);
move_uploaded_file($_FILES['import']['tmp_name'], $uploadfile);
$imported = file_get_contents($_FILES['import']['name']);
$database = file_get_contents("books.txt");
$database .= $imported;
file_put_contents('books.txt', $database , LOCK_EX);
header("Refresh:0 url='main.php'");
?>
