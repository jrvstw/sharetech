<?php

header("Content-Type:text/html; charset=utf-8");

include("COLUMNS.php");
include("class/MyTable.php");
$bookTable = new MyTable();

include("xhtml/home.html");

