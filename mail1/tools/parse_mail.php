<?php
include_once "mail_analyzing_tools.php";

function parse_mail($file)
{
	$fp = fopen($file, 'r');
	$mail = array();
	$current_line = fgets($fp);
	$escape = false;
	parse_head_and_body($fp, $mail, $current_line, $escape);
	fclose($fp);
	return $mail;
}

function parse_head_and_body(&$fp, &$data, $line, $escape)
{
	$data["header"] = array();
	$line = parse_headers($fp, $data["header"], $line);
	//$boundary = get_boundary($data["header"]);
	//return parse_body($fp, $data["body"], $line, $escape, $boundary);
}

function parse_headers(&$fp, &$data, $line)
{
	if (trim($line) == "") {
		do {
			$line = fgets($fp);
		} while (trim($line) == "" and $line != false);
		return $line;
	}
	$line = parse_header($fp, $data, $line);
	return parse_headers($fp, $data, $line);
}

function parse_header(&$fp, &$data, $line)
{
	// merge incomplete lines into $line.
	$header = trim($line);
	$line = fgets($fp);
	while (preg_match('/^\s+[^\s]/', $line)) {
		$header .= " " . trim($line);
		$line = fgets($fp);
	}
	// store data given the format $line = $data[$key]: $data["value"].
	$delim = strpos($header, ":");
	$key = strtolower(substr($header, 0, $delim));
	$value = substr($header, $delim + 2);
	$data[$key][] = $value;
	return $line;
}

function parse_body(&$fp, &$data, $line, $escape, $boundary)
{
	if ($boundary == null) {
		$content = "";
		while (escape($line, $escape) == false) {
			$content .= $line;
			$line = fgets($fp);
		}
		$data[] = $content;
	} else {
		while (trim($line) != "--$boundary" and trim($line) != "--$boundary--")
			$line = fgets($fp);
		while (trim($line) != "--$boundary--") {
			$line = fgets($fp);
			$line = parse_head_and_body($fp, $data[], $line, $boundary);
		}
	}
	while (escape($line, $escape) == false)
		$line = fgets($fp);
	return $line;


	if (empty($boundary)) {
		$content = $line;
		while (($line = fgets($fp)) != false)
			$content .= $line;
		$data[] = $content;
	} else {
		while ($line != "--$boundary")
			$line = fgets($fp);
		$line = fgets($fp);
		$body = array();
		parse_headers($fp, $body["header"]);
		parse_body($fp, $body["body"]);
		$data[] = $body;
	}
}

function escape($line, $escape)
{
	if ($escape === false)
		return ($line === false);
	elseif (trim($line) == "--$escape" or trim($line) == "--$escape--")
		return true;
	else
		return false;
}

