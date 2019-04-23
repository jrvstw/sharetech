<?php

/*
 * 1. setup
 */
$dir = "/var/www/html/statuslog";

/*
 * 2. grab content every time interval
 */
$conf_location = "$dir/conf.ini";
$output_location = "$dir/local.txt";
$conf = parse_ini_file($conf_location);
$min = date("i", time());
if ((intval($min) % $conf["refresh"]) != 0) {
	return;
}
$stat_local = get_statistics();

/*
 * 3. prepend to file
 */
//for ($i = 0; $i < 3495; $i++)
prepend($stat_local, $output_location, $conf["line_limit"]);

/*
 * Functions overview
 * --------------------------------
 * get_statistics()
 *  |- parse_and_grab($content, &$ptr, $open, $close)
 * prepend($stat_local, $output_location, $limit)
 *  |- my_export($stat)
{
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
	$stat["tasks"] = trim(parse_and_grab($output, $ptr, "Tasks: ", "tot"));
	$stat["running"] = trim(parse_and_grab($output, $ptr, ",", " run"));
	$stat["cpu_us"] = trim(parse_and_grab($output, $ptr, "(s):", " us"));
	$stat["cpu_sy"] = trim(parse_and_grab($output, $ptr, ",", " sy"));
	$stat["cpu_ni"] = trim(parse_and_grab($output, $ptr, ",", " ni"));
	$stat["cpu_id"] =trim(parse_and_grab($output, $ptr, ",", " id"));

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

function prepend($stat_local, $output_location, $limit)
{
	$lines = file($output_location);
	while (count($lines) >= $limit)
		array_pop($lines);
	$content = my_export($stat_local) . "\n";
	//echo $content;
	foreach ($lines as $line)
		$content .= $line;
	$handle = fopen($output_location, "w+");
	fwrite($handle, $content);
}

function my_export($stat)
{
	$pcpu = 100 - floatval($stat["cpu_id"]);
	$output = $stat["time"] . "," .
		$stat["avg1"] . "," .
		/*
		$stat["avg2"] . "," .
		$stat["avg3"] . "," .
		 */
		$stat["tasks"] . "," .
		$stat["running"] . "," .
		/*
		$stat["cpu_us"] . "," .
		$stat["cpu_sy"] . "," .
		$stat["cpu_ni"] . "," .
		$stat["cpu_id"] . ",";
		 */
		sprintf("%.1f,", $pcpu);
	foreach ($stat["proc"] as $proc)
		$output .= "[" . $proc[0] . "] " . $proc[1] . ",";
	return substr($output, 0, -1);
}

