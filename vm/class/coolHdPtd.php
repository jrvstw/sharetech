<?
include_once(file_exists('/h3/etc/version.ini') ? 'io.php' : '/PDATA/apache/class/io.php');

//Ex:
//$oHdPtd = new coolHdPtd();
//$oHdPtd->setKey();//HDD is currently associated with registration code
//if($oHdPtd->checkKey())//Check the HDD with the registration code legitimacy

class coolHdPtd
{
	var $sPtdFile1 = '/HDD/PDATA/sldap/log.0000000001';
	var $sPtdFile2 = '/HDD/MysqlDB/ibl_datafile0';
	var $sRegFile = '/h3/etc/.resolvf.conf';
	var $aPos = array('nInitPos' => 0x58f, 'nInitStep' => 39);
	var $aSalts = array('Q(mrtH.u{>=AA%H!2@z^jwn' , '$&tOD~:lgEpv8-vRY(V`*', '7:-mS2g%,H15v#0"wfUk6&oS+z');

	function checkKey()
	{
		$this->checkNotice();
		if(!$this->isDemo())
			return true;//not demo version
		$sMd5 = trim(file_get_contents($this->sRegFile));
		$nRet = 0;
		if(md5($this->aSalts[0] . $sMd5) == $this->getKey1())
			++$nRet;
		if(md5($this->aSalts[1] . $sMd5) == $this->getKey2())
			++$nRet;
		if(md5($this->aSalts[2] . $sMd5) == $this->getKey2($bFile1 = false))
			++$nRet;
		if($nRet > 1) return true;
		trigger_error("this is a demo version, HDD checkKey() fail!", E_USER_ERROR);
		unlink($this->sRegFile);
		return false;
	}

	function setKey()
	{
		if(!file_exists($this->sPtdFile1) || !file_exists($this->sPtdFile2))
		{
			$sHDD2file = file_exists('/h3/etc/version.ini') ? '/h3/PKGS/hdd2.tgz' : '/PDATA/vxc/hdd2.tgz';
			exec("tar xzf $sHDD2file -C /HDD");
		}
		$sMd5 = trim(file_get_contents($this->sRegFile));
		$this->setKey1(md5($this->aSalts[0] . $sMd5));
		$this->setKey2(md5($this->aSalts[1] . $sMd5));
		$this->setKey2(md5($this->aSalts[2] . $sMd5), $bFile1 = false);
	}

	function getKey1()
	{
		if(!file_exists($this->sPtdFile1)) return '';
		$sStr = file_get_contents($this->sPtdFile1);
		return $this->transMd5($sStr);
	}
	function setKey1($sMd5)
	{
		if(file_exists($this->sPtdFile1))
		{
			$aItem = $this->getFileStat($this->sPtdFile1);
			$sStr = file_get_contents($this->sPtdFile1);
		}
		else
		{
			$sPtdDir = '/HDD/PDATA/sldap';
			$sPtdFile1 = '/HDD/PDATA/ldap/log.0000000001';
			$aItem = $this->getFileStat($sPtdFile1);
			if( !($fp = @fopen($sPtdFile1, 'r')) ) return false;
			$sStr = fread($fp, 6144);
			fclose($fp);
			if(!is_dir($sPtdDir)) mkdir($sPtdDir);
		}
		$sStr = $this->transMd5($sStr, $sMd5);
		file_put_contents($this->sPtdFile1, $sStr);
		touch($this->sPtdFile1, $aItem['mt']);
		return true;
	}

	function getKey2($bFile1 = true)
	{
		$sPtdFile2 = $bFile1 ? $this->sPtdFile2 : substr($this->sPtdFile2, 0, strlen($this->sPtdFile2) - 1) . '1';
		if(!file_exists($sPtdFile2)) return '';
		$sStr = file_get_contents($sPtdFile2);
		return $this->transMd5($sStr);
	}
	function setKey2($sMd5, $bFile1 = true)
	{
		$sPtdFile2 = $bFile1 ? $this->sPtdFile2 : substr($this->sPtdFile2, 0, strlen($this->sPtdFile2) - 1) . '1';
		if(file_exists($sPtdFile2))
		{
			$aItem = $this->getFileStat($sPtdFile2);
			$sStr = file_get_contents($sPtdFile2);
		}
		else
		{
			$sPtdFile = '/HDD/MysqlDB/ib_logfile0';
			if(!file_exists($sPtdFile)) $sPtdFile = '/HDD/PDATA/ldap/log.0000000001';
			$aItem = $this->getFileStat($sPtdFile);
			if( !($fp = @fopen($sPtdFile, 'r')) ) return false;
			$sStr = fread($fp, 15236);
			fclose($fp);
		}
		$sStr = $this->transMd5($sStr, $sMd5);
		file_put_contents($sPtdFile2, $sStr);
		touch($sPtdFile2, $aItem['mt']);
		return true;
	}

	function transMd5($sStr, $sMd5 = '')
	{
		if(!$sMd5)
		{
			$sMd5 = '';
			for($n = 0, $i = $this->aPos['nInitPos'], $nStep = $this->aPos['nInitStep']; $n < 16; ++$n, ++$nStep)
				$sMd5 .= sprintf("%02x", ord($sStr[$i += $nStep]));
			return $sMd5;
		}
		for($n = 0, $i = $this->aPos['nInitPos'], $nStep = $this->aPos['nInitStep']; $n < 32; $n += 2, ++$nStep)
		{
			$sStr[$i += $nStep] = chr(base_convert(substr($sMd5, $n, 2), 16, 10));
			for($m = 0, $l = mt_rand(3, $nStep - 9); $m < $l; ++$m)
				$sStr[$i + mt_rand(1, $nStep)] = chr(mt_rand(1, 255));
		}
		return $sStr;
	}

	function getFileStat($sFilename)
	{
		$aRet = array();
		if( !($a = lstat($sFilename)) )
		{
			trigger_error("Fail in $sFilename lstat !", E_USER_ERROR);
			return $aRet;
		}
		$dMode = ($a['mode'] & 0170000) >> 12;
		if(4 == $dMode)//Dir
			$sLn = '.';
		else if(8 == $dMode)//File;
			$sLn = '';
		else if(10 == $dMode)//Symlink
			$sLn = readlink($sFilename);
		else
			return $aRet;

		$aRet['ln'] = $sLn;
		$aRet['ud'] = $a['uid'];
		$aRet['gd'] = $a['gid'];
		$aRet['sz'] = $a['size'];
		$aRet['ct'] = $a['ctime'];
		$aRet['mt'] = $a['mtime'];
		$aRet['pm'] = $a['mode'] & 000777;//file permissions
		return $aRet;
	}

	function isDemo()
	{
		$oFence = new stFence();
		return $oFence->get('demo');
	}

	function checkNotice()
	{//Verify that the directory has not been replaced(cleared) or back to the factory.(reset)
		$sNoticePath = '/var/log/notice/';
		$sNoticeBkPath = file_exists('/h3/etc/version.ini') ? '/h3/reset/notice/' : '/PDATA/vxc/notice/';
		if( !is_dir($sNoticeBkPath) || !($dh = opendir($sNoticeBkPath)) )
			return;
		while(($file = readdir($dh)) !== false)
		{
			if($file == '.' || $file == '..')
				continue;
			$sBkFile = $sNoticeBkPath . $file;
			$sNoticeFile = $sNoticePath . $file;
			if(is_file($sBkFile) && !file_exists($sNoticeFile))
				copy($sBkFile, $sNoticeFile);
		}
		closedir($dh);
	}

//------*** different with ur /ms in follow ! ***
	function coolHdPtd()
	{
		if(file_exists($this->sRegFile))
			return;
		//------ur path/file------
		$this->sPtdFile1 = '/HDD/rrdpic/mem_romf_query';
		$this->sPtdFile2 = '/HDD/mysqlDB/ibl_datafile0';
		$this->sRegFile = '/PDATA/L7FWMODEL/.L7fwA4R';
	}
}

?>