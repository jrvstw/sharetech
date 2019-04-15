#!/PGRAM/php/bin/php -q
<?
include_once("/PDATA/apache/class/Comm.php");
$Specific = new Specific();

include_once("/PDATA/apache/Program/Network/class/Wan_Interface_class.php");
$wic = new Wan_Interface_class;
include_once("/PDATA/apache/class/prefix_convert_mask_class.php");
$pcm = new prefix_convert_mask_class;

//指定專屬WAN出去
$WAN1_table_num = 9781;//
$WAN2_table_num = 9782;//
$WAN3_table_num = 9783;//
$WAN4_table_num = 9784;//
$LAN1_table_num = 5781;
$LAN1A_table_num = 57811;
$LAN1B_table_num = 57812;
$LAN2_table_num = 5782;
$WAN1_mark_num = "0x100/0xf00";//
$WAN2_mark_num = "0x200/0xf00";//
$WAN3_mark_num = "0x300/0xf00";//
$WAN4_mark_num = "0x400/0xf00";//
$WAN1_LB_num = "0x900/0xf00";//
$WAN2_LB_num = "0xa00/0xf00";//
$WAN3_LB_num = "0xb00/0xf00";//
$WAN4_LB_num = "0xc00/0xf00";//
$routeadd_cmd = "/sbin/ip route add 0/0 via";
$routeadd_cmd3 = "/sbin/ip route add 0/0 dev";
$routeadd_cmd2 = "/sbin/ip route add";
$routedel_cmd = "/sbin/ip route del";
$routeshow_cmd = "/sbin/ip route show table";
$ruleadd_cmd = "/sbin/ip rule add fwmark";
$ruleadd_cmd2 = "/sbin/ip rule add to";
$ruledel_cmd = "/sbin/ip rule del pref";
$ruleshow_cmd = "/sbin/ip rule show";
$dhcpclient_leases = "/var/dhcp/dhclient.leases";
	
	function get_laninfo($DEV){
		unset($device);
		switch($DEV){
			case "LAN1":
				$device = "eth0";
				break;
			case "LAN2":
				$device = "eth3";
				break;
			case "LAN1A":
				$device = "eth01";
				break;
			case "LAN1B":
				$device = "eth02";
				break;
		}
		
		$cmd = '/sbin/ip addr show '.$device.' |grep "global '.$device.'"';
		exec($cmd,$msg);
		if($msg){
			 $str =trim($msg[0]);
			 $ip_str = trim(substr($str,strpos($str,"inet")+4,strpos($str,"brd")-strpos($str,"inet")-4));
			 $ip = explode ("/",$ip_str);

			 $info["ip"] = trim($ip[0]);
			 $info["mask"] = trim($ip[1]);
			 $info["dev"] = $device;
		}else{
			$info=0;
		}
		
		return $info;
	}
	
	function del_all_route_rule(){
		global $WAN1_table_num,$WAN2_table_num,$WAN3_table_num,$WAN4_table_num;
		global $ruledel_cmd,$routedel_cmd;
		
		$pref[] = get_rulepref($WAN1_table_num);
		$pref[] = get_rulepref($WAN2_table_num);
		$pref[] = get_rulepref($WAN3_table_num);
		$pref[] = get_rulepref($WAN4_table_num);
		
		//del ip rule start===========================================================================
		for($i=0;$i<count($pref);$i++){
			if(is_array($pref[$i])){
				for($j=0;$j<count($pref[$i]);$j++){
					$num[] = trim($pref[$i][$j]);
				}
			}
		}
		
		for($i=0;$i<count($num);$i++){
			$cmd = $ruledel_cmd." ".$num[$i];
			//var_dump($cmd);
			exec($cmd);
		}
		//del ip rule end=============================================================================
		//del route start=============================================================================
		$del_cmd[] = $routedel_cmd." table ".$WAN1_table_num;
		$del_cmd[] = $routedel_cmd." table ".$WAN2_table_num;
		$del_cmd[] = $routedel_cmd." table ".$WAN3_table_num;
		$del_cmd[] = $routedel_cmd." table ".$WAN4_table_num;
		for($i=0;$i<count($del_cmd);$i++){
			//var_dump($del_cmd[$i]);
			exec($del_cmd[$i]);
		}
		//del route end===============================================================================
	}
	
	function get_rulepref($table_num){
		global $ruleshow_cmd;
		
		$show_cmd = $ruleshow_cmd." |grep ".$table_num;
		exec($show_cmd,$msg);
		for($i=0;$i<count($msg);$i++){
			$a = explode(":",$msg[$i]);
			$pref[] = trim($a[0]);
		}
		
		return $pref;
	}
	
	function add_route_table($table_num,$mark_num,$lb_num,$gw,$pref,$via_dev){
		global $routeadd_cmd,$ruleadd_cmd,$routeadd_cmd3,$wanstatus;
		
		$cmd2_pref = $pref;
		$cmd3_pref = $pref+1;
		
		//add 
		if($via_dev) {
			$add_cmd1 = $routeadd_cmd3." ".$via_dev." table ".$table_num;//route	
		} else {
			$via_dev = $wanstatus["WAN".($table_num % 10)];
			$add_cmd1 = $routeadd_cmd." ".$gw." dev $via_dev table ".$table_num;//route
		}
		$add_cmd2 = $ruleadd_cmd." ".$mark_num." table ".$table_num." pref ".$cmd2_pref;//rule
		$add_cmd3 = $ruleadd_cmd." ".$lb_num." table ".$table_num." pref ".$cmd3_pref;//rule
				
		exec($add_cmd1);
		exec($add_cmd2);
		exec($add_cmd3);
	}
	
	function add_route_ipmask_table($table_num,$wanip,$wanmask){
		global $routeshow_cmd,$routeadd_cmd2,$pcm;
		$exist_devs = array("eth1","eth2","br0","eth4","eth5");
		
		$getdev_cmd = $routeshow_cmd." ".$table_num;
		exec($getdev_cmd,$msg);		
		$a = explode("dev",$msg[0]);
		$table_dev = trim($a[1]);
		
		if(in_array($table_dev,$exist_devs) && $wanmask!="255.255.255.255"){
			$add_cmd = $routeadd_cmd2." ".$pcm->get_cidr_ip($wanip,$wanmask)."/".$wanmask." dev ".$table_dev." table ".$table_num;
			exec($add_cmd);
		}
		
	}
	
	function get_first_exist_wan($wan1_gw,$wan2_gw,$wan3_gw,$wan4_gw){
		global $wanstatus;
		if($wan1_gw && $wanstatus["WAN1"]) return 1;
		if($wan2_gw && $wanstatus["WAN2"]) return 2;
		if($wan3_gw && $wanstatus["WAN3"]) return 3;
		if($wan4_gw && $wanstatus["WAN4"]) return 4;
	}
	
	function add_lb_table($lb_table_num,$wan_num,$lb_num,$pref){
		global $ruleadd_cmd;
		
		$add_cmd1 = $ruleadd_cmd." ".$wan_num." table ".$lb_table_num." pref ".$pref;
		$add_cmd2 = $ruleadd_cmd." ".$lb_num." table ".$lb_table_num." pref ".($pref+1);
		
		exec($add_cmd1);
		exec($add_cmd2);
	}
	
	function del_all_static_route_rule(){
		global $ruleshow_cmd,$ruledel_cmd;
		
		$show_cmd1 = $ruleshow_cmd.' |grep "main" |grep -v "all"';
		$show_cmd2 = $ruleshow_cmd.' |grep "main" |grep "to"';
		
		exec($show_cmd1,$msg1);
		exec($show_cmd2,$msg2);
		
		for($i=0;$i<count($msg1);$i++){
			$a = explode(":",$msg1[$i]);
			$pref[] = trim($a[0]);
		}
		for($i=0;$i<count($msg2);$i++){
			$b = explode(":",$msg2[$i]);
			$pref[] = trim($b[0]);
		}

		for($i=0;$i<count($pref);$i++){
			$cmd = $ruledel_cmd." ".$pref[$i];
			//var_dump($cmd);
			exec($cmd);
		}
	}
	
	function add_all_static_route_rule($pref){
		
		$all_route = get_allroute();
		
		for($i=0;$i<count($all_route);$i++){
			if(trim($all_route[$i]["DIP"])!="" && trim($all_route[$i]["MASK"])!=""){
				//$addcmd[] = "/sbin/ip rule add from ".trim($all_route[$i]["DIP"])."/".trim($all_route[$i]["MASK"]);
				$addcmd[] = "/sbin/ip rule add to ".trim($all_route[$i]["DIP"])."/".trim($all_route[$i]["MASK"]);
			}
		}

		for($i=0;$i<count($addcmd);$i++){
			unset($cmd);
			$cmd_pref = $pref+$i;
			$cmd = $addcmd[$i]." pref ".$cmd_pref;
			//var_dump($cmd);
			exec($cmd);
		}
		
	}
	
	function get_allroute(){
		include("/PDATA/apache/conf/fw.ini");
		if(!is_file($ROUTEFILE)) return false;
		$msg = file($ROUTEFILE);
		for($i=0;$i<count($msg);$i++){
			unset($a);
			$a = explode (":::",trim($msg[$i]));
			
			$route[$i]["SN"] = trim($a[0]);
			$route[$i]["DIP"] = trim($a[1]);
			$route[$i]["MASK"] = trim($a[2]);
			$route[$i]["GW"] = trim($a[3]);
		}
		
		return $route;
	}
	
	function del_all_lan2lan(){
	global $LAN1_table_num,$LAN1A_table_num,$LAN1B_table_num,$LAN2_table_num;
	global $Specific,$ruledel_cmd,$routedel_cmd;
	
	$exception_num = array("101", "102");
	
		//del rule start=============================================================================
		$pref[] = get_rulepref($LAN1_table_num);
		$pref[] = get_rulepref($LAN2_table_num);
		
		for($i=0;$i<count($pref);$i++){
			if(is_array($pref[$i])){
				for($j=0;$j<count($pref[$i]);$j++){
					if(!in_array(trim($pref[$i][$j]),$exception_num))$num[] = trim($pref[$i][$j]);
				}
			}
		}
		
		for($i=0;$i<count($num);$i++){
			$cmd = $ruledel_cmd." ".$num[$i];
			//var_dump($cmd);
			exec($cmd);
		}
		//del rule end=============================================================================
		//del route start=============================================================================
		$del_cmd[] = $routedel_cmd." table ".$LAN1_table_num;
		if($Specific->getv("LANs") == 2) {
			$del_cmd[] = $routedel_cmd." table ".$LAN1A_table_num;
			$del_cmd[] = $routedel_cmd." table ".$LAN1B_table_num;
		}
		$del_cmd[] = $routedel_cmd." table ".$LAN2_table_num;
		for($i=0;$i<count($del_cmd);$i++){
			//var_dump($del_cmd[$i]);
			exec($del_cmd[$i]);
		}
		//del route end===============================================================================
	}
	
	function add_lan2lan($table_num,$info,$pref){
		global $routeadd_cmd,$ruleadd_cmd2;
		
		
		if($info["ip"]!="" ){
			$route_cmd = $routeadd_cmd." ".$info["ip"]." table ".$table_num;
			$rule_cmd = $ruleadd_cmd2." ".$info["ip"]."/".$info["mask"]." table ".$table_num." pref ".$pref;
			exec($route_cmd);
			exec($rule_cmd);
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
			}
		}
			
		return $quality;
	}

	function add_subnet_rule_LAN(){
		global $LAN1_table_num,$LAN1A_table_num,$LAN1B_table_num;
		global $Specific,$ruleadd_cmd2,$routeadd_cmd3;       
		
		if($Specific->getv("LANs") == 2) {		
			$lan1Ainfo = get_laninfo("LAN1A");
			$lan1Binfo = get_laninfo("LAN1B");
			if(isset($lan1Ainfo["ip"])) {
				exec($routeadd_cmd3." eth01 table ".$LAN1A_table_num);
				exec($ruleadd_cmd2." ".$lan1Ainfo["ip"]."/".$lan1Ainfo["mask"]." table ".$LAN1A_table_num." pref 25300");    			
			}
			if(isset($lan1Binfo["ip"])) {
				exec($routeadd_cmd3." eth02 table ".$LAN1B_table_num);
				exec($ruleadd_cmd2." ".$lan1Binfo["ip"]."/".$lan1Binfo["mask"]." table ".$LAN1B_table_num." pref 25400");    			
			}
		}

		$all_landev = get_quality("LAN1");
		$prefnew = 0; 
		for($j=0;$j<count($all_landev);$j++){
			if($all_landev[$j]["DEV"] == "1") {
				continue; //no bind interface
			}
			if($all_landev[$j]["ENABLED"] == "ON"){
				$prefnew++;
				if(strpos($all_landev[$j]["DEV"], "eth01:") !== false) {
					$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".get_netmask($all_landev[$j]["MASK"])." table main pref ".(25300+$prefnew);    
				} else if(strpos($all_landev[$j]["DEV"], "eth02:") !== false) {
					$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".get_netmask($all_landev[$j]["MASK"])." table main pref ".(25400+$prefnew);    
				} else {
					$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".get_netmask($all_landev[$j]["MASK"])." table main pref ".(25100+$prefnew);    
				}
				exec($rule_cmd);
			}
		}
	}

	function add_subnet_rule_DMZ(){
		global $LAN2_table_num;
		global $ruleadd_cmd2;           
		$all_landev = get_quality("LAN2");
		$prefnew = 25200; 
		for($j=0;$j<count($all_landev);$j++){
			if($all_landev[$j]["DEV"] == "1") {
				continue; //no bind interface
			}
			if($all_landev[$j]["ENABLED"] == "ON"){
				$prefnew++;
				$rule_cmd = $ruleadd_cmd2." ".$all_landev[$j]["IP"]."/".get_netmask($all_landev[$j]["MASK"])." table main pref ".$prefnew;    
				exec($rule_cmd);
			}
		}	
	}

	function get_netmask($mask) {
		$mapPrefix = array("head",
			"128.0.0.0", "192.0.0.0", "224.0.0.0", "240.0.0.0", "248.0.0.0", "252.0.0.0", "254.0.0.0", "255.0.0.0",
			"255.128.0.0", "255.192.0.0", "255.224.0.0", "255.240.0.0", "255.248.0.0", "255.252.0.0", "255.254.0.0", "255.255.0.0",
			"255.255.128.0", "255.255.192.0", "255.255.224.0", "255.255.240.0", "255.255.248.0", "255.255.252.0", "255.255.254.0", "255.255.255.0",
			"255.255.255.128", "255.255.255.192", "255.255.255.224", "255.255.255.240", "255.255.255.248", "255.255.255.252", "255.255.255.254", "255.255.255.255"
		);

		$ret = array_search(trim($mask), $mapPrefix);
		return ($ret == false) ? 24 : $ret;
	}

	function get_wanstatus()
	{
		$status = array();
		$filename = "/ram/tmp/wanstatus";
		if(file_exists($filename))
		{
			$content = file($filename);
			foreach((Array)$content as $line)
			{
				$elt = explode("=", $line);
				if($elt[1] != "OFF")
					$status[$elt[0]] = $elt[1]; //dev
			}
		}
		
		return $status;
	}

	function del_bri_multiple_rule() {
		global $ruledel_cmd,$routedel_cmd;
		exec("$ruledel_cmd 27000");
		exec("$routedel_cmd table 1009");
	}
	function del_bri01_multiple_rule() {
		global $ruledel_cmd,$routedel_cmd;
		exec("$ruledel_cmd 27001");
		exec("$routedel_cmd table 1001");
	}
	function del_bri02_multiple_rule() {
		global $ruledel_cmd,$routedel_cmd;
		exec("$ruledel_cmd 27002");
		exec("$routedel_cmd table 1002");
	}

	function add_bri_multiple_rule() {
		global $routeadd_cmd3;
		exec("$routeadd_cmd3 br0 table 1009");
		exec("/sbin/ip rule add dev lo fwmark 0xa/0xf table 1009 pref 27000");		

		exec("/sbin/ip route | grep \"dev br0\"", $ret);
		$pref = 24700;
		foreach((Array)$ret as $line) {
			unset($match);
			if(preg_match('/(\d+\.\d+\.\d+\.\d+\/\d+) dev br0.+proto kernel/', $line, $match)) {
				exec("/sbin/ip rule add to {$match[1]} table main pref {$pref}");
				$pref++;
			}
		}	
	}
	function add_bri01_multiple_rule() {
		global $routeadd_cmd3;
		exec("$routeadd_cmd3 br01 table 1001");
		exec("/sbin/ip rule add dev lo fwmark 0xa/0xf table 1001 pref 27001");		

		exec("/sbin/ip route | grep \"dev br01\"", $ret);
		$pref = 24733;
		foreach((Array)$ret as $line) {
			unset($match);
			if(preg_match('/(\d+\.\d+\.\d+\.\d+\/\d+) dev br01.+proto kernel/', $line, $match)) {
				exec("/sbin/ip rule add to {$match[1]} table main pref {$pref}");
				$pref++;
			}
		}	
	}
	function add_bri02_multiple_rule() {
		global $routeadd_cmd3;
		exec("$routeadd_cmd3 br02 table 1002");
		exec("/sbin/ip rule add dev lo fwmark 0xb/0xf table 1002 pref 27002");		

		exec("/sbin/ip route | grep \"dev br02\"", $ret);
		$pref = 24766;
		foreach((Array)$ret as $line) {
			unset($match);
			if(preg_match('/(\d+\.\d+\.\d+\.\d+\/\d+) dev br02.+proto kernel/', $line, $match)) {
				exec("/sbin/ip rule add to {$match[1]} table main pref {$pref}");
				$pref++;
			}
		}	
	}

	function del_bri_routing_rule() {
		global $ruledel_cmd,$routedel_cmd;
		exec("$ruledel_cmd 21001");
		exec("$ruledel_cmd 21002");
		exec("$ruledel_cmd 21003");
		exec("$ruledel_cmd 21004");
		exec("$ruledel_cmd 21005");

		exec("$ruledel_cmd 27501");
		exec("$ruledel_cmd 27502");
		exec("$ruledel_cmd 27503");
		exec("$ruledel_cmd 27504");

		exec("$routedel_cmd table 8781");
		exec("$routedel_cmd table 8782");
		exec("$routedel_cmd table 8783");
		exec("$routedel_cmd table 8784");
		exec("$routedel_cmd table 8785");
	}

	function add_bri_routing_rule() {
		global $routeadd_cmd3,$wanstatus;
		exec("$routeadd_cmd3 eth1 table 8781");
		exec("$routeadd_cmd3 eth2 table 8782");
		exec("$routeadd_cmd3 eth4 table 8783");
		exec("$routeadd_cmd3 eth5 table 8784");
		exec("$routeadd_cmd3 eth3 table 8785");

		exec("/sbin/ip rule add fwmark 0x1/0x400007 table 8781 pref 21001");		
		exec("/sbin/ip rule add fwmark 0x2/0x400007 table 8782 pref 21002");		
		exec("/sbin/ip rule add fwmark 0x3/0x400007 table 8783 pref 21003");		
		exec("/sbin/ip rule add fwmark 0x4/0x400007 table 8784 pref 21004");		
		exec("/sbin/ip rule add fwmark 0x5/0x400007 table 8785 pref 21005");		
	
		if($wanstatus["WAN1"] == "ppp4000") {
			exec("/sbin/ip rule add dev ppp4000 table 8785 pref 27501");
		}
		if($wanstatus["WAN2"] == "ppp4001") {
			exec("/sbin/ip rule add dev ppp4001 table 8785 pref 27502");
		}
		if($wanstatus["WAN3"] == "ppp4002") {
			exec("/sbin/ip rule add dev ppp4002 table 8785 pref 27503");
		}
		if($wanstatus["WAN4"] == "ppp4003") {
			exec("/sbin/ip rule add dev ppp4003 table 8785 pref 27504");
		}
	}

	function build_ssl_vpn_rule()
	{
		global $ruleadd_cmd2;
		if(is_file("/PDATA/AUTHUSER/SSL_VPN_START")) {
			exec("/PGRAM/ipsets/sbin/ipset -L SSL_VPN_MASK", $ret);
			foreach((Array)$ret as $line)
			{
				if(preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\/[0-9]+)/', $line, $match))
				{//Add SSL VPN rule
					exec("$ruleadd_cmd2 $match[1] table main pref 24900");		
					exec("/sbin/ifconfig imq6 up");
				}
			}
		}
	}

	function build_pptpd_rule()
	{
		global $ruleadd_cmd2;		
		exec("/bin/grep ^remoteip /PCONF/pptp/pptpd.conf", $ret);		
		if(preg_match('/\s([0-9]+\.[0-9]+\.[0-9]+\.)[0-9]+/', $ret[0], $match)) {
			//localip 10.10.20.61
			exec("$ruleadd_cmd2 " . $match[1] . "0/24 table main pref 24800");	
		}
	}

	function del_vlan_id_rule()
	{
		global $ruleshow_cmd, $ruledel_cmd, $routedel_cmd;
		exec("$ruleshow_cmd | grep -E \"lookup 246[0-9][0-9]\"", $ret);
		foreach((Array)$ret as $line) 
		{
			$elt = explode(":", $line);
			exec("$ruledel_cmd {$elt[0]}");
			exec("$routedel_cmd table {$elt[0]}");
		}
	}
	
	function add_vlan_id_rule()
	{
		global $ruleadd_cmd2,$routeadd_cmd3,$pcm;		
		$pref = 24600;		
		$filename = "/PDATA/DEV/VLAN_802Q";
		$txt = file($filename);
		foreach((Array)$txt as $line) {
			$elt = explode(":::", $line);				
			if($elt[3] == "LAN") $Iface = "eth0";
			if($elt[3] == "LAN1") $Iface = "eth01";
			if($elt[3] == "LAN2") $Iface = "eth02";
			if($elt[3] == "DMZ") $Iface = "eth3";
			exec("$routeadd_cmd3 {$Iface}.{$elt[0]} table $pref");
			exec("$ruleadd_cmd2 {$elt[1]}/" . $pcm->mask2prefix($elt[2]) . " table $pref pref $pref");
			$pref++;
		}	
	}

	function del_bri_routing_dnat_rule() 
	{
		exec("/sbin/ip rule show | grep \"lo lookup 8785\"", $ret);
		foreach((Array)$ret as $line) 
		{
			$elt = explode(":", $line);
			exec("/sbin/ip rule del pref {$elt[0]}");
		}
	}

	function add_bri_routing_dnat_rule() 
	{
		$dnat_ips = array();
		$wan_subnet = array();
		$pref = 20000;
		
		exec('/PGRAM/ipt4/sbin/iptables -t nat -S | grep "\-\-to\-destination"', $ret);
		foreach((Array)$ret as $line)
		{
			unset($match);
			if(preg_match('/\-\d+\.\d+\.\d+\.\d+\-.+\-j DNAT \-\-to\-destination (\d+\.\d+\.\d+\.\d+)/', $line, $match))
			{
				if(!in_array($match[1], $dnat_ips))
				{
					$dnat_ips[] = $match[1];
				}
			}
		}
		
		unset($ret);
		exec("/sbin/ip route", $ret);
		foreach((Array)$ret as $line)
		{
			unset($match);
			if(preg_match('/(\d+\.\d+\.\d+\.\d+)\/(\d+) dev eth[1245]  proto kernel  scope link  src/', $line, $match))
			{
				$tmp = array("IP" => $match[1], "PREFIX" => $match[2], "BINARY" => ip2long($match[1]) >> (32 - $match[2]));
				$wan_subnet[] = $tmp;
			}	
		}
		
		if(count($dnat_ips) > 0) 
		{
			foreach((Array)$dnat_ips as $ip)
			{
				foreach((Array)$wan_subnet as $subnet) 
				{	
					if(ip2long($ip) >> (32 - $subnet["PREFIX"]) == $subnet["BINARY"]) 
					{
						exec("/sbin/ip rule add to {$ip} dev lo lookup 8785 pref {$pref}");
						$pref++;
					}
				}	
			}
		}
	}

	function flush_conntrack($wanstatus, $only_clean) {
		$deal_sessions = 5000;
		$filename = "/tmp/update_route_table_" . time() . "_" . mt_rand(1000,9999);
		
		exec("/PGRAM/conntrack/sbin/conntrack -C", $ret);
		if($ret[0] > $deal_sessions || $only_clean != "--only-clean=wan") {
			exec("/PGRAM/conntrack/sbin/conntrack -F");
		} else {
			exec("/PGRAM/conntrack/sbin/conntrack -L > $filename");
			
			if(!is_file($filename)) {
				return false;
			}
		
			$all_marks = array();
			$CMD = array();
			
			$txt = file($filename);
			foreach((Array)$txt as $line) {
				$xx = explode(" mark=", $line);
				$elt = split("[ \t]", $xx[1]);
				if($elt[0] > 0 && !isset($all_marks[$elt[0]])) {
					$all_marks[$elt[0]] = base_convert($elt[0], 10, 16);
				}
			}
			
			foreach((Array)$all_marks as $key => $val) {
				$outgoing = substr($val, -3, 1);
				//echo "$key $val $outgoing\n";
				
				if($outgoing == "9" || $outgoing == "1") {
					if(!$wanstatus["WAN1"]) $CMD[] = "/PGRAM/conntrack/sbin/conntrack -D --mark $key";
				}
				if($outgoing == "a" || $outgoing == "2") {
					if(!$wanstatus["WAN2"]) $CMD[] = "/PGRAM/conntrack/sbin/conntrack -D --mark $key";
				}
				if($outgoing == "b" || $outgoing == "3") {
					if(!$wanstatus["WAN3"]) $CMD[] = "/PGRAM/conntrack/sbin/conntrack -D --mark $key";
				}
				if($outgoing == "c" || $outgoing == "4") {
					if(!$wanstatus["WAN4"]) $CMD[] = "/PGRAM/conntrack/sbin/conntrack -D --mark $key";
				}		
			}
			
			foreach((Array)$CMD as $line) {
				echo "$line\n";
				exec($line);
			}
			
			@unlink($filename);
		}
	}

//start update.......

	$wan1info = $wic->get_waninfo("WAN1");
	$wan2info = $wic->get_waninfo("WAN2");
	$wan1_gw = $wan1info["WAN_GATEWAY"];
	$wan2_gw = $wan2info["WAN_GATEWAY"];

	if($Specific->getv("WAN") == 4) {	
		$wan3info = $wic->get_waninfo("WAN3");
		$wan4info = $wic->get_waninfo("WAN4");
		$wan3_gw = $wan3info["WAN_GATEWAY"];
		$wan4_gw = $wan4info["WAN_GATEWAY"];
	}
	
	$lan1info = get_laninfo("LAN1");
	$lan2info = get_laninfo("LAN2");
	
	$wanstatus = get_wanstatus();
	
	del_all_route_rule();
	
	if($wan1_gw && $wanstatus["WAN1"]){
		add_route_table($WAN1_table_num,$WAN1_mark_num,$WAN1_LB_num,$wan1_gw,29100,$wan1info["WAN_VIA_DEV"]);
		add_route_ipmask_table($WAN1_table_num,$wan1info["WAN_IP"],$wan1info["WAN_MASK"]);
	}	
	if($wan2_gw && $wanstatus["WAN2"]){
		add_route_table($WAN2_table_num,$WAN2_mark_num,$WAN2_LB_num,$wan2_gw,29200,$wan2info["WAN_VIA_DEV"]);
		add_route_ipmask_table($WAN2_table_num,$wan2info["WAN_IP"],$wan2info["WAN_MASK"]);
	}	
	if($wan3_gw && $wanstatus["WAN3"]){
		add_route_table($WAN3_table_num,$WAN3_mark_num,$WAN3_LB_num,$wan3_gw,29300,$wan3info["WAN_VIA_DEV"]);
		add_route_ipmask_table($WAN3_table_num,$wan3info["WAN_IP"],$wan3info["WAN_MASK"]);
	}	
	if($wan4_gw && $wanstatus["WAN4"]){
		add_route_table($WAN4_table_num,$WAN4_mark_num,$WAN4_LB_num,$wan4_gw,29400,$wan4info["WAN_VIA_DEV"]);
		add_route_ipmask_table($WAN4_table_num,$wan4info["WAN_IP"],$wan4info["WAN_MASK"]);
	}	
	
	
	$lb_wan = get_first_exist_wan($wan1_gw,$wan2_gw,$wan3_gw,$wan4_gw);
	switch($lb_wan){
		case 1:
			$lb_table_num = $WAN1_table_num;
			break;
		case 2:
			$lb_table_num = $WAN2_table_num;
			break;
		case 3:
			$lb_table_num = $WAN3_table_num;
			break;
		case 4:
			$lb_table_num = $WAN4_table_num;
			break;
	}
	
	if($lb_table_num)
	{
		if(!$wanstatus["WAN1"]) add_lb_table($lb_table_num,$WAN1_mark_num,$WAN1_LB_num,28100);
		if(!$wanstatus["WAN2"]) add_lb_table($lb_table_num,$WAN2_mark_num,$WAN2_LB_num,28200);
		if(!$wanstatus["WAN3"]) add_lb_table($lb_table_num,$WAN3_mark_num,$WAN3_LB_num,28300);
		if(!$wanstatus["WAN4"]) add_lb_table($lb_table_num,$WAN4_mark_num,$WAN4_LB_num,28400);
	}
	
//end update.......

//update static route about

//del all static route about rule
del_all_static_route_rule();
//add all static route about rule
add_all_static_route_rule(26000);

//when ip port map need route and rule
del_all_lan2lan();

if($lan1info != 0) add_lan2lan($LAN1_table_num,$lan1info,25100);
if($lan2info != 0) add_lan2lan($LAN2_table_num,$lan2info,25200);
add_subnet_rule_LAN();
add_subnet_rule_DMZ();

del_bri_multiple_rule();
del_bri01_multiple_rule();
del_bri02_multiple_rule();
if(is_file("/PCONF/brictl/BRI_MODE_RUN")) {
	add_bri_multiple_rule();
}
if(is_file("/PCONF/brictl/BRI_MODE_G1_RUN")) {
	add_bri01_multiple_rule();
}
if(is_file("/PCONF/brictl/BRI_MODE_G2_RUN")) {
	add_bri02_multiple_rule();
}

del_bri_routing_rule();
del_bri_routing_dnat_rule();
if(is_file("/PCONF/brictl/BRI_MODE_ROUTING_RUN")) {
	add_bri_routing_rule();
	add_bri_routing_dnat_rule();
}

del_vlan_id_rule();
if(is_file("/PDATA/DEV/VLAN_802Q")) {
	add_vlan_id_rule();	
}

build_pptpd_rule();
build_ssl_vpn_rule();

//clean cache
exec("/sbin/ip route flush cache");
flush_conntrack($wanstatus, $argv[1]);
?>
