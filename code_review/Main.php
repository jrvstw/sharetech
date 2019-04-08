#!/PGRAM/php/bin/php -q
<?
	// Main.php is executed by cron once a minute, eg: 09:00:00, 09:01:00, 09:02:00 
	include_once("PortDev.php");
	$port = new Port();
	$aPortDev = $port->get_port_dev();
	ksort($aPortDev);
	$filename = "/PCONF/IFdata";

	$refresh = 2;
	$aIFTxRx = array();
	read_ifdata(&$refresh, &$aIFTxRx);

	if(is_file("/ram/tmp/wanstatus")) {
		$wanstatus = file("/ram/tmp/wanstatus");
	} else {
		$wanstatus = array(
			0 => "WAN1=OFF=OFF",
			1 => "WAN2=OFF=OFF",
			2 => "WAN3=OFF=OFF",
			3 => "WAN4=OFF=OFF",
			4 => "WAN5=OFF=OFF"
		);
	}
	$eth0_RxTx = fetchRxTx("eth0_arr", "eth0");
	$a = explode("=", $wanstatus[0]);
	$eth1_RxTx = fetchRxTx("eth1_arr", $a[1]);

	echo "$eth0_RxTx\n";
	echo "$eth1_RxTx\n";

	foreach($aPortDev as $dev){
		switch($dev){
			case "L2":
				$eth3_RxTx = fetchRxTx("eth3_arr", "eth3");
				echo "$eth3_RxTx\n";
				break;
			case "W2":
				$b = explode("=", $wanstatus[1]);
				$eth2_RxTx = fetchRxTx("eth2_arr", $b[1]);
				echo "$eth2_RxTx\n";
				break;
			case "W3":
				$c = explode("=", $wanstatus[2]);
				$eth4_RxTx = fetchRxTx("eth4_arr", $c[1]);
				echo "$eth4_RxTx\n";
				break;
			case "W4":
				$d = explode("=", $wanstatus[3]);
				$eth5_RxTx = fetchRxTx("eth5_arr", $d[1]);	
				echo "$eth5_RxTx\n";
				break;
			case "W5":
				$e = explode("=", $wanstatus[4]);
				$eth6_RxTx = fetchRxTx("eth6_arr", $e[1]);	
				echo "$eth6_RxTx\n";
				break;
			case "L1A":
				$eth01_RxTx = fetchRxTx("eth01_arr", "eth01");
				echo "$eth01_RxTx\n";
				break;
			case "L1B":
				$eth02_RxTx = fetchRxTx("eth02_arr", "eth02");
				echo "$eth02_RxTx\n";
				break;
			case "L1C":
				$eth03_RxTx = fetchRxTx("eth03_arr", "eth03");
				echo "$eth03_RxTx\n";
				break;
		}
	}

	echo "--------get_ethinfo--------\n";
	get_ethinfo();
	echo "--------get_dns--------\n";
	get_dns();
	echo "--------cpuloading--------\n";
	cpuloading();
	echo "--------hddinfo--------\n";
	hddinfo();
	echo "--------meminfo--------\n";
	meminfo();

	function fetchRxTx($devS, $devR) {
		global $refresh, $aIFTxRx;

		$timestamp = time();
		$Min = date("i", $timestamp);
		if(intval($Min) % $refresh != 0) {
			return "";
		}

		if($aIFTxRx[$devS]) {
			$rxflow_old = $aIFTxRx[$devS][0];
			$txflow_old = $aIFTxRx[$devS][1];
			$rxpack_old = $aIFTxRx[$devS][2];
			$txpack_old = $aIFTxRx[$devS][3];
		}

		exec("/sbin/ip -s link show $devR", $ret);

		$arx = listStringSplit(trim($ret[3]));
		$rxflow = trim($arx[0]);
		$rxpack = trim($arx[1]);

		$atx = listStringSplit(trim($ret[5]));
		$txflow = trim($atx[0]);
		$txpack = trim($atx[1]);

		$rxflowdiff = ($rxflow_old) ? (floor(($rxflow - $rxflow_old) / $refresh)) : $rxflow;
		$txflowdiff = ($txflow_old) ? (floor(($txflow - $txflow_old) / $refresh)) : $txflow;
		$rxpackdiff = ($rxpack_old) ? (floor(($rxpack - $rxpack_old) / $refresh)) : $rxpack;
		$txpackdiff = ($txpack_old) ? (floor(($txpack - $txpack_old) / $refresh)) : $txpack;

		$aNewData = array($rxflow,$txflow,$rxpack,$txpack);
		write_ifdata($devS, $aNewData);

		$rxflowdiff = $rxflowdiff * 8;
		$txflowdiff = $txflowdiff * 8;

		return "$rxflowdiff,$txflowdiff,$rxpackdiff,$txpackdiff,$rxflow,$txflow,$rxpack,$txpack";
	}

	function read_ifdata(&$refresh, &$aIFTxRx) {
		global $filename;

		if(!is_file($filename))	{
			$val = "refresh = " . $refresh;
			exec("/bin/echo \"".$val."\" >> " . $filename);
			return;
		}

		$aData = file($filename);
		foreach($aData as $line){
			$aTmp = explode("=", $line);
			if(strstr($line, "refresh")) {
				$refresh = trim($aTmp[1]);
			} else {
				$key = trim($aTmp[0]);
				$aTmp[1] = trim($aTmp[1]);
				$aIFTxRx[$key] = explode(",", $aTmp[1]);
			}
		}
	}

	function write_ifdata($dev, $aNewData) {
		global $filename;
		$aData = file($filename);
		$found = false;
		foreach($aData as $key => $line) {
			$aTmp = explode("=", $line);
			if($dev == trim($aTmp[0])) {
				$aData[$key] = $dev . " = ".implode(",", $aNewData) . "\n";
				$found = true;
				break;
			}
		}

		if(!$found)	{
			$aData[$key + 1] = $dev . " = ".implode(",", $aNewData) . "\n";
		}

		$tmp_filename = $filename . "_tmp";
		$fp = fopen($tmp_filename, "w+");
		foreach ($aData as $value) {
			fwrite($fp, $value);
		}
		fclose($fp);

		if(is_file($tmp_filename)) {
			exec("/bin/mv ".$tmp_filename." ".$filename);
		} else {
			@unlink($tmp_filename);
			@unlink($filename);
		}
	}

	function get_ethinfo(){
		$dev = array("eth0" , "eth1" , "eth2" , "eth3");
		$msg0 = array();
		if(file_exists("/ram/tmp/wanstatus")){
			exec("cat /ram/tmp/wanstatus", $msg0);
		}
		for($i = 0; $i < count($dev); $i++) {
			unset($a);
			unset($devline);
			if($dev[$i] == "eth1") {
				if(isset($msg0[0])) {
					$a = explode("=",trim($msg0[0]));
					$info[$i]["dev"] = $dev[$i];
					$info[$i]["ip"] = trim($a[2]);
					$info[$i]["name"] = "WAN1";
					if($a[1] == "OFF") {
						$info[$i]["connect"] = "down";
					} else {
						$info[$i]["connect"] = "up";
					}
					$info[$i]["link"] = get_ethlink($dev[$i]);
					if($a[1] == "OFF") {
						$ethflow = get_ethflow($dev[$i]);
					} else {
						$ethflow = get_ethflow($a[1]);
					}
					$info[$i]["rx_error"] = $ethflow["rx_error"];
					$info[$i]["tx_error"] = $ethflow["tx_error"];
					$info[$i]["collisions"] = $ethflow["collisions"];
				} else {
					$info[$i]["dev"] = "eth1";
					$info[$i]["ip"] = "OFF";
					$info[$i]["name"] = "WAN1";
					$info[$i]["connect"] = "down";
					$info[$i]["link"] = "no";
					$info[$i]["rx_error"] = 0;
					$info[$i]["tx_error"] = 0;
					$info[$i]["collisions"] = 0;
				}
			}else if($dev[$i] == "eth2") {
				foreach($msg0 as $line) {
					if(strstr($line, 'WAN2')) {
						$devline = $line;
					}
				}
				if(isset($devline)) {
					$a = explode("=",trim($devline));
					$info[$i]["dev"] = $dev[$i];
					$info[$i]["ip"] = trim($a[2]);
					$info[$i]["name"] = "WAN2";
					if($a[1] == "OFF") {
						$info[$i]["connect"] = "down";
					} else {
						$info[$i]["connect"] = "up";
					}
					$info[$i]["link"] = get_ethlink($dev[$i]);
					if($a[1] == "OFF") {
						$ethflow = get_ethflow($dev[$i]);
					} else {
						$ethflow = get_ethflow($a[1]);
					}
					$info[$i]["rx_error"] = $ethflow["rx_error"];
					$info[$i]["tx_error"] = $ethflow["tx_error"];
					$info[$i]["collisions"] = $ethflow["collisions"];
				}else {
					$info[$i]["dev"] = "eth2";
					$info[$i]["ip"] = "OFF";
					$info[$i]["name"] = "WAN2";
					$info[$i]["connect"] = "down";
					$info[$i]["link"] = "no";
					$info[$i]["rx_error"] = 0;
					$info[$i]["tx_error"] = 0;
					$info[$i]["collisions"] = 0;
				}
			}else if($dev[$i] == "eth0") {
				$info[$i]["dev"] = $dev[$i];
				
				$info[$i]["name"] = "LAN"; 
				$infolan = get_landev($dev[$i]);
				$info[$i]["ip"] = $infolan["ip"];
				$ethflow = get_ethflow($dev[$i]);

				$xx = get_ethlink($dev[$i]);
				$info[$i]["connect"] = ($xx == "yes") ? "up" : "down";
				$info[$i]["link"] = $xx;
				$info[$i]["rx_error"] = $ethflow["rx_error"];
				$info[$i]["tx_error"] = $ethflow["tx_error"];
				$info[$i]["collisions"] = $ethflow["collisions"];
			}else if($dev[$i] == "eth3") {
				$info[$i]["dev"] = $dev[$i];
				$info[$i]["name"] = "DMZ"; 
				$infolan = get_landev($dev[$i]);
				$info[$i]["ip"] = $infolan["ip"];
				$ethflow = get_ethflow($dev[$i]);

				$xx = get_ethlink($dev[$i]);
				$info[$i]["connect"] = ($xx == "yes") ? "up" : "down";
				$info[$i]["link"] = $xx;
				$info[$i]["rx_error"] = $ethflow["rx_error"];
				$info[$i]["tx_error"] = $ethflow["tx_error"];
				$info[$i]["collisions"] = $ethflow["collisions"];
			}
		}

		print_r($info);
	}

	function get_landev($device) {
		$cmd1 = "cat /etc/sysconfig/network-devices/ifconfig.".$device."/ipv4";
		$cmd2 = "ifconfig ".$device;
		exec($cmd1, $msg1);
		exec($cmd2, $msg2);
		$info["onboot"] = trim(str_replace("ONBOOT=","",$msg1[0]));
		$devip = trim(str_replace("IP=","",$msg1[2]));
		if($devip == null || $devip == "#") $info["ip"] = "OFF";
		else $info["ip"] = $devip;
		return $info;
	}

	function listStringSplit($sList) {
		$aList = preg_split("/[\s,]+/", $sList , -1, PREG_SPLIT_NO_EMPTY);
		return $aList;
	}

	function get_ethlink($dev) {
		exec("/bin/cat /sys/class/net/$dev/carrier", $retCont, $retCode);
		if($retCode == 0 && $retCont[0] == "1")	{
			return "yes";
		} else {
			return "no";
		}
	}

	function get_ethflow($dev) {
		exec("/sbin/ifconfig $dev", $msg0);
		exec("/sbin/ip -s link show $dev",$msg1);

		$ethflow = array();

		$acoll = listStringSplit(trim($msg0[5]));
		$acolli = explode(":",$acoll[0]);
		$collisions = $acolli[1];
		$ethflow["collisions"] = trim($collisions);

		$arx = listStringSplit(trim($msg1[3]));
		$rxerror = trim($arx[2]); 
		$atx = listStringSplit(trim($msg1[5])); 
		$txerror = trim($atx[2]);  
		if(trim($rxerror)) $ethflow["rx_error"] = trim($rxerror);
		else $ethflow["rx_error"] = 0;
		if(trim($txerror)) $ethflow["tx_error"] = trim($txerror);
		else $ethflow["tx_error"] = 0;

		return $ethflow;
	}


	function get_dns() {
		exec("ps aux | grep bind", $ret);
		foreach((Array)$ret as $line) {
			$elt = split("[ \t]+", $line);
			if($elt[10] == "/PGRAM/bind/sbin/named")
				echo "running\n";
				return;
		}
		echo "not running\n";
		return;
	}


	function cpuloading() {
		$fd = fopen("/proc/stat", "r");
		if ($fd) {
			$statinfo = explode("\n", fgets($fd, 1024));
			fclose($fd);
			foreach($statinfo as $line) {
				$info = split(" +", $line);
				if ($info[0] == "cpu") {
					$user = $info[1];
					$nice = $info[2];
					$system = $info[3];
					$idle = $info[4];
					break;
				}
			}
		}
		$usage = $user + $nice + $system;
		$total = $usage + $idle;

		$fd = fopen("/proc/loadavg", "r");
		if ($fd) {
			$loadavg = fgets($fd, 1024);
			fclose($fd);
			list($oneminute, $fiveminute, $fifteenminute) = explode(' ', $loadavg);
		}

		echo "$total, $usage, $oneminute, $fiveminute, $fifteenminute\n";

	}

	function hddinfo() {
	
		unset($disk_info);
		unset($ret);
		unset($info);

		exec("blkid", $searhdd);
		if($searhdd){
			for($i = 0; $i < count($searhdd); $i++){
				if(strpos($searhdd[$i], "HDD")){
					$asearhdd = preg_split ("/[\s:]+/", $searhdd[$i]);
					$datadisk = trim($asearhdd[0]); 
				}
			}
		}

		exec("df $datadisk -m | tail -n 1", $ret);
		$info = preg_split ("/[\s,]+/",$ret[0]);
		$diskinfo[all] = $info[1];
		$diskinfo[used] = $info[2];
		$diskinfo[free] = $info[3];
		$diskinfo[percent] = $info[4];

		$disk_info = $diskinfo[all].",".$diskinfo[free].",".$diskinfo[percent]."\n";
		echo $disk_info;
	}

	function meminfo() {
		exec("free | grep \"Mem:\"", $msg);
		$mem = split(" +", $msg[0]);
		$mem_all = round($mem[1]/1000, 2);
		$mem_free = round(($mem[3]+$mem[6])/1000, 2);
	
		if($mem_all > 3000 && $mem_all < 3200)
		{//Because the current kernel is 32-bit, it can only be caught 3G  memory size, AW-590
			$mem_all += 1000;
			$mem_free += 1000;
		}

		echo $mem_all.",".$mem_free."\n";
	}


?>