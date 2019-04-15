<?
define("__ROOTDIR","/PDATA/apache/");
include(__ROOTDIR."class/class.admin.php");
$admin=new CAdmin;
$SYS_ROOTINI= "/PCONF/rootaccount/system_root.ini";
$SYS_PWINI= "/PCONF/rootaccount/system_passwd.ini";
chk_admin();
clear_Main_Session();
	
function chk_admin() {
	global $admin;
	$allroots = GetAllrootaccounts();
	for($i=0;$i<count($allroots);$i++){
		if($_SESSION['ulogin'] == $allroots[$i]) {
			if($_SESSION['upasswd'] != get_pw($allroots[$i])) {
				$admin->clearlogin();
				echo 'Close the window to logout';
				echo '<script language=javascript>window.close();</script>';
				exit;
			}
		}
	} 
}
	
function GetAllrootaccounts(){
	global $SYS_ROOTINI;
		
	$admin_u=file($SYS_ROOTINI);
	for($i=0;$i<count($admin_u);$i++){
		$u[]=rtrim($admin_u[$i]);
	}
	return $u;
}
	
function get_pw($account){
	global $SYS_PWINI;
  	
	$alllin=file($SYS_PWINI);
	for($i=0;$i<count($alllin);$i++){
		$adminaccount[$i]=split(":",trim($alllin[$i]));
	}

	for($i=0;$i<count($adminaccount);$i++){
		if(trim($account)==$adminaccount[$i][0]) $pw = $adminaccount[$i][1];
	}
	return $pw;
}

function clear_Main_Session() {
	unset($_SESSION['Main_More']);
	unset($_SESSION['eth0_arr']);
	unset($_SESSION['eth1_arr']);
	unset($_SESSION['eth2_arr']);
	unset($_SESSION['eth3_arr']);
}	
?>
