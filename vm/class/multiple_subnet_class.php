<?
include_once("/PDATA/apache/Program/Network/Lan_Inte_Use.php");
include_once("/PDATA/apache/class/prefix_convert_mask_class.php");
class multiple_subnet_class{
	function multiple_subnet_class(){
		
	}
	
	function lan_and_dmz(){
		include_once("/PDATA/apache/conf/fw.ini"); 
		$liu = new Lan_Inte_Use; 
		include_once("/PDATA/apache/Program/Network/class/Wan_Interface_class.php");
		$wic = new Wan_Interface_class();		
		$pcm = new prefix_convert_mask_class;
		if(file_exists($DEVL1M_FILE)) {
			$quality1 = $this->get_quality("LAN1");
			for($i=0;$i<count($quality1);$i++){
				$cmd1_enable = "/sbin/ifconfig " . $quality1[$i]["DEV"] . " " . $quality1[$i]["IP"] . " netmask " . $quality1[$i]["MASK"];	
				$LAN1_PREFIX = $pcm->mask2prefix($quality1[$i]["MASK"]);
				$LAN1_IP_MASK = $liu->cidr($quality1[$i]["IP"]."/".$LAN1_PREFIX);
				$cmd1_ipset = "/PGRAM/ipsets/sbin/ipset -A LAN_IP_MASK ".$LAN1_IP_MASK;
				
				if($quality1[$i]["ENABLED"] == "ON") {
					if($quality1[$i]["DEV"] != "1") exec($cmd1_enable);
					exec($cmd1_ipset);    
				}
			}
		}
		
		if($wic->get_bri() == false){
			if(file_exists($DEVL2M_FILE)) {
				$quality2 = $this->get_quality("LAN2");
				for($j=0;$j<count($quality2);$j++){
					$cmd2_enable = "/sbin/ifconfig " . $quality2[$j]["DEV"] . " " . $quality2[$j]["IP"] . " netmask " . $quality2[$j]["MASK"];	
					$LAN2_PREFIX = $pcm->mask2prefix($quality2[$j]["MASK"]);
					$LAN2_IP_MASK = $liu->cidr($quality2[$j]["IP"]."/".$LAN2_PREFIX);
					$cmd2_ipset = "/PGRAM/ipsets/sbin/ipset -A DMZ_IP_MASK ".$LAN2_IP_MASK;
					
					if($quality2[$j]["ENABLED"] == "ON") {
						if($quality2[$j]["DEV"] != "1") exec($cmd2_enable);
						exec($cmd2_ipset);    
					}
				}
			}
		}else{
			$quality_bri = $this->get_quality_bri();	
			for($k=0;$k<count($quality_bri);$k++){
				$cmd3_enable = "/sbin/ifconfig " . $quality_bri[$k]["DEV"] . " " . $quality_bri[$k]["IP"] . " netmask " . $quality_bri[$k]["MASK"];	
				$BRI_PREFIX = $pcm->mask2prefix($quality_bri[$k]["MASK"]);
				$BRI_IP_MASK = $liu->cidr($quality_bri[$k]["IP"]."/".$BRI_PREFIX);
				$cmd3_ipset = "/PGRAM/ipsets/sbin/ipset -A DMZ_IP_MASK ".$BRI_IP_MASK;	
				if($quality_bri[$k]["DEV"] != "1") {
					exec($cmd3_enable);
					exec($cmd3_ipset);    
				} else {
					exec("/PGRAM/ipsets/sbin/ipset -A BR_IP_MASK ".$BRI_IP_MASK);	
				}
			}
		}
	
		//vlan id interface
		$VLANFILE = "/PDATA/DEV/VLAN_802Q";
		if(is_file($VLANFILE)) {
			$msg = file($VLANFILE);
			for($i=0;$i<count($msg);$i++) {
				$a = explode (":::",trim($msg[$i]));
				if($a[3] == "LAN") $Iface = "eth0";
				if($a[3] == "LAN1") $Iface = "eth01";
				if($a[3] == "LAN2") $Iface = "eth02";
				if($a[3] == "DMZ") $Iface = "eth3";				
				exec("/sbin/ip link add link {$Iface} name {$Iface}.{$a[0]} type vlan id {$a[4]}");
				exec("/sbin/ifconfig {$Iface}.{$a[0]} {$a[1]} netmask {$a[2]}");
			}		
		}
	}
	
	function wan(){
		include_once("/PDATA/apache/conf/fw.ini"); 
		$liu = new Lan_Inte_Use; 
		$pcm = new prefix_convert_mask_class;
		exec("cat /ram/tmp/wanstatus",$msg0);
		
		$a = explode("=",trim($msg0[0]));
		if($a[1] != "OFF"){
			exec("cat /PDATA/DEV/WAN1DEV",$msgWAN1DEV);
			$aWAN1DEV = explode(",",trim($msgWAN1DEV[0]));

				if(file_exists($DEVW1M_FILE)){
					$qualityw1 = $this->get_wanMulsub("eth1");
					for($w1=0;$w1<count($qualityw1);$w1++){
						$cmdw1_enable = "/sbin/ifconfig " . $qualityw1[$w1]["dev"] . " " . $qualityw1[$w1]["ip"] . " netmask " . $qualityw1[$w1]["mask"];	
						$WAN1_PREFIX = $pcm->mask2prefix($qualityw1[$w1]["mask"]);
						$WAN1_IP_MASK = $liu->cidr($qualityw1[$w1]["ip"]."/".$WAN1_PREFIX);
						$cmdw1_ipset = "/PGRAM/ipsets/sbin/ipset -A WAN1_IP_MASK ".$WAN1_IP_MASK;
						$cmdw1_ipset_br = "/PGRAM/ipsets/sbin/ipset -A BR_IP_MASK ".$WAN1_IP_MASK;
						$cmdw1_down = "/sbin/ifconfig " . $qualityw1[$w1]["dev"] . " down";
						$cmdw1_ipsetd = "/PGRAM/ipsets/sbin/ipset -D WAN1_IP_MASK ".$WAN1_IP_MASK;
						$cmdw1_ipsetd_br = "/PGRAM/ipsets/sbin/ipset -D BR_IP_MASK ".$WAN1_IP_MASK;

						if($qualityw1[$w1]["enabled"] == "ON" && trim($aWAN1DEV[0]) == "STATIC") {
							unset($msg_wan1_dev);
							unset($msg_wan1_ipset1);
							unset($msg_wan1_ipset2);
							if($a[1]=="br0"){
								$chk_wan1_dev = 'ip addr show br0 | grep "'.$qualityw1[$w1]["ip"].'/"';
								$chk_wan1_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN1_IP_MASK | grep "'.$WAN1_IP_MASK.'"';
								$chk_wan1_ipset2 = '/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$WAN1_IP_MASK.'"';
								exec($chk_wan1_dev,$msg_wan1_dev);
								exec($chk_wan1_ipset1,$msg_wan1_ipset1);
								exec($chk_wan1_ipset2,$msg_wan1_ipset2);
								if(count($msg_wan1_dev) == 0) exec($cmdw1_enable);	
								if(count($msg_wan1_ipset1) == 0) exec($cmdw1_ipset); 
								if(count($msg_wan1_ipset2) == 0) exec($cmdw1_ipset_br);   							
							} else if($a[1]=="br01") {
								$chk_wan1_dev = 'ip addr show br01 | grep "'.$qualityw1[$w1]["ip"].'/"';
								$chk_wan1_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN1_IP_MASK | grep "'.$WAN1_IP_MASK.'"';
								$chk_wan1_ipset2 = '/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$WAN1_IP_MASK.'"';
								exec($chk_wan1_dev,$msg_wan1_dev);
								exec($chk_wan1_ipset1,$msg_wan1_ipset1);
								exec($chk_wan1_ipset2,$msg_wan1_ipset2);
								if(count($msg_wan1_dev) == 0) exec($cmdw1_enable);	
								if(count($msg_wan1_ipset1) == 0) exec($cmdw1_ipset); 
								if(count($msg_wan1_ipset2) == 0) exec($cmdw1_ipset_br);   
							} else {
								$chk_wan1_dev = 'ip addr show eth1 | grep "'.$qualityw1[$w1]["ip"].'/"';
								$chk_wan1_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN1_IP_MASK | grep "'.$WAN1_IP_MASK.'"';
								$chk_wan1_ipset2 = '/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$WAN1_IP_MASK.'"';
								exec($chk_wan1_dev,$msg_wan1_dev);
								exec($chk_wan1_ipset1,$msg_wan1_ipset1);
								exec($chk_wan1_ipset2,$msg_wan1_ipset2);
								if(count($msg_wan1_dev) == 0) exec($cmdw1_enable);	
								if(count($msg_wan1_ipset1) == 0) exec($cmdw1_ipset);  
								if(count($msg_wan1_ipset2) != 0) exec($cmdw1_ipsetd_br);   
							}
						}else{
							exec($cmdw1_down);
							exec($cmdw1_ipsetd);
							exec($cmdw1_ipsetd_br);	
						}
					}
				}

				if($a[1]=="br0" && file_exists("/PDATA/DEV/BRIDEV_M")){
					$quality_bri = $this->get_quality_bri();	
					for($k=0;$k<count($quality_bri);$k++){
						$BRI_PREFIX = $pcm->mask2prefix($quality_bri[$k]["MASK"]);
						$BRI_IP_MASK = $liu->cidr($quality_bri[$k]["IP"]."/".$BRI_PREFIX);
						$chk_dmzbri_dev = 'ip addr show br0 | grep "'.$quality_bri[$k]["DEV"].'"';
						$chk_dmzbri_ipset1 = '/PGRAM/ipsets/sbin/ipset -L DMZ_IP_MASK | grep "'.$BRI_IP_MASK.'"';
						$cmd_dmzbri_enable = "/sbin/ifconfig " . $quality_bri[$k]["DEV"] . " " . $quality_bri[$k]["IP"] . " netmask " . $quality_bri[$k]["MASK"];	
						$cmd_dmzbri_ipset = "/PGRAM/ipsets/sbin/ipset -A DMZ_IP_MASK ".$BRI_IP_MASK;	
						if($quality_bri[$k]["DEV"] != "1") {
							unset($msg_dmzbri_dev);
							unset($msg_dmzbri_ipset1);
							exec($chk_dmzbri_dev,$msg_dmzbri_dev);
							exec($chk_dmzbri_ipset1,$msg_dmzbri_ipset1);
							if(count($msg_dmzbri_dev) == 0) exec($cmd_dmzbri_enable);	
							if(count($msg_dmzbri_ipset1) == 0) exec($cmd_dmzbri_ipset);     
						} else {
							unset($msg_dmzbri_ipset1);
							exec('/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$BRI_IP_MASK.'"',$msg_dmzbri_ipset1);
							if(count($msg_dmzbri_ipset1) == 0) exec("/PGRAM/ipsets/sbin/ipset -A BR_IP_MASK ".$BRI_IP_MASK);     
						}
					}	
				}

				if($a[1]=="br01" && file_exists("/PDATA/DEV/BRIDEV_M_G1")){
					$quality_bri = $this->get_quality_bri_g1();	
					for($k=0;$k<count($quality_bri);$k++){
						$BRI_PREFIX = $pcm->mask2prefix($quality_bri[$k]["MASK"]);
						$BRI_IP_MASK = $liu->cidr($quality_bri[$k]["IP"]."/".$BRI_PREFIX);
						$chk_dmzbri_dev = 'ip addr show br01 | grep "'.$quality_bri[$k]["DEV"].'"';
						$chk_dmzbri_ipset1 = '/PGRAM/ipsets/sbin/ipset -L DMZ_IP_MASK | grep "'.$BRI_IP_MASK.'"';
						$cmd_dmzbri_enable = "/sbin/ifconfig " . $quality_bri[$k]["DEV"] . " " . $quality_bri[$k]["IP"] . " netmask " . $quality_bri[$k]["MASK"];	
						$cmd_dmzbri_ipset = "/PGRAM/ipsets/sbin/ipset -A DMZ_IP_MASK ".$BRI_IP_MASK;	
						if($quality_bri[$k]["DEV"] != "1") {
							unset($msg_dmzbri_dev);
							unset($msg_dmzbri_ipset1);
							exec($chk_dmzbri_dev,$msg_dmzbri_dev);
							exec($chk_dmzbri_ipset1,$msg_dmzbri_ipset1);
							if(count($msg_dmzbri_dev) == 0) exec($cmd_dmzbri_enable);	
							if(count($msg_dmzbri_ipset1) == 0) exec($cmd_dmzbri_ipset);     
						} else {
							unset($msg_dmzbri_ipset1);
							exec('/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$BRI_IP_MASK.'"',$msg_dmzbri_ipset1);
							if(count($msg_dmzbri_ipset1) == 0) exec("/PGRAM/ipsets/sbin/ipset -A BR_IP_MASK ".$BRI_IP_MASK);     
						}
					}	
				}
		}
		
		$b = explode("=",trim($msg0[1]));
		if($b[1] != "OFF"){
			exec("cat /PDATA/DEV/WAN2DEV",$msgWAN2DEV);
			$aWAN2DEV = explode(",",trim($msgWAN2DEV[0]));
			
				if(file_exists($DEVW2M_FILE)){
					$qualityw2 = $this->get_wanMulsub("eth2");
					for($w2=0;$w2<count($qualityw2);$w2++){
						$cmdw2_enable = "/sbin/ifconfig " . $qualityw2[$w2]["dev"] . " " . $qualityw2[$w2]["ip"] . " netmask " . $qualityw2[$w2]["mask"];	
						$WAN2_PREFIX = $pcm->mask2prefix($qualityw2[$w2]["mask"]);
						$WAN2_IP_MASK = $liu->cidr($qualityw2[$w2]["ip"]."/".$WAN2_PREFIX);
						$cmdw2_ipset = "/PGRAM/ipsets/sbin/ipset -A WAN2_IP_MASK ".$WAN2_IP_MASK;
						$cmdw2_ipset_br = "/PGRAM/ipsets/sbin/ipset -A BR_IP_MASK ".$WAN2_IP_MASK;
						$cmdw2_down = "/sbin/ifconfig " . $qualityw2[$w2]["dev"] . " down";
						$cmdw2_ipsetd = "/PGRAM/ipsets/sbin/ipset -D WAN2_IP_MASK ".$WAN2_IP_MASK;
						$cmdw2_ipsetd_br = "/PGRAM/ipsets/sbin/ipset -D BR_IP_MASK ".$WAN2_IP_MASK;
							
						if($qualityw2[$w2]["enabled"] == "ON" && trim($aWAN2DEV[0]) == "STATIC") {
							unset($msg_wan2_dev);
							unset($msg_wan2_ipset1);
							unset($msg_wan2_ipset2);
							if($b[1]=="br02") {
								$chk_wan2_dev = 'ip addr show br02 | grep "'.$qualityw2[$w2]["ip"].'/"';
								$chk_wan2_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN2_IP_MASK | grep "'.$WAN2_IP_MASK.'"';
								$chk_wan2_ipset2 = '/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$WAN2_IP_MASK.'"';
								exec($chk_wan2_dev,$msg_wan2_dev);
								exec($chk_wan2_ipset1,$msg_wan2_ipset1);
								exec($chk_wan2_ipset2,$msg_wan2_ipset2);
								if(count($msg_wan2_dev) == 0) exec($cmdw2_enable);	
								if(count($msg_wan2_ipset1) == 0) exec($cmdw2_ipset);   
								if(count($msg_wan2_ipset2) == 0) exec($cmdw2_ipset_br);   							
							} else {
								$chk_wan2_dev = 'ip addr show eth2 | grep "'.$qualityw2[$w2]["ip"].'/"';
								$chk_wan2_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN2_IP_MASK | grep "'.$WAN2_IP_MASK.'"';
								$chk_wan2_ipset2 = '/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$WAN2_IP_MASK.'"';
								exec($chk_wan2_dev,$msg_wan2_dev);
								exec($chk_wan2_ipset1,$msg_wan2_ipset1);
								exec($chk_wan2_ipset2,$msg_wan2_ipset2);
								if(count($msg_wan2_dev) == 0) exec($cmdw2_enable);	
								if(count($msg_wan2_ipset1) == 0) exec($cmdw2_ipset);   
								if(count($msg_wan2_ipset2) != 0) exec($cmdw2_ipsetd_br);   
							}
						}else{
							exec($cmdw2_down);
							exec($cmdw2_ipsetd);	
							exec($cmdw2_ipsetd_br);	
						}
					}
				}

				if($b[1]=="br02" && file_exists("/PDATA/DEV/BRIDEV_M_G2")){
					$quality_bri = $this->get_quality_bri_g2();	
					for($k=0;$k<count($quality_bri);$k++){
						$BRI_PREFIX = $pcm->mask2prefix($quality_bri[$k]["MASK"]);
						$BRI_IP_MASK = $liu->cidr($quality_bri[$k]["IP"]."/".$BRI_PREFIX);
						$chk_dmzbri_dev = 'ip addr show br02 | grep "'.$quality_bri[$k]["DEV"].'"';
						$chk_dmzbri_ipset1 = '/PGRAM/ipsets/sbin/ipset -L DMZ_IP_MASK | grep "'.$BRI_IP_MASK.'"';
						$cmd_dmzbri_enable = "/sbin/ifconfig " . $quality_bri[$k]["DEV"] . " " . $quality_bri[$k]["IP"] . " netmask " . $quality_bri[$k]["MASK"];	
						$cmd_dmzbri_ipset = "/PGRAM/ipsets/sbin/ipset -A DMZ_IP_MASK ".$BRI_IP_MASK;	
						if($quality_bri[$k]["DEV"] != "1") {
							unset($msg_dmzbri_dev);
							unset($msg_dmzbri_ipset1);
							exec($chk_dmzbri_dev,$msg_dmzbri_dev);
							exec($chk_dmzbri_ipset1,$msg_dmzbri_ipset1);
							if(count($msg_dmzbri_dev) == 0) exec($cmd_dmzbri_enable);	
							if(count($msg_dmzbri_ipset1) == 0) exec($cmd_dmzbri_ipset);     
						} else {
							unset($msg_dmzbri_ipset1);
							exec('/PGRAM/ipsets/sbin/ipset -L BR_IP_MASK | grep "'.$BRI_IP_MASK.'"',$msg_dmzbri_ipset1);
							if(count($msg_dmzbri_ipset1) == 0) exec("/PGRAM/ipsets/sbin/ipset -A BR_IP_MASK ".$BRI_IP_MASK);     
						}
					}	
				}
		}
		
		$c = explode("=",trim($msg0[2]));
		if($c[1] != "OFF"){
			exec("cat /PDATA/DEV/WAN3DEV",$msgWAN3DEV);
			$aWAN3DEV = explode(",",trim($msgWAN3DEV[0]));
			if(file_exists($DEVW3M_FILE)){
				$qualityw3 = $this->get_wanMulsub("eth4");
				for($w3=0;$w3<count($qualityw3);$w3++){
						$cmdw3_enable = "/sbin/ifconfig " . $qualityw3[$w3]["dev"] . " " . $qualityw3[$w3]["ip"] . " netmask " . $qualityw3[$w3]["mask"];	
						$WAN3_PREFIX = $pcm->mask2prefix($qualityw3[$w3]["mask"]);
						$WAN3_IP_MASK = $liu->cidr($qualityw1[$w3]["ip"]."/".$WAN3_PREFIX);
						$cmdw3_ipset = "/PGRAM/ipsets/sbin/ipset -A WAN3_IP_MASK ".$WAN3_IP_MASK;
						
						$cmdw3_down = "/sbin/ifconfig " . $qualityw3[$w3]["dev"] . " down";
						$cmdw3_ipsetd = "/PGRAM/ipsets/sbin/ipset -D WAN3_IP_MASK ".$WAN3_IP_MASK;
						
						if($qualityw3[$w3]["enabled"] == "ON" && trim($aWAN3DEV[0]) == "STATIC") {
							unset($msg_wan3_dev);
							unset($msg_wan3_ipset1);
							$chk_wan3_dev = 'ip addr show eth4 | grep "'.$qualityw3[$w3]["ip"].'/"';
							$chk_wan3_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN3_IP_MASK | grep "'.$WAN3_IP_MASK.'"';
							exec($chk_wan3_dev,$msg_wan3_dev);
							exec($chk_wan3_ipset1,$msg_wan3_ipset1);
							if(count($msg_wan3_dev) == 0) exec($cmdw3_enable);	
							if(count($msg_wan3_ipset1) == 0) exec($cmdw3_ipset);   
						}else{
							exec($cmdw3_down);
							exec($cmdw3_ipsetd);	
						}
					}
			}
		}
		$d = explode("=",trim($msg0[3]));
		if($d[1] != "OFF"){
			exec("cat /PDATA/DEV/WAN4DEV",$msgWAN4DEV);
			$aWAN4DEV = explode(",",trim($msgWAN4DEV[0]));
			if(file_exists($DEVW4M_FILE)){
				$qualityw4 = $this->get_wanMulsub("eth5");
				for($w4=0;$w4<count($qualityw4);$w4++){
						$cmdw4_enable = "/sbin/ifconfig " . $qualityw4[$w4]["dev"] . " " . $qualityw4[$w4]["ip"] . " netmask " . $qualityw4[$w4]["mask"];	
						$WAN4_PREFIX = $pcm->mask2prefix($qualityw4[$w4]["mask"]);
						$WAN4_IP_MASK = $liu->cidr($qualityw4[$w4]["ip"]."/".$WAN4_PREFIX);
						$cmdw4_ipset = "/PGRAM/ipsets/sbin/ipset -A WAN4_IP_MASK ".$WAN4_IP_MASK;
						
						$cmdw4_down = "/sbin/ifconfig " . $qualityw4[$w4]["dev"] . " down";
						$cmdw4_ipsetd = "/PGRAM/ipsets/sbin/ipset -D WAN4_IP_MASK ".$WAN4_IP_MASK;
						
					
						if($qualityw4[$w4]["enabled"] == "ON" && trim($aWAN4DEV[0]) == "STATIC") {
							unset($msg_wan4_dev);
							unset($msg_wan4_ipset1);
							$chk_wan4_dev = 'ip addr show eth5 | grep "'.$qualityw4[$w4]["ip"].'/"';
							$chk_wan4_ipset1 = '/PGRAM/ipsets/sbin/ipset -L WAN4_IP_MASK | grep "'.$WAN4_IP_MASK.'"';
							exec($chk_wan4_dev,$msg_wan4_dev);
							exec($chk_wan4_ipset1,$msg_wan4_ipset1);
							if(count($msg_wan4_dev) == 0) exec($cmdw4_enable);	
							if(count($msg_wan4_ipset1) == 0) exec($cmdw4_ipset);   
						}else{
							exec($cmdw4_down);
							exec($cmdw4_ipsetd);	
						}
					}
			}
		}
	}
	
	function get_quality($DEV){
		include("/PDATA/apache/conf/fw.ini");
		switch($DEV){
			case "LAN1":
				$DEVL_FILE = $DEVL1_FILE;
				$DEVLM_FILE = $DEVL1M_FILE;     
				break;
			case "LAN2":
				$DEVL_FILE = $DEVL2_FILE;
				$DEVLM_FILE = $DEVL2M_FILE;    
				break;
		}
		if(!is_file($DEVLM_FILE)) return false;
			
		if(is_file($DEVLM_FILE)) {
			$msg1 = file($DEVLM_FILE);
			for($j=0;$j<count($msg1);$j++){
				unset($a);
				$a = explode(",",trim($msg1[$j]));     
				$quality[$j]["DEV"] = trim($a[0]);
				$quality[$j]["ENABLED"] = trim($a[1]);
				$quality[$j]["IP"] = trim($a[2]);
				$quality[$j]["MASK"] = trim($a[3]);
				$quality[$j]["UP"] = trim($a[4]);
				$quality[$j]["DOWN"] = trim($a[5]);
				$quality[$j]["MAC"] = trim($a[6]);
				$quality[$j]["NAME"] = trim($a[7]); 
				$quality[$j]["BIND_WAN1"] = trim($a[8]);	  
				$quality[$j]["BIND_WAN2"] = trim($a[9]);
				$quality[$j]["BIND_WAN3"] = trim($a[10]);	  
				$quality[$j]["BIND_WAN4"] = trim($a[11]);
			}
		}
			
		return $quality;
	}
	
	function get_quality_bri(){
		$DEVFILE_M = "/PDATA/DEV/BRIDEV_M";    
		if(!is_file($DEVFILE_M)) return false;
		$msg1 = file($DEVFILE_M);
		for($i=0;$i<count($msg1);$i++){
			unset($a);
			$a = explode(",",trim($msg1[$i])); 
			$quality[$i]["DEV"] = trim($a[0]);
			$quality[$i]["ENABLED"] = trim($a[1]); 
			$quality[$i]["IP"] = trim($a[2]);
			$quality[$i]["MASK"] = trim($a[3]);
			$quality[$i]["NAME"] = trim($a[4]);
		}
		return $quality;
	}

	function get_quality_bri_g1(){
		$DEVFILE_M = "/PDATA/DEV/BRIDEV_M_G1";    
		if(!is_file($DEVFILE_M)) return false;
		$msg1 = file($DEVFILE_M);
		for($i=0;$i<count($msg1);$i++){
			unset($a);
			$a = explode(",",trim($msg1[$i])); 
			$quality[$i]["DEV"] = trim($a[0]);
			$quality[$i]["ENABLED"] = trim($a[1]); 
			$quality[$i]["IP"] = trim($a[2]);
			$quality[$i]["MASK"] = trim($a[3]);
			$quality[$i]["NAME"] = trim($a[4]);
		}
		return $quality;
	}

	function get_quality_bri_g2(){
		$DEVFILE_M = "/PDATA/DEV/BRIDEV_M_G2";    
		if(!is_file($DEVFILE_M)) return false;
		$msg1 = file($DEVFILE_M);
		for($i=0;$i<count($msg1);$i++){
			unset($a);
			$a = explode(",",trim($msg1[$i])); 
			$quality[$i]["DEV"] = trim($a[0]);
			$quality[$i]["ENABLED"] = trim($a[1]); 
			$quality[$i]["IP"] = trim($a[2]);
			$quality[$i]["MASK"] = trim($a[3]);
			$quality[$i]["NAME"] = trim($a[4]);
		}
		return $quality;
	}

	function add_subnet_rule_LAN(){
		$liu = new Lan_Inte_Use; 
		$pcm = new prefix_convert_mask_class;
		$ruledel_cmd = "/sbin/ip rule del pref";
		$ruleshow_cmd = "/sbin/ip rule show";
		$ruleadd_cmd2 = "/sbin/ip rule add to";
		$all_landev = $this->get_quality("LAN1");
		$prefnew = 0; 
		for($j=0;$j<count($all_landev);$j++){
			if($all_landev[$j]["ENABLED"] == "ON" && $all_landev[$j]["DEV"] != "1"){
				$prefnew++;								
				$ip_prefix = $pcm->mask2prefix($all_landev[$j]["MASK"]); 
				if(strpos($all_landev[$j]["DEV"], "eth01") !== false) {
					$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".$ip_prefix." table main pref ".(25300+$prefnew); 
				} else if(strpos($all_landev[$j]["DEV"], "eth02") !== false) {
					$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".$ip_prefix." table main pref ".(25400+$prefnew);    
				} else {
					$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".$ip_prefix." table main pref ".(25100+$prefnew);  
				}				
				exec($rule_cmd);
			}
		}	
	}
	
	function add_subnet_rule_DMZ(){
		$liu = new Lan_Inte_Use; 
		$pcm = new prefix_convert_mask_class;
		$ruledel_cmd = "/sbin/ip rule del pref";
		$ruleshow_cmd = "/sbin/ip rule show";
		$ruleadd_cmd2 = "/sbin/ip rule add to";
		$all_landev = $this->get_quality("LAN2");
		$prefnew = 25200; 
		for($j=0;$j<count($all_landev);$j++){
			if($all_landev[$j]["ENABLED"] == "ON" && $all_landev[$j]["DEV"] != "1"){
				$prefnew++;
				$ip_prefix = $pcm->mask2prefix($all_landev[$j]["MASK"]); 
				$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".$ip_prefix." table main pref ".$prefnew;    
				exec($rule_cmd);
			}
		}	
	}

	function get_wanMulsub($dev){
		include("/PDATA/apache/conf/fw.ini");

		switch($dev){
			case "eth1":
				$DEVFILE_M = $DEVW1M_FILE;      
				break;
			case "eth2":
				$DEVFILE_M = $DEVW2M_FILE;
				break;
			case "eth4":
				$DEVFILE_M = $DEVW3M_FILE;
				break;
			case "eth5":
				$DEVFILE_M = $DEVW4M_FILE;
				break;
		}
		
		if(!is_file($DEVFILE_M)) return false;
		
		if(is_file($DEVFILE_M)) {
			$msg1 = file($DEVFILE_M);
			for($i=0;$i<count($msg1);$i++){
				unset($a);
				$a = explode(",",trim($msg1[$i]));     
				$quality[$i]["dev"] = trim($a[0]);
				$quality[$i]["enabled"] = trim($a[1]);
				$quality[$i]["ip"] = trim($a[2]);
				$quality[$i]["mask"] = trim($a[3]);
				$quality[$i]["name"] = trim($a[4]); 
			}
		}
		
		return $quality;
	}
	
	function get_natbind($DEV){
		unset($msg1);
		switch($DEV){
			case "WAN1":
				$snat_cmd = "/PGRAM/ipt4/sbin/iptables -t nat -L OUT_TO_WAN1 -n --line-numbers |grep \"SNAT\"";
				break;
			case "WAN2":
				$snat_cmd = "/PGRAM/ipt4/sbin/iptables -t nat -L OUT_TO_WAN2 -n --line-numbers |grep \"SNAT\"";
				break;
			case "WAN3":
				$snat_cmd = "/PGRAM/ipt4/sbin/iptables -t nat -L OUT_TO_WAN3 -n --line-numbers |grep \"SNAT\"";
				break;
			case "WAN4":
				$snat_cmd = "/PGRAM/ipt4/sbin/iptables -t nat -L OUT_TO_WAN4 -n --line-numbers |grep \"SNAT\"";
				break;
		}
		
		exec("$snat_cmd",$msg1);
		if(count($msg1) == 0) return false;
		for($i=0;$i<count($msg1);$i++){
			$a = preg_split ("/[\s,]+/",$msg1[$i]);
			$allnatbind[$i]["source_subnet"] = trim($a[4]);
			$b = explode(":",$a[6]);
			$allnatbind[$i]["bind_ip"] = trim($b[1]);	
		}
		
		return $allnatbind;
	}
	
	function get_routeip_mask($routeip){
		include_once("/PDATA/apache/conf/fw.ini"); 
		$liu = new Lan_Inte_Use; 
		$pcm = new prefix_convert_mask_class;
		$quality1 = $this->get_quality("LAN1");
		$quality2 = $this->get_quality("LAN2");
		if($quality1){
			for($i=0;$i<count($quality1);$i++){
				if($quality1[$i]["IP"] == $routeip) {
					unset($routeip_prefix);
					unset($routeip_subnet);
					unset($routeip_data);
					$routeip_prefix = $pcm->mask2prefix($quality1[$i]["MASK"]);
					$routeip_subnet = $liu->cidr($routeip."/".$routeip_prefix);
					$routeip_data = str_replace("/","=",$routeip_subnet);
					return $routeip_data;	
				}	
			}	
		}	
		if($quality2){
			for($j=0;$j<count($quality2);$j++){
				if($quality2[$j]["IP"] == $routeip) {
					unset($routeip_prefix);
					unset($routeip_subnet);
					unset($routeip_data);
					$routeip_prefix = $pcm->mask2prefix($quality2[$j]["MASK"]);
					$routeip_subnet = $liu->cidr($routeip."/".$routeip_prefix);
					$routeip_data = str_replace("/","=",$routeip_subnet);
					return $routeip_data;	
				}	
			}	
		}	
	}
	
	function get_LAN_DMZ_onbind_ip() {
		exec("/sbin/ip addr | grep \"inet \"", $ret);
		$ipmask = array();
		foreach((Array)$ret as $line) {
			unset($match);
			if(preg_match('/\s(\d+\.\d+\.\d+\.\d+)\/(\d+)\s.+\seth[03]/', $line, $match)) {
				$ipmask[] = $match[1];
			}
		}	
		return $ipmask;
	}
	
	function get_proxy_ip($onBindIP, $aIP) {
		foreach((Array)$onBindIP as $val) {
			if($val == $aIP)
			{//IP addresses that are bound to the interface
				return $aIP;
			}
		}

		$m1 = ip2long($aIP);
		for($i = 31; $i >= 16; $i--)
		{//Using mask 31 ~ 16 compared with a suitable IP
			foreach((Array)$onBindIP as $val)
			{
				$m2 = ip2long($val);
				if($m1 >> (32 - $i) == $m2 >> (32 - $i)) {
					//echo "match IP $val\n";
					return $val;
				}
			}
		}		
		
		return $aIP;	//All conditions are not established
	}
	
	function update_natbind(){
		$dev_array = array("WAN1","WAN2","WAN3","WAN4");	
		$Route_chkfile_w1 = "/CFH3/ROUTE_MODE_RUN_W1";
		$Route_chkfile_w2 = "/CFH3/ROUTE_MODE_RUN_W2";
		$Route_chkfile_w3 = "/CFH3/ROUTE_MODE_RUN_W3";
		$Route_chkfile_w4 = "/CFH3/ROUTE_MODE_RUN_W4";

		$onBindIP = $this->get_LAN_DMZ_onbind_ip();
		
		for($i=0;$i<count($dev_array);$i++){
			unset($save_data);
			
			if($dev_array[$i] == "WAN1") {
				$allnatbind = $this->get_natbind($dev_array[$i]);	
				if($allnatbind){
					for($j=0;$j<count($allnatbind);$j++){
						if(strpos($allnatbind[$j]["source_subnet"],"/")) {
							$resource_subnet = explode("/",$allnatbind[$j]["source_subnet"]);
							$data_str = trim($resource_subnet[0]) . "=" . trim($resource_subnet[1]) . "=" . trim($allnatbind[$j]["bind_ip"]);
						}else{
							$data_str = trim($allnatbind[$j]["source_subnet"]) . "=" . "32" . "=" . trim($allnatbind[$j]["bind_ip"]); 
						}					
						$save_data[] = $data_str;
					}
				}
				if(file_exists("/PDATA/DEV/ROUTE_MODE_START_W1")) {
					$msgroute1 = file("/PDATA/DEV/ROUTE_MODE_START_W1");
					for($iw1=0;$iw1<count($msgroute1);$iw1++){
						$aw1 = explode(",",trim($msgroute1[$iw1]));
						$routeip_data = $this->get_routeip_mask(trim($aw1[0]));
						$proxyIP = $this->get_proxy_ip($onBindIP, trim($aw1[0]));
						$save_data[] = $routeip_data . "=" . $proxyIP;
					}
				}
				$this->save_natbind_data($dev_array[$i],$save_data);
			}else if($dev_array[$i] == "WAN2") {
				$allnatbind = $this->get_natbind($dev_array[$i]);	
				if($allnatbind){
					for($j=0;$j<count($allnatbind);$j++){
						if(strpos($allnatbind[$j]["source_subnet"],"/")) {
							$resource_subnet = explode("/",$allnatbind[$j]["source_subnet"]);
							$data_str = trim($resource_subnet[0]) . "=" . trim($resource_subnet[1]) . "=" . trim($allnatbind[$j]["bind_ip"]);
						}else{
							$data_str = trim($allnatbind[$j]["source_subnet"]) . "=" . "32" . "=" . trim($allnatbind[$j]["bind_ip"]); 
						}
						
						$save_data[] = $data_str;
					}
				}
				if(file_exists("/PDATA/DEV/ROUTE_MODE_START_W2")) {
					$msgroute2 = file("/PDATA/DEV/ROUTE_MODE_START_W2");
					for($iw2=0;$iw2<count($msgroute2);$iw2++){
						$aw2 = explode(",",trim($msgroute2[$iw2]));
						$routeip_data = $this->get_routeip_mask(trim($aw2[0]));
						$proxyIP = $this->get_proxy_ip($onBindIP, trim($aw2[0]));
						$save_data[] = $routeip_data . "=" . $proxyIP;
					}
				}
				$this->save_natbind_data($dev_array[$i],$save_data);	
			}else if($dev_array[$i] == "WAN3") {
				$allnatbind = $this->get_natbind($dev_array[$i]);	
				if($allnatbind){
					for($j=0;$j<count($allnatbind);$j++){
						if(strpos($allnatbind[$j]["source_subnet"],"/")) {
							$resource_subnet = explode("/",$allnatbind[$j]["source_subnet"]);
							$data_str = trim($resource_subnet[0]) . "=" . trim($resource_subnet[1]) . "=" . trim($allnatbind[$j]["bind_ip"]);
						}else{
							$data_str = trim($allnatbind[$j]["source_subnet"]) . "=" . "32" . "=" . trim($allnatbind[$j]["bind_ip"]); 
						}
						
						$save_data[] = $data_str;
					}
				}
				if(file_exists("/PDATA/DEV/ROUTE_MODE_START_W3")) {
					$msgroute3 = file("/PDATA/DEV/ROUTE_MODE_START_W3");
					for($iw3=0;$iw3<count($msgroute3);$iw3++){
						$aw3 = explode(",",trim($msgroute3[$iw3]));
						$routeip_data = $this->get_routeip_mask(trim($aw3[0]));
						$proxyIP = $this->get_proxy_ip($onBindIP, trim($aw3[0]));
						$save_data[] = $routeip_data . "=" . $proxyIP;
					}
				}
				$this->save_natbind_data($dev_array[$i],$save_data);	
			}else if($dev_array[$i] == "WAN4") {
				$allnatbind = $this->get_natbind($dev_array[$i]);	
				if($allnatbind){
					for($j=0;$j<count($allnatbind);$j++){
						if(strpos($allnatbind[$j]["source_subnet"],"/")) {
							$resource_subnet = explode("/",$allnatbind[$j]["source_subnet"]);
							$data_str = trim($resource_subnet[0]) . "=" . trim($resource_subnet[1]) . "=" . trim($allnatbind[$j]["bind_ip"]);
						}else{
							$data_str = trim($allnatbind[$j]["source_subnet"]) . "=" . "32" . "=" . trim($allnatbind[$j]["bind_ip"]); 
						}
						
						$save_data[] = $data_str;
					}
				}
				if(file_exists("/PDATA/DEV/ROUTE_MODE_START_W4")) {
					$msgroute4 = file("/PDATA/DEV/ROUTE_MODE_START_W4");
					for($iw4=0;$iw4<count($msgroute4);$iw4++){
						$aw4 = explode(",",trim($msgroute4[$iw4]));
						$routeip_data = $this->get_routeip_mask(trim($aw4[0]));
						$proxyIP = $this->get_proxy_ip($onBindIP, trim($aw4[0]));
						$save_data[] = $routeip_data . "=" . $proxyIP;
					}
				}
				$this->save_natbind_data($dev_array[$i],$save_data);	
			}
		}
	}
	
	function save_natbind_data($dev,$save_data){

		switch($dev){
			case "WAN1":
				$DEVFILE = "/ram/tmp/wanstatus_wan1";
				$DEVFILE_TMP = "/ram/tmp/wanstatus_wan1_tmp";     
				break;
			case "WAN2":
				$DEVFILE = "/ram/tmp/wanstatus_wan2";
				$DEVFILE_TMP = "/ram/tmp/wanstatus_wan2_tmp";
				break;
			case "WAN3":
				$DEVFILE = "/ram/tmp/wanstatus_wan3";
				$DEVFILE_TMP = "/ram/tmp/wanstatus_wan3_tmp";
				break;
			case "WAN4":
				$DEVFILE = "/ram/tmp/wanstatus_wan4";
				$DEVFILE_TMP = "/ram/tmp/wanstatus_wan4_tmp";
				break;
		}
		
		if(!$save_data){
			if(is_file($DEVFILE)) unlink($DEVFILE);
		}else{
			$buf = implode("\n",$save_data);
			if(is_file($DEVFILE_TMP)) unlink($DEVFILE_TMP);
			$fp = fopen($DEVFILE_TMP,"x");
			fwrite($fp,$buf);
			fclose($fp);
			
			$cmd1 = "chmod 600 ".$DEVFILE_TMP;
			$cmd2 = "cp ".$DEVFILE_TMP." ".$DEVFILE;
			$cmd3 = "rm -rf ".$DEVFILE_TMP;
			 
			exec($cmd1);
			if(md5_file($DEVFILE_TMP) != md5_file($DEVFILE)) exec($cmd2);
			exec($cmd3);
		}
	}
	
	function update_MultSub_FloatDev(){
		include("/PDATA/apache/conf/fw.ini");
		include("/PDATA/apache/Program/Network/class/wan_natbind_class.php");
		$wanbind = new wan_natbind_class;
		if(is_file($sFloatDev)) {
			$isChange = false;
			$save_data = array();
			$floatConf = file($sFloatDev);
			$wanStatus = file("/ram/tmp/wanstatus");
			for($i=0;$i<count($floatConf);$i++){
				$m = explode(",",trim($floatConf[$i]));
				$a = array();
				switch(trim($m[3])){
	  			case "WAN1":
	  				$a = explode("=",trim($wanStatus[0]));
	  				break;
	  			case "WAN2":
	  				$a = explode("=",trim($wanStatus[1]));
	  				break;
	  			case "WAN3":
	  				$a = explode("=",trim($wanStatus[2]));
	  				break;
	  			case "WAN4":
	  				$a = explode("=",trim($wanStatus[3]));
	  				break;
	  		}
	  		if(trim($m[2]) == trim($a[2]) || trim($a[2]) == "OFF") {
	  			$save_data[] = trim($floatConf[$i]);
				} else {
					$wanbind->del_nat_bind(trim($m[0]),trim($m[1]),trim($m[2]),trim($m[3]));
					$wanbind->add_nat_bind(trim($m[0]),trim($m[1]),trim($a[2]),trim($m[3]));
					$this->save_landevinfo(trim($m[0]),trim($m[1]),trim($m[2]),trim($a[2]),trim($m[3]));
					$save_data[] = trim($m[0]).",".trim($m[1]).",".trim($a[2]).",".trim($m[3]);
					$isChange = true;
				} 
			}
			
			if($isChange == true) {
				//資料有異動時, 再做寫回
				$buf = implode("\n",$save_data);
				$fp = fopen($sFloatDevTmp,"x");
				fwrite($fp,$buf);
				fclose($fp); 
				exec("chmod 600 $sFloatDevTmp");
				exec("cp $sFloatDevTmp $sFloatDev");
				exec("rm $sFloatDevTmp");			
			}			
		}	
	}
	
	function save_landevinfo($lanip,$lanprefix,$org_bindwanip,$bindwanip,$wandev){
		include("/PDATA/apache/conf/fw.ini");

		if(file_exists($DEVL1M_FILE)){
			$dev1mConf = file_get_contents($DEVL1M_FILE);
			$dev1mConf = str_replace("\n", ",\n", $dev1mConf); //補齊
			if(strpos($dev1mConf, ",$org_bindwanip,") !== false) {
				//找到取代的目標, 進行全部舊IP換新IP
				$dev1mConf = str_replace(",$org_bindwanip,", ",$bindwanip,", $dev1mConf);
				$dev1mConf = str_replace(",\n", "\n", $dev1mConf); //復原
				//檔案寫回
				$fp = fopen($DEVL1M_FILETMP,"x");
				fwrite($fp,$dev1mConf);
				fclose($fp); 
				exec("chmod 600 $DEVL1M_FILETMP");
				exec("cp $DEVL1M_FILETMP $DEVL1M_FILE");
				exec("rm $DEVL1M_FILETMP");
			}			
		}
		
		if(file_exists($DEVL2M_FILE)){
			$dev2mConf = file_get_contents($DEVL2M_FILE);
			$dev2mConf = str_replace("\n", ",\n", $dev2mConf); //補齊
			if(strpos($dev2mConf, ",$org_bindwanip,") !== false) {
				//找到取代的目標, 進行全部舊IP換新IP
				$dev2mConf = str_replace(",$org_bindwanip,", ",$bindwanip,", $dev2mConf);
				$dev2mConf = str_replace(",\n", "\n", $dev2mConf); //復原
				//檔案寫回
				$fp = fopen($DEVL2M_FILETMP,"x");
				fwrite($fp,$dev2mConf);
				fclose($fp); 
				exec("chmod 600 $DEVL2M_FILETMP");
				exec("cp $DEVL2M_FILETMP $DEVL2M_FILE");
				exec("rm $DEVL2M_FILETMP");
			}			
		}
	}
	
	function change_WanDev_Action($dev){
		include("/PDATA/apache/conf/fw.ini");
		switch($dev){
			case "WAN1":
				$DEVFILE_M = $DEVW1M_FILE;     
				$OUT_TO_WAN = "OUT_TO_WAN1";
				break;
			case "WAN2":
				$DEVFILE_M = $DEVW2M_FILE;
				$OUT_TO_WAN = "OUT_TO_WAN2";
				break;
			case "WAN3":
				$DEVFILE_M = $DEVW3M_FILE;
				$OUT_TO_WAN = "OUT_TO_WAN3";
				break;
			case "WAN4":
				$DEVFILE_M = $DEVW4M_FILE;
				$OUT_TO_WAN = "OUT_TO_WAN4";
				break;
		}   
		
		//1./DEV/XXXX_M LAN檔案  
		$aLanFile = array($DEVL1M_FILE,$DEVL2M_FILE);
		foreach($aLanFile as $sLanFile) {
			if(file_exists($sLanFile)){
				$isChange1 = 0;
				$msg1 = array();
				$dev1mConf = array();
				$msg1 = file($sLanFile);
				for($i=0;$i<count($msg1);$i++){
					unset($a);
					$a = explode(",",trim($msg1[$i]));
					switch($dev){
						case "WAN1":
							if($a[8] != "") {
								$dev1mConf[] = $a[0].",ON,".$a[2].",".$a[3].",".",".",".",".$a[7].",".",".$a[9].",".$a[10].",".$a[11];
								$isChange1++;
							}else{
								$dev1mConf[] = 	trim($msg1[$i]);
							}
							break;
						case "WAN2":
							if($a[9] != "") {
								$dev1mConf[] = $a[0].",ON,".$a[2].",".$a[3].",".",".",".",".$a[7].",".$a[8].",".",".$a[10].",".$a[11];
								$isChange1++;
							}else{
								$dev1mConf[] = 	trim($msg1[$i]);
							}
							break;
						case "WAN3":
							if($a[10] != "") {
								$dev1mConf[] = $a[0].",ON,".$a[2].",".$a[3].",".",".",".",".$a[7].",".$a[8].",".$a[9].",".",".$a[11];
								$isChange1++;
							}else{
								$dev1mConf[] = 	trim($msg1[$i]);
							}
							break;
						case "WAN4":
							if($a[11] != "") {
								$dev1mConf[] = $a[0].",ON,".$a[2].",".$a[3].",".",".",".",".$a[7].",".$a[8].",".$a[9].",".$a[10].",";
								$isChange1++;
							}else{
								$dev1mConf[] = 	trim($msg1[$i]);
							}
							break;
					}   
					
				}
				if($isChange1 != 0) {
					$sdev1mConf = implode("\n",$dev1mConf);
					$DEVLM_FILETMP = "/ram/tmp/DEVLM_FILETMP";
					$fp = fopen($DEVLM_FILETMP,"x");
					fwrite($fp,$sdev1mConf);
					fclose($fp); 
					exec("chmod 600 $DEVLM_FILETMP");
					exec("cp $DEVLM_FILETMP $sLanFile");
					exec("rm $DEVLM_FILETMP");	
				}
			}
		}
		
		//2.iptable部分
		$snat_cmd = "/PGRAM/ipt4/sbin/iptables -t nat -F " . $OUT_TO_WAN;
		exec($snat_cmd);
		
		//3.chkMultSub_FloatDev
		if(file_exists($sFloatDev)){
			$isChange2 = 0;
			$msg2 = file($sFloatDev);
			for($j=0;$j<count($msg2);$j++){
				unset($a);
				$a = explode(",",trim($msg2[$j]));
				if(trim($a[3]) != $dev)	$save_data[] = trim($msg2[$j]);
				else $isChange2++;
			}
			if($isChange2 != 0) {
				//資料有異動時, 再做寫回
				$buf = implode("\n",$save_data);
				$fp = fopen($sFloatDevTmp,"x");
				fwrite($fp,$buf);
				fclose($fp); 
				exec("chmod 600 $sFloatDevTmp");
				exec("cp $sFloatDevTmp $sFloatDev");
				exec("rm $sFloatDevTmp");			
			}		
		}	
		
		//4./DEV/XXXX_M WAN檔案
		if(file_exists($DEVFILE_M)) exec("rm $DEVFILE_M");
	}

}
?>