<?
include_once("/PDATA/apache/conf/fw.ini");
include_once("$sIncludeClassPath/xBasic_class.php");
include_once("$sIncludeApachePath/cpage.php");
include_once("$sIncludeApachePath/rootsession.php");
include_once("$sIncludeClassPath/L7fw_Model_Use_Func.php");

function getL7FwMenu($sLogin_acc)
{
	$sFunctioncfg = "/PDATA/L7FWMODEL/L7fw_Function.cfg";
	CPage::include_lang1("index.lang");  
	$l7fw_modeluse = new L7fw_Model_Use_Func;
	//$sHostModelName = $l7fw_modeluse->get_model();
	//$aOption['model'] = $sHostModelName;
	$aOption['modelnum'] = $l7fw_modeluse->get_cfgFileNum();//$sHostModelName);
	$acc_class = get_accountclass($sLogin_acc);
	
	$sMainItemFormat = "]\n, [null, '&nbsp;%s', null, [null, null, null, null]";
	$sSubItemFormat = ", [null, '&nbsp;%s', \"javascript:setURL('%s')\", null]";
	$sJSCookTree_menu = "var JSCookTree_menu = [ [' ', '&nbsp;" . FW . "', \"javascript:setURL('Main/MainWelcome.php')\", null";
	
	if($aOption['modelnum'])
	{//normal
		xBasicClass::configFile($sFunctioncfg, $aFunc, true);
		foreach((array)$aFunc as $skey => $sval)
		{
			if("_" != $skey[0])
				continue;
			$aval = explode(",", $sval);
			if($aOption['modelnum'] < $aval[0] || !($acc_class & $aval[1]))
				continue;//型號不支援的功能大項 或 登入管理者權限不足
			if($aOption['modelnum'] >= $aval[0])
			{
				$sJSCookTree_menu .= sprintf($sMainItemFormat, defined($aval[2])? constant($aval[2]): $aval[2]);
				$l = count($aval);
				for($i = 3; $i < $l; $i++)
				{
					if(!isset($aFunc[$aval[$i]]))
						continue;
					$a = explode(",", $aFunc[$aval[$i]]);
					if($aOption['modelnum'] < $a[0] || !($acc_class & $a[1]))
						continue;//型號不支援的功能大項 或 登入管理者權限不足
					$sJSCookTree_menu .= sprintf($sSubItemFormat, defined($a[2])? constant($a[2]): $a[2], $a[3]);
				}
			}	
		}
	}
	else
	{//Not register
		$sJSCookTree_menu .= "]\n, [null, '&nbsp;" . L7REGISTER . "', \"javascript:setURL('Configuration/Register.php')\", null";
	}
	$sJSCookTree_menu .= "]];\n";			
/*
	if($aOption['modelnum']) {
		xBasicClass::configFile($sFunctioncfg, $aFunc, true);
		foreach($aFunc as $skey => $sval){
			if("_" != $skey[0]) continue;
			$aval = explode(",",$sval);
			if($aOption['modelnum'] < $aval[0]) continue;
			if(!($acc_class & $aval[1])) continue; 
			if($aOption['modelnum'] >= $aval[0]) {
				if(defined($aval[2])) $sJSCookTree_menu .= "[null, '&nbsp;".sprintf('%s',constant($aval[2]))."', null,[null, null, null, null],";
				else $sJSCookTree_menu .= "[null, '&nbsp;".$aval[2]."', null,[null, null, null, null],";
				for($i=3;$i<count($aval);$i++) {
					$a = explode(",",$aFunc[$aval[$i]]);
					if($aOption['modelnum'] < $a[0]) continue;
					if($i > 3) $sJSCookTree_menu .= ",";
					//UR-710 在內容紀錄的郵件紀錄中功能的判斷
					if(strpos($a[3],"CMrtg_gwreport.php")){
						if($sHostModelName == "UR-710") $a[3] = "mailrec/CMrtg_psreport.php";
					}
				}
				if($skey == "_STATUS" || $acc_class == 4) {
					$sJSCookTree_menu .= "]";
				}else{ 
					$sJSCookTree_menu .= "],";
				}
			}	
		}
		$sJSCookTree_menu .= "];</script>";			
	}
*/
	return $sJSCookTree_menu;
}
/*
$aOption['nTimeout'] = 60;
$aOption['bOn'] = '1';
if(!configFile($sCfgFileName, $aOption, false))
	echo "$sCfgFileName\nFAIL to save config file!\n";
*/
?>