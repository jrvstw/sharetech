<?php
include_once "parse_mail.php";
include_once "class/TableAgent.php";

$output = array();
traverse_emls($argv[1], $output);

print_r($output);
$my_table = new TableAgent("work5", "ad_mail", "jarvis", "localhost",
	"27050888");

return;

function traverse_emls($path, &$output)
{
	if (is_dir($path) == false) {
		if (substr($path, -4) == ".eml")
			deal_with_eml($path, $output);
	} elseif ($handle = opendir($path)) {
		while (($entry = readdir($handle)) !== false)
			if (substr($entry, 0, 1) != ".")
				traverse_emls("$path/$entry", $output);
		closedir($handle);
	}
}

function deal_with_eml($file, &$output)
{
	$mail = parse_mail($file);
	if (array_key_exists("received", $mail["header"])) { // testing using "received"
		$id = $mail["header"]["message-id"];
		if (substr($id,0,1) == "<" and substr($id,-1) == ">")
			$id = substr($id, 1, -1);
		$is_ad = is_possibly_ad($mail["header"]);
		$subject = $mail["header"]["subject"];
		//$subject = mb_convert_encoding($mail["header"]["subject"], "UTF-8", "BIG-5");
		//$subject = iconv("BIG-5", "UTF-8", $mail["header"]["subject"]);
		$output[] = array("id" => $id, "is_ad" => $is_ad, "subject" => $subject);
	}
	return $output;
}

function is_possibly_ad($header)
{
	if (array_key_exists("list-unsubscribe", $header))
		return 1;
	if (array_key_exists("precedence", $header)) {
		$value = $header["precedence"];
		if ($value == "bulk" or $value == "list")
			return 1;
	}
	return 0;
}

