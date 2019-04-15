<?
include_once("/PDATA/apache/conf/fw.ini");
include_once("/PDATA/apache/class/xBasic_class.php");

class auth_l7fw{
	function auth_main($sAccount , $sPassword , $sType, $sServerAddress = "")
	{
		switch ($sType)
		{
			case "pop3":
			$res = $this->auth_pop3($sAccount , $sPassword, $sServerAddress);
			break;
			case "ad":
			$res = $this->auth_ad($sAccount , $sPassword);
			break;
			case "mail_ad":
			$res = $this->mail_ad($sAccount , $sPassword);
			break;
			case "radius":
			$res = $this->auth_radius($sAccount , $sPassword, $sServerAddress);
			break;
		}
		return $res;
	}

	function auth_pop3($sAccount , $sPassword, $sServerAddress)
	{
		$logFile = "/CFH3/AUTH/auth_pop3";
		include_once("/PDATA/apache/class/pop3_auth_class.php");
		$pop3_auth = new pop3_auth_class;
		$pop3info = $pop3_auth->get_pop3info($sServerAddress);
		if(!$pop3info["pop3_address"]) return false;
		$sServer = $pop3info["pop3_address"];
		$sDomain = $pop3info["pop3_domain"];
		$sAccount_Domain = $sAccount . "@" . $sDomain;
		$sRet1 = $pop3_auth->pop3_auth_start($sServer , $sAccount_Domain , $sPassword);
		if($sRet1) {
			return true;
		} else {
			$sRet2 = $pop3_auth->pop3_auth_start($sServer , $sAccount , $sPassword);
			return $sRet2;
		}
	}

	function auth_ad($sAccount , $sPassword)
	{
		$logFile = "/CFH3/AUTH/auth_ad";
		$ldap_chkuser_cmd = "/PDATA/apache/Crontab/run_ldap_user.php check ".escapeshellarg($sAccount)." ".escapeshellarg($sPassword);
		exec($ldap_chkuser_cmd , $sRet);
		if($sRet[0] == "false") {
			$messagee = $sAccount . "," . $sPassword . "," . "Login FAIL";
			$this->auth_write_log($messagee , $logFile);
		}else{
			$messagee = $sAccount . "," . $sPassword . "," . "Login OK";
			$this->auth_write_log($messagee , $logFile);
		}

		if($sRet[0] == "false") return false;
		else return true;
	}

	function mail_ad($sAccount , $sPassword)
	{
		$logFile = "/CFH3/AUTH/auth_ad";
		$ldap_chkuser_cmd = "/PDATA/apache/Crontab/run_ldap_user.php check ".escapeshellarg($sAccount)." ".escapeshellarg($sPassword)." mail";
		exec($ldap_chkuser_cmd , $sRet);
		if($sRet[0] == "false") {
			$messagee = $sAccount . "," . $sPassword . "," . "Login FAIL";
			$this->auth_write_log($messagee , $logFile);
		}else{
			$messagee = $sAccount . "," . $sPassword . "," . "Login OK";
			$this->auth_write_log($messagee , $logFile);
		}

		if($sRet[0] == "false") return false;
		else return true;
	}

	function auth_radius($sAccount , $sPassword, $sServerAddress)
	{
		include_once("/PDATA/apache/class/radius_auth_class.php");
		$radius_auth = new radius_auth_class;
		$radius_auth->getConfig($sServerAddress);
		if($radius_auth->login($sAccount, $sPassword)) return true;
		else return false;
	}

	function auth_write_log($message , $logFile)
	{
		if(!file_exists("/CFH3/AUTH/")) mkdir("/CFH3/AUTH/");
		$fp = fopen($logFile, "a");
		fwrite($fp, sprintf("%s %s \n", date("Y-m-d H:i:s"), $message));
		fclose($fp);
	}
}
?>