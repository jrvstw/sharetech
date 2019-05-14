<?php
include_once "mail_analyzing_tools.php";

function parse_mail($file)
{
	$fp = fopen($file, 'r');
	$mail = array();
	$line = fgets($fp);
	$boundary = false;
	parse_content($fp, $mail, $line, $boundary);
	fclose($fp);
	return $mail;
}

function parse_content(&$fp, &$data, $line, $boundary)
{
	$line = parse_headers($fp, $mail["header"], $line);
	$sub_boundary = get_boundary($mail["header"]);
	if ($sub_boundary == null) {
		parse_body($fp, $mail["body"], $line, $sub_boundary);
	} else {
		while (trim($line) != "--$sub_boundary")
			$line = fgets($fp);
		while (trim($line) != "--$sub_boundary--") {
			//$line = parse_body($fp, $mail["body"], $line, false);
		}
	}
	return $line;
}

function parse_headers(&$fp, &$data, $line)
{
	if (trim($line) == "") {
		do {
			$line = fgets($fp);
		} while (trim($line) == "");
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

function parse_body(&$fp, &$data, $line, $boundary)
{
	$content = "";
	while ($line !== $boundary) {
		$content .= $line;
		$line = fgets($fp);
	}

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
	return $line;
}

