<?php

/*
 * 1. setup
 */
$dir = "/HDD/STATUSLOG";
$line_limit = 3500;

/*
 * 2. grab content every time interval
 */
$conf_location = "$dir/conf.ini";
$output_location = "$dir/local.txt";
$conf = parse_ini_file($conf_location);
if (isset($conf["update"]))
	$update = $conf["update"];
else
	$update = 1;

$min = date("i", time());
if ((intval($min) % $update) != 0) {
	return;
}
$stat_local = get_statistics();

/*
 * 3. prepend to file
 */
//for ($i = 0; $i < 3495; $i++)
prepend($stat_local, $output_location, $line_limit);

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
	$stat["cpu_us"] = trim(substr(parse_and_grab($output, $ptr, "(s):", "us"), 0, -1));
	$stat["cpu_sy"] = trim(substr(parse_and_grab($output, $ptr, ",", "sy"), 0, -1));
	$stat["cpu_ni"] = trim(substr(parse_and_grab($output, $ptr, ",", "ni"), 0, -1));
	$stat["cpu_id"] =trim(substr(parse_and_grab($output, $ptr, ",", "id"), 0, -1));

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
		sprintf("%.1f,", $pcpu) .
		$stat["cpu_us"] . "," .
		$stat["cpu_sy"] . "," .
		$stat["cpu_ni"] . "," .
		$stat["avg1"] . "," .
		$stat["avg2"] . "," .
		$stat["avg3"] . "," .
		$stat["tasks"] . "," .
		$stat["running"] . "," .
		$stat["proc"][0][0] . ",\"" . str_replace('"', '""', $stat["proc"][0][1]) . "\"," .
		$stat["proc"][1][0] . ",\"" . str_replace('"', '""', $stat["proc"][1][1]) . "\"," .
		$stat["proc"][2][0] . ",\"" . str_replace('"', '""', $stat["proc"][2][1]) . "\",";
	return substr($output, 0, -1);
}

