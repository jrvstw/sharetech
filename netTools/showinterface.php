<?php
header("Content-Type:text/html; charset=utf-8");

$devs = array("eth0", "eth1", "eth2", "eth3");
//$devs = array("lo", "eno1");
$table = fetch_ifconfig($devs);
$title = "Interfaces";

include("xhtml/showtable.html");

/*
 * Functions overview:
 * --------------------------------
 * function fetch_ifconfig($devs)
 * function print_title($title)
 * function print_table($table)
 */

/*
root:/PDATA/apache# ifconfig eth0
eth0      Link encap:Ethernet  HWaddr 00:50:56:35:F3:CB  
          inet addr:192.168.33.141  Bcast:192.168.33.255  Mask:255.255.255.0
          inet6 addr: fe80::250:56ff:fe35:f3cb/64 Scope:Link
          UP BROADCAST RUNNING MULTICAST  MTU:1500  Metric:1
          RX packets:123135 errors:0 dropped:0 overruns:0 frame:0
          TX packets:6 errors:0 dropped:0 overruns:0 carrier:0
          collisions:0 txqueuelen:1000 
          RX bytes:10988666 (10.4 Mb)  TX bytes:548 (548.0 b)
          Interrupt:18 Base address:0x1400 
 */

function fetch_ifconfig($devs)
{
	$command = "/sbin/ifconfig";
	$table[] = array("ip", "mask", "rx_pack", "rx_erro", "tx_pack", "tx_error", "rx_flow", "tx_flow");
	//$table[] = array("ip", "mask", "rx_pack", "rx_flow", "rx_error", "tx_pack", "tx_flow", "tx_error");
	foreach ($devs as $dev) {
		exec("$command $dev", $output, $ret);
		if ($ret != 0)
			die("Error $ret executing \"$command $dev\"");
		$output = implode($output, " ");

		$pattern =
			"/inet addr:([0-9\.]+) .*" .
			"Mask:([0-9\.]+) .*" .
			"RX packets:([0-9]+) errors:([0-9]+) .*" .
			"TX packets:([0-9]+) errors:([0-9]+) .*" .
			"RX bytes:([0-9]+) .*" .
			"TX bytes:([0-9]+) .*" .
			"/";/*
			 */
		$patternNew =
			"/inet ([0-9\.]+)  netmask ([0-9\.]+) .*" .
			"RX packets ([0-9]+)  bytes ([0-9]+) .*" .
			"RX errors ([0-9]+) .*" .
			"TX packets ([0-9]+)  bytes ([0-9]+) .*" .
			"TX errors ([0-9]+) /";
		if (preg_match($pattern, $output, $match)) {
			$attr[0] = $match[1];
			$attr[1] = $match[2];
			$attr[2] = $match[3];
			$attr[3] = $match[4];
			$attr[4] = $match[5];
			$attr[5] = $match[6];
			$attr[6] = $match[7];
			$attr[7] = $match[8];
			$table[] = $attr;
		}
		else
			$table[] = array();
	}
	return $table;
}

function print_title($title)
{
	if ($title)
		echo "<div class=\"title\">$title</div>";
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
		foreach ($line as $field)
			echo "<td>" . $field . "</td>\n";
		echo "</tr>\n";
	}
	echo "</table>";
	return;
}

