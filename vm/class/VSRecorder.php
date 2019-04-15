<?
class VSRecorder
{
	var $configFile = "/PDATA/MAP/MAP";
	var $IDField = 0;
	var $ChainField = 1;
	var $CommentField = 2;	

	function insert($chainName, $sno, $port)
	{
		$chainName = str_replace("-L", "", $chainName);
		$chainName = str_replace("-D", "", $chainName);
		
		$config = $this->readfile($this->configFile);		
		foreach($config as $idx => $line)
		{
			$elt = explode(",", $line);
			if(trim($elt[$this->ChainField]) == $chainName)
			{//find it
				$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
				$comment[] = "$sno&$port"; /** Append **/
				$elt[$this->CommentField] = implode("#", $comment);
				$config[$idx] = implode(",", $elt);
				$this->writefile($this->configFile, $config);
				break;
			}
		}
	}
		
	function delete($sno)
	{
		$config = $this->readfile($this->configFile);		
		foreach($config as $idx => $line)
		{
			$elt = explode(",", $line);
			$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
			foreach($comment as $key => $val)
			{
				if(strpos($val, $sno) !== false)
				{//find it
					unset($comment[$key]); /** Delete **/
					$elt[$this->CommentField] = implode("#", $comment);
					$config[$idx] = implode(",", $elt);
					$this->writefile($this->configFile, $config);
					break;
				}
			}
		}
	}
	
	function readfile($filename)
	{
		$config = array();
		
		if(file_exists($filename))
		{			
			$tmp = file($filename);
			foreach($tmp as $line)
			{
				$line = str_replace("\n", "", $line);
				if(trim($line) == "")
					continue; //Ignore blank line
				$config[] = $line;
			}
		}
		
		return $config;
	}
	
	function writefile($filename, $config)
	{
		$fp = fopen($filename, "w");
		fwrite($fp, implode("\n", $config));
		fclose($fp);				
	}	

	function mapPolicyAlias()
	{
		$map = array();
		$map[] = "var map = new Array();";
				
		$dictionary = array(
			"INCOM_RULE" => "/PCONF/incomingrule/incomingrule"
		);

		foreach($dictionary as $key => $mapfile)
		{
			if(file_exists($mapfile))
			{				
				$position = $this->findPolicyPosition($key);

				$file = file($mapfile);
				foreach((Array)$file as $line)
				{
					$line = str_replace("\n", "", $line);
					if(trim($line) == "")
						continue;	//blank line

					$elt = explode(",", $line);
					$map[] = "map[\"n$elt[0]\"] = \"".$position[$elt[0]]." ".str_replace("\n", "", $elt[1])."\";";
				}
			}
		}

		return implode("\n", $map) . "\n"; 
	}

	function findPolicyPosition($cast)
	{
		$position = array();
		
		if($cast == "OUTGO_RULE")
		{
			$chain = array(
				"LAN_to_WAN" => "outgoing.pre",
				"DMZ_to_WAN" => "DMZ_outgoing.pre"
			);
			
			foreach($chain as $title => $chainName)
			{
				unset($ret);
				exec("/PGRAM/ipt4/sbin/iptables -t mangle -S $chainName", $ret);
				foreach($ret as $num => $line)
				{
					if(preg_match('/"n([\d]+\.?[\d]*)_/', $line, $match)) {
						$position[$match[1]] = "$title $num";
					}
				}
			}
		}
		else if($cast == "INCOM_RULE")
		{
			$chain = array(
				"WAN_to_LAN" => "incoming_L",
				"WAN_to_DMZ" => "incoming_D"
			);

			foreach($chain as $title => $chainName)
			{
				unset($ret);
				exec("/PGRAM/ipt4/sbin/iptables -t nat -S $chainName", $ret);
				foreach($ret as $num => $line)
				{
					if(preg_match('/"n([\d]+\.?[\d]*)_/', $line, $match)) {
						$position[$match[1]] = "$title $num";
					}
				}
			}
		}
		
		return $position;
	}

	function showPortMap($chainName)
	{
		$config = $this->readfile($this->configFile);		
		foreach($config as $idx => $line)
		{
			$elt = explode(",", $line);
			if(trim($elt[$this->ChainField]) == $chainName)
			{//find it
				$comment = trim($elt[$this->CommentField]);
				$comment = str_replace("\n", "", $comment);
				break;
			}
		}		
	
		return "var portMapList = \"$comment\";\n";
	}
	
	function deletePolicyRule($vno)
	{
		$sns = array();
		$config = $this->readfile($this->configFile);		
		foreach($config as $idx => $line)
		{
			$elt = explode(",", $line);
			if(trim($elt[$this->IDField]) == $vno)
			{//find it
				$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
				foreach($comment as $val)
				{
					$val = str_replace("n", "", $val);
					$val = explode("&", $val);
					$sns[] = $val[0];
				}	
				break;
			}
		}
		
		$this->removePolicy($sns);
	}
	
	function deletePortMapPolicyRule($row)
	{
		$sns = array();
		$config = $this->readfile($this->configFile);		
		$remove_list = $this->getRemoveList($row["sn"]);
		foreach($config as $idx => $line)
		{
			$elt = explode(",", $line);
			if(trim($elt[$this->ChainField]) == $row["sn"])
			{//find it
				$sSearch = "";
				if(!empty($row["chainname"])) {
					$elt_chainname = explode("-",$row["chainname"]);
					$sSearch = $elt_chainname[3]."-";
				}else {
					if(!empty($row["lan_type"])) {
						$sSearch = $row["lan_type"]."-";
					}else {
						$sSearch = $row["saveid"]."-";
					}
				}
				$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
				foreach($comment as $key => $val)
				{
					$ee = explode("&", $val);
					if(!empty($row["chainname"]) && !empty($row["org_wp"]) && !empty($row["org_saveid"])) { //Server_Lb_List.php use
						$sRemove = $sSearch;
						if($ee[1] == $row["org_wp"]) {
							$sRemove .= $row["org_saveid"]."-";
						}
						if(in_array($sRemove.$ee[0],$remove_list))
						{
							unset($comment[$key]);
							$sns[] = str_replace("n", "", $ee[0]);
						}
					}else {
						if($ee[1] == $row["wp"] || $ee[1] == $row["protocol"] || ($ee[1] == "ALL" && in_array($sSearch.$ee[0],$remove_list)))
						{
							unset($comment[$key]);
							$sns[] = str_replace("n", "", $ee[0]);
						}
					}
				}
				$elt[$this->CommentField] = implode("#", $comment);
				$config[$idx] = implode(",", $elt);
				$this->writefile($this->configFile, $config);				
				break;
			}
		}
		
		$this->removePolicy($sns);
	}

	function removePolicy($sns)
	{
		$total = count($sns);
		for($i = 0; $i < $total; $i++)
		{
			if(trim($sns[$i]) == "")
				continue; //空白
			
			$isFind = false;
			
			if($isFind == false)
			{//試著在 incoming_L Chain 裡找 rule id
				unset($ret);
				exec("/PGRAM/ipt4/sbin/iptables -t nat -L incoming_L -n | grep ".$sns[$i], $ret);
				if(count($ret) > 0)
				{
					exec("/PDATA/apache/start_local/inpolicyL.php del ".$sns[$i]);
					$isFind = true;
				}
			}

			if($isFind == false)
			{//試著在 incoming_D Chain 裡找 rule id
				unset($ret);
				exec("/PGRAM/ipt4/sbin/iptables -t nat -L incoming_D -n | grep ".$sns[$i], $ret);
				if(count($ret) > 0)
				{
					exec("/PDATA/apache/start_local/inpolicyD.php del ".$sns[$i]);
					$isFind = true;
				}
			}
								
			$this->delete("n".$sns[$i]);
			$this->deleteNetBehavior("n".$sns[$i]);
			$this->deleteIPAlias("n".$sns[$i]);
			$this->deleteIncomingChain($sns[$i]);
		}	
	}

	function deleteNetBehavior($sno)
	{
		include_once("/PDATA/apache/class/NetBehavior.php");
		$netBehavior = new NetBehavior();
		$netBehavior->deleteComment($sno);
	}

	function deleteIPAlias($sno)
	{
		include_once("/PDATA/apache/class/IPAliasRecorder.php");
		$IPAlias = new IPAliasRecorder();
		$IPAlias->delete($sno);
	}

	function deleteIncomingChain($sno)
	{
		include_once("/PDATA/apache/Program/Rule/class/all_Lan_And_Dmz_Destination.php");
		$aladd = new all_Lan_And_Dmz_Destination();
		$aladd->update_configure("del", $sno);
	}

	function getRemoveList($sn)
	{
		$aRemoveList = array();
		$sSaveListFile = "/PDATA/MAP/savelist";
		$sSlbDir = "/PDATA/MAP/server_lb/";
		$aSlbFileName = array();
		$aSlbNum = array();
		
		$aSnL = array();
		$aSnD = array();
		$nSumL = 0;
		$nSumD = 0;
		
		if(!is_file($sSaveListFile)) {
			return $aRemoveList;
		}
		$msg = file($sSaveListFile);
		foreach((Array)$msg as $v) {
			$a = explode(",",trim($v));
			if($a[1] != $sn || empty($a[9])) {
				continue;
			}
			if($a[6] == "L") {
				$aSnL = array_merge($aSnL,explode("#",$a[9]));
				$nSumL++;
			}else if($a[6] == "D") {
				$aSnD = array_merge($aSnD,explode("#",$a[9]));
				$nSumD++;
			}else {
				$aSlbFileName[] = $a[0];
			}
		}
		foreach((Array)$aSlbFileName as $v) {
			if(!is_file($sSlbDir.$v)) {
				continue;
			}
			$aSlbNum[$v]["L"] = 0;
			$aSlbNum[$v]["D"] = 0;
			$msg = file($sSlbDir.$v);
			foreach((Array)$msg as $vv) {
				$a = explode(",",trim($vv));
				if(empty($a[7])) {
					continue;
				}
				$b = explode("-",$a[1]);
				if($b[3] == "L") {
					$aSnL = array_merge($aSnL,explode("#",$a[7]));
					$nSumL++;
					$aSlbNum[$v]["L"]++;
				}else if($b[3] == "D") {
					$aSnD = array_merge($aSnD,explode("#",$a[7]));
					$nSumD++;
					$aSlbNum[$v]["D"]++;
				}
			}
		}
		
		$sClassifyFile = "/PCONF/incomingrule/incoming_classifying";
		$aClassifying = array();
		if(is_file($sClassifyFile)) {
			$msg = file($sClassifyFile);
			foreach((Array)$msg as $line) {
				$elt = explode(",", trim($line));
				$aClassifying[$elt[0]] = $elt[1];
			}
		}
		foreach((Array)$aSnL as $i => $v) {
			if(isset($aClassifying[$v])) {
				$aSnL[$i] = "n".$aClassifying[$v];
			}
		}
		foreach((Array)$aSnD as $i => $v) {
			if(isset($aClassifying[$v])) {
				$aSnD[$i] = "n".$aClassifying[$v];
			}
		}
		$aSnL = array_unique($aSnL);
		$aSnD = array_unique($aSnD);
		
		if($nSumL == 1) {
			foreach((Array)$aSnL as $v) {
				$aRemoveList[] = "L-".$v;
			}
		}
		if($nSumD == 1) {
			foreach((Array)$aSnD as $v) {
				$aRemoveList[] = "D-".$v;
			}
		}
		foreach((Array)$aSlbNum as $i => $v) {
			if($v["L"] == $nSumL) {
				foreach((Array)$aSnL as $vv) {
					$aRemoveList[] = $i."-".$vv;
				}
			}
			if($v["L"] == 1) {
				foreach((Array)$aSnL as $vv) {
					$aRemoveList[] = "L-".$i."-".$vv;
				}
			}
			if($v["D"] == $nSumD) {
				foreach((Array)$aSnD as $vv) {
					$aRemoveList[] = $i."-".$vv;
				}
			}
			if($v["D"] == 1) {
				foreach((Array)$aSnD as $vv) {
					$aRemoveList[] = "D-".$i."-".$vv;
				}
			}
		}
		
		return $aRemoveList;
	}
}
?>