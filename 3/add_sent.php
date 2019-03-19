<?php

$contents = file_get_contents('books.txt');
$contents .= $_POST['isbn'] . "," .
             $_POST['publisher'] . "," .
             $_POST['name'] . "," .
             $_POST['author'] . "," .
             $_POST['price'] . "," .
             $_POST['date'] . "\n";
//$contents = preg_replace('/^$\r?\n/m', '', $contents);
//echo $contents;
file_put_contents('books.txt', $contents , LOCK_EX);
header("Refresh:0 url='main.php'");


?>
