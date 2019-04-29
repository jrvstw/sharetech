<?php

$file = "1.eml";

$data = array();
$fp = fopen($file, 'r');
parse_mail($fp, $data);
fclose($fp);

print_r($data);

// End of main function

function parse_mail(&$fp, &$data)
{
	parse_header($fp, $data["header"]);
	parse_content($fp, $data["body"]);
}

function parse_header(&$fp, &$data)
{
	$end = parse_attr($fp, $data, null);
	parse_attrs($fp, $data, trim($end));
}

function parse_attrs(&$fp, &$data, $start)
{
	if ($start == "")
		return;
	$end = parse_attr($fp, $data, $start);
	parse_attrs($fp, $data, trim($end));
}

function parse_attr(&$fp, &$data, $start)
{
	$line = $start;
	while (trim($line) != "") {
		$data["header"][] = $line;
		$line = fgets($fp);
	}
}

function parse_content(&$fp, &$data)
{
	$line = fgets($fp);
	while (trim($line) == "") {
		$line = fgets($fp);
	}
	while (trim($line) != "</html>") {
		$data["body"][] = $line;
		$line = fgets($fp);
	}
}

