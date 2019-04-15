#!/PGRAM/php/bin/php -q
<?
	
	addip("wan1");
	addip("wan2");
	
	function addip($dev){
		switch($dev){
		case "wan1":
			$wan = get_wan1info();
			break;
		case "wan2":
			$wan = get_wan2info();
			break;
		}

		$interface = trim($wan["WAN_DEV"]);
		$mac = trim($wan["WAN_MAC"]);
		$ip = trim($wan["WAN_IP"]);
		$mask = trim($wan["WAN_MASK"]);
		$gateway = trim($wan["WAN_GATEWAY"]);
		$broadcast = get_bcast($ip,$mask);
		$hostmin = get_hostmin($ip,$mask);
		$hostmax = get_hostmax($ip,$mask);
		
		$ip_range = get_iprange($interface,$ip,$hostmin,$hostmax);
		
		if(in_array($hostmin,$ip_range)){
			$ip_range = reject_array($hostmin,$ip_range);
		}
		if(in_array($hostmax,$ip_range)){
			$ip_range = reject_array($hostmax,$ip_range);
		}
		if(in_array($gateway,$ip_range)){
			$ip_range = reject_array($gateway,$ip_range);
		}
		
		if($wan["WAN_TYPE"]=="STATIC") addips2dev($interface,$ip,$ip_range);
			
	}
	
	
	function addips2dev($interface,$ip,$ip_range){
		
		for($i=0;$i<count($ip_range);$i++){
			if(trim($ip_range[$i]) != trim($ip)) $ips[] = trim($ip_range[$i]);
		}
		//var_dump($ips);
		for($i=0;$i<count($ips);$i++){
			unset($cmd);
			$cmd = "ifconfig ".$interface.":".$i." ".$ips[$i];
			exec($cmd);
		}
	}

	
	function reject_array($reject,$ip_range){
		
		for($i=0;$i<count($ip_range);$i++){
			if($reject != $ip_range[$i]) $iprange[] = trim($ip_range[$i]);
		}
		
		return $iprange;
	}
	
	function get_wan1info(){
		include("/PDATA/apache/conf/fw.ini");
		$msg = @file($DEVW1_FILE);
		$devinfo = explode(",",$msg[0]);
		
		$cmd1 = "cat ".$SYS_WAN1;
		$cmd2 = "ifconfig eth1";
		exec($cmd1,$msg1);
		exec($cmd2,$msg2);
		
		$info["WAN_TYPE"] = $devinfo[0];
//--------------------------------------------------------------------------------------
	switch($info["WAN_TYPE"]){
		case "STATIC":
			if(strstr($msg1[2],"#")){
				$info["WAN_IP"] = "";
			}else{
				$info["WAN_IP"] = trim(str_replace("IP=","",$msg1[2]));
			}
			
			if(strstr($msg1[3],"#")){
				$info["WAN_GATEWAY"] = "";
			}else{
				$info["WAN_GATEWAY"] = trim(str_replace("GATEWAY=","",$msg1[3]));
			}
			break;
		case "PPPoE":
			$pidmsg = @file("/var/run/ppp-PPPOE4000.pid");
			$ppp_sn = trim($pidmsg[1]);
			
			if($ppp_sn=="ppp4000") $ppp0 = get_ppp4000();
			if($ppp_sn=="ppp4001") $ppp0 = get_ppp4001();
			$info["WAN_IP"] = trim($ppp0["ip"]);
			$info["WAN_MASK"] = trim($ppp0["mask"]);
			$peer = explode("/",$ppp0["peer"]);
			$info["WAN_GATEWAY"] = trim($peer[0]);
			$info["WAN_DEV"] = $ppp_sn;
			break;
		case "DHCP":
			$dhcp1 = get_dhcpeth1();
			$info["WAN_IP"] = $dhcp1["ip"];
			$info["WAN_GATEWAY"] = $dhcp1["gateway"];
			break;
	}
		if(!$info["WAN_MASK"]){
			$info["WAN_PREFIX"] = trim(str_replace("PREFIX=","",$msg1[4]));
			$info["WAN_MASK"] = prefix2mask($info["WAN_PREFIX"]);
		} 
		$info["WAN_MAC"] =  trim(str_replace("HWaddr","",strrchr($msg2[0],"HWaddr")));
		if(!$info["WAN_DEV"]) $info["WAN_DEV"] = "eth1";
		
		return $info;
	}
	
	function get_wan2info(){
		include("/PDATA/apache/conf/fw.ini");
		$msg = @file($DEVW2_FILE);
		$devinfo = explode(",",$msg[0]);
		
		$cmd1 = "cat ".$SYS_WAN2;
		$cmd2 = "ifconfig eth2";
		exec($cmd1,$msg1);
		exec($cmd2,$msg2);
		
		$info["WAN_TYPE"] = $devinfo[0];
		
		switch($info["WAN_TYPE"]){
			case "STATIC":
				if(strstr($msg1[2],"#")){
					$info["WAN_IP"] = "";
				}else{
					$info["WAN_IP"] = trim(str_replace("IP=","",$msg1[2]));
				}
				
				if(strstr($msg1[3],"#")){
					$info["WAN_GATEWAY"] = "";
				}else{
					$info["WAN_GATEWAY"] = trim(str_replace("GATEWAY=","",$msg1[3]));
				}
				break;
			case "PPPoE":
				$pidmsg = file("/var/run/ppp-pppoe4001.pid");
				$ppp_sn = trim($pidmsg[1]);
				if($ppp_sn=="ppp4000") $ppp1 = get_ppp4000();
				if($ppp_sn=="ppp4001") $ppp1 = get_ppp4001();
				$info["WAN_IP"] = trim($ppp1["ip"]);
				$info["WAN_MASK"] = trim($ppp1["mask"]);
				$peer = explode("/",$ppp1["peer"]);
				$info["WAN_GATEWAY"] = trim($peer[0]);
				$info["WAN_DEV"] = $ppp_sn;
				break;
			case "DHCP":
				$dhcp2 = get_dhcpeth2();
				$info["WAN_IP"] = $dhcp2["ip"];
				$info["WAN_GATEWAY"] = $dhcp2["gateway"];
				break;
		}
		
		if(!$info["WAN_MASK"]){
			$info["WAN_PREFIX"] = trim(str_replace("PREFIX=","",$msg1[4]));
			$info["WAN_MASK"] = prefix2mask($info["WAN_PREFIX"]);
		}
		$info["WAN_MAC"] =  trim(str_replace("HWaddr","",strrchr($msg2[0],"HWaddr")));
		if(!$info["WAN_DEV"]) $info["WAN_DEV"] = "eth2";
		
		return $info;
	
	}
	
	function get_ppp4000(){
		$cmd = "ifconfig ppp4000 |grep P-t-P";
		exec($cmd,$msg);
		if($msg){
			 $str =  explode(" ",trim($msg[0]));
			 
			 for($i=0;$i<count($str);$i++){
			 	if(strstr($str[$i],"addr:")) $info["ip"] = trim(str_replace("addr:","",$str[$i]));
			 	if(strstr($str[$i],"P-t-P:")) $info["peer"] = trim(str_replace("P-t-P:","",$str[$i]));
			 	if(strstr($str[$i],"Mask:")) $info["mask"] = trim(str_replace("Mask:","",$str[$i]));
			 }
			 $info["dev"] = "ppp0";
		}else{
			$info=0;
		}
		
		return $info;
	}
	
	function get_ppp4001(){
		$cmd = "ifconfig ppp4001 |grep P-t-P";
		exec($cmd,$msg);
		if($msg){
			 $str =  explode(" ",trim($msg[0]));
			 
			 for($i=0;$i<count($str);$i++){
			 	if(strstr($str[$i],"addr:")) $info["ip"] = trim(str_replace("addr:","",$str[$i]));
			 	if(strstr($str[$i],"P-t-P:")) $info["peer"] = trim(str_replace("P-t-P:","",$str[$i]));
			 	if(strstr($str[$i],"Mask:")) $info["mask"] = trim(str_replace("Mask:","",$str[$i]));
			 }
			 $info["dev"] = "ppp1";
		}else{
			$info=0;
		}
		
		return $info;
	}
	
	
	function prefix2mask($prefix){
		
		$cdr_nmark = trim($prefix);
		$bin_nmark = cdrtobin($cdr_nmark);
		$mask = bintodq($bin_nmark);
		
		return $mask;
	}
	
	function cdrtobin ($cdrin){
		return str_pad(str_pad("", $cdrin, "1"), 32, "0");
	}
	
	function bintodq ($binin) {

		if ($binin=="N/A") return $binin;
		$binin=explode(".", chunk_split($binin,8,"."));
		for ($i=0; $i<4 ; $i++) {
			$dq[$i]=bindec($binin[$i]);
		}
	        return implode(".",$dq) ;
	}
	
	function dqtobin($dqin) {
        $dq = explode(".",$dqin);
        for ($i=0; $i<4 ; $i++) {
           $bin[$i]=str_pad(decbin($dq[$i]), 8, "0", STR_PAD_LEFT);
        }
        return implode("",$bin);
	}
	
	function binnmtowm($binin){
		$binin=rtrim($binin, "0");
		if (!ereg("0",$binin) ){
			return str_pad(str_replace("1","0",$binin), 32, "1");
		} else return "1010101010101010101010101010101010101010";
	}
	
	function bintocdr ($binin){
		return strlen(rtrim($binin,"0"));
	}
	
	function get_bcast($ip,$mask){
		
		$dq_host = $ip;
		$bin_host=dqtobin($dq_host);
		
		$bin_nmask=dqtobin($mask);
		$bin_wmask=binnmtowm($bin_nmask);
		$cdr_nmask=bintocdr($bin_nmask);
		
		$bin_net=(str_pad(substr($bin_host,0,$cdr_nmask),32,0));
		if ($bin_net === $bin_bcast){
			$bin_bcast="N/A";
		}else{
			$bin_bcast=(str_pad(substr($bin_host,0,$cdr_nmask),32,1));
		}
		
		$broadcast = bintodq($bin_bcast);
		
		return $broadcast;
	}
	
	function get_hostmin($ip,$mask){
		
		$dq_host = $ip;
		$bin_host=dqtobin($dq_host);
		
		$bin_nmask=dqtobin($mask);
		$bin_wmask=binnmtowm($bin_nmask);
		$cdr_nmask=bintocdr($bin_nmask);
		
		$bin_net=(str_pad(substr($bin_host,0,$cdr_nmask),32,0));
		
		$host_total=(bindec(str_pad("",(32-$cdr_nmask),1)) - 1);
		if($host_total <= 0){
			$bin_first="N/A";
		}else{
			$bin_first=(str_pad(substr($bin_net,0,31),32,1));
		}
		
		$hostmin = bintodq($bin_first);
		
		return $hostmin;
	
	}
	
	function get_hostmax($ip,$mask){
		
		$dq_host = $ip;
		$bin_host=dqtobin($dq_host);
		
		$bin_nmask=dqtobin($mask);
		$bin_wmask=binnmtowm($bin_nmask);
		$cdr_nmask=bintocdr($bin_nmask);
		
		$bin_bcast=(str_pad(substr($bin_host,0,$cdr_nmask),32,1));
		
		$host_total=(bindec(str_pad("",(32-$cdr_nmask),1)) - 1);
		if($host_total <= 0){
			$bin_last="N/A";
		}else{
			$bin_last=(str_pad(substr($bin_bcast,0,31),32,0));
		}
		
		$hostmax = bintodq($bin_last);
		
		return $hostmax;
	
	}
	
	function get_iprange($interface,$ip,$hostmin,$hostmax){
		
		if($hostmin=="N/A" || $hostmax=="N/A"){
			$ips[] = $ip;
		}else{
			$ip_start = $hostmin;
			$ip_end = $hostmax;
			$strStart = ($ip_start < $ip_end) ? $ip_start : $ip_end;
			$strEnd = ($ip_start > $ip_end) ? $ip_start : $ip_end;
			$a=ip2long($strStart);
			$b=ip2long($strEnd);
			
			for($x=$a;$x<=$b;$x++){
				$ips[] =  long2ip($x);
			}
		}
		
		iprangetmp($interface,$ips);
		
		return $ips;
	}
	
	function iprangetmp($interface,$ips){
		$iprangetmp = "/ram/tmp/iprangetmp";
		if(is_file($iprangetmp)){
			$ips_str =  implode(":::",$ips);
			$msg = file($iprangetmp);
			for($i=0;$i<count($msg);$i++){
				if( strstr(trim($msg[$i]), $interface)){
					$str[] = $interface.",".$ips_str;
				}else{
					$str[] = trim($msg[$i]);
				}
			}
			
			$buf = implode("\n",$str);
			unlink($iprangetmp);
		}else{
			$face = array("eth1","eth2","ppp4000","ppp4001");
			$ips_str =  implode(":::",$ips);
			for($i=0;$i<count($face);$i++){
				if($face[$i] == $interface){
					$str[] = $face[$i].",".$ips_str;
				}else{
					$str[] = $face[$i].",";
				} 
			}
			$buf = implode("\n",$str);
		}
		
		$fp = fopen($iprangetmp,"x");
		fwrite($fp,$buf);	
		fclose($fp);
	}
	
	function  get_dhcpeth1(){
		$dhcpclient = "/var/dhcp/dhclient.leases";
		$cmd = "cat ".$dhcpclient;
		exec($cmd,$msg);
		for($i=0;$i<count($msg);$i++){
			if(strpos($msg[$i],'"eth1"')) $eth_sn = $i;
		}
		
		for($y=$eth_sn;$y<$eth_sn+5;$y++){
			if(strpos($msg[$y],'ixed-address')) $ip = trim(str_replace(";","",str_replace("fixed-address","",$msg[$y])));
			if(strpos($msg[$y],'ption routers')) $gateway = trim(str_replace(";","",str_replace("option routers","",$msg[$y])));
		}
		$info["ip"] = $ip;
		$info["gateway"] = $gateway;
		
		return $info;
	
	}
	
	function get_dhcpeth2(){
		$dhcpclient = "/var/dhcp/dhclient.leases";
		$cmd = "cat ".$dhcpclient;
		exec($cmd,$msg);
		for($i=0;$i<count($msg);$i++){
			if(strpos($msg[$i],'"eth2"')) $eth_sn = $i;
		}
		
		for($y=$eth_sn;$y<$eth_sn+5;$y++){
			if(strpos($msg[$y],'ixed-address')) $ip = trim(str_replace(";","",str_replace("fixed-address","",$msg[$y])));
			if(strpos($msg[$y],'ption routers')) $gateway = trim(str_replace(";","",str_replace("option routers","",$msg[$y])));
		}
		$info["ip"] = $ip;
		$info["gateway"] = $gateway;
		
		return $info;
	}

?>