<?php

/*
 * uploads files.
 */
$uploadfile = '/var/www/html/3/' . basename($_FILES['import']['name']);
move_uploaded_file($_FILES['import']['tmp_name'], $uploadfile);
//$imported = file_get_contents($_FILES['import']['name']);

/*
 * checks format
 */
$pattern ='/^[^,]+(,[^,]+){5}$/';
$checkformat = true;
$imported = "";
$lines = file($_FILES['import']['name']);
foreach ($lines as $line_num => $line) {
    if (preg_match('/.+/', $line) == false)
        continue;
    $tmp = str_getcsv($line, ",");
    if (preg_match('/^[^,]+(,[^,]+){5}$/', $line) and
        preg_match("/^([0-9]{3}-){3}[0-9]$/", $tmp[0]) and
        preg_match("/^[^,]+$/", $tmp[1]) and
        preg_match("/^[^,]+$/", $tmp[2]) and
        preg_match("/^[^,]+$/", $tmp[3]) and
        preg_match("/^[0-9]+$/", $tmp[4]) and
        preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/", $tmp[5]) and
        is_numeric($tmp[4]) and
        strtotime($tmp[5]))
        $imported .= $line;
    else
        $checkformat = false;
}
$imported .= "\n";
$imported = preg_replace('/[\n]+$/', "\n", $imported);

/*
 * imports if the format is correct
 */
if ($checkformat == true) {
    //echo $database;
    $database = file_get_contents("books.txt") . $imported;
    file_put_contents('books.txt', $database , LOCK_EX);
    header("Refresh:0 url='main.php'");
} else
    die("Import failed: format error.");
?>
