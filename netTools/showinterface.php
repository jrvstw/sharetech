<?php
/*
 * This program simply prints list of interfaces.
 *
 * Main Function Work Flow
 * --------------------------------
 * 	1. setup
 * 	2. print html page
 */
header("Content-Type:text/html; charset=utf-8");

/*
 * 1.
 * 		$title		: Title to print on the page.
 * 		$ip			: Full path of the ip command.
 * 		$devs		: Interfaces to print.
 * 		$table		: The table to print.
 */
$title = "Interfaces";
$ip = "/sbin/ip";
$devs = array("eth0", "eth1", "eth2", "eth3");
//$devs = array("lo", "wlp3s0");
$table = fetch_IF($devs, $ip);

/*
 * 2.
 */
include("xhtml/showtable.html");

/*
 * End of Main Function
 */

/*
 * Functions overview:
 * --------------------------------
 *  fetch_IF($devs, $ip)
 *  	to_ip($mask)
 *  function alert($string)
 *  print_title($title)
 *  print_buttons($permission, $mode)
 *  print_table($table)
 *  include_js()
 */

function fetch_IF($devs, $ip)
{
	$table[] = array(
		"dev" => "dev",
		"ip" => "ip",
		"mask" => "mask",
		"connect" => "connect",
		"link" => "link",
		"tx_pack" => "tx_pack",
		"rx_pack" => "rx_pack",
		"tx_flow" => "tx_flow",
		"rx_flow" => "rx_flow",
		"tx_error" => "tx_error",
		"rx_error" => "rx_error");

	foreach ($devs as $dev) {
		$attr["dev"] = $dev;

		/*
		 * execute "ip address show $dev" to fetch ip, mask and connect status.
		 */
		$command = "$ip address show $dev";
		exec($command, $output, $ret);
		if ($ret != 0) {
			$attr["ip"] = "error:$ret";
			$attr["mask"] = "error:$ret";
			$attr["connect"] = "error:$ret";
			//alert("Error $ret executing $command");
		}
		$output = implode($output, " ");
		$pattern = "/ inet ([0-9\.]+)\/([0-9]+) /";
		if (preg_match($pattern, $output, $match)) {
			$attr["ip"] = $match[1];
			$attr["mask"] = to_ip($match[2]);
		}
		$pattern = "/[<,]UP[,>]/";
		if (preg_match($pattern, $output, $match))
			$attr["connect"] = "UP";
		else
			$attr["connect"] = "DOWN";

		/*
		 * cat carrier files to fetch link status.
		 */
		$command = "/bin/cat /sys/class/net/$dev/carrier 2> /dev/null";
		exec($command, $output, $retVal);
		if ($retVal == 0 and $output[0] == "1")
			$attr["link"] = "YES";
		else
			$attr["link"] = "NO";

		/*
		 * execute "ip -s link show $dev" to fetch flow information.
		 */
		$command = "$ip -s link show $dev";
		exec($command, $output, $retVal);
		$output = implode($output, "\n");

		$pattern = "/ RX: bytes [^\n]*\n +([0-9]+) +([0-9]+) +([0-9]+) /";
		if (preg_match($pattern, $output, $match)) {
			$attr["rx_flow"] = $match[1];
			$attr["rx_pack"] = $match[2];
			$attr["rx_error"] = $match[3];
		} else {
			$attr["rx_flow"] = "error:$ret";
			$attr["rx_pack"] = "error:$ret";
			$attr["rx_error"] = "error:$ret";
			//alert("Error matching string with command $command");
		}

		$pattern = "/TX: bytes [^\n]*\n +([0-9]+) +([0-9]+) +([0-9]+) /";
		if (preg_match($pattern, $output, $match)) {
			$attr["tx_flow"] = $match[1];
			$attr["tx_pack"] = $match[2];
			$attr["tx_error"] = $match[3];
		} else {
			$attr["tx_flow"] = "error:$ret";
			$attr["tx_pack"] = "error:$ret";
			$attr["tx_error"] = "error:$ret";
			//alert("Error matching string with command $command");
		}

		$table[] = $attr;
	}
	return $table;
}

function to_ip($mask)
{
	if (empty($mask))
		return "";
	$rem = 32 - (int)$mask;
	$long = ip2long("255.255.255.255");
	$long = $long >> $rem << $rem;
	return long2ip($long);
}

function alert($string)
{
	echo "<script type='text/javascript'>alert(\"$string\");</script>";
}

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
	return;
}

function print_buttons($permission, $mode)
{
	return;
}

function print_table($table)
{
	echo "<table border=1>";
	foreach ($table as $row => $line) {
		if ($row == 0)
			echo "<tr class=\"header\">\n";
		else
			echo "<tr>\n";
		foreach ($table[0] as $key => $field)
			echo "<td>" . $line[$key] . "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>";
	return;
}

function include_js()
{
	return;
}

