<?php

function parse_mail($file)
{
	$mail = array();
	$fp = fopen($file, 'r');
	parse_header($fp, $mail["header"]);
	//parse_body($fp, $mail["body"]);
	fclose($fp);
	return $mail;
}

function parse_header(&$fp, &$data)
{
	$line = fgets($fp);
	$next = parse_attr($fp, $data, $line);
	parse_attrs_list($fp, $data, $next);
}

/*
function parse_body(&$fp, &$data)
{
	$line = fgets($fp);
	while (trim($line) == "")
		$line = fgets($fp);

	$content = $line;
	while (($line = fgets($fp)) != false)
		$content .= $line;
	$data[] = $content;
}
 */

function parse_attr(&$fp, &$data, $start)
{
	// merge incomplete lines into $line.
	$attr = trim($start);
	$line = fgets($fp);
	while (preg_match('/^\s+/', $line)) {
		$attr .= " " . trim($line);
		$line = fgets($fp);
	}
	// store data given the format $line = $data[$key]: $data["value"].
	$delim = strpos($attr, ":");
	$key = strtolower(substr($attr, 0, $delim));
	$value = substr($attr, $delim + 2);
	if ($key == "received")
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

function get_received_time($received)
{
	$time_str = substr($received, strrpos($received, ";") + 1);
	$time = strtotime($time_str);
	return $time;
}

/*
$received = array();
foreach ($data["header"]["Received"] as $key => $value) {
	$received[$key]["time"] = date("Y-m-d H:i:s", get_received_time($value));
}
print_r($received);
 */

