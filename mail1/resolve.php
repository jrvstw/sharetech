<?php
include_once "parse_mail.php";
include_once "class/MailDBAgent.php";

$type = ".eml";
$path = $argv[1];
$action = 'fetch_info';
$my_table = new MailDBAgent("work5", "mails", "jarvis", "localhost", "27050888");

$output = array();
find_files($path, $action, $output);

/*
$filter = array(
	'message-id' => '%sharetech%',
	'subject' => '%randoll%',
);
 */
$output = $my_table->filter($filter);
print_r($output);
//$my_table->overwrite($output);

return;

function fetch_info($file, &$output)
{
	$mail = parse_mail($file);
	if (array_key_exists("x-mailer", $mail["header"]))
		$mailer = "X-mailer: " . $mail["header"]["x-mailer"][0];
	if (array_key_exists("user-agent", $mail["header"]))
		$mailer = "User-agent: " . $mail["header"]["user-agent"][0];
	if (isset($mailer)) {
		$mid = $mail["header"]["message-id"][0];
		if (substr($mid,0,1) == "<" and substr($mid,-1) == ">")
			$mid = substr($mid, 1, -1);

		$epaper = find_feature($mail["header"]);
		$subject = get_subject($mail["header"]["subject"][0], get_charset($mail));
		$output[] = array(
			//"file" => $file,
			"user-agent" => $mailer,
			"message-id" => $mid,
			"epaper" => $epaper,
			"subject" => $subject
		);
	}
	return $output;
}

function find_files($path, $action, &$output)
{
	if (is_dir($path) == false) {
		if (substr($path, -4) == ".eml")
			$action($path, $output);
	} elseif ($handle = opendir($path)) {
		while (($entry = readdir($handle)) !== false)
			if (substr($entry, 0, 1) != ".")
				find_files("$path/$entry", $action, $output);
		closedir($handle);
	}
}

function find_feature($header)
{
	if (array_key_exists("list-unsubscribe", $header))
		return "List-Unsubscribe:";
	if (array_key_exists("precedence", $header)) {
		if ($value == "bulk" or $value == "list")
			return "Precedence: " . $header["precedence"][0];
	}
	return null;
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

