<?
class CSystem{
	var $db_table="System";
	//===========================================================================================
	function SetValu($param,$valu) {
		
			
		if(!$this->IsNewParam($param)!=NULL) {
			$db=new CDbShell;
			$field=array("valu"); 
			$value=array($valu);
			$db->update($this->db_table,$field,$value,"param='$param'");
		
				
		} else {
			
			$this->NewValu($param,$valu);
		} 
		
	}
	//===========================================================================================
	function NewValu($param,$valu) {
		$db=new CDbShell;
		$field=array("param","valu");
		$value=array($param,$valu);
		$db->Insert($this->db_table,$field,$value);
		
	}
	
	function IsNewParam($param) {
		$db=new CDbShell;
		$db->Query("select * from $this->db_table where param='$param'");	
		return !($db->num_rows() > 0 );
	}
	
	function DelParam($param){
		$db=new CDbShell;
		$db->Query("DELETE FROM $this->db_table WHERE param='$param'");
	}
	
	//===========================================================================================
	function GetValu($param,$default="") {
		$db=new CDbShell;
		$db->Query("select * from $this->db_table where param='$param'");
		if($db->num_rows()) {
			$row=$db->fetch_array();
			return $row['valu'];
		} else {
			//$this->SetValu($param,$default);
			return $default;
			
		}
	}
//--------------------------------------------------------
	function setCrond($srh,$cndstr){
		$crofile = "/addpkg/conf/crontab";
		$tmpfile = tempnam("/ram/tmp","");
		$tmpfs = fopen($tmpfile,"w");
		$findbk = "";
		if(is_file($crofile)){
			$arrfile = file($crofile);
			for($i=0;$i<count($arrfile);$i++){
				if(trim($arrfile[$i])){
					if(strpos(trim($arrfile[$i]),$srh)){
						$findbk = "1";
						fputs($tmpfs,$cndstr."\n");
					}else fputs($tmpfs,$arrfile[$i]);						
				}
			}
			if(!$findbk) fputs($tmpfs,$cndstr."\n");
			fclose($tmpfs);
			@copy($tmpfile,$crofile);
			@unlink($tmpfile);
		}
		exec("/etc/init.d/crond stop");
		exec("sync");
		exec("/etc/init.d/crond start");
		exec("sync");
	}//end setCrond

	function delCrond($sDelSearch)
	{
		$crofile = "/addpkg/conf/crontab";
		$tmpfile = tempnam("/ram/tmp", "");
		if(!is_file($crofile) || !($tmpfs = fopen($tmpfile,"w")) )
			return;
		$arrfile = file($crofile);
		foreach($arrfile as $sLine)
		{
			if(strpos($sLine, $sDelSearch) === false)
				fputs($tmpfs, $sLine);						
		}
		fclose($tmpfs);
		@copy($tmpfile, $crofile);
		@unlink($tmpfile);
		exec("/etc/init.d/cron stop", $run_msg, $run_id);
		if($run_id != 0) {//try again
			exec("/etc/init.d/cron stop");
		}
		exec("sync");
	}//end setCrond
//----------------------------------------------------------
	function check_process($p_name){
		$cmd = '/bin/ps -ef |grep "'.$p_name.'"';
		exec($cmd,$msg);
		for($i=0;$i<count($msg);$i++){
			if(strpos($msg[$i], "/PGRAM/php/bin/php")) $all_process[] = $msg[$i];
		}
		if(count($all_process)>1){
			return true;
		}else{
			return false;
		}
	}	
	
}

?>