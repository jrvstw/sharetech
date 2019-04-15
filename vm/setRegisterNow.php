<?
include_once("/PDATA/apache/conf/fw.ini");
include_once("$sIncludeClassPath/xBasic_class.php");
include_once("$sIncludeApachePath/cpage.php");
include_once("$sIncludeClassPath/regL7Key_class.php");

set_time_limit(0);
$nRetryMax = 5;//Max retry error.
$sCheckFile = '/tmp/register.rcd';
global $HTTP_GET_VARS;
$sRegCode = $HTTP_GET_VARS['regCode'];
$oL7Reg = new RegL7Key;


if(is_file($sCheckFile) && ($a = @file($sCheckFile)))
{
	$tfTime = time() - (60 * 10);//10 分鐘前
	if(filemtime($sCheckFile) < $tfTime)
	{
		@unlink($sCheckFile);//超過停用時間限制
		unset($a);
	}
}

if(is_file($oL7Reg->sDebugLogFile))
{//除錯模式，先建立 $sDebugLogFile (/tmp/regDebug.log)檔案
	$oL7Reg->clearDebugLog();
	$sSn = $oL7Reg->getDomSnSource();//CF SN.
	$sDomSn = $oL7Reg->getDomSn();//取得機器序號
	$sRegSn = $oL7Reg->getRegSn();//取得註冊序號
	$oL7Reg->debugMsg("$sRegCode (PostCode)\n$sSn\n$sDomSn\n$sRegSn");
}

if($a && $a[0] > $nRetryMax)
{
	$oL7Reg->debugMsg("Register Max=$nRetryMax");//除錯模式
	echo ($nRetryMax + 1);//超過嘗試次數
}
else if($oL7Reg->register($sRegCode))
{//註冊序號正確並註冊成功
	$oL7Reg->debugMsg("Register OK");//除錯模式
	@unlink($sCheckFile);
	echo "RegOK";
}
else
{
	if($a)
		$nRetry = $a[0] + 1;
	else
		$nRetry = 1;
	$oL7Reg->debugMsg("Register FAIL ($nRetry)!");//除錯模式
	exec("echo '$nRetry' > $sCheckFile");
	echo $nRetry;
}

?>