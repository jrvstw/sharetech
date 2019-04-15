<?
define("__ROOTDIR","/PDATA/apache/");
include_once(__ROOTDIR."class/syslog.php");
$syslog = new syslog;
$obj = new loging_login;
$obj->main();

Class loging_login{
	var $logtype=12;
	function loging_login() {
	
	}
	function main() {
		global $syslog;
		$title_parameters = array("Login successful");
		$syslog->log('SYSTEM_LOGIN', $this->logtype, SYSTEM_LOGIN, LOGIN_OK,$title_parameters);
	}
}
		
?>
