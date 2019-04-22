<?php

//$dir = "/HDD/STATUSLOG";
$dir = "/home/sharetechrd33/sharetech/last_review/statuslog";
$conf_location = "$dir/conf.ini";
$output_location = "$dir/log.txt";

/*
 * V time
 *   load average
 *   task amount
 *   running task amount
 *   cpu usage
 *   cpu loading 3 highest pid and their instructiono
 */

$stat_local = get_statistics();
echo my_export($stat_local);
$time = time();
//echo date("Ymd_his", $time);

function get_statistics()
{
	$command = "top -b -n 1 | head";
	exec($command, $output, $ret);
	$output = implode(" ",$output);

	$ptr = 0;
	$stat["time"] = parse_and_grab($output, $ptr, "top - ", " ");
	$ptr = strpos($output, "load average:");
	$stat["avg1"] = parse_and_grab($output, $ptr, ": ", ",");
	$stat["avg2"] = parse_and_grab($output, $ptr, " ", ",");
	$stat["avg3"] = parse_and_grab($output, $ptr, " ", " ");
	$stat["tasks"] = parse_and_grab($output, $ptr, "Tasks: ", " ");
	$stat["running"] = trim(parse_and_grab($output, $ptr, ",", " run"));
	$stat["cpu_us"] = trim(parse_and_grab($output, $ptr, "(s):", " us"));
	$stat["cpu_sy"] = trim(parse_and_grab($output, $ptr, ",", " sy"));
	$stat["cpu_ni"] = trim(parse_and_grab($output, $ptr, ",", " ni"));
	$stat["cpu_id"] = trim(parse_and_grab($output, $ptr, ",", " id"));
	$command = "ps axo pid,comm --sort -pcpu | head";
	exec($command, $output, $ret);
	for ($i = 1; $i < 4; $i++) {
		$array = explode(" ", $output[$i]);
		$stat["proc1"] = $array;
	}
	return $stat;
}

function parse_and_grab($content, &$ptr, $open, $close)
{
	$ptr0 = strpos($content, $open, $ptr) + strlen($open);
	$ptr = strpos($content, $close, $ptr0) + strlen($close);
	return substr($content, $ptr0, $ptr - $ptr0 - strlen($close));
}

function my_export($data)
{
	$output = $stat["time"] . "," . $stat["time"] . "," . $stat["time"] . "," . $stat["time"] . "," . $stat["time"] . ",";
	foreach ($data as $value)
		$output .= $value . ",";
	//$output = $data["time"] . "," . $data["avg1"] . "," . $data["avg2"] . "," . $data["avg3"] . "," ;
	/*
	$output = $data["last_update"] . "," . $data["total_entry"] . "," .
		$data["newest_version"] . ",";
	foreach ($data["update"] as $package => $info)
		$output .= $info[0] . "," . $info[1] . "," . $info[2] . ",";
	 */
	return $output;
}

function modify_ini()
{
	$conf = parse_ini_file($conf_location);
	$put = "refresh = " . $conf["refresh"] .
		"\nline_limit = " . $conf["line_limit"];
	file_put_contents($conf_location, $put, LOCK_EX);
}

