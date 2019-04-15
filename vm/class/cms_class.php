<?
include_once("/PDATA/apache/class/Comm.php");

class cms_class{
	var $conf_path = "/PDATA/CMS/";
	var $data_path = "/HDD/CMS/";

	function get_auth_info($key = "") {
		$s_auth_path = $this->conf_path . "AUTH/";
		exec("/bin/ls " . $s_auth_path , $res_model);
		foreach($res_model as $val_model){
			unset($res_mac);
			exec("/bin/ls " . $s_auth_path . "/" . $val_model , $res_mac);
			foreach($res_mac as $val_mac){
				unset($res_auth_list, $auth_msg);
				exec("/bin/ls " . $s_auth_path . "/" . $val_model . "/" . $val_mac, $res_auth_list);
				foreach($res_auth_list as $val){
					unset($aClientFile);
					$aClientFile = file($s_auth_path . "/" . $val_model . "/" . $val_mac . "/". $val);
					if(trim($aClientFile[0]) == ""){
						continue;
					}
					$aTmp = explode(",", trim($aClientFile[0]));
					$aClient["key"] = $aTmp[0];
					$aClient["model"] = $aTmp[1];
					$aClient["mac"] = $aTmp[2];
					$aClient["alias"] = $aTmp[3];
					$aClient["group"] = $aTmp[4];
					$aClient["auto_backup"] = $aTmp[5];
					$aClient["ip"] = $aTmp[6];
					
					if ($key !== "" && $key == $aClient["key"]) {
						return $aClient;
					}
					$aClientList[] = $aClient;
				}
			}
		}
		return $aClientList;
	}
	
	function get_unauth_info($sMac = false){
		$unauth_list = $this->conf_path . "unauthorized";
		if(!is_file($unauth_list)){
			return false;
		}
		$aFile = file($unauth_list);
		foreach ((Array)$aFile as $sFile) {
			unset($aTmp);
			if (trim($sFile) == ""){
				continue;
			}
			$aTmp = explode(",", trim($sFile));
			$aClient["date"] = $aTmp[0];
			$aClient["model"] = $aTmp[1];
			$aClient["mac"] = $aTmp[2];
			$aClient["alias"] = $aTmp[3];
			$aClient["ip"] = $aTmp[4];
			$aClient["start_time"] = $aTmp[5];
			if ($sMac != false && strpos(trim($sFile), $sMac) !== false) {
				return $aClient;
			}
			$aList[] = $aClient;
		}
		return $aList;
	}
	
	function search_pid($program) {
		exec("ps aux | grep " . $program , $aRet);
		$allProcess = false;
		foreach ((array)$aRet as $sRet) {
			$aTab = split(" +", $sRet);
			if ($aTab[10] == "/PGRAM/php/bin/php"){
				$allProcess[] = $aTab[1];
			}
		}
		
		return ($program == "run_cms.php") ? (count($allProcess) > 1) : $allProcess;
	}
	
	function chk_server_mode() {
		$sConfFile = $this->conf_path . "conf.ini";
		if (file_exists($sConfFile)) {
			$aConf = parse_ini_file($sConfFile);
			if ($aConf["mode"] == "server"){ 
				return true;
			}
		}
		return false;
	}
	
	function check_cmsCtl($key){
		$client = $this->get_auth_info($key);
		if(stristr($client["mode"], "HiGuard SOHO") == false) {
			unset($aRet);
			$program = sprintf("'/PDATA/apache/Crontab/cmsCtl.php server %s %s'", $client["ip"], $key);
			exec("ps aux | grep ".$program , $aRet);
			$count=0;
			foreach ((array)$aRet as $sRet) {
				$aTab = split(" +", $sRet);
				if ($aTab[10] == "/PGRAM/php/bin/php") {
					$count++;
				}
			}
			return $count > 0 ? "1" : "0";
		}else{
			return "0";
		}
	}
	
	function fetchURL($url){
		$in = "";
		$url_parsed = parse_url($url);
		$host = $url_parsed["host"];
		$port = $url_parsed["port"];
		if ($port==0) $port = 80;
		$path = $url_parsed["path"];
		if ($url_parsed["query"] != "") $path .= "?".$url_parsed["query"];
		$out = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";
		$fp = @fsockopen($host, $port, $errno, $errstr, 5);
		if(!$fp) {
			return false;
		}else{
			fwrite($fp, $out);
			$body = false;
			while (!feof($fp)){
				$s = fgets($fp, 1024);
				if($body) $in .= $s;
				if($s=="\r\n") $body = true;
			}
			fclose($fp);
			return $in;
		}
	}
		
	function write_log($dst_file, $message) {
		if(!is_dir($this->data_path)) {
			mkdir($this->data_path);
		}
		$Specific = new Specific();
		if($Specific->getv("HDD") == 0) {
			$maxSize = 300 * 1024;
		}else{
			$maxSize = 1024 * 1024;
		}

		if(is_file($dst_file)){
			if(filesize($dst_file) > $maxSize) {
				exec("/bin/tail -n 100 {$dst_file} > {$dst_file}~");
				exec("/bin/mv {$dst_file}~ {$dst_file}");
			}
		}
		
		$sDate = date("Y-m-d H:i:s");
		$sMsg = $sDate . " " . $message . "\n";
		$fp = fopen($dst_file, "a");
		fwrite($fp, $sMsg);
		fclose($fp);
	}
	
	function check_status($key){
		$sPeriodFile = $this->conf_path . $key . "/period";
		if (file_exists($sPeriodFile)) {
			$aPeriod = file($sPeriodFile);
			$nPeriod = intval(trim($aPeriod[0]));
			if (time() - filemtime($sPeriodFile) < $nPeriod * 60 * 5 + 5) {
				return true;
			}
		}
		return false;
	}
	
	function get_all_groupname(){
		$client = $this->get_auth_info();
		$all_group = array();
		
		foreach((Array)$client as $val){
			$all_group[trim($val["group"])] = trim($val["group"]);
		}
		
		return $all_group;
	}
	
}
?>