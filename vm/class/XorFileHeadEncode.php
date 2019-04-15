<?
/* DOC_NO:A2-080826-00006 */

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// XorFileHeadEncode 檔案加解密定義物件
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
class XorFileHeadEncode
{
	var $name = 'name';
	var $size = 'size';
	var $md5 = 'md5';
	var $aFiles = array();

	function specChar(&$sbBuf, $sSpecChars)
	{//將 $sBuf字串與 $sSpecChars 字串做 XOR 處理
		$l = strlen($sbBuf);
		$lmax = strlen($sSpecChars);
		for($i = $n = 0; $i < $l; $i++)
			$sbBuf[$i] = $sbBuf[$i] ^ substr($sSpecChars, $i % $lmax, 1);
	}
	function md5Char(&$sbBuf, $sMd5)
	{//將 $sBuf字串與 $sMd5 md5碼字串('1a2b3c...')做 XOR 處理
		$l = strlen($sbBuf);
		$lmax = strlen($sMd5) / 2;
		for($i = $n = 0; $i < $l; $i++)
			$sbBuf[$i] = $sbBuf[$i] ^ chr(hexdec(substr($sMd5, $i * 2 % $lmax, 2)));
	}
	function encFile($sFilename, &$sMd5, $bIsEncrypt)
	{//加解密檔案, 最大編碼 = ($nSize1 + $nSize2) * $nRepeatTime = 391 bytes, 檔案超過部份則不做變更
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
	{//各個檔案資訊的存取, $aFiles 陣列單項格式 ('name' => 'savename.txt', 'size' => '1024', 'md5' = > '9fc9d...cf7')
		//檔案格式, 每行 "a<filename>\tb<fileszie>\tc<md5=source md5 + file header charaters(4) + new md5>"
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
	{//建立單一加密檔資訊
		$this->aFiles[] = array($this->name => $sFilename, $this->size => $nFilesize, $this->md5 => $sMd5);
	}

	function getmd5Item($sFilename)
	{//$aFiles 陣列單項格式 ('name' => 'savename.txt', 'size' => '1024', 'md5' = > '9fc9d...cf7')
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
// MailBoxDirs 郵件帳號資料夾匯出/入物件定義
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$sFilename = "/tmp/dir.txt";
//$oMailBox = new MailBoxDirs;
//$oMailBox->fileMailBox($sFilename, false);//取得所有帳號並儲存
//$oMailBox->fileMailBox($sFilename, true);//載入所有帳號並建立資料夾

class MailBoxDirs
{
	var $sRootPath = "/hd2/PDATA/mail";//Mailbox root path. 郵件根目錄

	function dirsFile($sFilename, &$aDirs, $bIsLoading)
	{//所有資料夾名稱檔案的存取
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
	{//取得單一帳號下所有資料夾
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
	{//取得所有帳號下所有資料夾 getAllSubDir($aAllDirs);
		$aAllDirs = array();
		if(!is_dir($this->sRootPath) || !($dh = opendir($this->sRootPath)) )
			return;
		while(($file = readdir($dh)) !== false)
		{
			$sPath = "$this->sRootPath/$file";
			if(!is_dir($sPath) || $file == '.' || $file == '..')
				continue;
			$aAllDirs[] = $sPath;//add domain. 加入網域名稱
			if( !($rsDir = opendir($sPath)) )
				continue;
			while(($file = readdir($rsDir)) !== false)
			{//the domain all account. 此網域下所有帳號
				$sBoxPath = "$sPath/$file";
				if(is_dir($sBoxPath) && $file != '.' && $file != '..')
					$this->getSubDir($sBoxPath, $aAllDirs);//add an account all directories. 加入單一帳號下所有資料夾
			}
			closedir($rsDir);
		}
		closedir($dh);
	}

	function fileMailBox($sFilename, $bIsLoading)
	{//所有帳號存取(含讀取後建立)
		if($bIsLoading)
		{//Loading >>
			if(!$this->dirsFile($sFilename, $aDirs, $bIsLoading))
				return false;
			foreach($aDirs as $sDir)//建立所有帳號下所有資料夾
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
	{//建立資料夾與其屬性等設定
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
// BackupResult 備份結果物件定義
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$sFilename = "/tmp/dir.txt";
//$oBackupResult = new BackupResult;
//$oBackupResult->fileResult($sFilename, true);//載入訊息資料
//$oBackupResult->fileResult($sFilename, false);//訊息資料儲存
class BackupResult
{
	var $tInitTime;//起始時間
	var $tEndTime;//結束時間
	var $sVersion = "1.2.3";
	var $aResults = array();//Records of the completion of all the back-up options. //記錄所有完成的備份選項 (option id => result[1/0])
	var $afSize = array();//Each backup option cumulative size of file(s). //各個備份選項檔案累計大小
	var $sUserNotes = "";//自訂備份說明
	var $aResultTexts =  array(BACKUP_SUCCESS, BACKUP_SUCCESS_PARTOFONLY, BACKUP_FAILURE);//完成, 部份完成, 失敗
	var $nResultOK = 2;//0=完成, 1=部份完成, 2=失敗
	var $bScriptOK = false;
	var $nTotalSize = 0;
	var $aSambaSize = array();
	var $bSambaType = false;
	var $nMaxBackup = 0;

	var $aRun_types = array();//使用者選擇要備份的主要項目
	var $aOptions = array(7 => BACKUP_FILEINFO, 1 => INCLUDE_CORE, 2 => INCLUDE_LOG, 3 => INCLUDE_SEPARATE_MAIL, 4 => INCLUDE_MAIL, 5 => INCLUDE_BULLETIN, 6 => INCLUDE_SHARE_FOLDER);
	//0 => "備份資訊檔", 1 => "系統設定", 2 => "日誌", 3 => "隔離區信件", 4 => "郵件", 5 => "公告欄, 6 => "共享資料夾"
	//##**注意: 修改 aOptions 類別項目時, 需同時更正 BackupResult::getSystemInfo($sys) 與 backup_sys.scr, restore_sts.scr檔案 !!**##

	function getSystemInfo($sys)
	{
		$this->tInitTime = time();
		include("/h3/etc/version.ini");
		list($sModel, $this->sVersion) = explode("_", $version);
		$this->aRun_types = array();//使用者選擇要備份的主要項目
		if($sys->GetValu("BACKUP_INCLUDE_CORE", "1"))
			$this->aRun_types[] = 1;//系統設定
		if($sys->GetValu("BACKUP_INCLUDE_LOG", "1"))
			$this->aRun_types[] = 2;//日誌
		if($sys->GetValu("BACKUP_INCLUDE_SEPARATE_MAIL", "1"))
			$this->aRun_types[] = 3;//隔離區信件
		if($sys->GetValu("BACKUP_INCLUDE_MAIL", "1"))
			$this->aRun_types[] = 4;//郵件
		if($sys->GetValu("BACKUP_INCLUDE_BULLETIN", "1"))
			$this->aRun_types[] = 5;//公告欄
		if($sys->GetValu("BACKUP_INCLUDE_SHAREFOLDER", "1"))
			$this->aRun_types[] = 6;//共享資料夾
		$this->sUserNotes = $sys->GetValu("BACKUP_NOTE", "");//自訂說明
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
			$sMsg .= BACKUP_INCLUDE . ":\n";//備份項目
			foreach($this->aResults as $iId => $iResult)
			{//備份選項
				$n++;
				$sRet = $iResult ? BACKUP_SUCCESS : BACKUP_FAILURE;//完成 失敗
				$sMsg .= " ($n). " . $this->aOptions[$iId] . "...$sRet! (" . humanSize($this->afSize[$iId]) . ")\r\n";
			}
			humanSize($this->nTotalSize);
			if($this->sUserNotes)
				$sMsg .= "\r\n" . BACKUP_NOTE . ":\r\n$this->sUserNotes\r\n\r\n";//自訂說明
		}
		else if(1 == $nError)
			$sMsg = date('Y-m-d H:i') ." " . CONNECT_TESTFAIL . "\r\n\r\n";
		else //if(2 == $nError)
			$sMsg = date('Y-m-d H:i') ." " . LACK_OF_SPACE . "\r\n\r\n";
		$sMsg .= BACKUP_RESULT . ": [ {$this->aResultTexts[$this->nResultOK]} ]\r\n{$sErrMsg}";//結果
		$sFromName = 'Ms-SysBackuper';
		$sSenderAddress = "$sFromName";
		include_once("/ram/mailrec/script/phpmailer/phpmailer.inc");
		$mail = new PHPMailer();
		$mail->CharSet = "utf-8";
		$mail->IsMail();
		$aRecipient = split("[,;]", $email);//分割所有收件者
		foreach($aRecipient as $sRecipient)
		{
			if($sRecipient = trim($sRecipient))
				$mail->AddAddress($sRecipient);//加入一個收件者
		}
		$mail->From = $sSenderAddress;
		$mail->FromName = $sFromName;
		$mail->Subject = $sSubject;
		$mail->Body = $sMsg;
		$mail->IsHTML(false);
		$mail->Send();
	}

	function fileResult($sFilename, $bIsLoading)
	{//訊息資料存取
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
			fwrite($fp, BACKUP_STARTTIME .": " . date('Y-m-d H:i', $this->tInitTime) . "\r\n");//開始時間
			fwrite($fp, BACKUP_ENDTIME . ": " . date('Y-m-d H:i', $this->tEndTime) . "\r\n");//結束時間
			fwrite($fp, BACKUP_VERSION . ": $this->sVersion\r\n");//軔體版本
			if($this->bSambaType)
				fwrite($fp, sprintf(BACKUP_USEANDFREE . "\r\n" . BACKUP_USEANDFREE2 . "\r\n", humanSize($this->nTotalSize)
					, humanSize($this->aSambaSize['free']), $this->aSambaSize['free'] / $this->aSambaSize['total'] * 100));
			else
				fwrite($fp, sprintf(BACKUP_USEANDFREE . "\r\n", humanSize($this->nTotalSize)));
			//"本次備份使用: %s; 遠端空間剩餘: %s(%.1f%%)"
			fwrite($fp, BACKUP_INCLUDE . ":\r\n");//備份項目
			$n = 0;
			foreach($this->aResults as $iId => $iResult)
			{//備份選項
				$n++;
				$sRet = $iResult ? BACKUP_SUCCESS : BACKUP_FAILURE;//完成 失敗
				fwrite($fp, " ($n). " . $this->aOptions[$iId] . "...$sRet! (" . humanSize($this->afSize[$iId]) . ")\r\n");
			}
			if($this->sUserNotes)
				fwrite($fp, "\r\n" . BACKUP_NOTE . ":\r\n$this->sUserNotes\r\n\r\n");//自訂說明
			fwrite($fp, BACKUP_RESULT . ": [ {$this->aResultTexts[$this->nResultOK]} ]\r\n{$sErrMsg}");//結果
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
				return sprintf(BACKUP_USEANDFREE, humanSize($sLastBackupSpace));//"本次備份使用: %s"
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
			$s = sprintf(BACKUP_USEANDFREE2, humanSize($nFree), $nFree / $nTotal * 100);//"遠端空間剩餘: %s(%.1f%%)"
			return sprintf(BACKUP_USEANDFREE . BACKUP_USEANDFREE1, humanSize($sLastBackupSpace), $sColor, $s);
			//"本次備份使用: %s - <span style="color:%s;">%s</span>"
		}
		else
		{
    	$sys->SetValu("_LAST_SYSBACKUP_SPACE", $this->nTotalSize);
    	$sys->SetValu("_LAST_SYSBACKUP_RSPACE", $this->aSambaSize['total'] . $sSplitChar . $this->aSambaSize['use']
    		. $sSplitChar . $this->aSambaSize['free']);
		}
	}

	function fileResultBinary($sFilename, $bIsLoading)
	{//訊息資料存取(二進位檔)
		if($bIsLoading)
		{// Loading >>
			$this->nResultOK = 2;
			if(! ($fp = @fopen($sFilename, 'r')) )
				return false;
			$this->fileIntValue($fp, 0x100, 2, true);//檔頭
			$this->tInitTime = $this->fileIntValue($fp, $this->tInitTime, 4, true);
			$this->tEndTime = $this->fileIntValue($fp, $this->tEndTime, 4, true);
			$this->sVersion = $this->fileNormalString($fp, $this->sVersion, true);
			$nAllOption = $this->fileIntValue($fp, $nAllOption, 2, true);//備份選項數目
			for($i = 0; $i < $nAllOption; $i++)
			{//備份選項 (10 bytes)
				$iId = $this->fileIntValue($fp, $iId, 2, true);
				$iResult = $this->fileIntValue($fp, $iResult, 2, true);
				$this->aResults[$iId] = $iResult;
				$this->afSize[$iId] = $this->fileIntValue($fp, $iId, 6, true);
			}
			$this->sUserNotes = $this->fileNormalString($fp, $this->sUserNotes, true);//自訂說明
			$this->nResultOK = $this->fileIntValue($fp, $this->nResultOK, 1, true);//結果
			fclose($fp);
		}// Loading End
		else
		{// Storing <<
			if( !($fp = @fopen($sFilename, 'w')) )
				return false;
			$this->fileIntValue($fp, 0x100, 2, false);//檔頭
			$this->fileIntValue($fp, $this->tInitTime, 4, false);
			$this->fileIntValue($fp, $this->tEndTime, 4, false);
			$this->fileNormalString($fp, $this->sVersion, false);
			$nAllOption = count($this->aResults);
			$nOptionOk = 0;
			$this->fileIntValue($fp, $nAllOption, 2, false);//備份選項數目
			foreach($this->aResults as $iId => $iResult)
			{//備份選項
				if($iResult)
					$nOptionOk++;
				$this->fileIntValue($fp, $iId, 2, false);
				$this->fileIntValue($fp, $iResult, 2, false);
				$this->fileIntValue($fp, $this->afSize[$iId], 6, false);
			}
			$this->fileNormalString($fp, $this->sUserNotes, false);//自訂說明
			$this->getResult($nAllOption, $nOptionOk);
			$this->fileIntValue($fp, $this->nResultOK, 1, false);//結果
	  	fclose($fp);
		}// Storing End
		return true;
	}

	function getResult($nAllOption, $nOptionOk)
	{//取結果 0=完成, 1=部份完成, 2=失敗
		if($nOptionOk == $nAllOption)
			$this->nResultOK = 0;//完成
		else if($nOptionOk > 0)
			$this->nResultOK = 1;//部份完成
		else
			$this->nResultOK = 2;//失敗

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
// countMicroTime 時間累計計算物件定義
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$oCmt = new countMicroTime;//建立時間累計物件
//$oCmt->start();//開始計時
//echo $oCmt->now() . "\n";//完成時間 $nSec = $oCmt->nowSecond();
class countMicroTime
{
	var $nSec = 0;
	var $nRound = 2;//小數位數

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
{//Encrypt 加密: encryptDES(plainTextToEncrypt, true);
 //Decrypt 解密: encryptDES(toDecryptData, false);
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
{//字元與數值轉換
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
{//檔案加解密
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
	{//Encrypt 加密
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
	{//Decrypt 解密
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
//encryptDESfile($sFilename, true);//Encrypt file use DES 使用DES方式加密檔案
//encryptDESfile($sFilename, false);//Decrypt file use DES 使用DES方式解密檔案

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
{//取得指定資料夾 $sPath 下佔用容量(KBs)
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
{//取得 /hd2 剩餘容量(KBs)
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
		if($n < 1)//不在根目錄建立資料夾
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
{//一般設定檔的存取, 成功傳回 true 否則 false ($aConfig 用來存取所有設定資料陣列)
	if($bIsLoading)
	{//Loading
		$aConfig = array();// **注意: 載入資訊時原陣列內資料會被清空!!
		if(! ($fp = @fopen($sFileName, 'r')) )
			return false;
		while(!feof($fp))
		{
			$sLine = trim(fgets($fp));
			if(strlen($sLine) < 1 || $sLine[0] == '#')
				continue;//空白或註解
			list($name, $value) = explode('=', $sLine, 2);
			$name = str_replace('.', '', trim($name));//避免無效字元, 如: 'Backup.Dir.Drop' ==> 'BackupDirDrop'
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
在 crontab 被例行執行的指令不要使用上面例子，因為實際會得到兩組有效值造成錯誤
/bin/bash -c /ram/mailrec/script/acAdSync.phs > /dev/null 2>&1
/ram/php/bin/php -q /ram/mailrec/script/acAdSync.phs
可以使用 isServiceRunOnce("acAdSync", "/ram/php/bin/php");
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