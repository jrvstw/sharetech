<?php
include_once "parse_mail.php";
include_once "class/MailDBAgent.php";

/*
 * 1. setup
 */
$mode    = $argv[1];
$path    = $argv[2];
$pattern = '/^[0-9]{2,}$/';
$mail_db = new MailDBAgent("work5", "jarvis", "localhost", "27050888");
$table   = "mails2";
//$pattern = '/.eml$/';

/*
 * 2. fetch
 */
$output = array();
find_files($path, $pattern, $output);

/*
 * 3. output
 */
if ($mode == "p")
	print_r($output);
elseif ($mode == "w")
	$mail_db->overwrite($output, $table);
elseif ($mode == "a")
	$mail_db->append($output, $table);
else
	exit("invalid format");

return;

/*
 * functions overview
 * ------------------------------------
 * find_files($path, $pattern, &$output)
 *  |- fetch_info($file, &$output)
 * 		|- find_feature($header)
 * 		|- get_subject($subject, $charset)
 * 		|- get_charset($mail)
 */
function find_files($path, $pattern, &$output)
{
	if (is_dir($path) == false) {
		if (preg_match($pattern, basename($path)))
			fetch_info($path, $output);
	} elseif ($handle = opendir($path)) {
		while (($entry = readdir($handle)) !== false)
			if (substr($entry, 0, 1) != ".")
				find_files("$path/$entry", $pattern, $output);
		closedir($handle);
	}
}

function fetch_info($file, &$output)
{
	echo "$file: ";
	$mail = parse_mail($file);
	if (array_key_exists("x-mailer", $mail["header"])) {
		$ua = "X-mailer";
		$ua_value = $mail["header"]["x-mailer"][0];
	}
	if (array_key_exists("user-agent", $mail["header"])) {
		$ua = "User-agent";
		$ua_value = $mailer["header"]["user-agent"][0];
	}
	if (isset($ua)) {
		echo "fetching... ";
		$m_id = $mail["header"]["message-id"][0];
		if (substr($m_id,0,1) == "<" and substr($m_id,-1) == ">")
			$m_id = substr($m_id, 1, -1);

		$epaper = find_feature($mail["header"]);
		$is_epaper = (empty($epaper))? 0: 1;
		$subject = get_subject($mail["header"]["subject"][0], get_charset($mail));
		$output[] = array(
			"user-agent" => $ua,
			"ua-value" => $ua_value,
			"message-id" => $m_id,
			"epaper" => $epaper,
			"is-epaper" => $is_epaper,
			"subject" => $subject
		);
		echo "done";
	} else {
		echo "skipped";
	}
	echo "\n";
	return $output;
}

function find_feature($header)
{
	if (array_key_exists("list-unsubscribe", $header))
		return "List-Unsubscribe:";
	if (array_key_exists("precedence", $header)) {
		$value = $header["precedence"][0];
		if ($value == "bulk" or $value == "list")
			return "Precedence: " . $value;
	}
	return null;
}

function get_subject($subject, $charset)
{
	if (substr($subject, 0, 1) == "=") {
		if (substr($subject, 2, 6) == "gb2312")
			$subject = substr_replace($subject, "gbk", 2, 6);
		//return mb_decode_mimeheader($subject);
		return iconv_mime_decode($subject);
	} elseif (substr($charset, 0, 4) == "big5") {
		return iconv("BIG-5", "UTF-8", $subject);
	} else {
		return $subject;
	}
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

