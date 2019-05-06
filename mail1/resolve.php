<?php

$file = $argv[1];

$data = array();
$fp = fopen($file, 'r');
parse_mail($fp, $data);
fclose($fp);

//print_r($data);
$received = array();
foreach ($data["header"]["Received"] as $key => $value) {
	$received[$key]["time"] = date("Y-m-d H:i:s", get_received_time($value));
}
print_r($received);

// End of main function

function parse_mail(&$fp, &$data)
{
	parse_header($fp, $data["header"]);
	parse_content($fp, $data["body"]);
}

function parse_header(&$fp, &$data)
{
	$line = fgets($fp);
	$next = parse_attr($fp, $data, $line);
	parse_attrs_list($fp, $data, $next);
}

function parse_attr(&$fp, &$data, $start)
{
	$attr = trim($start);
	$line = fgets($fp);
	while (substr($line, 0, 1) == "\t") {
		$attr .= " " . trim($line);
		$line = fgets($fp);
	}
	$delim = strpos($attr, ":");
	$key = substr($attr, 0, $delim);
	$value = substr($attr, $delim + 2);
	if ($key == "Received")
		$data[$key][] = $value;
	else
		$data[$key] = $value;
	return $line;
}

function parse_attrs_list(&$fp, &$data, $start)
{
	if (trim($start) == "")
		return;
	$next = parse_attr($fp, $data, $start);
	parse_attrs_list($fp, $data, $next);
}

function parse_content(&$fp, &$data)
{
	$line = fgets($fp);
	while (trim($line) == "")
		$line = fgets($fp);

	$content = $line;
	while (($line = fgets($fp)) != false)
		$content .= $line;
	$data[] = $content;
}

function get_received_time($received)
{
	$time_str = substr($received, strrpos($received, ";") + 1);
	$time = strtotime($time_str);
	return $time;
}

