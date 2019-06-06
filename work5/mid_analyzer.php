<?php
include_once "../class/DatabaseAgent.php";
$mail_db = new DatabaseAgent("work5", "jarvis", "localhost", "27050888");
$table   = "mails";

$array = $mail_db->fetch_all("select * from `$table` order by `ua-value`");

/*
echo $array[0]["ua-value"] . "\n";
for ($i = 1; $i < count($array); $i++) {
	if ($array[$i]["ua-value"] != $array[$i - 1]["ua-value"]) {
		echo "\n\n" . $array[$i]["ua-value"] . "\n";
	}
	$value = trim($array[$i]["message-id"], "<>");
	echo substr($value, 0, strpos($value, '@')) . "\n";
}
 */

$count = 1;
for ($i = 1; $i < count($array); $i++) {
	if ($array[$i]["ua-value"] != $array[$i - 1]["ua-value"]) {
		$count++;
	}
}

echo $count;

