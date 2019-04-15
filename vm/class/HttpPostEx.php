<?
/* DOC_NO:A2-090116-00038 */
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// Http POST 資訊交換物件
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
include_once("/PDATA/apache/conf/postfix_system.ini");
if(!class_exists("CDbshell")) {
	include_once("/PDATA/apache/class/CDbshell.php"); 
}
if(!class_exists("CSystem")) {
	include_once("/PDATA/apache/class/CSystem.php");
}
if(!is_object($sys))
	$sys = new CSystem;//HttpPostEx will use, must global
if(!is_object($db))
	$db = new CDbShell;//CSyslog will use, must global

class HttpPostEx
{
	var $aPostEx;
	//define all POST variable('default value', 'System param'): array("nBkTime" => array("00:00", "BACKUP_TIME"), ...);
	
	function load()
	{
		if(!is_array($this->aPostEx))
			return false;
		global $sys;
		foreach($this->aPostEx as $sKey => $aRow)
			$this->aPostEx[$sKey][0] = $sys->GetValu($aRow[1], $aRow[0]);
		return true;
	}

	function store()
	{
		if(!is_array($this->aPostEx))
			return false;
		global $sys, $HTTP_POST_VARS;
		foreach($this->aPostEx as $sKey => $aRow)
			$sys->SetValu($aRow[1], $this->aPostEx[$sKey][0] = $HTTP_POST_VARS[$sKey]);
		return true;
	}
}

function htmlHelp1($sHelpMsg)
{
	return "&nbsp;<img src='/images/icon/help.gif' border='0' class='help' onMouseOver=\"Tip(event,'$sHelpMsg')\" onmouseout=\"UnTip()\" />";
}

function htmlStopTip1($sTipMsg)
{
	return "&nbsp;<img src='/images/icon/stop.png' border='0' class='help' onMouseOver=\"Tip(event,'$sTipMsg')\" onmouseout=\"UnTip()\" />";
}

function inputCheckbox($sName, $sValue, $sPrompt, $bEnable = true, $sOnClick = "", $sOthers = "")
{//
	$sValue = $sValue ? 'checked': '';
	$bEnable = $bEnable ? '': "disabled='disabled'";
	if($sOnClick)$sOnClick = "onclick='$sOnClick'";
	if($sPrompt)$sPrompt = "<label for='$sName'>$sPrompt</label>";
	return "<input type='checkbox' name='$sName' id='$sName' value='1' $sValue $bEnable $sOnClick $sOthers />$sPrompt";
}

function inputRadio($sName, $nId, $sValue, $sPrompt, $bEnable = true, $sOnClick = "", $sOthers = "")
{
	$sCheck = ($sValue == $nId ? 'checked': '');
	$bEnable = $bEnable ? '': 'disabled';
	if($sOnClick)$sOnClick = "onclick='$sOnClick'";
	if($sPrompt)$sPrompt = "<label for='{$sName}_{$nId}'>$sPrompt</label>";
	return "<input type='radio' name='$sName' id='{$sName}_{$nId}' value='$nId' $sCheck $bEnable $sOnClick $sOthers />$sPrompt";
}

function inputText($sName, $sValue, $sPrompt = '', $nSize = 0, $nMaxLength = 0, $bEnable = true, $sAlign = "", $sOthers = "")
{
	$nSize = ($nSize > 0) ? "size='$nSize'" : '';
	$nMaxLength = ($nMaxLength > 0) ? "maxlength='$nMaxLength'" : '';
	if($sAlign)$sAlign = "style='text-align: $sAlign;'";
	$bEnable = $bEnable ? '': 'disabled';
	return "$sPrompt<input type='text' name='$sName' id='$sName' value='$sValue' $nSize $nMaxLength $sAlign $bEnable $sOthers />";
}

function inputPassword($sName, $sValue, $sPrompt = '', $nSize = 0, $nMaxLength = 0, $bEnable = true, $sOthers = "")
{
	if($nSize > 0)$nSize = "size='$nSize'";
	if($nMaxLength > 0)$nMaxLength = "maxlength='$nSize'";
	$bEnable = $bEnable ? '': 'disabled';
	return "$sPrompt<input type='password' name='$sName' id='$sName' value='$sValue' $nSize $nMaxLength $bEnable $sOthers />";
}

function inputTextArea($sValue, $sName = '', $nCol = 0, $nRow = 0, $sPrompt = '', $bEnable = true, $sOthers = "")
{
	if($sName)$sName = "name='$sName' id='$sName'";
	$nCol = ($nCol > 0) ? "cols='$nCol'" : "";
	$nRow = ($nRow > 0) ? "rows='$nRow'" : "";
	$bEnable = $bEnable ? '': 'disabled';
	return "$sPrompt<textarea $sName $nCol $nRow $bEnable $sOthers>$sValue</textarea>";
}

function inputButton($sName, $sValue, $bEnable = true, $sOnClick = "", $sOthers = "")
{
	$bEnable = $bEnable ? '': "disabled='disabled'";
	if($sOnClick)$sOnClick = "onclick='$sOnClick'";
	return "<input type='button' name='$sName' id='$sName' value='$sValue' $bEnable $sOnClick $sOthers />";
}

function inputSubmit($sValue, $sName = '', $bEnable = true, $sOthers = "")
{
	if($sName)$sName = "name='$sName' id='$sName'";
	$bEnable = $bEnable ? '': 'disabled';
	return "<input type='submit' value='$sValue' $sName $bEnable $sOthers />";
}

function inputSelect($sName, $sValue, $aOption, $bEnable = true, $sOthers = "")
{
	if($sName)$sName = "name='$sName' id='$sName'";
	$bEnable = $bEnable ? '': 'disabled';
	$sHtml = "<select $sName $bEnable $sOthers>";
	foreach($aOption as $sKey => $sOption)
	{
		if($sValue == $sKey)
			$sHtml .= "<option selected='selected' value='$sKey'>$sOption</option>";
		else
			$sHtml .= "<option value='$sKey'>$sOption</option>";
	}
	return "$sHtml</select>";
}


/*____________________________________________________________________________________
範例說明 I:
html-------------------------------
<script language=javascript>
function onCheckType(oForm)
{
	if(oForm.nCheck1.checked)
		bDisabled = false;
	else
		bDisabled = true;
	oForm.nCheck2.disabled = bDisabled;
	for(i=0; i<oForm.nRadioOne.length; i++)
		oForm.nRadioOne[i].disabled = bDisabled;
}
</script>

<br>
<FORM NAME=editset ENCTYPE=multipart/form-data METHOD=POST ACTION='http://127.0.0.1/test1.php' >
	<?= $aInputForm['sAdServerIP'] ?><br>
	<?= $aInputForm['sAdName'] ?><br>
	<?= $aInputForm['sAdPasswd'] ?><br>
	<?= $aInputForm['nCheck1'] . $aInputForm['nCheck2'] ?><br>
	<?= $aInputForm['nRadioOne'][1] . $aInputForm['nRadioOne'][2] . $aInputForm['nRadioOne'][3] ?><br>
	<?= $aInputForm['nRadioTwo'][1] . $aInputForm['nRadioTwo'][2] . $aInputForm['nRadioTwo'][3] ?><br>
	<?= $aInputForm['sRemark'] ?><br />
	<?= $aInputForm['sSelect'] ?><br />
	<?= $aInputForm['sSend'] ?><br>
</FORM>
-------------------------------html

php-------------------------------

$oPost = new HttpPostEx;
$oPost->aPostEx = array(
		'sAdName' => array("", "AD_LOGIN_NAME")
	, 'sAdServerIP' => array("", "AD_SERVER_IP")
	, 'sAdPasswd' => array("", "AD_LOGIN_PASSWD")
	, 'nCheck1' => array("1", "CHECK01")
	, 'nCheck2' => array("0", "CHECK02")
	, 'nRadioOne' => array("2", "RADIO_ONE")
	, 'nRadioTwo' => array("2", "RADIO_TWO")
	, 'sRemark' => array("remark", "REMARK_")
	, 'sSelect' => array("Blue", "SELECT_")
	);//End $oPost->aPostEx array
if($HTTP_POST_VARS)
{
	echo date('h:i:s') . "<br>";
	$oPost->store();
}
//$aInputForm = array();
$aInputForm['sAdServerIP'] = inputText('sAdServerIP', $oPost->aPostEx['sAdServerIP'][0], 'IP: ', 30, 20, true, 'right');
$aInputForm['sAdName'] = inputText('sAdName', $oPost->aPostEx['sAdName'][0], 'Login: ', 30);
$aInputForm['sAdPasswd'] = inputPassword('sAdPasswd', $oPost->aPostEx['sAdPasswd'][0], 'Password: ', 30);
$aInputForm['nCheck1'] = inputCheckbox('nCheck1', $oPost->aPostEx['nCheck1'][0], 'Check 1', true, "javascript:onCheckType(this.form, 1);");
$aInputForm['nCheck2'] = inputCheckbox('nCheck2', $oPost->aPostEx['nCheck2'][0], 'Check 2', $oPost->aPostEx['nCheck1'][0]);
$aInputForm['nRadioOne'][1] = inputRadio('nRadioOne', 1, $oPost->aPostEx['nRadioOne'][0], "choose 1", $oPost->aPostEx['nCheck1'][0]);
$aInputForm['nRadioOne'][2] = inputRadio('nRadioOne', 2, $oPost->aPostEx['nRadioOne'][0], "choose 2", $oPost->aPostEx['nCheck1'][0]);
$aInputForm['nRadioOne'][3] = inputRadio('nRadioOne', 3, $oPost->aPostEx['nRadioOne'][0], "choose 3", $oPost->aPostEx['nCheck1'][0]);

$aInputForm['nRadioTwo'][1] = inputRadio('nRadioTwo', 1, $oPost->aPostEx['nRadioTwo'][0], "choose II 1");
$aInputForm['nRadioTwo'][2] = inputRadio('nRadioTwo', 2, $oPost->aPostEx['nRadioTwo'][0], "choose II 2");
$aInputForm['nRadioTwo'][3] = inputRadio('nRadioTwo', 3, $oPost->aPostEx['nRadioTwo'][0], "choose II 3");

$aInputForm['sRemark'] = inputTextArea($oPost->aPostEx['sRemark'][0], 'sRemark', 30, 6, 'Remark:');
$aOption = array('Red' => 'Red', 'Green' => 'Green', 'Blue' => 'Blue');
$aInputForm['sSelect'] = inputSelect('sSelect', $oPost->aPostEx['sSelect'][0], $aOption);
	 //sSelect\'' => 'Blue'

$aInputForm['sSend'] = inputSubmit('Send');
-------------------------------php
範例說明 II:
input*()參數
$sName: POST交換的索引名稱 <string>
$sValue: 對 inputButton, inputSubmit 為按鍵上的顯示的文字, 對其他 input項的則為值 <string>,<number>
$sPrompt: input項的提示文字<string>
$mxCheck: inputCheckbox, inputRadio項的核選狀態 <bool><string> (內容: true / false / '' / 'checked')
$bEnable: (= true) input項是否有效 <bool><string> (內容: true / false / '' / 'disabled')
$sOnClick: (= "") <string>
$sOthers: (= "") input項其他額外設定 <string>
____________________________________________________________________________________*/

?>