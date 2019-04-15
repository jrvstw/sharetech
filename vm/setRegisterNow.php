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
	$tfTime = time() - (60 * 10);//10 �����e
	if(filemtime($sCheckFile) < $tfTime)
	{
		@unlink($sCheckFile);//�W�L���ήɶ�����
		unset($a);
	}
}

if(is_file($oL7Reg->sDebugLogFile))
{//�����Ҧ��A���إ� $sDebugLogFile (/tmp/regDebug.log)�ɮ�
	$oL7Reg->clearDebugLog();
	$sSn = $oL7Reg->getDomSnSource();//CF SN.
	$sDomSn = $oL7Reg->getDomSn();//���o�����Ǹ�
	$sRegSn = $oL7Reg->getRegSn();//���o���U�Ǹ�
	$oL7Reg->debugMsg("$sRegCode (PostCode)\n$sSn\n$sDomSn\n$sRegSn");
}

if($a && $a[0] > $nRetryMax)
{
	$oL7Reg->debugMsg("Register Max=$nRetryMax");//�����Ҧ�
	echo ($nRetryMax + 1);//�W�L���զ���
}
else if($oL7Reg->register($sRegCode))
{//���U�Ǹ����T�õ��U���\
	$oL7Reg->debugMsg("Register OK");//�����Ҧ�
	@unlink($sCheckFile);
	echo "RegOK";
}
else
{
	if($a)
		$nRetry = $a[0] + 1;
	else
		$nRetry = 1;
	$oL7Reg->debugMsg("Register FAIL ($nRetry)!");//�����Ҧ�
	exec("echo '$nRetry' > $sCheckFile");
	echo $nRetry;
}

?>