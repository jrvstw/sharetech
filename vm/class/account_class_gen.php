<?
Class account_class_gen{
  function submit_gen(){
  	include_once("/PDATA/apache/rootsession.php");
		$login_acc = sess_getVar("ulogin");
		$acc_class = get_accountclass($login_acc);
		if(($acc_class & 1)) return 1;
  }

}
?>
