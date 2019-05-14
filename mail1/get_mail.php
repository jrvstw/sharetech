<?php
include_once "parse_mail.php";

$path = "tmp/1.eml";
$mail = parse_mail($path);
print_r($mail);

