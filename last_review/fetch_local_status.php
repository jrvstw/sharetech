<?php

//$dir = "/HDD/STATUSLOG";
//$dir = "/home/sharetechrd33/sharetech/last_review/statuslog";
$dir = "/var/www/html/sharetech/last_review/statuslog";
$conf_location = "$dir/conf.ini";
$output_location = "$dir/log.txt";

/*
 * V time
 *   load average
 *   task amount
 *   running task amount
 *   cpu usage
 *   cpu loading 3 highest pid and their instruction
 */

$conf = parse_ini_file($conf_location);

/*
$min = date("i", time());
if ((intval($min) % $conf["refresh"]) != 0) {
	return;
}
 */

$stat_local = get_statistics();
//for ($i = 0; $i < 3495; $i++)
append($stat_local, $output_location, $conf["line_limit"]);
//echo my_export($stat_local);

/*
 * Functions overview
 */
function get_statistics()
{
	$command = "top -b -n 1 | head -n 5";
	exec($command, $output, $ret);
	$output = implode(" ",$output);
	$ptr = 0;
	$stat["time"] = date("Y-m-d ", time());
	$stat["time"] .= parse_and_grab($output, $ptr, "top - ", " ");
	$ptr = strpos($output, "load average:");
	$stat["avg1"] = parse_and_grab($output, $ptr, ": ", ",");
	$stat["avg2"] = parse_and_grab($output, $ptr, " ", ",");
	$stat["avg3"] = parse_and_grab($output, $ptr, " ", " ");
	$stat["tasks"] = parse_and_grab($output, $ptr, "Tasks: ", " ");
	$stat["running"] = trim(parse_and_grab($output, $ptr, ",", " run"));
	$stat["cpu_us"] = trim(parse_and_grab($output, $ptr, "(s):", " us"));
	$stat["cpu_sy"] = trim(parse_and_grab($output, $ptr, ",", " sy"));
	$stat["cpu_ni"] = trim(parse_and_grab($output, $ptr, ",", " ni"));
	$stat["cpu_id"] = 100 - floatval(trim(parse_and_grab($output, $ptr, ",", " id")));

	$command = "ps axho pid,args --sort -pcpu | head -n 3";
	exec($command, $output, $ret);
	foreach ($output as $line) {
		$array = explode(" ", trim($line), 2);
		$stat["proc"][] = $array;
	}
	return $stat;
}

function parse_and_grab($content, &$ptr, $open, $close)
{
	$ptr0 = strpos($content, $open, $ptr) + strlen($open);
	$ptr = strpos($content, $close, $ptr0) + strlen($close);
	return substr($content, $ptr0, $ptr - $ptr0 - strlen($close));
}

function my_export($stat)
{
	$output = $stat["time"] . "," .
		$stat["avg1"] . "," .
		$stat["avg2"] . "," .
		$stat["avg3"] . "," .
		$stat["tasks"] . "," .
		$stat["running"] . "," .
		$stat["cpu_us"] . "," .
		$stat["cpu_sy"] . "," .
		$stat["cpu_ni"] . "," .
		$stat["cpu_id"] . ",";
	foreach ($stat["proc"] as $proc)
		$output .= $proc[0] . "," . $proc[1] . ",";
	return $output;
}

function modify_ini()
{
	$conf = parse_ini_file($conf_location);
	$put = "refresh = " . $conf["refresh"] .
		"\nline_limit = " . $conf["line_limit"];
	file_put_contents($conf_location, $put, LOCK_EX);
}

function append($stat_local, $output_location, $limit)
{
	$lines = file($output_location);
	while (count($lines) >= $limit)
		array_pop($lines);
	$content = substr(my_export($stat_local), 0, -1) . "\n";
	foreach ($lines as $line)
		$content .= $line;
	//return $content;
	file_put_contents($output_location, $content, LOCK_EX);
}

