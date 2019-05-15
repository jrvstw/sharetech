<?php
include_once "tools/parse_mail.php";

$path = $argv[1];
$mail = parse_mail($path);
print_r($mail);

