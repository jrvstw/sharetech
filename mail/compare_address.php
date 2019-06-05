<?php

include_once 'class/DatabaseAgent.php';

$agent = new DatabaseAgent('work6', 'jarvis', 'localhost', '27050888');

$data = $agent->fetch_all('select id, `message-id`, `from` from mails');

foreach ($data as $key => $row) {
	$r_mid = strrev($row["message-id"]);
	$r_from = strrev($row["from"]);
	$ptr = 0;
	while (substr($r_mid, $ptr, 1) == substr($r_from, $ptr, 1))
		$ptr++;
	//$data[$key]["message-id"] = substr($r_mid, 0, $ptr);
	//$data[$key]["from"] = substr($r_from, 0, $ptr);
	if ($ptr == 0) {
		$data[$key]["match"] = "no";
		continue;
	}
	if (strpos(substr($r_mid, 0, $ptr), '@') != false) {
		$data[$key]["match"] = "perfect";
		continue;
	}
	if (preg_match_all('/\./', substr($r_mid, 0, $ptr)) < 2) {
		$data[$key]["match"] = "no";
		continue;
	}
	$pattern = '/^\.?[^\.]+$/';
	$str1 = substr($r_mid, $ptr, strpos($r_mid, '@') - $ptr);
	$str2 = substr($r_from, $ptr, strpos($r_from, '@') - $ptr);
	if (preg_match($pattern, $str1) and preg_match($pattern, $str2))
		$data[$key]["match"] = "partial";
	else
		$data[$key]["match"] = "no";
}

	$agent->open();
foreach ($data as $key => $row) {
	//print_r($row);
	$agent->query("update classification set `match-type` = '" . $row["match"] . "' where `id` = " . $row["id"]);
}
	$agent->close();

//print_r($data);

