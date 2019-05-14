<?php
include_once "parse_mail.php";
include_once "class/MailDBAgent.php";
include_once "mail_analyzing_tools.php";

/*
 * 1. setup
 */
$mode    = $argv[1]; // [p|w|a]
$path    = $argv[2];
//$pattern = '/.eml$/';
$pattern = '/^[0-9]{2,}$/';
$mail_db = new MailDBAgent("work5", "jarvis", "localhost", "27050888");
$table   = "mails";


/*
 * 2. fetch
 */
$output = array();
parse_dir($path, $pattern, $output);


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
 * parse_dir($path, $pattern, &$output)
 *  |- collect_info($file, &$output)
 * 		|- verify_epaper($header)
 */
function parse_dir($path, $pattern, &$output)
{
	if (is_dir($path) == false) {
		if (preg_match($pattern, basename($path)))
			collect_info($path, $output);
	} elseif ($handle = opendir($path)) {
		while (($entry = readdir($handle)) !== false)
			if (substr($entry, 0, 1) != ".")
				parse_dir("$path/$entry", $pattern, $output);
		closedir($handle);
	}
}

function collect_info($file, &$output)
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
		$m_id = trim($mail["header"]["message-id"][0], "<>");
		$epaper = verify_epaper($mail["header"]);
		$is_epaper = (empty($epaper))? 0: 1;
		$subject = decode($mail["header"]["subject"][0], get_charset($mail["header"]));
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

function verify_epaper($header)
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

