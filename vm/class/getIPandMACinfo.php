<?
Class getIPandMACinfo{
	
	var $lan_ipmac = "/ram/tmp/lan_ipmac";
	var $lan_ipmac_v6 = "/ram/tmp/lan_ipmac_v6";
	var $lan_def_ipmac = "/PDATA/IPMAC/srcipmacalias";
	var $dmz_def_ipmac = "/PDATA/IPMAC/dmzsrcipmacalias";
	var $alan_ipmacinfo = array();
	var $alan_def_ipmacinfo = array();
	var $admz_def_ipmacinfo = array();
	
	function getIPandMACinfo(){
		 if(is_file($this->lan_ipmac))
		 {
			$conf = file($this->lan_ipmac);
			for($i=0;$i<count($conf);$i++){
				$a = explode(",",trim($conf[$i]));
				$tmp = array(
					ip 		=> trim($a[0]),
					mac 	=> trim($a[1]),
					alias => trim($a[2])
				);
				$this->alan_ipmacinfo[] = $tmp;
			}
		}
			
		if(is_file($this->lan_def_ipmac))
		{
			$conf = file($this->lan_def_ipmac);
			for($i=0;$i<count($conf);$i++){
				$a = explode(",",trim($conf[$i]));
				$tmp = array(
					ip 		=> trim($a[0]),
					mac 	=> trim($a[1]),
					alias => trim($a[2])
				);
				$this->alan_def_ipmacinfo[] = $tmp;
			}
		}
		
		if(is_file($this->dmz_def_ipmac))
		{
			$conf = file($this->dmz_def_ipmac);
			for($i=0;$i<count($conf);$i++){
				$a = explode(",",trim($conf[$i]));
				$tmp = array(
					ip 		=> trim($a[0]),
					mac 	=> trim($a[1]),
					alias => trim($a[2])
				);
				$this->admz_def_ipmacinfo[] = $tmp;
			}
		}
	}
	
	function get_mac($ip){
		$sort = array(
			$this->alan_ipmacinfo,
			$this->alan_def_ipmacinfo,
			$this->admz_def_ipmacinfo
		);

		foreach($sort as $conf) 
		{
			for($i=0;$i<count($conf);$i++)
			{
				if($conf[$i]["ip"] == $ip && $conf[$i]["ip"] != "" && strlen($conf[$i]["mac"]) == 17)
				{//Find it
					return $conf[$i]["mac"];
				}	
			}	
		}
		return false;
	}
			
	function get_lanname($ip){
		$msg = $this->alan_def_ipmacinfo;
		for($i=0;$i<count($msg);$i++){
			if($msg[$i]["ip"] == $ip) {
				$name = trim($msg[$i]["alias"]);
				break;
			}
		}
		return $name;
	}
		
	function get_dmzname($ip){
		$msg = $this->admz_def_ipmacinfo;
		for($i=0;$i<count($msg);$i++){
			if($msg[$i]["ip"] == $ip) {
				$name = trim($msg[$i]["alias"]);
				break;
			}
		}
		return $name;
	}  
	
	function get_mac_v6($ip){
		
		if(!is_file($this->lan_ipmac_v6)) return false;
		
		$msg = file($this->lan_ipmac_v6);
		for($i=0;$i<count($msg);$i++){
			$a = explode(",",trim($msg[$i]));
			if($a[0] == $ip) {
				$mac = trim($a[1]);
				break;
			}
		}
		return $mac;
	}

	function get_lanname_v6($ip){
		include("/PDATA/apache/conf/fw.ini");
		if(!is_file($SRCIPMAC_ALIAS_v6)) return false;
		
		$msg = file($SRCIPMAC_ALIAS_v6);
		for($i=0;$i<count($msg);$i++){
			$a = explode(",",trim($msg[$i]));
			if($a[0] == $ip) {
				$name = trim($a[2]);
				break;
			}
		}
		return $name;
	}

	function get_dmzname_v6($ip){
		$SRCIPMAC_DMZ_ALIAS_v6 = "/PDATA/IPMAC/dmzsrcipmacalias_v6";
		if(!is_file($SRCIPMAC_DMZ_ALIAS_v6)) return false;
		
		$msg = file($SRCIPMAC_DMZ_ALIAS_v6);
		for($i=0;$i<count($msg);$i++){
			$a = explode(",",trim($msg[$i]));
			if($a[0] == $ip) {
				$name = trim($a[2]);
				break;
			}
		}
		return $name;
	} 
	
	function get_allipmacs(){
		if(!is_file($this->lan_ipmac)) return false;		
		$msg = file($this->lan_ipmac);
		for($i=0;$i<count($msg);$i++){
			$a = explode(",",trim($msg[$i]));
			if($this->i_ip($a["0"])){
				 $ipmacs[$i]["alias"] = $a["2"];
				 $ipmacs[$i]["ip"] = $a["0"];
				 $ipmacs[$i]["mac"] = $a["1"];
				 $ipmacs[$i]["status"] = $a["3"];
				 $ipmacs[$i]["interface"] = ($a["4"] == "br0") ? "BRI" : $a["4"];
				 $ipmacs[$i]["userspeaktime"] = $a["5"];
			}
		}		
		return $ipmacs;
	} 
	
	function i_ip($ip){
		if(!ereg("^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$", $ip)){
			return false;
		}else{
			return true;
		}
	}
	
	function get_alan_info(){
		$res= array();
		$existip = array();
		$sort = array(
			$this->alan_def_ipmacinfo,
			$this->admz_def_ipmacinfo
		);
		$msg = $this->alan_ipmacinfo;
		for($i=0;$i<count($msg);$i++){
			$existip[] = $msg[$i]["ip"];
			if($msg[$i]["alias"] == null) $res[$msg[$i]["ip"]]["alias"] = $msg[$i]["ip"];
			else $res[$msg[$i]["ip"]]["alias"] = trim($msg[$i]["alias"]);
			$res[$msg[$i]["ip"]]["mac"] = trim($msg[$i]["mac"]);
		}
		
		foreach($sort as $conf) 
		{
			for($i=0;$i<count($conf);$i++)
			{
				if($conf[$i]["ip"] != "" && !in_array($conf[$i]["ip"], $existip))
				{
					$existip[] = $conf[$i]["ip"];
					if($conf[$i]["alias"] == null) $res[$conf[$i]["ip"]]["alias"] = $conf[$i]["ip"];
					else $res[$conf[$i]["ip"]]["alias"] = trim($conf[$i]["alias"]);
					$res[$conf[$i]["ip"]]["mac"] = trim($conf[$i]["mac"]);
				}	
			}	
		}
		return $res;
	}
}
?>
