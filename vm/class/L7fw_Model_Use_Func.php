<?
include_once("/PDATA/apache/conf/fw.ini");
include_once("$sIncludeClassPath/xBasic_class.php");
include_once("$sIncludeClassPath/CJavaScript.php");
include_once("$sIncludeApachePath/cpage.php");
include_once("$sIncludeApachePath/rootsession.php");
include_once("$sIncludeClassPath/syslog.php");

class L7fw_Model_Use_Func{
	var $sModelFile = '/CFH3/servermodel/servermodel';//型號設定檔
	var $sModelsUseFuncFile = '/PDATA/L7FWMODEL/L7fw_Model_Score.cfg';//型號功能檔
	var $sFunctionFile = '/PDATA/L7FWMODEL/L7fw_Function.cfg';//對應功能權限設定檔
	var $nModelScore = 0;

	function get_model() {
	 	include($this->sModelFile);
	  return SERVERMODEL;
	}

	function get_cfgFileNum($sHostModelName = '', $bDaemon = false){
		if(!is_file($this->sModelsUseFuncFile)) return 0;//Error
		if($this->nModelScore) return $this->nModelScore;
		if(!$bDaemon && !$this->isRegister()) return ($this->nModelScore = 0);

		if(!$sHostModelName)
			$sHostModelName = $this->get_model();
		xBasicClass::configFile($this->sModelsUseFuncFile, $aOption, true);
		if(isset($aOption[$sHostModelName]))
			return ($this->nModelScore = intval($aOption[$sHostModelName]));
		return $this->nModelScore;
	}

	function runValid($sFuncName)
	{
		if(!get_login())
			return false;
		$nAuthFlag = get_accountclass(sess_getVar("ulogin"));
		$aRow = $this->getFunctions($sFuncName);
		return ($nAuthFlag & $aRow[1]);
	}

	function getFunctions($sFuncName)
	{
		xBasicClass::configFile($this->sFunctionFile, $aOption, true);
		if(isset($aOption[$sFuncName]))
			return explode(',', $aOption[$sFuncName]);
		return array();
	}

	function isRegister()
	{//設備是否註冊
		global $sIncludeClassPath;
		include_once("$sIncludeClassPath/regL7Key_class.php");
		$oL7Reg = new RegL7Key;
		return $oL7Reg->isRegistered();
	}
}
?>
