<?php
<<<<<<< HEAD

function get_port_dev($port_conf, $port_dev_info_file, $map)
=======
class Port
>>>>>>> addb2ebba149e8d83062067bc622aa89b1e87dea
{
	$tmp = parse_ini_file($port_conf);
	$WAN = $tmp["WAN"];
	$LANs = $tmp["LANs"];

	$aPortDev = array();
	if (file_exists($port_dev_info_file)) {
		$lines = file($port_dev_info_file);
		list($lines[1], $lines[2], $lines[3]) =
			array($lines[2], $lines[3], $lines[1]);
		foreach ($lines as $line) {
			if (preg_match("/NAME=\"(eth([2-9]|0[1-9]))\"/", $line, $match)) {
				$name = $match[1];
				if (array_key_exists($name, $map))
					array_push($aPortDev, $map[$name]);
				else
					die("dev $name not on mapping list");
			}
		}
	}

	if (empty($aPortDev)) {
		array_push($aPortDev, "W2");
		if ($WAN == 4)
			array_push($aPortDev, "W3", "W4");
		array_push($aPortDev, "L2");
		if ($LANs == 2)
			array_push($aPortDev, "L1A", "L1B");
	}

	return $aPortDev;
}

