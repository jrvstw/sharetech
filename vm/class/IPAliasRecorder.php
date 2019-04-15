<?
include_once("/PDATA/apache/Program/Rule/class/policyrule_class.php");
include_once("/PDATA/apache/Program/Object/class/srcip_class.php");
include_once("/PDATA/apache/Program/Object/class/dstip_class.php");
include_once("/PDATA/apache/Program/Object/class/addrGroupSet_class.php");
include_once("/PDATA/apache/Program/Object/class/srcip_nat_class.php");

class IPAliasRecorder
{
	var $configFile = array(
		LAN 			=> "/PDATA/IPMAC/srcipmacalias",
		DMZ 			=> "/PDATA/IPMAC/dmzsrcipmacalias",
		WAN 			=> "/PDATA/DESTINATIONIP/destinationipalias",		
		LAN_SRC 	=> "/PDATA/IPMAC/GROUP/IPMACGROUP",
		LAN_DST 	=> "/PDATA/IPMAC/GROUP/IPMACGROUP_DST",
		DMZ_SRC 	=> "/PDATA/IPMAC/GROUP/IPMACGROUP",
		DMZ_DST 	=> "/PDATA/IPMAC/GROUP/IPMACGROUP_DST",		
		WAN_SRC 	=> "/PDATA/DESTINATIONIP/destinationipgroup_src",
		WAN_DST 	=> "/PDATA/DESTINATIONIP/destinationipgroup",
		WAN_BRI 	=> "/PDATA/DESTINATIONIP/destinationipgroup_src_b"
	);

	var $pool = array();
	var $copy = array();
	
	var $IPField = 0;
	var $AliasField = 0;
	var $CommentField = 0;

	function setField($type)
	{
		if($type == "LAN" || $type == "DMZ")
		{//成員 LAN,DMZ
			$this->IPField = 0;
			$this->AliasField = 2;
			$this->CommentField = 3;
		}
		else if($type == "WAN")
		{//成員 WAN
			$this->IPField = 0;
			$this->AliasField = 1;
			$this->CommentField = 2;
		}
		else if(strpos($type, "LAN_") !== false || strpos($type, "DMZ_") !== false)
		{//群組 LAN_SRC,LAN_DST,DMZ_SRC,DMZ_DST
			$this->IPField = 0;
			$this->AliasField = 1;
			$this->CommentField = 4;
		}
		else if(strpos($type, "WAN_") !== false)
		{//群組 WAN_SRC,WAN_DST,WAN_BRI
			$this->IPField = 0;
			$this->AliasField = 1;
			$this->CommentField = 2;
		}
	}

	function insert($type, $ip, $alias, $sno, $writeBack = true)
	{
		$this->setField($type);		
		$config = $this->readfile($this->configFile[$type]);
		$isModify = false;
		foreach($config as $lineNumber => $data)
		{
			$data = str_replace("\n", "", $data);
			
			if(trim($data) == "")
				continue; //Ignore blank line
		
			$elt = explode(",", $data);
			if($elt[$this->IPField] == $ip && $elt[$this->AliasField] == $alias)
			{//找到要修改的行
				$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
				$comment[] = $sno;
				$elt[$this->CommentField] = implode("#", $comment);
				$config[$lineNumber] = implode(",", $elt) . "\n";								
				$isModify = true;
				break;
			}
		}
		
		if($isModify)
		{//需要做寫回
			$this->writefile($this->configFile[$type], $config, $writeBack);
		}
	
		return $isModify;
	}

	function insertByMember($type, $members, $sno)
	{
		if(!is_array($members))
		{//傳進來的東東是字串的話, 要切成陣列
			$elts = explode("@", substr($members, 0, -1));			
			$members = array();
			foreach($elts as $unit)
				$members[] = explode("#", $unit);
		}
				
		$total = count($members);
		for($i = 0; $i < $total; $i++)
		{
			$writeBack = ( $i == $total - 1 ) ? true : false; //最後一圈要做檔案寫回
			$this->insert($type, $members[$i][0], $members[$i][1], $sno, $writeBack);
		}
	}
		
	function insertByGroup($type, $gno, $sno, $writeBack = true)
	{
		$this->setField($type);		
		$config = $this->readfile($this->configFile[$type]);
		$isModify = false;
		foreach($config as $lineNumber => $data)
		{
			$data = str_replace("\n", "", $data);
			
			if(trim($data) == "")
				continue; //Ignore blank line
		
			$elt = explode(",", $data);
			if($elt[$this->IPField] == $gno)
			{//找到要修改的行
				$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
				$comment[] = $sno;
				$elt[$this->CommentField] = implode("#", $comment);
				if($type == "LAN_DST" || $type == "DMZ_DST")
				{
					$elt[3] = "x"; /** 填空 **/
					ksort($elt);
				}
				$config[$lineNumber] = implode(",", $elt) . "\n";								
				$isModify = true;
				break;
			}
		}
		
		if($isModify)
		{//需要做寫回
			$this->writefile($this->configFile[$type], $config, $writeBack);
		}
		
		return $isModify;
	}
	
	function delete($sno, $writeBack = true)
	{
		foreach($this->configFile as $type => $filname)
		{
			$this->setField($type);
			$config = $this->readfile($filname);
			$isModify = false;
			foreach($config as $lineNumber => $data)
			{
				$data = str_replace("\n", "", $data);
				
				if(trim($data) == "")
					continue; //Ignore blank line
			
				$elt = explode(",", $data);				
				$comment = explode("#", $elt[$this->CommentField]);

				if(is_array($comment) && in_array($sno, $comment))
				{//找到要修改的行
					$idx = array_search($sno, $comment);
					unset($comment[$idx]);
					$elt[$this->CommentField] = implode("#", $comment);
					$config[$lineNumber] = implode(",", $elt) . "\n";								
					$isModify = true;
				}
			}
			
			if($isModify)
			{//需要做寫回
				$this->writefile($filname, $config, $writeBack);
			}
		}
	}

	function query($type, $ip, $sno)
	{
		$this->setField($type);		
		$config = $this->readfile($this->configFile[$type]);

		foreach($config as $lineNumber => $data)
		{
			$data = str_replace("\n", "", $data);
			
			if(trim($data) == "")
				continue; //Ignore blank line
		
			$elt = explode(",", $data);
			if($elt[$this->IPField] == $ip)
			{
				$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
				if(in_array($sno, $comment))
				{					
					if(trim($elt[$this->AliasField]) != "")
						return $elt[$this->AliasField];
					else
						return $ip;
				}
			}
		}	
		
		return $ip;
	}
	
	function readfile($filename)
	{
		$idx = md5($filename);

		if(!isset($this->copy[$idx]))
		{
			if(file_exists($filename))
				$tmp = file($filename);
			else
				$tmp = array();
				
			$this->pool[$idx] = $tmp;
			$this->copy[$idx] = $tmp;
		}

		return $this->copy[$idx];
	}
	
	function writefile($filename, $data, $writeBack)
	{
		$idx = md5($filename);
		
		$writeBack = true; /* 暫時先全部寫回 */
		
		if($writeBack)
		{
			if(implode("", $this->pool[$idx]) != implode("", $data))
			{//資料寫回檔案				
				$fp = fopen($filename, "w");
				fwrite($fp, implode("", $data));
				fclose($fp);
			}
			unset($this->pool[$idx]);
			unset($this->copy[$idx]);
		}
		else
		{//資料寫回副本
			$this->copy[$idx] = $data;
		}
	}
	
	function jsArray()
	{
		$map = array();
		$map[] = "var map = new Array();";
				
		$dictionary = $this->configFile;
		unset($dictionary["LAN"], $dictionary["DMZ"], $dictionary["WAN"]);
		unset($dictionary["DMZ_SRC"], $dictionary["DMZ_DST"]);
		$dictionary["OUTGO_RULE"] = "/PCONF/outgoingrule/outgoingrule";
		$dictionary["INCOM_RULE"] = "/PCONF/incomingrule/incomingrule";

		foreach($dictionary as $key => $mapfile)
		{
			if(file_exists($mapfile))
			{				
				$flag = (strpos($key, "_RULE") !== false) ? "n" : "s";
				$position = $this->findPolicyPosition($key);

				$file = file($mapfile);
				foreach((Array)$file as $line)
				{
					$line = str_replace("\n", "", $line);
					if(trim($line) == "")
						continue;	//blank line

					$elt = explode(",", $line);
					$map[] = "map[\"{$flag}{$elt[0]}\"] = \"".$position[$elt[0]]." ".str_replace("\n", "", $elt[1])."\";";
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
					if(preg_match('/"n([\d]+\.[\d]+)_/', $line, $match)) {
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
					if(preg_match('/"n([\d]+\.[\d]+)_/', $line, $match)) {
						$position[$match[1]] = "$title $num";
					}
				}
			}
		}
		
		return $position;
	}
	
	function rsyncDelete($type, $data)
	{
		if(!is_array($data) || count($data) == 0)
			return true; //不須處理
	
		$delPolicy = array();
		$delGroup = array();
		
		/** 篩出 n123456.78, s789456.23 **/
		$this->setField($type);		
		foreach($data as $line)
		{
			$line = str_replace("\n", "", $line);
			$elt = explode(",", $line);
			$comment = empty($elt[$this->CommentField]) ? array() : explode("#", $elt[$this->CommentField]);
			foreach((Array)$comment as $val)
			{
				if($val[0] == "n")
					$delPolicy[] = substr($val, 1);
				else if($val[0] == "s")
					$delGroup[] = array(substr($val, 1), $elt[$this->IPField]);
			}
		}
	
		if(count($delPolicy) > 0)
		{//刪除條例
			$this->deletePolicyRule($delPolicy);
		}
	
		if(count($delGroup) > 0)
		{//刪除群組裡成員
			foreach($delGroup as $val)
				$this->deleteGroupMember($val[0], $val[1], $type);
		}
	
		if(count($delPolicy) > 0 || count($delPolicy) > 0)
			$this->save_iptable();
	}

	function deletePolicyRule($sns)
	{
		$total = count($sns);
		for($i = 0; $i < $total; $i++)
		{
			if(trim($sns[$i]) == "")
				continue; //空白
			
			$isFind = false;

			unset($ret);
			exec("/PGRAM/ipt4/sbin/iptables -t mangle -L -n | grep ".$sns[$i], $ret);
			if(preg_match('/Chain \.(.+)\.p/', $ret[0], $match))
			{//找出 rule id 在哪個 Chain 裡
				$typerule = $match[1];
				$typeruleing = $match[1]."ing";
				
				if($typeruleing == "o2i_bridgeing")
					$typeruleing = "o2i_bridging";
				else if($typeruleing == "i2o_bridgeing")
					$typeruleing = "i2o_bridging";
				else if($typeruleing == "incoing")
					$typeruleing = "incoming";
				else if($typeruleing == "inco_rting")
					$typeruleing = "incoming_routing";
				
				$policyrule = new policyrule();
				$del_rule = $policyrule->del_policy($sns[$i], $typerule, $typeruleing);
				$policyrule->run_cmd($del_rule);
				$policyrule->del2cfg(array($sns[$i]));	
				$isFind = true;
			}
			
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
								
			$writeBack = ( $i == $total - 1 ) ? true : false; //最後一圈要做檔案寫回
			$this->delete("n".$sns[$i], $writeBack);
			$this->deleteNetBehavior("n".$sns[$i]);
			$this->deleteVSRecorder("n".$sns[$i]);
			$this->deleteIncomingChain($sns[$i]);
		}
	}
	
	function deleteNetBehavior($sno)
	{
		include_once("/PDATA/apache/class/NetBehavior.php");
		$netBehavior = new NetBehavior();
		$netBehavior->deleteComment($sno);
	}

	function deleteVSRecorder($sno)
	{
		include_once("/PDATA/apache/class/VSRecorder.php");
		$VSRecorder = new VSRecorder();
		$VSRecorder->delete($sno);
	}

	function deleteIncomingChain($sno)
	{
		include_once("/PDATA/apache/Program/Rule/class/all_Lan_And_Dmz_Destination.php");
		$aladd = new all_Lan_And_Dmz_Destination();
		$aladd->update_configure("del", $sno);
	}
	
	/**
	 *	LAN,DMZ SRC -- srcip_class
	 *					DST -- addrGroupSet_class
	 *					
	 *	WAN 		NAT -- srcip_nat_class
	 *					BRI -- addrGroupSet_class
	 *					DST -- dstip_class
	 */

	function deleteGroup($sno, $type)
	{
		$comment = array();

		if($type == "LAN" || $type == "DMZ")
		{
			/** 找出DST群組所使用的id **/
			$srcGroupInfo = $this->getGroupInfoById($sno, "LAN_SRC");			
			$dstGroupInfo = $this->getGroupInfoByAlias($srcGroupInfo[1], "LAN_DST");
			$sno_dst = $dstGroupInfo[0];

			$srcGroupInfo[4] = str_replace("n", "", $srcGroupInfo[4]);								
			$tmp = explode("#", $srcGroupInfo[4]);
			foreach((Array)$tmp as $val)	$comment[] =  $val;
				
			$dstGroupInfo[4] = str_replace("n", "", $dstGroupInfo[4]);								
			$tmp = explode("#", $dstGroupInfo[4]);
			foreach((Array)$tmp as $val)	$comment[] =  $val;
		
			$this->delete("s$sno");
			$this->delete("s$sno_dst");
		}
		else if($type == "WAN")
		{
			/** 找出SRC群組所使用的id **/
			$dstGroupInfo = $this->getGroupInfoById($sno, "WAN_DST");
			$srcGroupInfo = $this->getGroupInfoByAlias($dstGroupInfo[1], "WAN_SRC");						
			$briGroupInfo = $this->getGroupInfoByAlias($dstGroupInfo[1], "WAN_BRI");						
			$sno_src = $srcGroupInfo[0];
			$sno_bri = $briGroupInfo[0];

			$srcGroupInfo[2] = str_replace("n", "", $srcGroupInfo[2]);								
			$tmp = explode("#", $srcGroupInfo[2]);
			foreach((Array)$tmp as $val)	$comment[] =  $val;
				
			$dstGroupInfo[2] = str_replace("n", "", $dstGroupInfo[2]);								
			$tmp = explode("#", $dstGroupInfo[2]);
			foreach((Array)$tmp as $val)	$comment[] =  $val;

			$briGroupInfo[2] = str_replace("n", "", $briGroupInfo[2]);								
			$tmp = explode("#", $briGroupInfo[2]);
			foreach((Array)$tmp as $val)	$comment[] =  $val;
		
			$this->delete("s$sno");
			$this->delete("s$sno_src");
			$this->delete("s$sno_bri");
		}

		if(count($comment) > 0)
		{//刪除群組被參考到的所有條例
			$this->deletePolicyRule($comment);
		}
	}
	
	function deleteGroupMember($sno, $ip, $type)
	{		
		if($type == "LAN" || $type == "DMZ")
		{
			/** 找出DST群組所使用的id **/
			$srcGroupInfo = $this->getGroupInfoById($sno, "LAN_SRC");			
			$dstGroupInfo = $this->getGroupInfoByAlias($srcGroupInfo[1], "LAN_DST");
			$sno_dst = $dstGroupInfo[0];
		
			$sip = new srcip();			
			$dip = new addrGroupSet_class();
			
			$pat = $sip->list_src_ip($sno);
			$new_pat = array();
			$new_pat2 = array();
			foreach($pat as $val)
			{//新的群組成員組合
				if(empty($val[1]))
				{//unset empty
					unset($val[1]);
				}
				if($ip != $val[0])
				{
					$new_pat[] 	= $val;
					$new_pat2[] = $val[0];
				}
			}

			if(count($new_pat) == 0)
			{//先刪除群組相關的條例, 再刪除群組這個Chain.			
				$comment = array();
				
				$srcGroupInfo[4] = str_replace("n", "", $srcGroupInfo[4]);								
				$tmp = explode("#", $srcGroupInfo[4]);
				foreach((Array)$tmp as $val)	$comment[] =  $val;
				
				$dstGroupInfo[4] = str_replace("n", "", $dstGroupInfo[4]);								
				$tmp = explode("#", $dstGroupInfo[4]);
				foreach((Array)$tmp as $val)	$comment[] =  $val;
				
				if(count($comment) > 0)
					$this->deletePolicyRule($comment);
				
				$sip->del(array($sno));
				$dip->del_dst(array($sno_dst));
				
				$this->copy = array();	//flush
			}
			else
			{//刪除成員
				/*
				$sip->edit_src_ip($sno, $srcGroupInfo[1], $new_pat, $srcGroupInfo[2], $srcGroupInfo[3]);
				$dip->edit_dst_ip($sno_dst, $dstGroupInfo[1], $new_pat2);
				*/
				$IPTABLES = "/PGRAM/ipt4/sbin/iptables";		
				$srcip_pre = ".srcip.pre.".$sno;
				$srcip_post = ".srcip.post.".$sno;
				$dstip_pre = ".dstip.pre.".$sno_dst;
				$dstip_post = ".dstip.post.".$sno_dst;
				exec("$IPTABLES -t mangle -L $srcip_pre -n --line-numbers | grep \"$ip\"", $ret);
				foreach((Array)$ret as $line)
				{
					$elt = split("[ \t]+", $line);
					if($elt[4] == $ip)
					{//Delete
						exec("$IPTABLES -t mangle -D $srcip_pre $elt[0]");
						exec("$IPTABLES -t mangle -D $srcip_pre $elt[0]");
						exec("$IPTABLES -t mangle -D $srcip_post $elt[0]");
						exec("$IPTABLES -t mangle -D $srcip_post $elt[0]");
						exec("$IPTABLES -t mangle -D $dstip_pre $elt[0]");
						exec("$IPTABLES -t mangle -D $dstip_pre $elt[0]");
						exec("$IPTABLES -t mangle -D $dstip_post $elt[0]");
						exec("$IPTABLES -t mangle -D $dstip_post $elt[0]");
						break;
					}
				}				
			}		
		}
		else if($type == "WAN")
		{
			/** 找出SRC群組所使用的id **/
			$dstGroupInfo = $this->getGroupInfoById($sno, "WAN_DST");			
			$srcGroupInfo = $this->getGroupInfoByAlias($dstGroupInfo[1], "WAN_SRC");			
			$briGroupInfo = $this->getGroupInfoByAlias($dstGroupInfo[1], "WAN_BRI");			
			$sno_src = $srcGroupInfo[0];
			$sno_bri = $briGroupInfo[0];
		
			$sip = new srcip_nat();			
			$dip = new dstip();
			$bip = new addrGroupSet_class();			
			
			$pat = $dip->list_dst_ip($sno);
			$new_pat = array();
			$new_pat2 = array();
			foreach($pat as $val)
			{//新的群組成員組合
				if($ip != $val)
				{
					$new_pat[] = $val;
					$new_pat2[][0] = $val;
				}
			}

			if(count($new_pat) == 0)
			{//先刪除群組相關的條例, 再刪除群組這個Chain.			
				$comment = array();
				
				$srcGroupInfo[2] = str_replace("n", "", $srcGroupInfo[2]);
				$tmp = explode("#", $srcGroupInfo[2]);
				foreach((Array)$tmp as $val)	$comment[] =  $val;
				
				$dstGroupInfo[2] = str_replace("n", "", $dstGroupInfo[2]);
				$tmp = explode("#", $dstGroupInfo[2]);
				foreach((Array)$tmp as $val)	$comment[] =  $val;

				$briGroupInfo[2] = str_replace("n", "", $briGroupInfo[2]);
				$tmp = explode("#", $briGroupInfo[2]);
				foreach((Array)$tmp as $val)	$comment[] =  $val;
				
				if(count($comment) > 0)
					$this->deletePolicyRule($comment);
				
				$sip->del(array($sno_src));
				$bip->del_src(array($sno_bri));
				$dip->del(array($sno));
			}
			else
			{//刪除成員
				$sip->edit_src_ip($sno_src, $srcGroupInfo[1], $new_pat2);
				$bip->edit_src_ip($sno_bri, $briGroupInfo[1], $new_pat2);
				$dip->edit_dst_ip($sno, $dstGroupInfo[1], $new_pat);
			}		
		}		
	}

	function save_iptable()
	{//Save iptables
		exec("/PDATA/apache/save_iptable.php");
	}

	function getGroupInfoById($sno, $type)
	{
		$config = $this->readfile($this->configFile[$type]);
		foreach($config as $line)
		{
			$line = str_replace("\n", "", $line);
			$elt = explode(",", $line);
			if($elt[0] == $sno)
				return $elt;
		}
	}

	function getGroupInfoByAlias($alias, $type)
	{
		$config = $this->readfile($this->configFile[$type]);
		foreach($config as $line)
		{
			$line = str_replace("\n", "", $line);
			$elt = explode(",", $line);
			if($elt[1] == $alias)
				return $elt;
		}
	}
	
	function log($message)
	{
		$filename = "/tmp/IPAliasRecorder.log";
		$fp = fopen($filename, "a");
		fwrite($fp, date("Y-m-d H:i:s")." ".$message."\n");
		fclose($fp);
	}
}
?>