<?
include_once("/PDATA/apache/class/Comm.php");
include_once("/PDATA/apache/conf/postfix_system.ini");
include_once("$sIncludeClassPath/CDbshell.php");
include_once("/PDATA/apache/class/CSystem.php");
$SysCfg = new SystemConfig();
$AdminUser = new AdminUser();
$AdminUser->SysCfg = $SysCfg;
$db = new CDbshell();
$sys = new CSystem;
$chkport = $sys->GetValu("SpamList_IP_Port", "");
if($chkport != "") {
	if($_SERVER["SERVER_PORT"] == $chkport) {
		header("HTTP/1.0 404 Not Found");
		header("Status: 404 Not Found");
		echo "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n";
		echo "<HTML><HEAD>\n";
		echo "<TITLE>404 Not Found</TITLE>\n";
		echo "</HEAD><BODY>\n";
		echo "<H1>Not Found</H1>\n";
		echo "The requested URL " . $_SERVER["REDIRECT_URL"] . " was not found on this server.<P>\n";
		echo "<HR>\n";
		echo $_SERVER["SERVER_SIGNATURE"];
		echo "</BODY></HTML>\n";
		exit;
	}
}

$AdminUser->firstLogin();

include_once("$sIncludeClassPath/syslog.php");
$syslog = new syslog;
$logtype=12;

if(isset($_GET["logout"]))
{//登出
	$title_parameters = array("Logout Successful");
	$syslog->log('SYSTEM_LOGOUT', $logtype, SYSTEM_LOGOUT, LOGOUT_OK,$title_parameters);
	$AdminUser->logout();
}
if($AdminUser->loginOK == 1)
{
	$login_failed_ip_file = "/CFH3/Login_failed/loginfiled_".$_SESSION["uloginip"];
	if(file_exists($login_failed_ip_file)){
		unlink($login_failed_ip_file);
	}
	
	$chk_overtime = check_login_overtime();
	if($chk_overtime == false) {
		$title_parameters = array("Login Successful");
		$syslog->log('SYSTEM_LOGIN', $logtype, SYSTEM_LOGIN, LOGIN_OK,$title_parameters);
	}
}
else
{
	check_login_failed($_SERVER["REMOTE_ADDR"]);
	
	$title_parameters = array("Login False");
	$syslog->log('SYSTEM_LOGIN', $logtype, SYSTEM_LOGIN, LOGIN_OK,$title_parameters);
	unset($_SESSION["uloginip"]);
	unset($_SESSION["logintime"]);
	unset($_SESSION["ulogin"]);
	unset($_SESSION["upasswd"]);
	header("Location: index.php");
	exit;
}

$Specific = new Specific();
$SVar = $Specific->getAll();

$tpl = "index_".$SVar["THEME"].".html";
$Layout = new Layout($tpl);
$LVar = $Layout->getAll();

$fwmodel = $SVar["SERVER_MODEL"];
$fwversion = $SVar["SERVER_VERSION"];
/*
$check_enable = parse_ini_file("/PDATA/sysnotify/config", true);
if($check_enable["No_Software_Upgrade"]["enable"] == 1){
	$fwlanguage = $Layout->language;
	
	//not encode post
	$check_url = "http://www.sharetech.com.tw/release.php?model=".$fwmodel."&version=".$fwversion."&language=".$fwlanguage;
	
	$return_code = @fetchURL($check_url);
	if($return_code != ""){
		$check_update = true;
	}
}
*/
include_once("$sIncludeClassPath/Menu.php");
include_once("$sIncludeClassPath/regL7Key_class.php");
$oL7Reg = new RegL7Key;

if($oL7Reg->isRegistered()) {
	$Menus = produceMenu($SVar, $LVar, $_SESSION["ulogin"]);
	$mainPage = "/Program/Main/MainWelcome.php";
} else {
	//尚未註冊
	$Menus = array();
	$Menus[] = array(
		0	=> $LVar["L7REGISTER"],
		1 => array(
			array($LVar["L7REGISTER"], "Configuration/Register.php", 1)
	));
	$mainPage = "/Program/Configuration/Register.php";
}

//輸出畫面
include($Layout->html);


/**
 * Customize Function
 */
function get_main_title()
{
	$filename = "/PDATA/UITITLE/main_title";
	if(file_exists($filename))
		return file_get_contents($filename);
	else
		return "";
}
function get_browser_title()
{
	$filename = "/PDATA/UITITLE/browser_title";
	if(file_exists($filename))
		return file_get_contents($filename);
	else
		return "";
}

function encrypt($data)
{
	$algorithm = MCRYPT_BLOWFISH;
	$mode = MCRYPT_MODE_CBC;
	
	$key = genKey();
	$iv = mcrypt_create_iv(mcrypt_get_iv_size($algorithm, $mode), MCRYPT_DEV_URANDOM);
	$ciper = base64_encode(mcrypt_encrypt($algorithm, $key, $data, $mode, $iv));

	return $key.base64_encode($iv).$ciper;
}

function decrypt($data)
{
	$algorithm = MCRYPT_BLOWFISH;
	$mode = MCRYPT_MODE_CBC;

	$key = substr($data, 0, 16);
	$iv = base64_decode(substr($data, 16, 12));
	$ciper = substr($data, 28);	
	
	return mcrypt_decrypt($algorithm, $key, base64_decode($ciper), $mode, $iv);
}

function genKey()
{	
	$pool = "#ZXCVBNMASDFGHJKLQWERTYUIOPzxcvbnmasdfghjklqwertyuiop0123456789";
	$max = strlen($pool) - 1;
	for($i = 0; $i < 16; $i++) {
		$idx = mt_rand(1, $max);
		$result.= $pool[$idx];		
	}

	return $result;
}

function fetchURL($url){
	$url_parsed = parse_url($url);
	$host = $url_parsed["host"];
	$port = $url_parsed["port"];
	if ($port==0) $port = 80;
	$path = $url_parsed["path"];
	if ($url_parsed["query"] != "") $path .= "?".$url_parsed["query"];
	$out = "GET $path HTTP/1.0\r\nHost: $host\r\n\r\n";
	$fp = fsockopen($host, $port, $errno, $errstr, 5);
	if(!$fp) return false;
	else{
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

function check_login_overtime() {
	global $db;
	if(!is_object($db)) {
		$db = new CDbshell;
	}
	if($db->db != "postfix") $db->db = "postfix";
	
	$sql = "show processlist";
	$res = $db->query($sql);
	$overtime = false;
	while($aRow = $db->fetch_array($res)){
		if((stristr($aRow["Info"], "syslogn") == true) || (stristr($aRow["Info"], "log_title_parameter") == true)) {
			if($aRow["Time"] > 60) {
				$overtime = true;
			}
		}
	}
	return $overtime;
}

function check_login_failed($ip){
	if(!file_exists("/CFH3/Login_failed/")){
		mkdir("/CFH3/Login_failed/");
	}
	
	$login_failed_conf_file = "/PDATA/UITITLE/login_failed_config";
	$block_times = 0;
	$block_minute = 0;
	
	if(file_exists($login_failed_conf_file)){
		$login_conf = parse_ini_file($login_failed_conf_file);
		$block_times = $login_conf["temporary_block_times"];
		$block_minute = $login_conf["temporary_block_remove"] * 60;
	}
	
	$tmp = time() . ":::" . $block_minute . ":::" . $block_times;
	$login_failed_ip_file = "/CFH3/Login_failed/loginfiled_".$ip;
	
	if($block_times == 0){
		if(is_file($login_failed_ip_file)){
			unlink($login_failed_ip_file);
		}
	}else{
		exec("/bin/echo " . $tmp . " >> " . $login_failed_ip_file);
	}
}
?>