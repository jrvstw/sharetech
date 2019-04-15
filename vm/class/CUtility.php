<?
class CUtility {
  function escape_string($sStr) {
    $aSearch = array('"', '\'');
    $aReplace = array('&quot;', '\\\'');
    return str_replace($aSearch, $aReplace, $sStr);
  } 

  function decode_mime_string ($string) {
    if (eregi("=\?([A-Z,0-9,-]+)\?([A-Z,0-9,-]+)\?([\x01-\x7F]+)\?=", $string)) {
      $coded_strings = explode('=?', $string);
      $counter = 1;
      $string = $coded_strings[0];
      while ($counter < sizeof($coded_strings)) {
        $elements = explode('?', $coded_strings[$counter]);
        if (eregi("Q", $elements[1])) {
          $elements[2] = str_replace('_', ' ', $elements[2]);
          $elements[2] = eregi_replace("=([A-F,0-9]{2})", "%\\1", $elements[2]);
          $string .= @mb_convert_encoding(urldecode($elements[2]), "UTF-8", $elements[0]);
        } else {
          $elements[2] = str_replace('=', '', $elements[2]);
          if ($elements[2]) {
            $string .= @mb_convert_encoding(base64_decode($elements[2]), "UTF-8", $elements[0]);
          } 
        } 
        if (isset($elements[3]) && $elements[3] != '') {
          $elements[3] = ereg_replace("^=", '', $elements[3]);
          $string .= $elements[3];
        } 
        $string .= " ";
        $counter++;
      } 
    } else {
      $string = mb_convert_encoding($string, "UTF-8", "ISO-8859-1");
    } 
    return $string;
  } 

  function read_config($sFileName) {
    $aConfig = array();
    $fp = @fopen($sFileName, 'r');
    if ($fp) {
      while (!feof($fp)) {
        $sLine = trim(fgets($fp));
        if (strlen($sLine) > 0 && substr($sLine, 0, 1) != '#') {
          list($name, $value) = explode('=', $sLine);
          $name = trim($name);
          $value = trim($value);
          $aConfig[$name] = $value;
        } 
      } 
      fclose($fp);
    } 
    return $aConfig;
  } 

  function write_config($aConfig, $sFileName) {
    $fp = @fopen($sFileName, 'w');
    if ($fp) {
      foreach($aConfig as $sKey => $sValue) {
        fputs($fp, "$sKey = $sValue\n");
      } 
    } 
    fclose($fp);
  } 

  function removeDir($sDir) {
    if (is_dir($sDir)) {
      if ($dhDir = opendir($sDir)) {
        while (($sFile = readdir($dhDir)) !== false) {
          if ($sFile != '.' && $sFile != '..') {
            if (is_dir("$sDir/$sFile")) {
              CUtility::removeDir("$sDir/$sFile");
            } else {
              @unlink("$sDir/$sFile");
            } 
          } 
        } 
        closedir($dhDir);
      } 
      @rmdir($sDir);
    } 
  } 

  function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
  }
  
  // 從設定檔找出主機名稱，找不到則傳回null
  function getLocalHostName() {
    $sHOSTS = '/etc/hosts';
    $sNETWORK = '/etc/sysconfig/network';
    $sLocalHostName = null;
    // 從hosts設定檔找主機名稱
		$rFile = fopen($sHOSTS, 'r');
  	if($rFile) {
  		$nLineCounter = 0;
    	while (!feof($rFile)) {
      	$sLine = fgets($rFile);
      	$nLineCounter++;
      	if($nLineCounter == 2 && preg_match('/^([\d\.]+)\s(\S+)(?:\s(\S+))?$/', $sLine, $aMatches)) {
      		$sIp = $aMatches[1];
      		$sHost = $aMatches[2];
      		$sAlias = $aMatches[3];
      		$sLocalHostName = $sHost;
      		break;
      	}
    	}
    	fclose($rFile);
  	}
  	if(isset($sLocalHostName)) {
  		return $sLocalHostName;
  	}
  	// 從network設定檔找主機名稱
  	$rFile = fopen($sNETWORK, 'r');
  	if($rFile) {
    	while (!feof($rFile)) {
      	$sLine = fgets($rFile);
      	if(preg_match('/^HOSTNAME=(\S+)$/', $sLine, $aMatches)) {
      		$sLocalHostName = $aMatches[1];
      		break;
      	}
    	}
    	fclose($rFile);
  	}
  	if(isset($sLocalHostName)) {
  		return $sLocalHostName;
  	}
    return null;
  }
  
  function check_ip($sIp, $aCidrIp) {
		if (is_array($aCidrIp)) {
			foreach ($aCidrIp as $sIp_a) {
				list($net_addr, $net_mask) = explode("/", $sIp_a);
				if ($net_mask) {
					$ip_binary_string = sprintf("%032b", ip2long($sIp));
					$net_binary_string = sprintf("%032b", ip2long($net_addr));
					if ((substr($ip_binary_string, 0, $net_mask) === substr($net_binary_string, 0, $net_mask))) return true;
				} elseif ($sIp === $sIp_a) {
					return true;
				}
			}
			return false;
		}
	}
	
	function getSmtpLoginUser($sSendacc) {
		$sDefdomain = '/hd2/PCONF/postfix/defdomain';
    $sLoginUser = null;
    // 如果寄件者有經過SMTP認證,則執行以下動作
    if($sSendacc != null) {
    	$sLoginUser = strtolower($sSendacc); // 認證的帳號
      // 如果認證的帳號沒有帶domain,則自動補上預設的domain  
      if(strpos($sLoginUser, '@') === false) {
      	$aLine = file($sDefdomain);
        $sDomain = trim($aLine[0]);
        $sLoginUser = "{$sLoginUser}@{$sDomain}";
        $sLoginUser = strtolower($sLoginUser);
      }
    }
    return $sLoginUser;
	}
	
	function getFileSystemName() {
		$sFileSystemData = "/h3/fs.system";
    if(is_file($sFileSystemData)) {
    	$sName = implode("", file($sFileSystemData));
    	$sName = trim($sName);
    	return $sName;
    }
    return false;
	}
	
	function file_perms($file, $octal = false)
	{
    if(!file_exists($file)) return false;
    $perms = fileperms($file);
    $cut = $octal ? 2 : 3;
    return substr(decoct($perms), $cut);
	}
	
	function isIpv4($ip)  {
		if(!is_array($ip)) $aIp[] = $ip;
		$sPattern = "/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/";
		foreach($aIp as $sIp)
		{
			if(!preg_match($sPattern, $sIp)) return false;
		}
		return true;
	}
	
	function isIpv6($ip)  {
		if(!is_array($ip)) $aIp[] = $ip; else $aIp = $ip;
		foreach($aIp as $sIp)
		{
			if(strlen(trim($sIp)) == 0) return false;
			if(substr_count($sIp, ":") == 0 && substr_count($sIp, ".") > 0) return false; 
	  }
	  return true; 
	}
}

?>