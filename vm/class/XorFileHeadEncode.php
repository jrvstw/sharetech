<?
/* DOC_NO:A2-080826-00006 */

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// XorFileHeadEncode �ɮץ[�ѱK�w�q����
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
class XorFileHeadEncode
{
	var $name = 'name';
	var $size = 'size';
	var $md5 = 'md5';
	var $aFiles = array();

	function specChar(&$sbBuf, $sSpecChars)
	{//�N $sBuf�r��P $sSpecChars �r�갵 XOR �B�z
		$l = strlen($sbBuf);
		$lmax = strlen($sSpecChars);
		for($i = $n = 0; $i < $l; $i++)
			$sbBuf[$i] = $sbBuf[$i] ^ substr($sSpecChars, $i % $lmax, 1);
	}
	function md5Char(&$sbBuf, $sMd5)
	{//�N $sBuf�r��P $sMd5 md5�X�r��('1a2b3c...')�� XOR �B�z
		$l = strlen($sbBuf);
		$lmax = strlen($sMd5) / 2;
		for($i = $n = 0; $i < $l; $i++)
			$sbBuf[$i] = $sbBuf[$i] ^ chr(hexdec(substr($sMd5, $i * 2 % $lmax, 2)));
	}
	function encFile($sFilename, &$sMd5, $bIsEncrypt)
	{//�[�ѱK�ɮ�, �̤j�s�X = ($nSize1 + $nSize2) * $nRepeatTime = 391 bytes, �ɮ׶W�L�����h�����ܧ�
		if($bIsEncrypt)
		{//encrypt file
			$sMd5 = md5_file($sFilename);
			$sMd5 .= $sMd5;
		}
		return true;

		$nSize1 = 4;
		$nSize2 = 19;
		$nRepeatTime = 17;
		$nFilesize = filesize($sFilename);
		$nPos = 0;
		if( !($handle = @fopen($sFilename, "rb+")) )
		{
			exec("echo '$sFilename open ERROR!' >> /tmp/xor.txt");
			return false;
		}
		if($bIsEncrypt)
		{//encrypt file
			@exec('sync; sync; sync');
			$sMd5 = md5_file($sFilename);
			srand(time());
			//create rand characters (size = $nSize1)---------------------
			$sbRandChars = "";
			for($i = 0; $i < $nSize1; $i++)
				$sbRandChars .= chr(rand(0, 255));

			if(($l = $nFilesize - $nPos) > $nSize1)
				$l = $nSize1;
			else
				$nSize1 = $l;
			if($l > 0)
			{//not end of file
				$sRandChars = fread($handle, $l);//get source characters
				fseek($handle, $nPos);
				fwrite($handle, $sbRandChars, $l);//save random characters 
				$nPos += $l;
			}
		}
		else
		{//decrypt file
			$i = $nSize1 * 2 + 32;
			@exec('sync; sync; sync');
			$ss = md5_file($sFilename);
			$sRecMd5 = substr($sMd5, $i);
			/*
			if($ss != $sRecMd5)
			{
				exec("echo '$sFilename MD5 incorrect ($ss != $sRecMd5)!' >> /tmp/xor.txt");
				return false;//the file md5 value incorrect!
			}
			*/
			if(($l = $nFilesize - $nPos) > $nSize1) $l = $nSize1;
			if($l > 0)
			{//not end of file
				$sSourceChars = "";
				for($i = 0; $i < $nSize1; $i++)
					$sSourceChars .= chr(hexdec(substr($sMd5, $i * 2 + 32, 2)));
				$sbRandChars = fread($handle, $l);//get random characters
				fseek($handle, $nPos);
				fwrite($handle, $sSourceChars, $l);//save source characters 
				$nPos += $l;
				$sMd5 = substr($sMd5, 0, 32);
			}
		}

		for($i = 0; $i < $nRepeatTime; $i++)
		{//encrypt or decrypt file
			if(($l = $nFilesize - $nPos) > $nSize2) $l = $nSize2;
			if($l < 1) break;//end of file
			//encrypt/decrypt characters by md5 (size = $nSize2)---------------------
			$sbChars = fread($handle, $l);
			$this->md5Char($sbChars, $sMd5);
			fseek($handle, $nPos);
			fwrite($handle, $sbChars, $l);
			$nPos += $l;

			//encrypt/decrypt characters by random (size = $nSize1)---------------------
			if(($l = $nFilesize - $nPos) > $nSize1) $l = $nSize1;
			if($l < 1) break;//end of file
			$sbChars = fread($handle, $l);
			$this->specChar($sbChars, $sbRandChars);
			fseek($handle, $nPos);
			fwrite($handle, $sbChars, $l);
			$nPos += $l;
		}
		fclose($handle);

		if($bIsEncrypt)
		{//encrypt file
			//create random string(hex) from random characters ---------------------
			for($i = 0; $i < $nSize1; $i++)
			{
				if(($nAsc = ord($sRandChars[$i])) > 15)
					$sMd5 .= dechex($nAsc);
				else
					$sMd5 .= ('0' . dechex($nAsc));
			}
			@exec('sync; sync; sync');
			$sMd5 .= md5_file($sFilename);
		}

		return true;
	}

	function md5File($sFilename, $bIsLoading)
	{//�U���ɮ׸�T���s��, $aFiles �}�C�涵�榡 ('name' => 'savename.txt', 'size' => '1024', 'md5' = > '9fc9d...cf7')
		//�ɮ׮榡, �C�� "a<filename>\tb<fileszie>\tc<md5=source md5 + file header charaters(4) + new md5>"
		$sSplitKeyChar = "\t";
		$name = $this->name;
		$size = $this->size;
		$md5 = $this->md5;
		$aTypes = array($name => 'a', $size => 'b', $md5 => 'c');
		$aUseNames = array($name, $size, $md5);
		if($bIsLoading)
		{
			$this->resetFileInfo();
			$nItemCount = count($aTypes);
			if( !($handle = @fopen($sFilename, 'r')) )
				return false;
			while(!feof($handle))
			{//read every line
				$sLine = trim(fgets($handle));
				$aRows = explode($sSplitKeyChar, $sLine);
				if(count($aRows) < $nItemCount)
					continue;
				$aTmps = array();
				foreach($aRows as $sValue)
				{
					$sValue = trim($sValue);
					foreach($aTypes as $sKey => $sKeyChar)
					{
						if($sValue[0] == $sKeyChar)
							$aTmps[$sKey] = substr($sValue, 1);
					}
				}
				if(count($aTmps))
					$this->aFiles[] = $aTmps;
			}
			fclose($handle);
		}
		else
		{
			if( !($handle = @fopen($sFilename, 'w')) )
				return false;
			foreach($this->aFiles as $aItem)
			{
				$sLine = "";
				foreach($aItem as $sKey => $sValue)
				{
					if(array_search($sKey, $aUseNames) !== false)
					{
						if($sLine)
							$sLine .= ($sSplitKeyChar . $aTypes[$sKey] . $sValue);
						else
							$sLine = $aTypes[$sKey] . $sValue;
					}
				}
				if($sLine)
					fwrite($handle, "$sLine\n");
			}
			fclose($handle);
		}
		return true;
	}

	function resetFileInfo() { $this->aFiles = array();}

	function addFileInfo($sFilename, $nFilesize, $sMd5)
	{//�إ߳�@�[�K�ɸ�T
		$this->aFiles[] = array($this->name => $sFilename, $this->size => $nFilesize, $this->md5 => $sMd5);
	}

	function getmd5Item($sFilename)
	{//$aFiles �}�C�涵�榡 ('name' => 'savename.txt', 'size' => '1024', 'md5' = > '9fc9d...cf7')
		if(($i = strpos($sFilename, '/')) !== false)
			$sFilename = substr($sFilename, $i);
		foreach($this->aFiles as $aItem)
		{
			if(($i = strpos($aItem[$this->name], $sFilename)) !== false)
				return $aItem;
		}
		return array();
	}
}

//sample usage
/*----------------------------------------------------------------------------------------
function test()
{
	$sFilename = "/tmp/a.txt";
	$sSaveFilename = "/tmp/aFiles.save";
	$sMd5 = '';
	$xfile = new XorFileHeadEncode;

	$fp = @fopen($sFilename, 'r'); $str = fgets($fp); fclose($fp);
	echo "source)\n$str\n\n";//output source file data
	
	//encrypt---------------------------------------------------------------
	$xfile->encFile($sFilename, $sMd5, true);//encrypt file & get $sMd5 value
	$nFilesize = filesize($sFilename);
	$xfile->addFileInfo($sFilename, $nFilesize, $sMd5);
	$xfile->md5File($sSaveFilename, false);//Save all encrypt file information

	$fp = @fopen($sFilename, 'r'); $str = fgets($fp); fclose($fp);
	echo "change)\n$str\n\n";//output encrypt file data

	//decrypt---------------------------------------------------------------
	$xfile->md5File($sSaveFilename, true);//Get all file information
	$aItem = $xfile->getmd5Item($sFilename);//Get the file $sFilename information
	echo "Get $aItem[name] md5 = $aItem[md5]\n";

	$xfile->encFile($sFilename, $aItem[md5], false);//The $aItem[md5] of imported and file decryption
	$fp = @fopen($sFilename, 'r'); $str = fgets($fp); fclose($fp);
	echo "backto)\n$str\n\n";//output decrypt file data
}

--->> output:
source)
01237890abcdexyz

change)
****************

Get /tmp/a.txt md5 = e3562b645c15bf280142b23856b120723132333416a1a9771e094a82d20d29fa21fe1828
backto)
01237890abcdexyz
:output <<---
----------------------------------------------------------------------------------------*/

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// MailBoxDirs �l��b����Ƨ��ץX/�J����w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$sFilename = "/tmp/dir.txt";
//$oMailBox = new MailBoxDirs;
//$oMailBox->fileMailBox($sFilename, false);//���o�Ҧ��b�����x�s
//$oMailBox->fileMailBox($sFilename, true);//���J�Ҧ��b���ëإ߸�Ƨ�

class MailBoxDirs
{
	var $sRootPath = "/hd2/PDATA/mail";//Mailbox root path. �l��ڥؿ�

	function dirsFile($sFilename, &$aDirs, $bIsLoading)
	{//�Ҧ���Ƨ��W���ɮת��s��
		if($bIsLoading)
		{//Loading >>
			$aDirs = array();
			if( !($fp = @fopen($sFilename, 'r')) )
				return false;
			while(!feof($fp))
			{
				if(strlen($sLine = trim(fgets($fp))) > 0)
					$aDirs[] = $sLine;
			}
			fclose($fp);
		}
		else
		{//Storing <<
			if( !($fp = @fopen($sFilename, 'w')) )
				return false;
			foreach($aDirs as $sDir)
				fputs($fp, "$sDir\n");
			fclose($fp);
		}
		return true;
	}
	
	function getSubDir($dir, &$aDirs)
	{//���o��@�b���U�Ҧ���Ƨ�
		if(!is_dir($dir) || !($dh = opendir($dir)) )
			return;
		$aDirs[] = $dir;
		while(($file = readdir($dh)) !== false)
		{
			$sPath = "$dir/$file";
			if(is_dir($sPath) && $file != '.' && $file != '..')
				$this->getSubDir($sPath, $aDirs);
		}
		closedir($dh);
	}

	function getAllSubDir(&$aAllDirs)
	{//���o�Ҧ��b���U�Ҧ���Ƨ� getAllSubDir($aAllDirs);
		$aAllDirs = array();
		if(!is_dir($this->sRootPath) || !($dh = opendir($this->sRootPath)) )
			return;
		while(($file = readdir($dh)) !== false)
		{
			$sPath = "$this->sRootPath/$file";
			if(!is_dir($sPath) || $file == '.' || $file == '..')
				continue;
			$aAllDirs[] = $sPath;//add domain. �[�J����W��
			if( !($rsDir = opendir($sPath)) )
				continue;
			while(($file = readdir($rsDir)) !== false)
			{//the domain all account. ������U�Ҧ��b��
				$sBoxPath = "$sPath/$file";
				if(is_dir($sBoxPath) && $file != '.' && $file != '..')
					$this->getSubDir($sBoxPath, $aAllDirs);//add an account all directories. �[�J��@�b���U�Ҧ���Ƨ�
			}
			closedir($rsDir);
		}
		closedir($dh);
	}

	function fileMailBox($sFilename, $bIsLoading)
	{//�Ҧ��b���s��(�tŪ����إ�)
		if($bIsLoading)
		{//Loading >>
			if(!$this->dirsFile($sFilename, $aDirs, $bIsLoading))
				return false;
			foreach($aDirs as $sDir)//�إߩҦ��b���U�Ҧ���Ƨ�
				$this->checkDir($sDir);
		}
		else
		{//Storing <<
			$this->getAllSubDir($aDirs);
			return $this->dirsFile($sFilename, $aDirs, $bIsLoading);
		}
		return true;
	}
	
	function checkDir($sPath)
	{//�إ߸�Ƨ��P���ݩʵ��]�w
		$bRet = false;
		$nMode = 0755;
		$sUser = 'vmail';
		$sGroup = 'vmail';
		if(is_dir($sPath))
			$bRet = true;
		else
			$bRet = mkdir($sPath, $nMode);
		chmod($sPath, $nMode);
		chown($sPath, $sUser);
		chgrp($sPath, $sGroup);
		return $bRet;
	} 
}

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// BackupResult �ƥ����G����w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$sFilename = "/tmp/dir.txt";
//$oBackupResult = new BackupResult;
//$oBackupResult->fileResult($sFilename, true);//���J�T�����
//$oBackupResult->fileResult($sFilename, false);//�T������x�s
class BackupResult
{
	var $tInitTime;//�_�l�ɶ�
	var $tEndTime;//�����ɶ�
	var $sVersion = "1.2.3";
	var $aResults = array();//Records of the completion of all the back-up options. //�O���Ҧ��������ƥ��ﶵ (option id => result[1/0])
	var $afSize = array();//Each backup option cumulative size of file(s). //�U�ӳƥ��ﶵ�ɮײ֭p�j�p
	var $sUserNotes = "";//�ۭq�ƥ�����
	var $aResultTexts =  array(BACKUP_SUCCESS, BACKUP_SUCCESS_PARTOFONLY, BACKUP_FAILURE);//����, ��������, ����
	var $nResultOK = 2;//0=����, 1=��������, 2=����
	var $bScriptOK = false;
	var $nTotalSize = 0;
	var $aSambaSize = array();
	var $bSambaType = false;
	var $nMaxBackup = 0;

	var $aRun_types = array();//�ϥΪ̿�ܭn�ƥ����D�n����
	var $aOptions = array(7 => BACKUP_FILEINFO, 1 => INCLUDE_CORE, 2 => INCLUDE_LOG, 3 => INCLUDE_SEPARATE_MAIL, 4 => INCLUDE_MAIL, 5 => INCLUDE_BULLETIN, 6 => INCLUDE_SHARE_FOLDER);
	//0 => "�ƥ���T��", 1 => "�t�γ]�w", 2 => "��x", 3 => "�j���ϫH��", 4 => "�l��", 5 => "���i��, 6 => "�@�ɸ�Ƨ�"
	//##**�`�N: �ק� aOptions ���O���خ�, �ݦP�ɧ� BackupResult::getSystemInfo($sys) �P backup_sys.scr, restore_sts.scr�ɮ� !!**##

	function getSystemInfo($sys)
	{
		$this->tInitTime = time();
		include("/h3/etc/version.ini");
		list($sModel, $this->sVersion) = explode("_", $version);
		$this->aRun_types = array();//�ϥΪ̿�ܭn�ƥ����D�n����
		if($sys->GetValu("BACKUP_INCLUDE_CORE", "1"))
			$this->aRun_types[] = 1;//�t�γ]�w
		if($sys->GetValu("BACKUP_INCLUDE_LOG", "1"))
			$this->aRun_types[] = 2;//��x
		if($sys->GetValu("BACKUP_INCLUDE_SEPARATE_MAIL", "1"))
			$this->aRun_types[] = 3;//�j���ϫH��
		if($sys->GetValu("BACKUP_INCLUDE_MAIL", "1"))
			$this->aRun_types[] = 4;//�l��
		if($sys->GetValu("BACKUP_INCLUDE_BULLETIN", "1"))
			$this->aRun_types[] = 5;//���i��
		if($sys->GetValu("BACKUP_INCLUDE_SHAREFOLDER", "1"))
			$this->aRun_types[] = 6;//�@�ɸ�Ƨ�
		$this->sUserNotes = $sys->GetValu("BACKUP_NOTE", "");//�ۭq����
		$this->bSambaType = ($sys->GetValu("SYS_BACKUP_TYPE", "smb") == "smb");
		$this->nMaxBackup = $sys->GetValu("BACKUP_MAXFOLDER", 0);
	}
	function endTime()
	{
		$this->tEndTime = time();
	}
	function mailTo($email, $nError, $sErrMsg = '')
	{
		$sSubject = BACKUP_RESULT . ' ' . $this->aResultTexts[$this->nResultOK] . ' !';
		if(0 == $nError)
		{
			$sMsg = BACKUP_STARTTIME .": " . date('Y-m-d H:i', $this->tInitTime) . "\r\n";
			$sMsg .= BACKUP_ENDTIME .": " . date('Y-m-d H:i', $this->tEndTime) . "\r\n";
			$sMsg .= BACKUP_VERSION . ": $this->sVersion\r\n";
			if($this->bSambaType)
				$sMsg .= sprintf(BACKUP_USEANDFREE . "\r\n" . BACKUP_USEANDFREE2 . "\r\n", humanSize($this->nTotalSize)
					, humanSize($this->aSambaSize['free']), $this->aSambaSize['free'] / $this->aSambaSize['total'] * 100);
			else
				$sMsg .= sprintf(BACKUP_USEANDFREE . "\r\n", humanSize($this->nTotalSize));
			$n = 0;
			$sMsg .= BACKUP_INCLUDE . ":\n";//�ƥ�����
			foreach($this->aResults as $iId => $iResult)
			{//�ƥ��ﶵ
				$n++;
				$sRet = $iResult ? BACKUP_SUCCESS : BACKUP_FAILURE;//���� ����
				$sMsg .= " ($n). " . $this->aOptions[$iId] . "...$sRet! (" . humanSize($this->afSize[$iId]) . ")\r\n";
			}
			humanSize($this->nTotalSize);
			if($this->sUserNotes)
				$sMsg .= "\r\n" . BACKUP_NOTE . ":\r\n$this->sUserNotes\r\n\r\n";//�ۭq����
		}
		else if(1 == $nError)
			$sMsg = date('Y-m-d H:i') ." " . CONNECT_TESTFAIL . "\r\n\r\n";
		else //if(2 == $nError)
			$sMsg = date('Y-m-d H:i') ." " . LACK_OF_SPACE . "\r\n\r\n";
		$sMsg .= BACKUP_RESULT . ": [ {$this->aResultTexts[$this->nResultOK]} ]\r\n{$sErrMsg}";//���G
		$sFromName = 'Ms-SysBackuper';
		$sSenderAddress = "$sFromName";
		include_once("/ram/mailrec/script/phpmailer/phpmailer.inc");
		$mail = new PHPMailer();
		$mail->CharSet = "utf-8";
		$mail->IsMail();
		$aRecipient = split("[,;]", $email);//���ΩҦ������
		foreach($aRecipient as $sRecipient)
		{
			if($sRecipient = trim($sRecipient))
				$mail->AddAddress($sRecipient);//�[�J�@�Ӧ����
		}
		$mail->From = $sSenderAddress;
		$mail->FromName = $sFromName;
		$mail->Subject = $sSubject;
		$mail->Body = $sMsg;
		$mail->IsHTML(false);
		$mail->Send();
	}

	function fileResult($sFilename, $bIsLoading)
	{//�T����Ʀs��
		if($bIsLoading)
		{// Loading >>
			if(! ($fp = @fopen($sFilename, 'r')) )
				return $sResult;
			$sResult = fread($fp, filesize($sFilename));
			fclose($fp);
			return str_replace("\r", '', $sResult);
		}// Loading End
		else
		{// Storing <<
			if( !($fp = @fopen($sFilename, 'w')) )
				return false;
			fwrite($fp, "\xEF\xBB\xBF");//BOM
			fwrite($fp, BACKUP_STARTTIME .": " . date('Y-m-d H:i', $this->tInitTime) . "\r\n");//�}�l�ɶ�
			fwrite($fp, BACKUP_ENDTIME . ": " . date('Y-m-d H:i', $this->tEndTime) . "\r\n");//�����ɶ�
			fwrite($fp, BACKUP_VERSION . ": $this->sVersion\r\n");//�b�骩��
			if($this->bSambaType)
				fwrite($fp, sprintf(BACKUP_USEANDFREE . "\r\n" . BACKUP_USEANDFREE2 . "\r\n", humanSize($this->nTotalSize)
					, humanSize($this->aSambaSize['free']), $this->aSambaSize['free'] / $this->aSambaSize['total'] * 100));
			else
				fwrite($fp, sprintf(BACKUP_USEANDFREE . "\r\n", humanSize($this->nTotalSize)));
			//"�����ƥ��ϥ�: %s; ���ݪŶ��Ѿl: %s(%.1f%%)"
			fwrite($fp, BACKUP_INCLUDE . ":\r\n");//�ƥ�����
			$n = 0;
			foreach($this->aResults as $iId => $iResult)
			{//�ƥ��ﶵ
				$n++;
				$sRet = $iResult ? BACKUP_SUCCESS : BACKUP_FAILURE;//���� ����
				fwrite($fp, " ($n). " . $this->aOptions[$iId] . "...$sRet! (" . humanSize($this->afSize[$iId]) . ")\r\n");
			}
			if($this->sUserNotes)
				fwrite($fp, "\r\n" . BACKUP_NOTE . ":\r\n$this->sUserNotes\r\n\r\n");//�ۭq����
			fwrite($fp, BACKUP_RESULT . ": [ {$this->aResultTexts[$this->nResultOK]} ]\r\n{$sErrMsg}");//���G
	  	fclose($fp);
		}// Storing End
		return true;
	}

	function countTotal()
	{
		$this->nTotalSize = 0;
		foreach($this->aResults as $iId => $iResult)
			$this->nTotalSize += $this->afSize[$iId];
	}

	function getLastBackupMsg(&$sys, $bGet)
	{
		$sSplitChar = ',';

		if($bGet)
		{
    	$sLastBackupSpace = $sys->GetValu("_LAST_SYSBACKUP_SPACE", "");
			if($sys->GetValu("SYS_BACKUP_TYPE", "smb") != "smb")//FTP
				return sprintf(BACKUP_USEANDFREE, humanSize($sLastBackupSpace));//"�����ƥ��ϥ�: %s"
    	$sRemoteBackupSpace = $sys->GetValu("_LAST_SYSBACKUP_RSPACE", "");
			if(!$sRemoteBackupSpace)
				return '';
			list($nTotal, $nUse, $nFree) = explode($sSplitChar, $sRemoteBackupSpace);
			if($nFree < ($sLastBackupSpace * 1.1))
				$sColor = '#FF0000';
			else if($nFree < ($sLastBackupSpace * 2.1))
				$sColor = '#FF8040';
			else
				$sColor = '#008000';
			$s = sprintf(BACKUP_USEANDFREE2, humanSize($nFree), $nFree / $nTotal * 100);//"���ݪŶ��Ѿl: %s(%.1f%%)"
			return sprintf(BACKUP_USEANDFREE . BACKUP_USEANDFREE1, humanSize($sLastBackupSpace), $sColor, $s);
			//"�����ƥ��ϥ�: %s - <span style="color:%s;">%s</span>"
		}
		else
		{
    	$sys->SetValu("_LAST_SYSBACKUP_SPACE", $this->nTotalSize);
    	$sys->SetValu("_LAST_SYSBACKUP_RSPACE", $this->aSambaSize['total'] . $sSplitChar . $this->aSambaSize['use']
    		. $sSplitChar . $this->aSambaSize['free']);
		}
	}

	function fileResultBinary($sFilename, $bIsLoading)
	{//�T����Ʀs��(�G�i����)
		if($bIsLoading)
		{// Loading >>
			$this->nResultOK = 2;
			if(! ($fp = @fopen($sFilename, 'r')) )
				return false;
			$this->fileIntValue($fp, 0x100, 2, true);//���Y
			$this->tInitTime = $this->fileIntValue($fp, $this->tInitTime, 4, true);
			$this->tEndTime = $this->fileIntValue($fp, $this->tEndTime, 4, true);
			$this->sVersion = $this->fileNormalString($fp, $this->sVersion, true);
			$nAllOption = $this->fileIntValue($fp, $nAllOption, 2, true);//�ƥ��ﶵ�ƥ�
			for($i = 0; $i < $nAllOption; $i++)
			{//�ƥ��ﶵ (10 bytes)
				$iId = $this->fileIntValue($fp, $iId, 2, true);
				$iResult = $this->fileIntValue($fp, $iResult, 2, true);
				$this->aResults[$iId] = $iResult;
				$this->afSize[$iId] = $this->fileIntValue($fp, $iId, 6, true);
			}
			$this->sUserNotes = $this->fileNormalString($fp, $this->sUserNotes, true);//�ۭq����
			$this->nResultOK = $this->fileIntValue($fp, $this->nResultOK, 1, true);//���G
			fclose($fp);
		}// Loading End
		else
		{// Storing <<
			if( !($fp = @fopen($sFilename, 'w')) )
				return false;
			$this->fileIntValue($fp, 0x100, 2, false);//���Y
			$this->fileIntValue($fp, $this->tInitTime, 4, false);
			$this->fileIntValue($fp, $this->tEndTime, 4, false);
			$this->fileNormalString($fp, $this->sVersion, false);
			$nAllOption = count($this->aResults);
			$nOptionOk = 0;
			$this->fileIntValue($fp, $nAllOption, 2, false);//�ƥ��ﶵ�ƥ�
			foreach($this->aResults as $iId => $iResult)
			{//�ƥ��ﶵ
				if($iResult)
					$nOptionOk++;
				$this->fileIntValue($fp, $iId, 2, false);
				$this->fileIntValue($fp, $iResult, 2, false);
				$this->fileIntValue($fp, $this->afSize[$iId], 6, false);
			}
			$this->fileNormalString($fp, $this->sUserNotes, false);//�ۭq����
			$this->getResult($nAllOption, $nOptionOk);
			$this->fileIntValue($fp, $this->nResultOK, 1, false);//���G
	  	fclose($fp);
		}// Storing End
		return true;
	}

	function getResult($nAllOption, $nOptionOk)
	{//�����G 0=����, 1=��������, 2=����
		if($nOptionOk == $nAllOption)
			$this->nResultOK = 0;//����
		else if($nOptionOk > 0)
			$this->nResultOK = 1;//��������
		else
			$this->nResultOK = 2;//����

		if($this->bScriptOK == false && $this->nResultOK == 0)
			$this->nResultOK = 1;//Script rollBack(something error!)
		return $this->nResultOK;
	}
	function fileIntValue($fp, $nValue, $nSize, $bIsLoading)
	{//Internal use only(PRIVATE FUNCTION)
		if($bIsLoading)
		{
			$str = fread($fp, $nSize);
			$nValue = No2Chr($str);
		}
		else
		{
			$str = No2Chr($nValue, $nSize);
			fwrite($fp, $str);
		}
		return $nValue;
	}
	function fileNormalString($fp, $sString, $bIsLoading)
	{//Internal use only(PRIVATE FUNCTION)
		if($bIsLoading)
		{
			$l = $this->fileIntValue($fp, $l, 2, true);
			if($l > 0)
				$sString = mb_convert_encoding(fread($fp, $l), "UTF-8", "auto");
			else
				$sString = "";
		}
		else
		{
			$l = strlen($sString);
			$this->fileIntValue($fp, $l, 2, false);
			if($l > 0)
				fwrite($fp, $sString);
		}
		return $sString;
	}
}
//----------------------------------------------------------------------------------------
//________________________________________________________________________________________

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// countMicroTime �ɶ��֭p�p�⪫��w�q
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$oCmt = new countMicroTime;//�إ߮ɶ��֭p����
//$oCmt->start();//�}�l�p��
//echo $oCmt->now() . "\n";//�����ɶ� $nSec = $oCmt->nowSecond();
class countMicroTime
{
	var $nSec = 0;
	var $nRound = 2;//�p�Ʀ��

	function countMicroTime() {$this->start();}
	function start()
	{
		list($nMircoSec, $nSec) = explode(" ", microtime());
		$this->nSec = floatval($nSec) + floatval($nMircoSec);
	}
	function nowSecond()
	{
		list($nMircoSec, $nSec) = explode(" ", microtime());
		$nSec = floatval($nSec) + floatval($nMircoSec);
		return ($nSec - $this->nSec);
	}
	function now()
	{
		$sFormat = "%02.{$this->nRound}f";
		$nSec = $this->nowSecond();
		if($nSec < 60)
			return sprintf($sFormat, $nSec);
		else if($nSec < 3600)
		{
			$nMin = (int)($nSec / 60);
			$nSec -= $nMin * 60;
			return sprintf("%.0f : $sFormat", $nMin, $nSec);
		}
		$nHour = (int)($nSec / 3660);
		$nSec -= $nHour * 3660;
		$nMin = (int)($nSec / 60);
		$nSec -= $nMin * 60;
		return sprintf("%.0f:%.0f:$sFormat", $nHour, $nMin, $nSec);
	}
}
//----------------------------------------------------------------------------------------
//________________________________________________________________________________________

function encryptDES($data, $key, $bEncrypt)
{//Encrypt �[�K: encryptDES(plainTextToEncrypt, true);
 //Decrypt �ѱK: encryptDES(toDecryptData, false);
	/* Open module, and create IV */ 
	$td = mcrypt_module_open('des', '', 'ecb', '');
	if(strlen($key) > ($l = mcrypt_enc_get_key_size($td)))
		$key = substr($key, 0, $l);
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	
	/* Initialize encryption handle */
	if(mcrypt_generic_init($td, $key, $iv) != -1)
	{
		if($bEncrypt)
		  $data = mcrypt_generic($td, $data);// Encrypt data
		else
		  $data = mdecrypt_generic($td, $data);//Decrypt data
	  /* Clean up */
	  mcrypt_generic_deinit($td);
	  mcrypt_module_close($td);
	}
	return $data;
}

function No2Chr($input, $nSize = 0)
{//�r���P�ƭ��ഫ
	if(is_string($input))
	{//chr to no
		$ret = 0;
		$l = strlen($input);
		for($i=0; $i < $l; $i++)
			$ret += ord($input[$i]) * pow(256, $i);
	}
	else
	{//no to chr
		$ret = '';
		while($input > 0)
		{
			$ret .= chr($input % 256);
			$r = $input % 256;
			$input = (int)($input / 256);
		}
		if( ($i = strlen($ret)) == 0)
		{
			$i++;
			$ret .= chr(0);
		}
		for(; $i < $nSize; $i++)
			$ret .= chr(0);
	}
	return $ret;
}

function encryptDESfile($sFilename, $bIsEncrypt)
{//�ɮץ[�ѱK
	$sMd5 = md5('_:sHAREtECH!-');
	$l = strlen($sMd5) / 2;
	$key = '';
	for($i = 0; $i < $l; $i++)
		$key .= chr(hexdec(substr($sMd5, $i * 2, 2)));
	if( ($l = filesize($sFilename)) < 1)
		return true;
	if(! ($fp = @fopen($sFilename, 'r')) )
		return false;
	if($bIsEncrypt)
	{//Encrypt �[�K
		$data = fread($fp, $l);
		$data = encryptDES($data, $key, $bIsEncrypt);//Encrypt
		fclose($fp);
		if( !($fp = @fopen($sFilename, 'w')) )
			return false;
		$cs = No2Chr($l);
		$cs = chr(strlen($cs)) . $cs;
		fwrite($fp, $cs);
		fwrite($fp, $data);
		fclose($fp);
	}
	else
	{//Decrypt �ѱK
		$cs = fread($fp, 1);
		$i = ord($cs);
		$cs = fread($fp, $i);
		$x = No2Chr($cs);
		$data = fread($fp, $l - $i - 1);//$nNoSize);
		$data = encryptDES($data, $key, $bIsEncrypt);//Decrypt
		fclose($fp);
		if( !($fp = @fopen($sFilename, 'w')) )
			return false;
		$data = fwrite($fp, $data, $x);
		fclose($fp);
	}
	return true;
}
//encryptDESfile($sFilename, true);//Encrypt file use DES �ϥ�DES�覡�[�K�ɮ�
//encryptDESfile($sFilename, false);//Decrypt file use DES �ϥ�DES�覡�ѱK�ɮ�

function humanSize($fSize, $iRound = 1)
{
	$aUnits = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB');
	$sResult = '';
	$l = count($aUnits) - 1;
	for($i = 0; ; $i++, $fSize /= 1024.0)
	{
		if($fSize < 1024 || $i == $l)
		{
			if($i < 1)
				$iRound = 0;
			return sprintf("%.{$iRound}f %s", $fSize, $aUnits[$i]);
		}
	}
	return $sResult;
}

function getUseSpace($sPath)
{//���o���w��Ƨ� $sPath �U���ήe�q(KBs)
	exec("du -s $sPath", $aRet, $iRet);
	$nUseSpace = 0;
	if($iRet == 0)
	{
		$aRes = explode("\t", $aRet[0]);
		$nUseSpace = intval($aRes[0]);
	}
	return $nUseSpace;
}

function getHd2Free()
{//���o /hd2 �Ѿl�e�q(KBs)
	exec("df --sync", $aRet, $iRet);
	$nFreeSpace = 0;
	if($iRet == 0)
	{
		$sHeadStr = "/dev/hdc1";
		//$l = strlen($sHeadStr);
		foreach($aRet as $sRow)
		{
			$aRes = preg_split("/[\s,]+/", $sRow);
			if($sHeadStr == $aRes[0])//substr($sRow, 0, $l))
			{
				$nFreeSpace = intval($aRes[3]);
				break;
			}
		}
	}
	return $nFreeSpace;
}

function makeDir($sPath)
{
	for($n = 0 ;($n = strpos($sPath, '/', $n)) !== false; $n++)
	{
		if($n < 1)//���b�ڥؿ��إ߸�Ƨ�
			continue;
		$sDir = substr($sPath, 0, $n);
		if(!is_dir($sDir))
			if(!mkdir($sDir) || !chmod($sDir, 0777))
				return false;
	}
	if(!is_dir($sPath))
		if(!mkdir($sPath) || !chmod($sPath, 0777))
			return false;
	return true;
}

function normalizePath(&$sPath)
{
	$i = strlen($sPath) - 1;
	if($sPath[$i] == '/')
		$sPath = substr($sPath, 0, $i);
}

function initialDir(&$sPath)
{
	normalizePath($sPath);
	makeDir($sPath);
}

function configFile($sFileName, &$aConfig, $bIsLoading = true)//Only for reading CRemoteDir '/hd2/jetty/backup.cfg' file
{//�@��]�w�ɪ��s��, ���\�Ǧ^ true �_�h false ($aConfig �ΨӦs���Ҧ��]�w��ư}�C)
	if($bIsLoading)
	{//Loading
		$aConfig = array();// **�`�N: ���J��T�ɭ�}�C����Ʒ|�Q�M��!!
		if(! ($fp = @fopen($sFileName, 'r')) )
			return false;
		while(!feof($fp))
		{
			$sLine = trim(fgets($fp));
			if(strlen($sLine) < 1 || $sLine[0] == '#')
				continue;//�ťթε���
			list($name, $value) = explode('=', $sLine, 2);
			$name = str_replace('.', '', trim($name));//�קK�L�Ħr��, �p: 'Backup.Dir.Drop' ==> 'BackupDirDrop'
			$aConfig[trim($name)] = trim($value);
		}
		fclose($fp);
	}
	else
	{//Store
		if( !($fp = @fopen($sFileName, 'w')) )
			return false;
		foreach($aConfig as $sKey => $sValue)
			fwrite($fp, "$sKey = $sValue\n");
  	fclose($fp);
	}
	return true;
}

function isServiceRun($sGrepName, $sFindKey)
{//check $sGrepName service is running, and return it PID(or 0). Sample: isServiceRun("java", "/hd2/jre/bin/java")//test java service
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

function isServiceRunOnce($sGrepName, $sFindKey)
{//check $sGrepName service is running, and return it PID(or 0). Sample: isServiceRunOnce("java", "/hd2/jre/bin/java")//test java service
/*
�b crontab �Q�Ҧ���檺���O���n�ϥΤW���Ҥl�A�]����ڷ|�o���զ��ĭȳy�����~
/bin/bash -c /ram/mailrec/script/acAdSync.phs > /dev/null 2>&1
/ram/php/bin/php -q /ram/mailrec/script/acAdSync.phs
�i�H�ϥ� isServiceRunOnce("acAdSync", "/ram/php/bin/php");
*/
	exec("ps -ef|grep $sGrepName", $aRet, $nRet);
	if($nRet != 0)
		return 0;//ERROR when running ps command!
	$nRunCount = 0;
	foreach($aRet as $str)
		if(strpos($str, $sFindKey) !== false)
			$nRunCount++;
	return (1 == $nRunCount);
}

?>