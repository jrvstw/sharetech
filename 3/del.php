<?php

echo $_GET['del'];
$contents = "";
$lines = file('books.txt');
foreach ($lines as $line_num => $line)
    if ($line_num != $_GET['del'])
        $contents .= $line;

//echo $contents;
file_put_contents('books.txt', $contents);
header("Refresh:0, url='main.php'");

?>
