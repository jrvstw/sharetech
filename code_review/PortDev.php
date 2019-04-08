<?
class Port
{
	function getv($key) {
		$aPortSet = parse_ini_file("port.conf");
		return $aPortSet[$key];
	}

	function get_port_dev(){
		$port_dev_info_file = "60-myorder.rules";
		if(file_exists($port_dev_info_file)){
			$port_dev_info = file($port_dev_info_file);
			foreach($port_dev_info as $key => $line){
				$line = trim($line);
				$msg = explode(",", $line);
				$i = $key;
				if($this->getv("WAN") == 4) {
					if(1 == $key){
						$i = 3; //dmz
					}
					if(2 == $key){
						$i = 1; //wan3
					}
					if(3 == $key){
						$i = 2; //wan4
					}
				}else if(2 == $key){
					break;
				}
				preg_match("/NAME=\"(eth([2-9]|0[1-9]))\"/", $msg[5], $name);
				switch($name[1]){
					case "eth2":
						$aPortDev[$i] = "W2";
						break;
					case "eth3":
						$aPortDev[$i] = "L2";
						break;
					case "eth4":
						$aPortDev[$i] = "W3";
						break;
					case "eth5":
						$aPortDev[$i] = "W4";
						break;
					case "eth6":
						$aPortDev[$i] = "W5";
						break;
					case "eth01":
						$aPortDev[$i] = "L1A";
						break;
					case "eth02":
						$aPortDev[$i] = "L1B";
						break;
					case "eth03":
						$aPortDev[$i] = "L1C";
						break;
				}
			}
		}

		if(empty($aPortDev)){
			$aPortDev[] = "W2";
			if($this->getv("WAN") == 4) {
				$aPortDev[] = "W3";
				$aPortDev[] = "W4";
			}
			$aPortDev[] = "L2";
			if($this->getv("LANs") == 2) {
				$aPortDev[] = "L1A";
				$aPortDev[] = "L1B";
			}
		}
		return $aPortDev;
	}
}

?>
