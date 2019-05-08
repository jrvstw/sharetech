<?php
include_once "parse_mail.php";
include_once "class/TableAgent.php";

$type = ".eml";
$path = $argv[1];
$method = 'fetch_info';

$output = array();
find_files($path, $method, $output);

print_r($output);
/*
$my_table = new TableAgent("work5", "ad_mail", "jarvis", "localhost", "27050888");
foreach ($output as $entry)
	$my_table->add_entry($entry);
 */

return;

function fetch_info($file, &$output)
{
	$mail = parse_mail($file);
	if (array_key_exists("received", $mail["header"])) { // testing using "received"
		$id = $mail["header"]["message-id"][0];
		if (substr($id,0,1) == "<" and substr($id,-1) == ">")
			$id = substr($id, 1, -1);

		// start point
		$is_ad = is_possibly_ad($mail["header"]);
		$subject = get_subject($mail["header"]["subject"][0], get_charset($mail));
		$output[] = array("message_id" => $id, "is_ad" => $is_ad, "subject" => $subject);
	}
	return $output;
}

function find_files($path, $method, &$output)
{
	if (is_dir($path) == false) {
		if (substr($path, -4) == ".eml")
			$method($path, $output);
	} elseif ($handle = opendir($path)) {
		while (($entry = readdir($handle)) !== false)
			if (substr($entry, 0, 1) != ".")
				find_files("$path/$entry", $method, $output);
		closedir($handle);
	}
}

function is_possibly_ad($header)
{
	if (array_key_exists("list-unsubscribe", $header))
		return 1;
	if (array_key_exists("precedence", $header)) {
		$value = $header["precedence"][0];
		if ($value == "bulk" or $value == "list")
			return 1;
	}
	return 0;
}

function get_subject($subject, $charset)
{
	if (substr($subject, 0, 1) == "=")
		return iconv_mime_decode($subject);

	if (substr($charset, 0, 4) == "big5")
		return iconv("BIG-5", "UTF-8", $subject);

	return $subject;
}

function get_charset($mail)
{
	$value = $mail["header"]["content-type"][0];
	if (empty($value))
		return "";
	if (($ptr = strpos($value, "charset=")) === false)
		return "";
	return strtolower(substr($value, $ptr + strlen("charset=")));
}

