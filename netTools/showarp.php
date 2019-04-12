<?php

$command = "/usr/sbin/arp -n";
exec($command, $output, $retVal);
if ($retVal != 0)
	die("Error executing command: $command");

array_shift($output);

$arp_table = array();
foreach ($output as $key => $line) {
	$tmp = preg_split('/[\s]+/', $line);
	$arp_table[$key]["ip"] = $tmp[0];
	$arp_table[$key]["mac"] = $tmp[2];
	$arp_table[$key]["iface"] = $tmp[4];
}
print_r($arp_table);

