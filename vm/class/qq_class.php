<?
class qq_class {
	var $profile = "/PCONF/qqchat/accounts";
	var $conf_file = "/PCONF/qqchat/config";
	var $page_slice = 16;
	var $use_db = "qqlog";
	var $chat_table = "chat_log";
	var $block_table = "block_log";

	function get_config_setting() {
		$aConf = array();
		if(is_file($this->conf_file) && filesize($this->conf_file) > 0) {
			$aConf = parse_ini_file($this->conf_file);
		} else {
			$aConf = array(
				"allow_login" => 0,
				"chat_record" => 0,
				"allow_file_transfer" => 0,
				"file_record" => 0,
				"allow_voip" => 0,
				"allow_desktop" => 0,
				"allow_http_transfer" => 0
			);
		}
		return $aConf;
	}
	
	function save_config_setting($aConf,$row) {
		foreach($aConf as $i => $v) {
			$aConf["$i"] = (!empty($row["$i"])) ? 1 : 0;
		}
		
		$res = array();
		foreach($aConf as $key => $val) {
			if(is_array($val)) {
				$res[] = "[$key]";
				foreach($val as $skey => $sval) {
					$res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
				}
			}else{
				$res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
			}
		}
		$sFileText = implode("\n", $res);
		$fp = fopen($this->conf_file, "w");
		fwrite($fp, $sFileText);
		fclose($fp);
		
		$this->change_general_accounts($aConf);
	}

	function get_qq_list() {
		$qq_list = array();
		
		if(is_file($this->profile) && filesize($this->profile) > 0) {
			$msg = file($this->profile);
			foreach($msg as $v) {
				$v = trim($v);
				if($v == "") {
					continue;
				}
				$exp_msg = explode(":",$v);
				$note = htmlspecialchars(preg_replace("/<[^>]*>/", "", trim($exp_msg[5])));
				$now_time = (empty($exp_msg[6])) ? "" : $exp_msg[6];
				$policy_type = (empty($exp_msg[7])) ? "1" : $exp_msg[7];
				$qq_list[] = array(
					"qq_number" => trim($exp_msg[0]),
					"password" => trim($exp_msg[1]),
					"mark" => trim($exp_msg[2]),
					"email" => trim($exp_msg[3]),
					"phone" => trim($exp_msg[4]),
					"note" => $note,
					"updatetime" => $now_time,
					"policy_type" => $policy_type
				);
			}
		}
		return $qq_list;
	}
	
	function add($row) {
		$save_data = $this->get_qq_list();
		$note_str = htmlspecialchars(preg_replace("/<[^>]*>/", "", trim($row["note"])));
		$now_time = time();
		$save_data[] = array(
			"qq_number" => trim($row["qq_number"]),
			"password" => trim($row["pw"]),
			"mark" => trim($row["mark_hex"]),
			"email" => trim($row["email"]),
			"phone" => trim($row["phone"]),
			"note" => $note_str,
			"updatetime" => $now_time,
			"policy_type" => trim($row["policy_type"])
		);
		$this->save_profile($save_data);
		$this->save_chain($save_data);
	}
	
	function edit($row) {
		$save_data = $this->get_qq_list();
		$note_str = htmlspecialchars(preg_replace("/<[^>]*>/", "", trim($row["note"])));
		foreach($save_data as $i => $v) {
			if(trim($row["qq_number"]) == trim($v["qq_number"])) {
				if(trim($row["pw"]) != trim($v["password"]) || trim($row["email"]) != trim($v["email"]) || trim($row["phone"]) != trim($v["phone"])) {
					$v["updatetime"] = time();
				}
				$save_data[$i] = array(
					"qq_number" => trim($row["qq_number"]),
					"password" => trim($row["pw"]),
					"mark" => trim($row["mark_hex"]),
					"email" => trim($row["email"]),
					"phone" => trim($row["phone"]),
					"note" => $note_str,
					"updatetime" => trim($v["updatetime"]),
					"policy_type" => trim($row["policy_type"])
				);
				break;
			}
		}
		$this->save_profile($save_data);
		$this->save_chain($save_data);
	}
	
	function del($del_list) {
		$save_data = $this->get_qq_list();
		foreach($save_data as $i => $v) {
			if(in_array(trim($v["qq_number"]),$del_list)) {
				unset($save_data[$i]);
			}
		}
		$this->save_profile($save_data);
		$this->save_chain($save_data);
	}
	
	function save_profile($save_data) {
		$input_data = array();
		foreach($save_data as $i => $v) {
			$input_data[] = trim(implode(":",$v));
		}
		$fp = fopen($this->profile , "w");
		fwrite($fp , implode("\n", $input_data));
		fclose($fp);
		exec("/etc/init.d/qqchat reload");
	}
	
	function save_chain($save_data = "") {
		include("/PDATA/apache/conf/fw.ini");
		
		if($save_data == "") {
			$save_data = $this->get_qq_list();
		}
		$aConf = $this->get_config_setting();
		if($aConf["allow_file_transfer"] == 1) {
			foreach($save_data as $v) {
				if($v["qq_number"] > 4294967295) {
					continue;
				}
				$block_info = $this->get_block_info($v["mark"]);
				if($block_info["allow_file_transfer"] == 0) {
					$aConf["allow_file_transfer"] = 0;
					break;
				}
			}
		}
		
		$tcpchain = "TCPQQID";
		$udpchain = "UDPQQID";
		$CMD = array();
		$CMD[] = $IPTABLES." -t mangle -F ".$tcpchain;
		$CMD[] = $IPTABLES." -t mangle -F ".$udpchain;
		
		$CMD[] = $IPTABLES." -t mangle -A ".$tcpchain." -m connmark --mark 0x400000/0x400000 -j RETURN";
		if($aConf["allow_http_transfer"] == 1) {
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -p tcp -m tcp --dport 80 -m layer7 --l7proto qq-filetransfer80 -j CONNMARK --set-xmark 0x400000/0x400000';
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -p tcp -m tcp --dport 80 -m layer7 --l7proto qqgrouppic -j CONNMARK --set-xmark 0x400000/0x400000';
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -p tcp -m multiport --ports 443 -m connbytes --connbytes 3:15 --connbytes-dir both --connbytes-mode packets -m length --length 147:200 -m u32 --u32 "0 >> 22 &0x3C @ 12 >> 26 & 0x3C @ 0>>24&0xFF=5 && 0 >> 22 & 0x3C @ 12 >> 26 & 0x3C @ 1&0xFF=107" -m string --hex-string "|2E6A7067|" --algo bm --from 112 -j CONNMARK --set-xmark 0x400000/0x400000';
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -p tcp --dport 443 -m connbytes --connbytes 3:15 --connbytes-dir both --connbytes-mode packets -m length --length 147:200 -m u32 --u32 "0 >> 22 &0x3C @ 12 >> 26 & 0x3C @ 0>>24&0xFF=5 && 0 >> 22 & 0x3C @ 12 >> 26 & 0x3C @ 1&0xFF=107" -m string --hex-string "|2E616d72|" --algo bm --from 112 -j CONNMARK --set-xmark 0x400000/0x400000';
//added by yu
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -m connmark --mark 0x400000/0x400000 -j LOG --log-prefix "blockqqother "';
//added --end
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -m connmark --mark 0x400000/0x400000 -j RETURN';
		}
		
		$CMD[] = $IPTABLES." -t mangle -A ".$udpchain." -p udp -m multiport --ports 53,123 -j RETURN";
		$CMD[] = $IPTABLES." -t mangle -A ".$udpchain." -m connmark --mark 0x400000/0x400000 -j RETURN";
		if($aConf["allow_http_transfer"] == 1) {
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -p udp -m layer7 --l7proto qq-filetransfer-udp -m length --length 190:200 -j CONNMARK --set-xmark 0x400000/0x400000';
//added by yu --start
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -m connmark --mark 0x400000/0x400000 -j LOG --log-prefix "blockqqother "';
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -m connmark --mark 0x400000/0x400000 -j RETURN';
//added --end
		}
		if($aConf["allow_file_transfer"] == 0) {
//			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -p udp -m connbytes --connbytes 20:70 --connbytes-dir both --connbytes-mode packets -m length --length 599 -m u32 --u32 "25&0xFF=0x05 && 47&0xFFFF=0x02BC" -j DROP';
//			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -p udp -m connbytes --connbytes 25:85 --connbytes-dir both --connbytes-mode packets -m length --length 1042 -m u32 --u32 "25&0xFF=0x05 && 38&0xFFFF=0x03e8" -j DROP';
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -p udp -m connbytes --connbytes 20:150 --connbytes-dir both --connbytes-mode packets -m length --length 595 -m u32 --u32 "25&0xff=0x05 && 47&0xffff=0x2BC,0x3E8" -j  DROP';
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -p udp -m connbytes --connbytes 20:150 --connbytes-dir both --connbytes-mode packets -m length --length 599 -m u32 --u32 "25&0xff=0x05 && 47&0xffff=0x2BC" -j DROP'; 
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -p udp -m connbytes --connbytes 25:150 --connbytes-dir both --connbytes-mode packets -m length --length 1042 -m u32 --u32 "25&0xff=0x05 && 38&0xffff=0x3E8" -j DROP';
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -m connmark --mark 0x400000/0x400000 -j RETURN';
		}
//		$CMD[] = $IPTABLES." -t mangle -A ".$udpchain." -m connmark --mark 0x400000/0x400000 -j RETURN";
		
		foreach($save_data as $v) {
			if($v["qq_number"] > 4294967295) {
				continue;
			}
			$block_info = $this->get_block_info($v["mark"]);
			if($block_info["allow_file_transfer"] == "0" && $block_info["allow_voip"] == "1" && $block_info["allow_desktop"] == "1") {
				$doAction = "BLOCKQQFILE";
			} else if($block_info["allow_file_transfer"] == "1" && $block_info["allow_voip"] == "0" && $block_info["allow_desktop"] == "1") {
				$doAction = "BLOCKQQVOIP";
			} else if($block_info["allow_file_transfer"] == "1" && $block_info["allow_voip"] == "1" && $block_info["allow_desktop"] == "0") {
				$doAction = "BLOCKQQRA";
			} else if($block_info["allow_file_transfer"] == "0" && $block_info["allow_voip"] == "1" && $block_info["allow_desktop"] == "0") {
				$doAction = "QQ_FILE_RA";
			} else if($block_info["allow_file_transfer"] == "0" && $block_info["allow_voip"] == "0" && $block_info["allow_desktop"] == "1") {
				$doAction = "QQ_FILE_VOIP";
			} else if($block_info["allow_file_transfer"] == "1" && $block_info["allow_voip"] == "0" && $block_info["allow_desktop"] == "0") {
				$doAction = "QQ_VOIP_RA";
			} else if($block_info["allow_file_transfer"] == "0" && $block_info["allow_voip"] == "0" && $block_info["allow_desktop"] == "0") {
				$doAction = "QQ_FILE_VOIP_RA";
			} else {
				continue;
			}
			$qq_hex = str_pad(base_convert($v["qq_number"],10,16),8,"0",STR_PAD_LEFT);
			
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -m u32 --u32 "49&0xFFFFFFFF=0x'.$qq_hex.'" -j '.$doAction;
			$CMD[] = $IPTABLES.' -t mangle -A '.$tcpchain.' -m connmark --mark 0x400000/0x400000 -j LOG --log-prefix "qq'.$v["qq_number"].' "'; 
			$CMD[] = $IPTABLES." -t mangle -A ".$tcpchain." -m connmark --mark 0x400000/0x400000 -j RETURN";
			
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -m string --algo bm --from 35 --to 64 --hex-string "|'.$qq_hex.'|" -j '.$doAction;
			$CMD[] = $IPTABLES.' -t mangle -A '.$udpchain.' -m connmark --mark 0x400000/0x400000 -j LOG --log-prefix "qq'.$v["qq_number"].' "'; 
			$CMD[] = $IPTABLES." -t mangle -A ".$udpchain." -m connmark --mark 0x400000/0x400000 -j RETURN";
		}
		$cmd_msg = $this->run_cmd($CMD);
		exec("/PDATA/apache/save_iptable.php");

		return $cmd_msg;
	}
	
	function run_cmd($CMD) {
		$nowtime = time() + microtime();
		$QQChianSave = "/tmp/QQChianSave_{$nowtime}";
		$tmpSave = array();
		$tmpSave[] = "*mangle";

		foreach($CMD as $line) {
			$tmpSave[] = str_replace("/PGRAM/ipt4/sbin/iptables -t mangle ", "", $line);
		}
		$tmpSave[] = "COMMIT";
		$tmpSave[] = "###############";
	
		$fp = fopen($QQChianSave, "w");
		fwrite($fp, implode("\n", $tmpSave));
		fclose($fp);

		exec("/PGRAM/ipt4/sbin/iptables-restore --noflush --test < {$QQChianSave} 2>&1", $ret);
		foreach((Array)$ret as $idx => $line) {//ignore message
			if(!empty($line) && strpos("Using intrapositioned negation (`--option ! this`) is deprecated in favor of extrapositioned (`! --option this`).", $line) !== false) {
				unset($ret[$idx]);
			}
		}
		
		
		if(count($ret) > 0) {
			return -1;
		}
		
		unset($ret);
		exec("/PGRAM/ipt4/sbin/iptables-restore --noflush < {$QQChianSave} 2>&1", $ret);
		
		foreach((Array)$ret as $idx => $line) {//ignore message
			if(!empty($line) && strpos("Using intrapositioned negation (`--option ! this`) is deprecated in favor of extrapositioned (`! --option this`).", $line) !== false) {
				unset($ret[$idx]);
			}
		}
		
		if(count($ret) > 0) {
			return -1;
		}
		
		if(is_file($QQChianSave)){
			unlink($QQChianSave);
		}
		
		return 1;
	}
	
	function get_block_info($row) {
		$aConf = $this->get_config_setting();
		$binMark = "";
		$block_info = array();
		if(is_array($row)) {
			foreach($aConf as $i => $v) {
				$block_info["$i"] = (!empty($row["$i"])) ? "1" : "0";
				$binMark .= $block_info["$i"];
			}
			$sBin = strrev($binMark);
			$block_info["mark"] = "0x".str_pad(base_convert($sBin,2,16),4,"0",STR_PAD_LEFT);
		} else {
			$sBin = strrev(base_convert($row,16,2));
			$k = 0;
			foreach($aConf as $i => $v) {
				$block_info["$i"] = ($k < strlen($sBin)) ? substr($sBin,$k,1) : "0";
				$k++;
			}
		}
		return $block_info;
	}
	
	function get_selectitems($offset,$page_slice,$items) {
		$selectitems = array();
		for($i = $offset; $i < ($offset+$page_slice); $i++) {
			if(trim($items[$i]) != "") {
				$selectitems[] = $items[$i];
			}
		}
		return $selectitems;
	}
	
	function get_qq_tip($LVar,$qq_list = "") {
		if($qq_list == "") {
			$qq_list = $this->get_qq_list();
		}
		$tip_list = array();
		foreach($qq_list as $v) {
			$id = trim($v["qq_number"]);
			$email = trim($v["email"]);
			$phone = trim($v["phone"]);
			$note = trim($v["note"]);
			$tip_list[$id]["tip"] = $LVar["QQ_Number"] . " : " . $id;
			if(!empty($email)) {
				$tip_list[$id]["tip"] .= "<br>".$LVar["Email"] . " : " . $email;
			}
			if(!empty($phone)) {
				$tip_list[$id]["tip"] .= "<br>".$LVar["Phone"] . " : " . $phone;
			}
			if(!empty($note)) {
				$tip_list[$id]["tip"] .= "<br>".$LVar["Note"] . " : " . $note;
				$tip_list[$id]["note"] = trim($v["note"]);
			}
		}
		return $tip_list;
	}
	
	function change_general_accounts($aConf) {
		unset($aConf["allow_http_transfer"]);
		$block_info = $this->get_block_info($aConf);
		$save_data = $this->get_qq_list();
		foreach($save_data as $i => $v) {
			if(trim($v["policy_type"]) == "1") {
				$save_data[$i]["mark"] = trim($block_info["mark"]);
			}
		}
		$this->save_profile($save_data);
		$this->save_chain($save_data);
	}
	
}
?>