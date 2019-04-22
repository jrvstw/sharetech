<?php
//#!/PGRAM/php/bin/php -q
//<?

$file_site = "http://192.168.195.151/malware/statistics.php";
$url_site = "http://192.168.195.151/malware/statistics_url.php";
$output_location = "/var/www/html/sharetech/last_review/statuslog/remote.txt";

/*
$stat_file = get_statistics($file_site);
$stat_url = get_statistics($url_site);

$output = my_export($stat_file) . "\n" . my_export($stat_url);
 */
$output = "time,total,version,pkg1a,pkg1b,pkg1c,pkg2a,pkg2b,pkg2c," . "\n" .
	"time2,total2,version2,pkg3a,pkg3b,pkg3c,pkg4a,pkg4b,pkg4c,";
write($output, $output_location);

/*
 * End of main function
 */

function get_statistics($site)
{
	$ptr = 0;
	$content = file_get_contents($site);
	$content = parse_and_grab($content, $ptr, '<table class="malwareTable">', '</table>');

	$ptr = strpos($content, '最後更新時間');
	$output["last_update"] = parse_and_grab($content, $ptr, "<td>", "</td>");

	$ptr = strpos($content, '總共', $ptr);
	//$output["total_entry"] = parse_and_grab($content, $ptr, "<td>", "</td>");
	$output["total_entry"] = parse_and_grab($content, $ptr, "<td>", " ");

	$ptr = strpos($content, '最新版本', $ptr);
	$output["newest_version"] = parse_and_grab($content, $ptr, "<td>", "</td>");

	$ptr = strpos($content, '更新狀態', $ptr);
	$ptr = strpos($content, "<tr>", $ptr);
	for ($i = 0; $i < 20; $i++) {
		if ($ptr == false)
			break;
		$package = parse_and_grab($content, $ptr, "<td>", "</td>");
		$ptr = strpos($content, "[", $ptr);
		$tmp = parse_and_grab($content, $ptr, ">", "<");
		$output["update"][$package][] = str_replace("&nbsp;", "", $tmp);
		$ptr = strpos($content, "]", $ptr);
		$output["update"][$package][] = parse_and_grab($content, $ptr, ";", "&");
		$output["update"][$package][] = parse_and_grab($content, $ptr, ":", "<");
		$ptr = strpos($content, "<tr>", $ptr);
	}
	return $output;
}

function parse_and_grab($content, &$ptr, $open, $close)
{
	$ptr0 = strpos($content, $open, $ptr) + strlen($open);
	$ptr = strpos($content, $close, $ptr0) + strlen($close);
	return substr($content, $ptr0, $ptr - $ptr0 - strlen($close));
}

function my_export($data)
{
	$output = $data["last_update"] . "," . $data["total_entry"] . "," .
		$data["newest_version"] . ",";
	foreach ($data["update"] as $package => $info)
		$output .= $info[0] . "," . $info[1] . "," . $info[2] . ",";
	return $output;
}

function write($content, $output_location)
{
	file_put_contents($output_location, $content, LOCK_EX);
}

