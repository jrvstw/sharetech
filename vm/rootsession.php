<?
include("/PDATA/apache/conf/fw.ini");

function sess_setVar($name, $vars) {
        global $$name;
        if(session_is_registered($name)) session_unregister($name);
        session_register($name);
        $_SESSION[$name] = $vars;
        //$$name = $vars;
    }

function sess_getVar($name,$default="") {

        global $$name;
        
        $return_str = FALSE;
        if(session_is_registered($name)) $return_str = $_SESSION[$name];
		else {
	  	  $return_str = $default;		  
		}
        return $return_str;
}

	function get_accountclass($account){
		include("/PDATA/apache/conf/fw.ini");
		$alllin=file($SYS_CLASSINI);
			for($i=0;$i<count($alllin);$i++){
			$adminclass[$i]=split(":",trim($alllin[$i]));
			}
			
			for($i=0;$i<count($adminclass);$i++){
				if(trim($account)==$adminclass[$i][0]) $class = $adminclass[$i][1];
			}
			return $class;
	}
	
	function get_privilege(){
		$login_iser = sess_getVar("ulogin");
		$privilege = get_accountclass($login_iser);

		if($privilege >= 2){
			return true;
		}else{
			return false;
		}
	}
	
	function get_login(){
		$login_iser = sess_getVar("ulogin");

		if($login_iser!=""){
			return true;
		}else{
			echo "Close the window to login again";
			//echo "<script language=javascript>window.close();</script>";
			return false;
		}
	}
    

if(!session_id()) {
	session_start();

	$sessfile = $SYS_SESSFILE.session_id();
	//var_dump($sessfile);
 	@chmod($sessfile,0666);
 }
?>