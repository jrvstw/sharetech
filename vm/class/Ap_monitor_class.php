<?
class ap_managment {
	var $ap_list = "/PCONF/ap/ap_list";
	var $data_format = array("alias", "group_name", "ip", "enable", "network_mode", "ssid", "hide_ssid", "frequency", "security_mode", "wpa_algorithms", "key", "sn", "channel", "lan_ip", "lan_mac");
	var $configFile = array("LAN_SRC" => "/PDATA/IPMAC/GROUP/IPMACGROUP");
	var $sock = 0;
	function encrypt($data){
		$base64 = base64_encode($data);
		$total = strlen($base64);
		$shuffle = array();
	
		//Shuffle
		for($i = 0; $i < $total; $i++) {
			$dst = mt_rand(0, $total);
			$shuffle[ ] = $dst;
			
			//Swap
			$tmp = $base64[$i];
			$base64[$i] = $base64[$dst];
			$base64[$dst] = $tmp;
		}
	
		$key = base64_encode(gzcompress(serialize($shuffle)));
		$split = substr($base64, -4);
	
		return $split.$base64.$key;
	}
	
	function fetchURL($url, $data){
		$res_data = "";
		$url_parsed = parse_url($url);
		$host = $url_parsed["host"];
		$port = $url_parsed["port"];
		$path = $url_parsed["path"];
		$sPostValues = "ap_conf=" . urlencode($data);
		
		$sRequest = "";
		$sRequest .= "POST $path HTTP/1.0\r\n";
		$sRequest .= "Host: $sHost\r\n";
		$sRequest .= "Accept: text/html\r\n";
		$sRequest .= "Connection: close\r\n";
		$sRequest .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$sRequest .= "Content-length: " . strlen($sPostValues) . "\r\n";
		$sRequest .= "\r\n";
		$sRequest .= ($sPostValues) . "\r\n";
		
		$connect = $this->connect_socket($host, $port);

		if($connect == true){
			socket_write($this->sock, $sRequest);
			$res_data = socket_read($this->sock, 2048);
			socket_close($this->sock); //close
		}else if($connect == false){ // try other interface
			socket_close($this->sock);// close
			$inter_ip = $this->get_inter_ip(); //get all interface
			
			foreach($inter_ip as $val){
				$res_data = "";
				$connect_try = $this->connect_socket($host, $port, $val);
				if($connect_try == false){
					socket_close($this->sock);// Close
					continue;
				}else{
					socket_write($this->sock, $sRequest);
					$res_data = socket_read($this->sock, 2048);
					socket_close($this->sock);// Close
					break;
				}
			}
		}
		return $res_data;
	}
	
	function connect_socket($host, $port, $bind = false){
		$this->sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); // Create a new socket
		socket_set_option($this->sock,SOL_SOCKET, SO_SNDTIMEO, array("sec"=> 1, "usec"=> 0));
		socket_set_option($this->sock,SOL_SOCKET, SO_RCVTIMEO, array("sec"=> 90, "usec"=> 0));
		if($bind !== false){
			socket_bind($this->sock, $bind); // Bind the source address
		}
		
		$connect = @socket_connect($this->sock, $host , $port);// Connect to destination address
		return $connect;
	}
	
	function get_inter_ip(){
		$dev = array("eth0", "eth3");
		$interface =  array();
		foreach($dev as $val){
			unset($ret);
			exec("/sbin/ip addr show dev {$val}", $ret);
			foreach((Array)$ret as $line) {
				unset($match);
				if(preg_match('/inet ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)\/[0-9]+ brd/', $line, $match)) {
					$interface[] = $match[1];
				}
			}
		}
		return $interface;
	}
	
	function get_all_groupname(){
		$ap = array();
		if(is_file($this->ap_list)){
			$msg = file($this->ap_list);
			foreach($msg as $val){
				$tmp = array();
				$tmp = explode(",", trim($val));
				$ap[$tmp[1]] = $tmp[1];
			}
		}
		return $ap;
	}
	
	function get_all_ssid(){
		$ap = array();
		if(is_file($this->ap_list)){
			$msg = file($this->ap_list);
			foreach($msg as $val){
				$tmp = array();
				$tmp = explode(",", trim($val));
				$ap[$tmp[5]] = $tmp[5];
			}
		}
		return $ap;
	}
	
	function get_ap_list($ap_name){
		if(is_file($this->ap_list)){
			$msg = file($this->ap_list);
			foreach($msg as $val){
				$tmp = array();
				$ap = array();
				$tmp = explode(",", trim($val));
				
				if($ap_name == $tmp[11]){
					for($i = 0; $i < count($tmp); $i++){
						$ap[$this->data_format[$i]] = $tmp[$i];
					}
					return $ap;
				}
			}
		}
		return array();
	}
	
	
	
	
	function get_channel($all_channel){
		$auto_channel = array(1, 6, 11, 4, 7, 10, 3, 5, 8, 2, 9);
		
		foreach($auto_channel as $val){
			if(!in_array($val, $all_channel)){
				return $val;
			}
		}
		
		$end_channel = end($all_channel);
	
		do{
			$rand_channel = mt_rand(1, 11);
		}while($rand_channel == $end_channel);
	
		return $rand_channel;
	}
	
	function get_ap_channel($s_ssid, $s_group_name){
		$all_channel = array();
	
		if(is_file($this->ap_list)){
			$msg = file($this->ap_list);
			foreach($msg as $val){
				$tmp = array();
				$tmp = explode(",", trim($val));
				if($tmp[5] == $s_ssid && $s_group_name == $tmp[1]){
					$all_channel[] = ($tmp[7] == 0) ?  $tmp[12] : $tmp[7];
				}
			}
		}
		
		return $all_channel;
	}
	
	function get_all_list(){
		$all_list = array();
		if(is_file($this->ap_list)){
			$msg = file($this->ap_list);
			foreach($msg as $val){
				$tmp = array();
				$ap = array();
				$tmp = explode(",", trim($val));
				for($i = 0; $i < count($tmp); $i++){
					$ap[$this->data_format[$i]] = $tmp[$i];
				}
				$all_list[] = $ap;
			}
		}
		return $all_list;
	}
	
	function get_all_list_for_rqt(){
		$all_list = array();
		if(is_file($this->ap_list)){
			$msg = file($this->ap_list);
			foreach($msg as $val){
				$tmp = array();
				$ap = array();
				$tmp = explode(",", trim($val));
				for($i = 0; $i < count($tmp); $i++){
					$ap[$this->data_format[$i]] = $tmp[$i];
				}
				$index = $ap["lan_mac"] == "" ? $ap["lan_ip"] : $ap["lan_mac"];
				$all_list[$index] = $ap;
			}
		}
		return $all_list;
	}
	
	
	function clear_old_files($type, $delete_time_before){
		$nTime2Delete = time() - $delete_time_before;
		exec("/bin/ls /HDD/rrdpic/", $ret);
		
		foreach((array)$ret as $line){
			if(strpos($line, "ap".$type) !== false) {
				$file = "/HDD/rrdpic/".$line;
				if(filemtime($file) < $nTime2Delete){
					unlink($file);
				}
			}
		}
	}
	
	function jsArray(){
		$map = array();
		$map[] = "var map = new Array();";
		
		$dictionary = $this->configFile;
		
		foreach($dictionary as $key => $mapfile){
			if(file_exists($mapfile)){
				$file = file($mapfile);
				foreach((Array)$file as $line){
					$line = str_replace("\n", "", $line);
					if(trim($line) == ""){
						continue;
					}
					
					$elt = explode(",", $line);
					$mark = $elt[2] == "LAN" ? "L" : "D";
					$map[] = "map[\"{$mark}{$elt[0]}\"] = \"".str_replace("\n", "", $elt[1])."\";";
				}
			}
		}
		return implode("\n", $map) . "\n"; 
	}
	
	function jsArray_ap(){
		$map = array();
		$map[] = "var map = new Array();";
		
		$dictionary = $this->configFile;
		
		foreach($dictionary as $key => $mapfile){
			if(file_exists($mapfile)){
				$file = file($mapfile);
				foreach((Array)$file as $line){
					$line = str_replace("\n", "", $line);
					if(trim($line) == ""){
						continue;	//blank line
					}
					$elt = explode(",", $line);
					$tmp = array();
					$use_users = array();
					$tmp = explode("#", $elt[3]);
					
					$use_users = $this->get_select_sn($tmp, $tmp[1]);
					
					$mark = $elt[2] == "LAN" ? "L" : "D";
					$map[] = "map[\"{$mark}{$elt[0]}\"] = \"".$tmp[1]."#".$elt[1]."#".implode("#", $use_users)."\";";
				}
			}
		}
		return implode("\n", $map) . "\n"; 
		
	}
	
	function get_select_sn($sn, $ap_type){
		$type_array = array("ssid" => 5, "group" => 1, "ap" => 0);
		$ap_list = "/PCONF/ap/ap_list";
		$ap = array();
	
		if(is_file($ap_list)){
			$msg = file($ap_list);
			foreach($msg as $val){
				$tmp = array();
				$tmp = explode(",", trim($val));
				
				if(in_array($tmp[11], $sn)){
					$ap[$tmp[$type_array[$ap_type]]] = $tmp[$type_array[$ap_type]];
				}
			}
		}
		return $ap;
	}
}
?>