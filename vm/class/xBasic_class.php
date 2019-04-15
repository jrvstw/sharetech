<?

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// xBasicClass ��¦����w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
class xBasicClass
{
	var $sFile4DebugLog;//--** dump all message to this file **-- /var/log/xxx.log
	var $bDebugMsg = false;//--** if true will display debug message in console **--

	function configFile($sCfgFileName, &$aOption, $bIsLoading)
	{//�@��]�w�ɪ��s��, ���\�Ǧ^ true �_�h false ($aOption �ΨӦs���Ҧ��]�w��ư}�C)
		//'#'�r���᪺���e�|�Q����; �������]�w�Ȥ��\�h��]�w, �������"����"�H�ťն}�Y�B����]�t'='
		if($bIsLoading)
		{//Loading
			$aOption = array();// **�`�N: ���J��T�ɭ�}�C����Ʒ|�Q�M��!!
			if(! ($fp = @fopen($sCfgFileName, 'r')) )
				return false;
			$sData = "";
			while(!feof($fp))
			{
				$sLine = fgets($fp);
				if(($i = strpos($sLine, '#')) !== false)
					$sLine = substr($sLine, 0, $i);
				$sInitChar = substr($sLine, 0, 1);
				$sLine = trim($sLine);
				if(strlen($sLine) < 1)
					continue;//�ťթε���
				if(strpos($sLine, '=') !== false)
				{
					if($sData)
					{
						list($name, $value) = explode('=', $sData, 2);
						$aOption[trim($name)] = xBasicClass::getConfigValue($value);
					}
					$sData = $sLine;
				}
				else if($sData && ($sInitChar == ' ' || $sInitChar == "\t"))
					$sData .= " $sLine";//�������]�w�Ȥ��\�h��]�w, �������"����"�H�ťն}�Y�B����]�t'='
			}
			if($sData)
			{
				list($name, $value) = explode('=', $sData, 2);
				$aOption[trim($name)] = xBasicClass::getConfigValue($value);
			}
			fclose($fp);
		}
		else
		{//Store
			if( !($fp = @fopen($sCfgFileName, 'w')) )
				return false;
			if(is_array($aOption))
				foreach($aOption as $sKey => $sValue)
					fwrite($fp, "$sKey = $sValue\n");
	  	fclose($fp);
		}
		return true;
	}

	function getConfigValue($sValue)
	{//-=Private=-
		$sValue = trim($sValue);
		$l = strlen($sValue) - 1;
		if('"' == $sValue[0] && '"' == $sValue[$l])
			$sValue = substr($sValue, 1, $l - 1);
		$l = strlen($sValue) - 1;
		if("'" == $sValue[0] && "'" == $sValue[$l])
			$sValue = substr($sValue, 1, $l - 1);
		return $sValue;
	}

	function debugMsg($msg, $iRet = 0)
	{//-=Private=-
		if($this->bDebugMsg)
			echo "$msg\n";
		if(!$this->sFile4DebugLog)
			return;
		$sDate = date("Y-m-d H:i:s");
		$sMsg = escapeshellarg("$sDate $msg" . ($iRet ? " ($iRet)": ""));
		exec("echo $sMsg >> $this->sFile4DebugLog");
	}
}
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// IntervalTime �T�w���j�ɶ�����w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$oInterval = new IntervalTime;//�إߩT�w���j�ɶ�����
//$oInterval->nSecondPerStep = 30;//���w�`���ɶ� 30��
//$oInterval->start();//�}�l�p��
//$oInterval->sleepNow();//�i�J��v
class IntervalTime
{
	var $nSecondPerStep = 30;
	var $nSec = 0;

	function start()
	{
		list($nMircoSec, $nSec) = explode(" ", microtime());
		$this->nSec = floatval($nSec) + floatval($nMircoSec);
	}
	function sleepNow()
	{
		$nSec = $this->nSecondPerStep - $this->nowSecond();
		if($nSec >= 1)
			sleep($nSec);
		$this->start();
	}

	function nowSecond()
	{//-=Private=-
		list($nMircoSec, $nSec) = explode(" ", microtime());
		$nSec = floatval($nSec) + floatval($nMircoSec);
		return ($nSec - $this->nSec);
	}
}

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// TimeStamp �ɶ��W�O����w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$oTimeStamp = new TimeStamp('/ram/myTime.Stamp');//�إ߮ɶ��W�O����
//if(!$oTimeStamp->start('checkDaemon.phs', '/ram/mailrec/script/checkDaemon.phs')) return false;//�ˬd�O�_�i�H����
//$oTimeStamp->update();//��s�ɶ��W�O
class TimeStamp extends xBasicClass
{
	var $nTimeout = 10;//minute(s)
	var $sFilename;

	function TimeStamp($sStampFilename, $nTimeoutMinute = 10)
	{
		$this->sFilename = $sStampFilename;
		$this->nTimeout = $nTimeoutMinute;
	}

	function start($sServiceName, $sServiceFullname)
	{
		$tTimeout = time() - $this->nTimeout * 60;
		if(!is_file($this->sFilename))
		{
			$this->update(true);
			return true;
		}
		if(filemtime($this->sFilename) > $tTimeout)
			return false;
		$this->killService($sServiceName, $sServiceFullname);
		$this->update(true);
		return true;
	}

	function update($bInitial = false)
	{
		if($bInitial)
		{
			$aData['InitialTime'] = date('Y-m-d H:i:s');
			$aData['nCount'] = 1;
		}
		else
		{
			$this->configFile($this->sFilename, $aData, true);
			$aData['nCount'] = $aData['nCount'] + 1;
		}
		return $this->configFile($this->sFilename, $aData, false);
	}

	function killService($sServiceName, $sServiceFullname)
	{
		$nPidLast = 0;
		$nMyPid = getmypid();
		while($nPid = TimeStamp::isServiceRun($sServiceName, $sServiceFullname))
		{//�O�ɥ���sstamp��, �R���ثe����u�@
			if($nPidLast == $nPid || $nMyPid == $nPid)
				break;
			exec("kill -9 $nPid");
			$nPidLast = $nPid;
		}
	}

	function isServiceRun($sGrepName, $sFindKey)
	{//check $sGrepName service is running, and return it PID(or 0). Sample: isServiceRun("java", "/hd2/jre/bin/java")//test java service
		echo "ps -ef|grep $sGrepName\n";
		exec("ps -ef|grep $sGrepName", $aRet, $nRet);
		if($nRet != 0)
			return 0;//ERROR when running ps command!
		foreach($aRet as $str)
		{
			if(strpos($str, $sFindKey) !== false)
			{
				$a = preg_split("/[\s]+/", $str);
				return intval($a[1]);//PID of service
			}
		}
		return 0;
	}
}

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// xPhpMailer �l�󪫥�w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$oMail = new xPhpMailer;//�إ߶l�󪫥�
//$oMail->From = 'root';
//$oMail->FromName = 'Notice for you';
//$oMail->recipient('user@mail.domain');
//$oMail->CharSet = "utf-8";
//$oMail->Subject = "=?UTF-8?B?" . base64_encode("Hello") . "?=";
//$oMail->IsMail();
//$oMail->IsHTML(true);
//$sHtmlBody = 'Hello world!<br>';
//$oMail->AddAttachment("/tmp/001.png");//����
///	���J����
//	$oMail->AddEmbeddedImage("/tmp/001.png", '000xid001');
//	$nCidOrFile = "cid:$nCidOrFile";
//	$sHtmlBody .= "<div align='center'><img src='$nCidOrFile' /></div>";
//$oMail->Body = $sHtmlBody;
//$oMail->Send();
/*
class xPhpMailer extends PHPMailer
{
	function recipient($asRecipients)
	{//�[�J����� ($asRecipients �p: 'user@mail.domain' �� 'user@mail.domain, joan@mail.domain;min@mail.domain')
		$aRecipient = split("[,;]", $asRecipients);
		$sRecipientAll = '';
		foreach($aRecipient as $sRecipient)
		{
			if($sRecipient = trim($sRecipient))
			{
				$this->AddAddress($sRecipient);
				$sRecipientAll .= "$sRecipient ";
			}
		}
		return $sRecipientAll;
	}
} */

?>