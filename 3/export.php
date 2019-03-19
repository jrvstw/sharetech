<?php

$contents = file_get_contents('sorted.txt');
header("Content-type: application/text");
header("Content-Disposition: attachment; filename=export.txt");
echo $contents;
?>
