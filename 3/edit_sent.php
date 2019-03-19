<?php

$contents = "";
$lines = file('books.txt');
foreach ($lines as $line_num => $line) {
    if ($line_num != $_POST['edit'])
        $contents .= $line;
    else
        $contents .= $_POST['isbn'] . "," .
                     $_POST['publisher'] . "," .
                     $_POST['name'] . "," .
                     $_POST['author'] . "," .
                     $_POST['price'] . "," .
                     $_POST['date'] .  "\n";
}
//echo $contents;
file_put_contents('books.txt', $contents, LOCK_EX);
header("Refresh:0 url='main.php'");

?>
