<?
include("/PDATA/apache/conf/postfix_system.ini");
 if(!class_exists("CDbshell")) {
	include_once("/PDATA/apache/class/CDbshell.php"); 
}
if(!is_object($db)) {
	$db = new CDbshell("temporary");  
}


class ReportFlowAnalyze {
	var $starttime;
	var $endtime;
	var $direct = 1;
	var $lan_ipmac = "/ram/tmp/lan_ipmac";
	var $aOneDayTime = array();
	var $ipmacs = array();
	var $Segment_range;
	var $nTotal = 0;
	
	function ReportFlowAnalyze($starttime, $endtime)
	{
		$this->starttime = $starttime;
		$this->endtime = $endtime;	
		$this->ipmacs = $this->get_allipmacs();
	}
	
	function OutgoingFlowTop($range = '')
	{
		global $db;
		$this->nTotal = 0;
		if($db->db != "temporary") $db->db = "temporary";
		$aTop = array();
		$iBound = array();
		$tables = array();
		
		for($i = $this->starttime; $i <= $this->endtime; $i+=86400) {
			$tables[] = date("Y-m-d", $i);
		}
		
		for($i = 0; $i < count($tables); $i++) {
			$sql = "SELECT src_ip, sum(up_bytes) AS up_total, sum(down_bytes) AS down_total "; 
			$sql.= "FROM `".$tables[$i]."` WHERE ";
			if($i == 0){
				$sql.= "time_stamp >= $this->starttime AND ";
			}else if($i == count($tables) - 1){
				$sql.= "time_stamp < $this->endtime AND ";
			}
			$sql.= "sessions = $this->direct ";
			if($range != ''){
				$sql .= "AND src_ip_long  >= ".$range['min']." AND src_ip_long  <= ".$range['max']." ";
			}
			$sql.= "GROUP BY src_ip_long ";
			$result = $db->query($sql);
			while($row = $db->fetch_array($result)){
				$iBound[$row['src_ip']] += $row['up_total'] + $row['down_total'];
			}
		}

		arsort($iBound);
		
		foreach((Array)$iBound as $ip => $bound) {
			if(!isset($this->ipmacs[$ip])){
				continue;
			}
			$tmp = array();
			$tmp['userip'] = $ip;
			$tmp['mac'] = $this->ipmacs[$ip][1];
			$tmp['pcname'] = $this->ipmacs[$ip][2];
			$tmp['ftotal'] = $bound;
			$aTop[] = $tmp;
			$this->nTotal += $bound;
		}
		return $aTop;
	}
	
	function OutgoingFlowTopByDst()
	{
		global $db;
		$this->nTotal = 0;
		if($db->db != "temporary") $db->db = "temporary";
		$aTop = array();
		$iBound = array();
		$tables = array();
		
		for($i = $this->starttime; $i <= $this->endtime; $i+=86400) {
			$tables[] = date("Y-m-d", $i);
		}
		for($i = 0; $i < count($tables); $i++) {
			$sql = "SELECT dst_ip, sum(up_bytes) AS up_total, sum(down_bytes) AS down_total "; 
			$sql.= "FROM `".$tables[$i]."` WHERE ";
			if($i == 0){
				$sql.= "time_stamp >= $this->starttime AND ";
			}else if($i == count($tables) - 1){
				$sql.= "time_stamp < $this->endtime AND ";
			}
			$sql.= "sessions = $this->direct ";
			$sql.= "GROUP BY dst_ip ";
			$result = $db->query($sql);
			while($row = $db->fetch_array($result)){
				$iBound[$row['dst_ip']] += $row['up_total'] + $row['down_total'];
			}
		}
		
		arsort($iBound);
	  foreach((Array)$iBound as $ip => $bound) {
			$tmp = array();
			$tmp['pcname'] = $ip;
			$tmp['ftotal'] = $bound;
			$aTop[] = $tmp;
			$this->nTotal += $bound;
		}
	  return $aTop;
	}
	
	function OutgoingFlowTopByPort()
	{
		global $db;
		$this->nTotal = 0;
		if($db->db != "temporary") $db->db = "temporary";
		$portMap = array();
		$conf = file("/PDATA/POLICESERVICE/basic_service");
		foreach($conf as $line) {
			$elt = explode(",", $line);
			$portMap[$elt[1]] = $elt[0];
		}
		
		$rec = array();
		
		$myport = ($this->direct == 1) ? "dst_port" : "src_port";
		
		$tables = array();
		for($i = $this->starttime; $i <= $this->endtime; $i+=86400) {
			$tables[] = date("Y-m-d", $i);
		}
		for($i = 0; $i < count($tables); $i++) {
			$sql = "SELECT $myport, sum(up_bytes) AS up_total, sum(down_bytes) AS down_total, sum(up_bytes + down_bytes) AS ftotal "; 
			$sql.= "FROM `".$tables[$i]."` WHERE ";
			if($i == 0){
				$sql.= "time_stamp >= $this->starttime AND ";
			}else if($i == count($tables) - 1){
				$sql.= "time_stamp < $this->endtime AND ";
			}
			$sql.= "sessions = $this->direct ";
			$sql.= "GROUP BY `$myport` ";
			$sql.= "HAVING ftotal > 1000000 ";
			$result = $db->query($sql);
			while($row = $db->fetch_array($result)) {
				$portname = (isset($portMap[$row[$myport]])) ? $portMap[$row[$myport]] : $row[$myport];
				$idx = $row[$myport];
				if(isset($rec[$idx])) {
					$rec[$idx]["up_total"] += $row["up_total"];
					$rec[$idx]["down_total"] += $row["down_total"];
					$rec[$idx]["ftotal"] += $row["ftotal"];
				} else {
					$rec[$idx] = array(
						port				=> $row[$myport],
						up_total 		=> $row["up_total"],
						down_total	=> $row["down_total"],
						ftotal			=> $row["ftotal"],
						portname		=> $portname
					);
				}
				$this->nTotal +=  $row["ftotal"];
			}
		}
		if(count($rec) > 0){
			usort($rec, "cmpUpDown");
		}
		return $rec;
	}
	
	function WebFlowTopBySite(){
		global $db;
		if($db->db != "tinyproxy") $db->db = "tinyproxy";
		$clientRec = array();
	
		$startMonth = "b".date("Y-m", $this->starttime);
		$endMonth = "b".date("Y-m", $this->endtime);
	
		$sMonth = intval("".(date("Ym", $this->starttime)));
		$eMonth = intval("".(date("Ym", $this->endtime)));
		$tables = array();
		$rResult = $db->query("SHOW TABLES");
		while($row = $db->fetch_array($rResult)){
			if(preg_match('/^b([\d]{4})-([\d]{2})$/', $row[0], $match)) {
				$m = intval("".$match[1].$match[2]);
				if($m >= $sMonth && $m <= $eMonth){
					$tables[] = "b".$match[1]."-".$match[2];
				}
			}
		}

		for($i = 0; $i < count($tables); $i++) {
			$sSql = "SELECT `connectURL`, count(*) AS `num` ";
			$sSql .= "FROM `".$tables[$i]."` ";
			$sSql.= "WHERE `client_stamp` >= $this->starttime AND `client_stamp` < $this->endtime ";
			$sSql.= "GROUP BY `connectURL` ";
			$rResult = $db->query($sSql);
			while($row = $db->fetch_array($rResult)){
				$idx = md5($row["connectURL"]);
				if(isset($clientRec[$idx])) {
					$clientRec[$idx]["num"]+= $row["num"];
				} else {
					$rec = array(
						connectURL => $row['connectURL'],
						num 		   => $row["num"]
					);
					$clientRec[$idx] = $rec;
				}
			}
		}
		
		foreach((Array)$clientRec as $row) {
			$tmp = array(
				web => $row["connectURL"],
				num => $row["num"]
			);
			$items[] = $tmp;
		}
		
		if(count($items) > 0){
			usort($items, "cmpHit");
		}
		return $items;
	}
	
	function WebFlowTopByUser()
	{
		global $db;
		$clientRec = array();
		if($db->db != "tinyproxy") $db->db = "tinyproxy";
		$startMonth = "b".date("Y-m", $this->starttime);
		$endMonth = "b".date("Y-m", $this->endtime); 
		$sMonth = intval("".(date("Ym", $this->starttime)));
		$eMonth = intval("".(date("Ym", $this->endtime)));
		$tables = array();
		
		$rResult = $db->query("SHOW TABLES");
		while($row = $db->fetch_array($rResult)){
			if(preg_match('/^b([\d]{4})-([\d]{2})$/', $row[0], $match)) {
				$m = intval("".$match[1].$match[2]);
				if($m >= $sMonth && $m <= $eMonth){
					$tables[] = "b".$match[1]."-".$match[2];
				}
			}
		}
		
		for($i = 0; $i < count($tables); $i++) {
			$sSql = "SELECT `clientaddress`, count(*) AS `num` ";
			$sSql .= "FROM `".$tables[$i]."` ";
			$sSql.= "WHERE `client_stamp` >= $this->starttime AND `client_stamp` < $this->endtime ";
			$sSql.= "GROUP BY `clientaddress` ";
			$rResult = $db->query($sSql);
			while($row = $db->fetch_array($rResult)){
				$idx = md5($row['clientaddress']);
				if(isset($clientRec[$idx])){
					$clientRec[$idx]["num"] += $row["num"];
				}else{
					$rec = array(
						clientaddress => $row['clientaddress'],
						num 		      => $row["num"],
						clientmac     => $this->ipmacs[$row['clientaddress']][1],
					  clientname    => $this->ipmacs[$row['clientaddress']][2]
					);
					$clientRec[$idx] = $rec;
				}
			}
		}

		foreach((Array)$clientRec as $row) {
			$tmp = array(
				clientaddress => $row['clientaddress'],
				num 		      => $row["num"],
				clientmac     => $row['clientmac'],
				clientname    => $row['clientname']
			);
			$items[] = $tmp;
		}
		if(count($items) > 0){
			usort($items, "cmpHit");
		}
		return $items;
	}
	
	function get_allipmacs(){
		if(!is_file($this->lan_ipmac)) return false;
		$msg = file($this->lan_ipmac);
		for($i=0;$i<count($msg);$i++){
			$a = explode(",",trim($msg[$i]));
			if($this->i_ip($a["0"])) $ipmacs[$a[0]] = $a;
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
}

function cmpUpDown($a, $b)
{
	if($a["ftotal"] == $b["ftotal"]) return 0;
	return ($a["ftotal"] > $b["ftotal"]) ? -1 : 1;
}

function cmpHit($a, $b)
{
	if($a["num"] == $b["num"]) return 0;
	return ($a["num"] > $b["num"]) ? -1 : 1;	
}
?>