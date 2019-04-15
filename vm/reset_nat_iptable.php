#!/PGRAM/php/bin/php -q
<?
$ipshow_cmd = "ip addr show";
	
	del_iptapleNAT("LAN1");
	del_iptapleNAT("LAN2");
	add_iptapleNAT("LAN1",get_ethinfo("eth0"));
	add_iptapleNAT("LAN2",get_ethinfo("eth3"));
	
	function del_iptapleNAT($DEVICE){
		include("/PDATA/apache/conf/fw.ini");
		switch($DEVICE){
			case "LAN1":
				$NAT_table = "do_lan1nat";
				break;
			case "LAN2":
				$NAT_table = "do_lan2nat";
				break;
		}
				if($NAT_table) $cmd = $IPTABLES." -t nat -F ".$NAT_table;
				exec($cmd);
	}
	
	function add_iptapleNAT($device,$ip){
		include("/PDATA/apache/conf/fw.ini");
		switch($device){
			case "LAN1":
				if($ip){
					$NAT_table = "do_lan1nat";
					$a = explode(".",$ip);
					$iprange = $a[0].".".$a[1].".".$a[2].".0/24";
				}
				break;
			case "LAN2":
				if($ip){
					$NAT_table = "do_lan2nat";
					$a = explode(".",$ip);
					$iprange = $a[0].".".$a[1].".".$a[2].".0/24";
				}
				break;
		}
		
					$static_wan1ip = get_static_wanip("WAN1");
					$static_wan2ip = get_static_wanip("WAN2");
		
					if($NAT_table=="do_lan1nat"){
						$cmd1 = $IPTABLES." -t nat -A ".$NAT_table." -o br0 -m set --set LAN_IP_MASK src -j MASQUERADE";
						if($static_wan1ip) $cmd2 = $IPTABLES." -t nat -A ".$NAT_table." -o eth1 -m set --set LAN_IP_MASK src -j MASQUERADE";
						if($static_wan2ip) $cmd3 = $IPTABLES." -t nat -A ".$NAT_table." -o eth2 -m set --set LAN_IP_MASK src -j MASQUERADE";
						$cmd4 = $IPTABLES." -t nat -A ".$NAT_table." -o ppp+ -m set --set LAN_IP_MASK src -j MASQUERADE";
					}
					if($NAT_table=="do_lan2nat"){
						if($static_wan1ip)  $cmd1 = $IPTABLES." -t nat -A ".$NAT_table." -o eth1 -m set --set DMZ_IP_MASK src -j MASQUERADE";
						if($static_wan2ip) $cmd2 = $IPTABLES." -t nat -A ".$NAT_table." -o eth2 -m set --set DMZ_IP_MASK src -j MASQUERADE";
						$cmd3 = $IPTABLES." -t nat -A ".$NAT_table." -o ppp+ -m set --set DMZ_IP_MASK src -j MASQUERADE";
					}
					
					if($cmd1) exec($cmd1);
					if($cmd2) exec($cmd2);
					if($cmd3) exec($cmd3);
					if($cmd4) exec($cmd4);
					
					
					/*$static_wan1ip = get_static_wanip("WAN1");
					$static_wan2ip = get_static_wanip("WAN2");
					if($static_wan1ip && $NAT_table) $cmd1 = $IPTABLES." -t nat -A ".$NAT_table." -s ".$iprange." -o eth1 -j SNAT --to-source ".$static_wan1ip;
					if($static_wan2ip && $NAT_table) $cmd2 = $IPTABLES." -t nat -A ".$NAT_table." -s ".$iprange." -o eth2 -j SNAT --to-source ".$static_wan2ip;
					if($NAT_table)$cmd3 = $IPTABLES." -t nat -A ".$NAT_table." -s ".$iprange." -o ppp+ -j MASQUERADE";
					
					if($cmd1) exec($cmd1);
					if($cmd2) exec($cmd2);
					if($cmd3) exec($cmd3);*/
	}
	
	function get_static_wanip($WAN){
		include("/PDATA/apache/conf/fw.ini");
		switch($WAN){
			case "WAN1":
				$DEVW_FILE = $DEVW1_FILE;
				$device = "eth1";
				break;
			case "WAN2":
				$DEVW_FILE = $DEVW2_FILE;
				$device = "eth2";
				break;
		}
		$msg = file($DEVW_FILE);
		$a = explode (",", trim($msg[0]));
		if(trim($a[0])=="STATIC"){
			$ip = get_ethinfo($device);
			return $ip;
		}
		if(trim($a[0])=="DHCP"){
			$ip = get_ethinfo($device);
			return $ip;
		}
		
		return false;
	}
	
	function  get_ethinfo($dev){
		global $ipshow_cmd;
		$cmd = $ipshow_cmd." ".$dev;
		unset($msg);
		unset($str1);
		unset($str2);
		exec($cmd,$msg);
		$str1 =  explode(" ",trim($msg[1]));
		$str2 =  explode(" ",trim($msg[2]));
		
		$ip = $str2[1];
		if($ip){
			$ip_str = explode("/",$ip);
			$ip = trim($ip_str[0]);
		}
		
		return $ip;
	
	}

?>
