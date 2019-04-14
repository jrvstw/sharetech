<?php
header("Content-Type:text/html; charset=utf-8");
include_once("fetch_table.php");

$command = "/usr/sbin/route -n";
$offset = 1;
$length = null;
$table = get_table($command, $offset, $length);

include("xhtml/showroute.html");

