<?php

header("Content-Type:text/html; charset=utf-8");

include("COLUMNS.php");
include("class/MyTable.php");
$bookTable = new MyTable("books");

/*
 * validates parameters: sort, order, page.
 */
$sortOption = array(
	""				,
	"default"		,
	"isbnasc"		,
	"isbndsc"		,
	"publisherasc"	,
	"publisherdsc"	,
	"nameasc"		,
	"namedsc"		,
	"authorasc"		,
	"authordsc"		,
	"priceasc"		,
	"pricedsc"		,
	"dateasc"		,
	"datedsc"		);

if (in_array($_GET['sortOrder'], $sortOption) == false)
	die("Sort option invalid");

if ( $_GET['page'] != "" and
	preg_match('/^[1-9][0-9]*$/', $_GET['page']) == false)
	die("Invalid input \"page\" = " . $_GET['page']);

if ($bookTable->get_pages() < $_GET['page'])
	die("Page exceeded range");

/*
 * runs homepage.
 */
include("xhtml/home.html");

