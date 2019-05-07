<?php

traverse_emls($argv[1]);
return;

function traverse_emls($path)
{
	$handle = opendir($path) or exit;
	while (($entry = readdir($handle)) !== false) {
		if (substr($entry, 0, 1) == ".") {
			continue;
		}
		$entry = $path . "/" . $entry;
		if (is_dir($entry)) {
			traverse_emls($entry);
		} elseif (substr($entry, -4) == ".eml") {
			analyze_eml($entry);
		}
	}
	closedir($handle);
}

function analyze_eml($file)
{
	$header = parse_mail($file);
	if (array_key_exists("received", $header)) {
		$id = $header["message-id"];
		if (substr($id,0,1) == "<" and substr($id,-1) == ">")
			$id = substr($id, 1, -1);
		$is_ad = is_possibly_ad($header);
		$subject = $header["subject"];
		//$subject = mb_convert_encoding($header["subject"], "UTF-8", "BIG-5");
		//$subject = iconv("BIG-5", "UTF-8", $header["subject"]);
		echo "$id, $is_ad, $subject\n";
	}
}

function parse_mail($file)
{
	$header = array();
	$fp = fopen($file, 'r');
	parse_header($fp, $header);
	//parse_content($fp, $header);
	fclose($fp);
	return $header;
}

function is_possibly_ad($header)
{
	if (array_key_exists("list-unsubscribe", $header))
		return 1;
	if (array_key_exists("precedence", $header)) {
		$value = $header["precendence"];
		if ($value == "bulk" or $value == "list")
			return 1;
	}
	return 0;
}

function parse_header(&$fp, &$data)
{
	$line = fgets($fp);
	$next = parse_attr($fp, $data, $line);
	parse_attrs_list($fp, $data, $next);
}

function parse_attr(&$fp, &$data, $start)
{
	// merge incomplete lines into $line.
	$attr = trim($start);
	$line = fgets($fp);
	while (substr($line, 0, 1) == "\t") {
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

/*
$received = array();
foreach ($data["header"]["Received"] as $key => $value) {
	$received[$key]["time"] = date("Y-m-d H:i:s", get_received_time($value));
}
print_r($received);
 */

