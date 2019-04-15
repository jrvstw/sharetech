<?
define("__ROOTDIR","/PDATA/apache/");
include_once(__ROOTDIR."class/syslog.php");
$syslog = new syslog;
$obj = new login_success;
$obj->main();

Class login_success{
	var $logtype=12;
	function login_success() {
	
	}
	function main() {
		global $syslog;
		
		$title_parameters = array("Login Successful");
		$syslog->log('SYSTEM_LOGIN', $this->logtype, SYSTEM_LOGIN, LOGIN_OK,$title_parameters);
	}
}
		
?>
