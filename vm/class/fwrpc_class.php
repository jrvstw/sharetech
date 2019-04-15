<?
include_once("/PDATA/apache/class/code_map.php");
include_once("/PDATA/apache/class/Comm.php");
class fwrpc {
	var $rSocket = 0;
	var $sSocketPath = "/tmp/fwrpc.sock";
	var $nErrNo = 0;
	var $sErrStr = "";
	var $nTimeout = 5;
	var $read_timeout = 30;
	var $nId = 0;
	var $sDstIP = "";
	var $sDstPort = "";
	var $bTestMode = false;
	
	var $sHead = "00";
	var $sReturn = "01";
	var $sEnd = "00";
	
	var $sPackage = "";
	var $debug_mode = false;
	
	function fwrpc($debug = false) {
		$this->bTestMode = $debug;
		if (!$this->bTestMode) {
			$this->rSocket = fsockopen($this->sSocketPath, 0, $this->nErrNo, $this->sErrStr, $this->nTimeout);
			if ($this->nErrNo){
				echo "Error = {$this->nErrNo} , {$this->sErrStr}\n";
			}
		}
	}
	
	function fwrpcClose() {
		if ($this->rSocket) {
			fclose($this->rSocket);
		}
	}
		
	function set_Ap_Disconnect() {
		$this->nId++;
		$ip = $this->pkgParse(FWRPC_IP, explode(".", $this->sDstIP));
		$this->sPackage = $ip. "42";
		$this->pkgSend();
	}
	/***********************************
	 *                  Customize Functions
	 ***********************************/
	
	function setApName($sName, $mod = "") {
		global $aCode;
		$this->nId++;
		
		$name = $this->pkgParse(FWRPC_SET_SRC_AP, array($sName), true);
		if($mod == "client") {
			$name .= $this->pkgParse(FWRPC_AUTHENTICATION_ACCOUNT, array("cms"), true);
			$name .= $this->pkgParse(FWRPC_AUTHENTICATION_PASSWORD, array("5uj8w28wdbdz"), true);
		}
		$name .= FWRPC_DIFF_TIME . str_pad($this->int2Hex("0") , 8 , "0"); //diff time = 0
		$name .= FWRPC_TIMEOUT_SET . str_pad($this->int2Hex("30") , 8 , "0"); //set timeout = 30
		$this->sPackage = $this->pkgParse(FWRPC_SET, array($name));
		
		$this->pkgSend();
	}
	
	function set_timeout($time_out){
		$this->nId++;
		$name = FWRPC_TIMEOUT_SET . str_pad($this->int2Hex($time_out) , 8 , "0"); //set timeout = 30
		$this->sPackage = $this->pkgParse(FWRPC_SET, array($name));
		$this->pkgSend();
	}
	
	function dataTransfer($aAp, $aData) {
		$this->nId++;
		$ip = $this->pkgParse(FWRPC_IP, explode(".", $this->sDstIP));
		$ap = $this->pkgParse(FWRPC_AP_NAME, $aAp, true);
		$data = $this->pkgParse(FWRPC_DATA, $aData, true);
		$port = ($this->sDstPort == "") ? FWRPC_PORT.$this->int2Hex("40000") : trim(FWRPC_PORT.implode("",(Array)$this->sDstPort));
		
		$this->sPackage = $ip. $port. $ap. $data;
		$this->pkgSend(); 
	}
	
	function fileTransfer($aSrcFile, $aDstDir) {
		$this->nId++;
		$ip = $this->pkgParse(FWRPC_IP, explode(".", $this->sDstIP));
		$src = $this->pkgParse(FWRPC_FILE, $aSrcFile, true);
		$dst = $this->pkgParse(FWRPC_TARGET_PATH, $aDstDir, true);
		$ufile = $this->pkgParse(FWRPC_UPLOAD_FILES, array($src, $dst, "3A3B"));// 3a keep file, 3b execute
		$port = ($this->sDstPort == "") ? FWRPC_PORT.$this->int2Hex("40000") : trim(FWRPC_PORT.implode("",(Array)$this->sDstPort));
		$this->sPackage = $ip .$port . $ufile;
		$this->pkgSend();
	}
	
	/******************************************
	 *             Package Transmit Functions *
	 ******************************************/
	
	function pkgParse($sCode, $aData, $bSub = false) {
		global $aCode;
		$aPkgRow = array();
		$aPkgRow["code"] = $sCode;
		switch ($aCode[$sCode]) {
			case "":
				break;
			case "uchar":
				break;
			case "ushort":
				foreach ((array)$aData as $sData) {
					$aPkgRow["data"][] = $this->int2Hex($sData);
				}
				$aPkgRow["type"] = "ushort";
				break;
			case "ulong":
				foreach ((array)$aData as $sData) {
					$aPkgRow["data"][] = $this->int2Hex($sData);
				}
				$aPkgRow["type"] = "ulong";
				break;
			case "float":
				break;
			case "double":
				break;
			case "uwlong":
				break;
			case "S":
				$aPkgRow["length"] = 0;
				foreach ((array)$aData as $sData) {
					$aPkgRow["length"] += $this->genDataLength($sData, $bSub);
					$aPkgRow["data"][] = $this->string2Hex($sData, $bSub);
				}
				$aPkgRow["type"] = "S";
				break;
			case "B":
				$aPkgRow["length"] = 0;
				foreach ((array)$aData as $sData) {
					$aPkgRow["length"] += $this->genDataLength($sData, $bSub);
					$aPkgRow["data"][] = $this->string2Hex($sData, $bSub);
				}
				$aPkgRow["type"] = "B";
				break;
			case "VD":
				$aPkgRow["length"] = 0;
				foreach ((array)$aData as $sData) {
					$aPkgRow["length"] += $this->genDataLength($sData, $bSub);
					$aPkgRow["data"][] = $this->string2Hex($sData, $bSub);
				}
				$aPkgRow["type"] = "VD";
				break;
			case "SVD":
				break;
			case "R":
				break;
		}
		$sPackage = $this->pkgCreate($aPkgRow);
		return $sPackage;
	}

	function pkgCreate($aRow) {
		$sPkgCode = $aRow["code"];
		switch ($aRow["type"]) {
			case "ulong":
				$sPkgData = str_pad(implode("", $aRow["data"]), 8, "0");
				$sPkg = $sPkgCode . $sPkgData;
				break;
			case "S":
				$sPkgHead = $this->pkgGenLength($aRow["length"]);
				$sPkgData = implode("", $aRow["data"]);
				$sPkg = $sPkgCode . $sPkgHead . $sPkgData;
				break;
			case "B":
				$sPkgHead = $this->pkgGenLength($aRow["length"], 2);
				$sPkgData = implode("", $aRow["data"]);
				$sPkg = $sPkgCode . $sPkgHead . $sPkgData;
				break;
			case "VD":
				$sPkgHead = $this->genVdLength($aRow["length"]);
				$sPkgData = implode("", $aRow["data"]);
				$sPkg = $sPkgCode . $sPkgHead . $sPkgData;
				break;
		}
		return $sPkg;
	}
	
	function pkgGenLength($length, $size = 0) {
		if (is_int($length)) {
			$hex = dechex($length);
			if ((strlen($hex) % 2) !== 0) $hex = "0" . $hex;
			for ($i = 0; $i < strlen($hex); $i += 2) {
				$pkg[] = substr($hex, $i, 2);
			}
			krsort($pkg);
			if ($size !== 0 && count($pkg) < $size) {
				for ($i = 0; $i < ($size - count($pkg)); $i++) {
					$pkg[] = "00";
				}
			}
			return implode("", $pkg);
		} else {
			return false;
		}
	}
	
	function genVdLength($length) {
		if (is_int($length)) {
			if ($length < pow(2,8)) return ("01" . $this->pkgGenLength($length));
			else if ($length >= pow(2, 8) && $length < pow(2, 16)) return ("02" . $this->pkgGenLength($length));
			else if ($length >= pow(2, 16) && $length < pow(2, 64)) return ("04" . $this->pkgGenLength($length));
			else if ($length >= pow(2, 64)) return ("08" . $this->pkgGenLength($length));
		} else {
			return false;
		}
	}
	
	function genDataLength($sStr, $bSub = false) {
		if ($bSub) {
			return strlen($sStr);
		} else {
			return (strlen($sStr) / 2);
		}
	}
	
	function pkgSend() {
		if ($this->sPackage === "") return false;
		$id = $this->int2string($this->nId);
		$data = $this->sHead . $id . $this->sPackage . $this->sEnd;
		if($this->debug_mode == true){
			$this->write_log("send:".$data);
		}
		if (!$this->bTestMode) {
			
			fwrite($this->rSocket, pack("H*", $data));
			
		} else {
			var_dump($data);
		}
	}
	
	/***********************************
	 *             Package Receive Functions
	 ***********************************/
	 function pkgRead() {
	 	stream_set_timeout($this->rSocket, $this->nTimeout);
	 	$time = time();
		while (true) {
			$bStr = fread($this->rSocket, 10240);
			$hStr = bin2hex($bStr);
			if ($hStr != "") {
				if($this->debug_mode == true){
					$this->write_log("Read:".$hStr);
				}
				$aData = $this->pkgDepart($hStr);
				if ($this->bTestMode) $this->dataAnalysis($aData);
				return $aData;
			}
			if(time()-$time >= $this->read_timeout) {
				break;
			}
		}
	}
	 
	function pkgDepart($bPackage) {
		$sPackage = $bPackage;
		$aPkg = $this->stringSplit($sPackage, 2);
		$aInfo["type"] = $aPkg[0];
		$aInfo["id"] = $aPkg[1];
		$aInfo["pkg"] = $this->pkgSolve(array_slice($aPkg, 2, -1));
		return $aInfo;
	}
	
	function pkgSolve($aPkg) {
		global $aCode;
		$nPtr = 0;
		$aDepart = array();
		
		while ($nPtr < count($aPkg)) {
			$sCode = $aPkg[$nPtr];
			if (!isset($aCode[$sCode])) {
				return $aPkg;
			}
			$nPtr++;
			$nLength = 0;
		
			switch ($aCode[$sCode]) {
				case "":
					return $aPkg;
					break;
				case "uchar":
					$nLength = 1;
					break;
				case "ushort":
					$nLength = 2;
					break;
				case "ulong":
					$nLength = 4;
					break;
				case "float":
					$nLength = 4;
					break;
				case "double":
					$nLength = 8;
					break;
				case "uwlong":
					$nLength = 8;
					break;
				case "S":
					$sLengthCount = $aPkg[$nPtr];
					$nLength = hexdec($sLengthCount);
					$nPtr++;
					if (!$aPkg[$nPtr]) return $aPkg;
					break;
				case "B":
					$sLengthCountLow = $aPkg[$nPtr];
					$nPtr++;
					if (!$aPkg[$nPtr]) return $aPkg;
					$nLengthLow = hexdec($sLengthCountLow);
					$sLengthCountHigh = $aPkg[$nPtr];
					$nPtr++;
					if (!$aPkg[$nPtr]) return $aPkg;
					$nLengthHigh = hexdec($sLengthCountHigh);
					$nLength = $nLengthHigh * 256 + $nLengthLow;
					break;
				case "VD":
					$sByteCount = $aPkg[$nPtr];
					$nPtr++;
					if (!$aPkg[$nPtr]) return $aPkg;
					for ($i = 0; $i < hexdec($sByteCount); $i++) {
						$nLength += hexdec($aPkg[$nPtr]) * pow(256, $i);
						$nPtr++;
						if (!$aPkg[$nPtr]) return $aPkg;
					}
					break;
				case "SVD":
					break;
				case "R":
					$nLength = 1;
					break;
			}
			if ($nLength  > count($aPkg) || $nLength == 0) {
				return $aPkg;
			}
			
			unset($aTmp);
			for ($i = 0; $i < $nLength; $i++) {
				$aTmp[] = $aPkg[$nPtr + $i];
			}
			
			$aRow["cmd"] = $sCode;
			if($sCode == "07"){
				$aRow["data"] = $aTmp;
			}else{
				$aRow["data"] = $this->pkgSolve($aTmp);
			}
			$aDepart[] = $aRow;
			$nPtr += $nLength;
			
		}
		return $aDepart;
	}
	
	function pkgReadData($aData) {
		global $aRevCode;
		$aRetData = array();
		foreach ((array)$aData["pkg"] as $aPkg) {
			switch ($aPkg["cmd"]) {
				case FWRPC_SRC_AP: 
					$aRetData["src_ap"] = $this->pkgUnpackStr($aPkg["data"]);
					break;
				case FWRPC_SRC_IP: 
					$aRetData["src_ip"] = $this->pkgUnpackIP($aPkg["data"]);
					break;
				case FWRPC_SRC_PORT: 
					$aRetData["src_port"] = $aPkg["data"];
					break;
				case FWRPC_DATA: 
					$aRetData["data"] = $this->pkgUnpackStr($aPkg["data"]);
					break;
				case FWRPC_ACCEPT: 
				case FWRPC_COMPLETE: 
					$aRetData["status"] = $aRevCode[$aPkg["data"][0]];
					break;
				case FWRPC_CONTINUEFWRPC: 
					$aRetData["status"] = $aRevCode[FWRPC_CONTINUEFWRPC];
					break;
				case FWRPC_CONTINUE_AP: 
				case FWRPC_FILE_ACCEPT: 
				case FWRPC_INVALID_COMMAND: 
				case FWRPC_ERROR_MSG: 
				case FWRPC_LIST_DATA: 
					break;
			}
		}
		return $aRetData;
	}
	
	function pkgUnpackStr($aPkg) {
		$str = "";
		foreach((Array)$aPkg as $sPkg) {
			$str  .= chr(hexdec($sPkg));
		}
		return $str;
	}
	
	function pkgUnpackInt($aPkg) {
		$nInt = 0;
		foreach ((Array)$aPkg as $key => $sPkg) {
			$nInt += hexdec($sPkg) * pow(256, count($aPkg) - $key - 1);
		}
		return $nInt;
	}
	
	function pkgUnpackIP($aPkg) {
		return long2ip($this->pkgUnpackInt($aPkg));
	}
	
	function pkgUnpackPort($aPkg) {
		foreach ((Array)$aPkg as $sPkg) {
			$nPort .= hexdec($sPkg);
		}
		return $nPort;
	}
	
	function stringSplit($string, $length = 1) {
		if ($length <= 0) {
			return false;
		}
		$splitted  = array();
		$str_length = strlen($string);
		$i = 0;
		if ($length == 1) {
			while ($str_length--) {
				$splitted[$i] = $string[$i++];
			}
		} else {
			$j = $i;
			while ($str_length > 0) {
				$splitted[$j++] = substr($string, $i, $length);
				$str_length -= $length;
				$i += $length;
			}
		}
		return $splitted;
	}
	
	/***********************************
	 *                Data Convert Functions
	 ***********************************/
	
	function string2Hex($sStr, $bSub = false) {
		if (!$bSub) return $sStr;
		else {
			$hex = "";
			for ($i = 0; $i < strlen($sStr); $i++){
				$tmp = dechex(ord($sStr[$i]));
				if (strlen($tmp) == 1) $tmp = "0" . $tmp;
				$hex .= $tmp;
			}
			return $hex;
		}
	}
	
	function int2Hex($nInt) {
		$hHex = dechex($nInt);
		if ((strlen($hHex) % 2) == 0) return $hHex;
		else  return ("0" . (string)$hHex);
	}
	
	function hex_2_string($hex) {
	    $string = "";
	    for ($i = 0; $i < strlen($hex) - 1; $i += 2){
	        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
	    }
	    return $string;
	}
	
	function int2string($int) {
		if ($int > 255) $int = $int % 256;
		$hex = dechex($int);
		if (strlen($hex) == 1) $value = "0" . (string)$hex;
		else $value = (string)$hex;
		return $value;
	}
	
	/***********************************
	 *                Data Debug Functions
	 ***********************************/
	 
	 function dataAnalysis($aData) {
	 	global $aRevCode;
	 	$bRecursive = false;
	 	$sType = ($aData["type"] == "00") ? "Request" : "Return";
	 	$this->drawLine();
	 	echo "type : " . $aData["type"] . " (" . $sType . ")";
	 	echo "\tid : " . $aData["id"] . "\n";
	 	$this->drawLine();
	 	
	 	foreach ((array)$aData["pkg"] as $key => $aPack) {
	 		echo ($key + 1);
	 		echo "\tcommand : " . $aPack["cmd"] . " (" . $aRevCode[$aPack["cmd"]] . ")\n";
	 		foreach ((array)$aPack["data"] as $aCheck) {
	 			if (is_array($aCheck)) $bRecursive = true;
	 		}
	 		if (!$bRecursive) {
	 			echo "\tlength : " . count($aPack["data"]);
	 			echo "\tdata : " . implode(" ", $aPack["data"]) . "\n";
	 		} else {
	 			$sInner = "";
	 			$nInnerCount = 0;
	 			foreach ((array)$aPack["data"] as $ikey => $sPack) {
	 				$sInner .= ($key + 1) . "-" . ($ikey + 1);
	 				$sInner .= "\tcommand : " . $sPack["cmd"] . " (" . $aRevCode[$sPack["cmd"]] . ")\n";
	 				$sInner .= "\tlength : " . count($sPack["data"]);
	 				$sInner .=  "\tdata : " . implode(" ", $sPack["data"]) . "\n";
	 				$nInnerCount += count($sPack["data"]);
	 			}
	 			echo "\tLength : " . $nInnerCount . "\n";
	 			echo $sInner;
	 		}
	 	}
	 	$this->drawLine();
	}
	
	function drawLine() {
		for ($i = 0; $i < 60; $i++) echo "-";
		echo "\n";
	}
	
	function write_log($message) {
		$Specific = new Specific();
		$dst_file = "/HDD/CMS/cms_debug.log";
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
}
?>