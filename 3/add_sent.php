<?php

$entry = $_POST['isbn'] . "," .
         $_POST['publisher'] . "," .
         $_POST['name'] . "," .
         $_POST['author'] . "," .
         $_POST['price'] . "," .
         $_POST['date'];
echo $entry;

if (file_put_contents('books.txt', $entry , FILE_APPEND | LOCK_EX) == true)
    echo "ok";
else
    echo "no ok";


?>
