<?
define("__ROOTDIR","/PDATA/apache/");
include_once(__ROOTDIR."class/syslog.php");
$syslog = new syslog;
$obj = new login_false;
$obj->main();

Class login_false{
	var $logtype=12;
	function login_false() {
	
	}
	function main() {
		global $syslog;
		$title_parameters = array("Login False");
		$syslog->log('SYSTEM_LOGIN', $this->logtype, SYSTEM_LOGIN, LOGIN_OK,$title_parameters);
	}
}
?>
