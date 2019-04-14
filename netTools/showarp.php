<?php
header("Content-Type:text/html; charset=utf-8");
include_once("fetch_table.php");

$command = "/usr/sbin/arp -n";
$offset = 0;
$length = null;
$table = get_table($command, $offset, $length);

$show_column = array(0, 2, 5);
$table = table_column_filter($table, $show_column);

include("xhtml/showarp.html");

