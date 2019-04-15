<?
include_once("/PDATA/apache/class/Comm.php");
include_once("/PDATA/apache/Program/Network/Lan_Inte_Use.php");

class OpenSwan
{
	function getHostname(){
		exec("/bin/hostname",$msg);
		$hostname = trim($msg[0]);
		if($hostname == ""){
			$hostname = "sharetech";
		}
		return $hostname;
	}

	function getAlgorithm1(){
		$algorithm = array("des", "aes", "3des");
		return $algorithm;
	}

	function getAlgorithm2(){
		$algorithm = array("md5", "sha1");
		return $algorithm;
	}

	function getMask($ipArr){
		for($i = 1;$i<=32;$i++) {
			$bin .= $ipArr >= $i ? '1' : '0';
		}
		$ipArr = bindec($bin);
		$ip = long2ip($ipArr);
		return $ip;
	}
	
	function cidr($ipAddrCidr){
		$ipArr = explode('/', $ipAddrCidr);
		$prefix = $ipArr[1];
		$dotcount = substr_count($ipArr[0], ".");
		$padding = str_repeat(".0", 3 - $dotcount);
		$ipArr[0].=$padding;
		$bin = '';
		for($i = 1;$i<=32;$i++) {
			$bin .= $ipArr[1] >= $i ? '1' : '0';
		}
		$ipArr[1] = bindec($bin);
		$ip = ip2long($ipArr[0]);
		$nm = ip2long($ipArr[1]);
		$nw = ($ip & $nm);
		$netCidr = long2ip($nw).'/'.$prefix;
		return $netCidr;
	}

	function doAdd($aIpsecList){
		$sIpsecList = implode("," , $aIpsecList);
		$this->saveToList($sIpsecList);
		if($aIpsecList['ENABLED'] == "on"){
			$this->isEnable($aIpsecList);
		}
	}
	
	function isEnable($aIpsecList){
		$this->saveToPeers($aIpsecList);
		$this->saveToSecrets($aIpsecList);
		$this->doIptable($aIpsecList,"A");
		$this->saveToConf();
		$this->saveIptables();
		if($this->ipsecCount() == 1){
			exec("/etc/rc.d/init.d/openswan start");
		}
		$this->upIpsec($aIpsecList["SN"]);
		$this->do_xfrm_policy($aIpsecList, "add");
	}

	function doEdit($aIpsecList){
		$peerFile = "/PCONF/openswan/peers/".$aIpsecList['SN'];
		if(is_file($peerFile)){
			$this->downIpsec($aIpsecList['SN']);
			$this->delIptable($aIpsecList['SN']);
			$this->delSecrets($aIpsecList['SN']);
			$this->delPeer($aIpsecList['SN']);
		}
		$this->editIpsecList($aIpsecList);
		if($aIpsecList['ENABLED'] == "on"){
			$this->isEnable($aIpsecList);
		}else if($aIpsecList['ENABLED'] == "off"){
			$this->write_disconnect_log($aIpsecList['SN'], "del");
		}
		$this->saveToConf();
		$this->saveIptables();
	}
	
	function do_xfrm_policy($ipsec, $type){
		$left = explode("/", $ipsec["LEFT_SUBNET"]);
		$right = explode("/", $ipsec["RIGHT_SUBNET"]);
		$left_tmp = ip2long($left[0]) >> (32 - $right[1]);
		$right_tmp = ip2long($right[0]) >> (32 - $right[1]);
		if($left_tmp == $right_tmp  && $right[1] < $left[1]){
			exec("ip xfrm policy ".$type." dir in src ".$ipsec["LEFT_SUBNET"]."  dst ".$ipsec["LEFT_SUBNET"]);
			exec("ip xfrm policy ".$type." dir fwd src ".$ipsec["LEFT_SUBNET"]."  dst ".$ipsec["LEFT_SUBNET"]);
			exec("ip xfrm policy ".$type." dir out src ".$ipsec["LEFT_SUBNET"]." dst ".$ipsec["LEFT_SUBNET"]);
		}
	}
	
	function doConfigure($configure,$sn){
		switch($configure){
			case "pause":
				$this->delIptable($sn);
				$result = $this->changeList($sn,"off");
				if($result != 1){
					$this->delSecrets($sn);
					$this->delPeer($sn);
					$this->saveToConf();
					$this->saveIptables();
					$this->downIpsec($sn);
				}
				$this->write_disconnect_log($sn, "del");
				break;
			case "delete":
				$peerFile = "/PCONF/openswan/peers/".$sn;
				if(is_file($peerFile)){
					$this->delIptable($sn);
					$this->delSecrets($sn);
					$this->delPeer($sn);
					$this->downIpsec($sn);
				}
				$this->delIpsecList($sn);
				$this->saveToConf();
				$this->saveIptables();
				$this->cleanLog($sn);
				$this->delLastTime($sn);
				$this->write_disconnect_log($sn, "del");
				break;
			case "enable":
				$aIpsecList = $this->getTunneldata($sn);
				$result = $this->changeList($aIpsecList["SN"],"on");
				if($result != 1){
					$this->isEnable($aIpsecList);
				}
				break;
		}
	}

	function saveToList($buf1){
		include("/PDATA/apache/conf/fw.ini");
		
		if(is_file($SYS_IPSECLIST)){
			$msg = file($SYS_IPSECLIST);
			for($i = 0; $i < count($msg); $i++){
				if(trim($msg[$i]) != ""){
					$org_buf[] = trim($msg[$i]);
				}
			}
		}
		$org_buf[] = $buf1;
		if($org_buf){
			$buf = implode("\n",$org_buf);
		}

		if(is_file($SYS_IPSECLISTTMP)){
			unlink($SYS_IPSECLISTTMP);
		}

		$fp = fopen($SYS_IPSECLISTTMP,"x");
		fwrite($fp,$buf);
		fclose($fp);
		exec("mv ".$SYS_IPSECLISTTMP." ".$SYS_IPSECLIST);
	}

	function changeList($sn,$change){
		include("/PDATA/apache/conf/fw.ini");
		$allIpsec = $this->getAllipsec();
		$no_change = 0;
		for($i = 0,$j = count($allIpsec); $i < $j; $i++){
			if( $sn == trim($allIpsec[$i]["SN"])){
				if($allIpsec[$i]["ENABLED"] == $change){
					$no_change = 1;
				}else{
					$allIpsec[$i]["ENABLED"] = $change;
				}
			}
			$newlist[$i] = implode(",",$allIpsec[$i]);
		}

		if($newlist){
			$buf = implode("\n",$newlist);
			if(is_file($SYS_IPSECLISTTMP)){
				unlink($SYS_IPSECLISTTMP);
			}
			$fp = fopen($SYS_IPSECLISTTMP,"x");
			fwrite($fp,$buf);
			fclose($fp);
			exec("mv ".$SYS_IPSECLISTTMP." ".$SYS_IPSECLIST);
		}else{
			unlink($SYS_IPSECLIST);
		}
		return $no_change;
	}
	
	function saveToPeers($aIpsecList){
		$a[] = "";
		$a[] = "conn ".$aIpsecList["SN"];
		$a[] = "	left=".$aIpsecList['LEFT'];
		$a[] = "	leftsubnet=".$aIpsecList['LEFT_SUBNET'];
		$a[] = "	right=".$aIpsecList['RIGHT'];
		$a[] = "	rightsubnet=".$aIpsecList['RIGHT_SUBNET'];
		$a[] = "	pfs=" . $aIpsecList['PFS'];
		/*if($aIpsecList['PFS'] == "yes"){
			$a[] = "	pfsgroup=modp" . $aIpsecList['PFSGroup'];
		}*/
		if($aIpsecList['DPD']){
			$a[] = "	dpdaction=".$aIpsecList['DPDACTION'];
			$a[] = "	dpddelay=".$aIpsecList['DPDDELAY'];
			$a[] = "	dpdtimeout=".$aIpsecList['DPDTIMEOUT'];
		}
		$a[] = "	keyingtries=%forever";
		if($aIpsecList['IKEAuto'] == "yes"){
		$a[] = "	ike=".$aIpsecList['IKE'].",des-md5,des-sha1,des-sha2_256,3des-md5,3des-sha1,3des-sha2_256,aes-md5,aes-sha1,aes-sha2_256";
		}else{
		$a[] = "	ike=".$aIpsecList['IKE']."!";
		}
		if($aIpsecList['ESPAuto'] == "yes"){
			$a[] = "	esp=".$aIpsecList['ESP'].",des-md5,des-sha1,des-sha2_256,3des-md5,3des-sha1,3des-sha2_256,aes-md5,aes-sha1,aes-sha2_256";
		}else{
			$a[] = "	esp=".$aIpsecList['ESP']."!";
		}
		if($aIpsecList['ChooseLocalID'] == "domain_name"){
			$a[] = "	leftid=" . $aIpsecList['LocalID'];
		}
		if($aIpsecList['ChooseRemoteID'] == "domain_name"){
			$a[] = "	rightid=" . $aIpsecList['RemoteID'];
		}
		$a[] = "	ikelifetime=" . $aIpsecList['LifetimeIKE'] . "h";
		$a[] = "	keylife=" . $aIpsecList['LifetimeESP'] . "h";
		$a[] = "	rekeymargin=3m";
		$a[] = "	aggrmode=" . $aIpsecList['ConnectionType'];
		$a[] = "";
		$buf = implode("\n",$a);
		$newIpsecTmp = "/ram/tmp/".$aIpsecList["SN"];
		if(is_file($newIpsecTmp)){
			unlink($newIpsecTmp);
		}
		$fp = fopen($newIpsecTmp,"x");
		fwrite($fp,$buf);
		fclose($fp);
		exec("mv ".$newIpsecTmp." "."/PCONF/openswan/peers/".$aIpsecList["SN"]);
	}

	function getSecrets($aIpsecList){
		if($aIpsecList['ChooseLocalID'] == "wan_ip"){
			$leftVal = $aIpsecList['LEFT'];
		}else{
			$leftVal = $aIpsecList['LocalID'];
		}
		if($aIpsecList['ChooseRemoteID'] == "wan_ip"){
			if($aIpsecList['RIGHT'] == "0.0.0.0/0"){
				$rightVal = "%any";
			}else{
				$rightVal = $aIpsecList['RIGHT'];
			}
		}else{
			$rightVal = $aIpsecList['RemoteID'];
		}
		$sSecrets = $leftVal.' '.$rightVal.': PSK "'.$aIpsecList['SECRET'] .'"';
		return $sSecrets;
	}

	function saveToSecrets($aIpsecList){
		include("/PDATA/apache/conf/fw.ini");

		$sSecrets = $this->getSecrets($aIpsecList);
		$msg = file($SYS_IPSECSECRET);
		$aSecrets = array();
		for($i=0; $i < count($msg); $i++){
			$aSecrets[] = trim($msg[$i]);
		}
		array_push($aSecrets,$sSecrets,"");
		$buf = implode("\n",$aSecrets);

		if(is_file($SYS_IPSECSECRETTMP)){
			unlink($SYS_IPSECSECRETTMP);
		}
		$fp = fopen($SYS_IPSECSECRETTMP,"x");
		fwrite($fp,$buf);
		fclose($fp);
		exec("mv ".$SYS_IPSECSECRETTMP." ".$SYS_IPSECSECRET);
	}

	function doIptable($aIpsecList,$type){
		if($aIpsecList['LEFT_SUBNET'] && $aIpsecList['RIGHT_SUBNET']){
			if(substr($aIpsecList['LEFT_SUBNET'],-3) == "/32"){
				$cmd1 = "/PGRAM/ipsets/sbin/ipset -".$type. " IPSEC_LOCAL_IP ".str_replace("/32","",$aIpsecList['LEFT_SUBNET']);
			}else{
				$cmd1 = "/PGRAM/ipsets/sbin/ipset -".$type. " IPSEC_LOCAL_IP_MASK ".$aIpsecList['LEFT_SUBNET'];
			}
			if(substr($aIpsecList['RIGHT_SUBNET'],-3) == "/32"){
				$cmd2 = "/PGRAM/ipsets/sbin/ipset -".$type. " IPSEC_REMOTE_IP ".str_replace("/32","",$aIpsecList['RIGHT_SUBNET']);
			}else{
				$cmd2 = "/PGRAM/ipsets/sbin/ipset -".$type. " IPSEC_REMOTE_IP_MASK ".$aIpsecList['RIGHT_SUBNET'];
			}
			exec($cmd1);
			exec($cmd2);
			if($aIpsecList['DROP_SMB'] == "ON"){
				$cmd3 = "/PGRAM/ipt4/sbin/iptables -t nat -".$type. " drop_smb -s ".$aIpsecList['LEFT_SUBNET']." -d ".$aIpsecList['RIGHT_SUBNET']." -j CONNMARK --set-mark 0x400000/0x400000";
				exec($cmd3);
			}
		}
	}

	function saveIptables(){
		exec("/PDATA/apache/save_iptable.php");
		exec("/PDATA/apache/save_ipset.php");
	}


	function getAllipsec(){
		include("/PDATA/apache/conf/fw.ini");

		if(!is_file($SYS_IPSECLIST)){
			return false;
		}
		$data = file($SYS_IPSECLIST);
		sort($data);
		foreach($data as $line){
			$ipsecs[] = $this->showIpsecList(trim($line));
		}
		if(!$ipsecs){
			return false;
		}
		return $ipsecs;
	}

	function getTunneldata($sn){
		$allipsec = $this->getAllipsec();
		for($i=0,$j = count($allipsec); $i< $j; $i++){
			if($sn == trim($allipsec[$i]["SN"])){
				$aTunnelData = $allipsec[$i];
			}
		}
		return $aTunnelData;
	}
	
	function ipsecCount(){
		exec("/bin/ls /PCONF/openswan/peers/",$msg);
		return count($msg);
	}

	function upIpsec($sn){
		exec("/PGRAM/openswan/sbin/ipsec auto --add ".$sn);
		exec("/PGRAM/openswan/sbin/ipsec auto --rereadsecrets");
		exec("nohup /PGRAM/openswan/sbin/ipsec auto --up ".$sn." > /dev/null 2>&1 &");
	}

	function downIpsec($sn){
		exec("/PGRAM/openswan/sbin/ipsec auto --delete ".$sn);
		exec("/PGRAM/openswan/sbin/ipsec auto --delete ".$sn);
		$aData = $this->getTunneldata($sn);
		$this->do_xfrm_policy($aData, "delete");
	}

	function showIpsecList($str){
		$ipsecList = array("SORT","SN","ENABLED","COMMENT","LEFT","RIGHT","LEFT_SUBNET","RIGHT_SUBNET","SECRET",
						"ConnectionType","IKEGroup","IKE","IKEAuto","ChooseLocalID","LocalID","ChooseRemoteID","RemoteID",
						"LifetimeIKE","ESP","ESPAuto","PFS","PFSGroup","LifetimeESP","DPD","DPDACTION","DPDDELAY","DPDTIMEOUT","DROP_SMB","KeepAliveIP");
		$aData = explode(",",$str);

		for($i=0,$j=count($ipsecList); $i< $j; $i++){
			$ipsec[$ipsecList[$i]] = trim($aData[$i]);
		}
		return $ipsec;
	}

	function delIpsecList($sn){
		include("/PDATA/apache/conf/fw.ini");
		$allIpsec = $this->getAllipsec();

		for($i = 0,$j = count($allIpsec); $i< $j; $i++){
			if($sn != trim($allIpsec[$i]["SN"])){
				$ipsecData[] = implode(",",$allIpsec[$i]);
			}
		}

		if($ipsecData){
			$buf = implode("\n",$ipsecData);
			if(is_file($SYS_IPSECLISTTMP)){
				unlink($SYS_IPSECLISTTMP);
			}
			$fp = fopen($SYS_IPSECLISTTMP,"x");
			fwrite($fp,$buf);
			fclose($fp);
			exec("mv ".$SYS_IPSECLISTTMP." ".$SYS_IPSECLIST);
		}else{
			unlink($SYS_IPSECLIST);
		}
	}
	
	function editIpsecList($ipsecList){
		include("/PDATA/apache/conf/fw.ini");
		$allIpsec = $this->getAllipsec();
		for($i = 0,$j = count($allIpsec); $i< $j; $i++){
			if($ipsecList['SN'] != trim($allIpsec[$i]["SN"])){
				$ipsecData[] = implode(",",$allIpsec[$i]);
			}else{
				$ipsecData[] = implode(",",$ipsecList);
			}
		}

		if($ipsecData){
			$buf = implode("\n",$ipsecData);
			if(is_file($SYS_IPSECLISTTMP)){
				unlink($SYS_IPSECLISTTMP);
			}
			$fp = fopen($SYS_IPSECLISTTMP,"x");
			fwrite($fp,$buf);
			fclose($fp);
			exec("mv ".$SYS_IPSECLISTTMP." ".$SYS_IPSECLIST);
		}else{
			unlink($SYS_IPSECLIST);
		}
	}

	function delPeer($sn){
		$ipsecfile = "/PCONF/openswan/peers/".$sn;
		if(is_file($ipsecfile)){
			unlink($ipsecfile);
		}
	}

	function delSecrets($sn){
		include("/PDATA/apache/conf/fw.ini");

		$aData = $this->getTunneldata($sn);
		$secrets = $this->getSecrets($aData);

		$msg = file($SYS_IPSECSECRET);
		unset($mark);
		$b = array();
		for($i = 0, $j = count($msg); $i < $j; $i++){
			if($secrets == trim($msg[$i]) && !$mark){
				$mark = 1;
			}else{
				$b[] = trim($msg[$i]);
			}
		}
		$b[] = "";
		$buf = implode("\n",$b);
		if(is_file($SYS_IPSECSECRETTMP)){
			unlink($SYS_IPSECSECRETTMP);
		}
		$fp = fopen($SYS_IPSECSECRETTMP,"x");
		fwrite($fp,$buf);
		fclose($fp);
		exec("mv ".$SYS_IPSECSECRETTMP." ".$SYS_IPSECSECRET);
	}

	function delIptable($sn){
		$aData = $this->getTunneldata($sn);
		if($aData['ENABLED'] == "on"){
			$this->doIptable($aData,"D");
		}
	}

	function getSelectdata($offset, $ipsecsdata, $record){
		for($i = $offset,$j = $offset+$record;$i< $j;$i++){
			if(trim($ipsecsdata[$i]) != ""){
				$aSelectData[] = $ipsecsdata[$i];
			}
		}
		
		if(!$aSelectData){
			return false;
		}
		return $aSelectData;
	}

	function cleanLog($sn){
		$file = "/CFH3/ipseclog/".$sn;
		if(is_file($file)){
			unlink($file);
		}
	}
	
	function delLastTime($sn){
		$lastTime = "/CFH3/ipseclog/".$sn."_last";
		if(is_file($lastTime)){
			unlink($lastTime);
		}
	}

	function analyzeIpsec($sn) {
		$log_file = "/CFH3/ipseclog/".$sn;

		if(!is_file($log_file)){
			return false;
		}
		
		$msg = file($log_file);
		for($i=0,$j = count($msg);$i < $j;$i++){
			$a = explode ("	",$msg[$i]);
			if(substr_count($a[2], "#") == 0 || !$a[3]){
				$ipseclog[$i]["TIME"] = trim($a[0]);
				$ipseclog[$i]["NUMBER"] = "";
				$ipseclog[$i]["EVENT"] = trim($a[2]);
			}else{
				$ipseclog[$i]["TIME"] = trim($a[0]);
				$ipseclog[$i]["NUMBER"] = trim($a[2]);
				$ipseclog[$i]["EVENT"] = trim($a[3]);
			}
		}
		return $ipseclog;
	}

	function checkOpenswan($aIpsecList){
		$Lan_Inte_Use = new Lan_Inte_Use;
		$Specific = new Specific();
		$SVar = $Specific->getAll();
		$aData = $this->getAllipsec();
		if(count($aData) == $SVar["FME_VPN_IPsec"]){
			return 10;
		}
		if($aIpsecList['LEFT'] == ""){
			return 3;
		}
		if(!$this->eregIp($aIpsecList['LEFT_SUBNET'])){
			return 4;
		}
		if(!$this->eregIp($aIpsecList['RIGHT_SUBNET'])){
			return 5;
		}
		if(trim($aIpsecList['KeepAliveIP'])){
			if(!$this->eregIp($aIpsecList['KeepAliveIP'])){
				return 12;
			}
		}
		if($Lan_Inte_Use->check_inLAN($aIpsecList['RIGHT_SUBNET'])){
			return 11;
		}
		if($aIpsecList['SECRET'] == ""){
			return 6;
		}
		if($aIpsecList['DPDDELAY'] && !is_numeric($aIpsecList['DPDDELAY'])){
			return 7;
		}
		if($aIpsecList['DPDTIMEOUT'] && !is_numeric($aIpsecList['DPDTIMEOUT'])){
			return 8;
		}
		if(strstr($aIpsecList['COMMENT'],",")){
			return 9;
		}
	}

	function eregIp($ipString){
		$ipString = trim($ipString);
		if(!ereg("^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$",$ipString)){
			return false;
		}
		$ipSegment = split("\.",$ipString); 
		foreach($ipSegment as $k => $v){
			if($v > 255){
				return false;
			}
			$ipSegment[$k] = (int)$ipSegment[$k];
		}
		return true;
	}
	
	function saveToConf(){
		include("/PDATA/apache/conf/fw.ini");
		$peers = array();
		exec("/bin/ls $SYS_IPSECPEERS_DIR", $retAry, $retCode);
		if($retCode == 0){
			foreach((Array)$retAry as $line) {
				$peers[] = "include " . $SYS_IPSECPEERS_DIR . $line . "\n";
			}
		}

		$oldConf = file($SYS_IPSECCONF);
		$newConf = array();
		$isCopy = true;

		foreach($oldConf as $line){
			if($isCopy == true) {
				$newConf[] = $line;
			}
			if($line == "#include_connections_start\n") {
				$isCopy = false;
				if(count($peers) > 0) {
					foreach($peers as $val) {
						$newConf[] = $val;
					}
				}
			}else if($line == "#include_connections_end\n") {
				$isCopy = true;
				$newConf[] = $line;
			}
		}
		$fp = fopen($SYS_IPSECCONF, "w");
		fwrite($fp, implode("", $newConf));
		fclose($fp);
	}
	
	function write_disconnect_log($sn, $action){
		$disconnect_file = "/tmp/ipsec_disconnect.log";
		$data = array();
		if(is_file($disconnect_file)){
			$msg = file($disconnect_file);
			for($i=0; $i < count($msg); $i++){
				if(trim($msg[$i]) != ""){
					$tmp = array();
					$tmp = explode(":::", trim($msg[$i]));
					$data[$tmp[0]] = trim($msg[$i]);
				}
			}
		}
		
		if($action == "del" && isset($data[$sn])){
			unset($data[$sn]);
		}else if($action == "add" && (!isset($data[$sn]) || $data[$sn] == "")){
			$data[$sn] = $sn.":::".time();
		}
		
		$fp = fopen($disconnect_file, "w");
		fwrite($fp, implode("\n", $data)."\n");
		fclose($fp);
	}
}


?>