<?
include_once("/PDATA/apache/class/base.php");

class ioFile
{
	var $sMultipleLineKey = '/.*\\+$/';

	function read($sFile, $bUtf8 = false, $sSplitKey = '')
	{
		$aData = array();
		$oUtf8 = new Utf8;
		if( !($fp = $oUtf8->openFile($sFile, $sEncoding, $bUtf8)) )
		{
			if(st_isDebugMode('io.php')) trigger_error("read openFile fail!");
			return $aData;
		}

		while(!feof($fp))
		{
			if( !($aRes = $this->readIn($fp)) )
				continue;
			if($sSplitKey && 'a.' == substr($aRes[0], 0, 2))
				$aData[$aRes[0]] = explode($sSplitKey, $aRes[1]);
			else
				$aData[$aRes[0]] = $aRes[1];
		}
		fclose($fp);
		if(st_isDebugMode('io.php')) trigger_error("read \$aData=" . st_DebugDump2String($aData));
		return $aData;
	}

	function save($sFile, $aData, $bUtf8 = false, $sSplitKey = ',')
	{
		if( !($fp = @fopen($sFile, 'w')) )
		{
			if(st_isDebugMode('io.php')) trigger_error("save openFile fail!");
			return false;
		}
		if($bUtf8)
			fwrite($fp, pack("CCC", 0xef, 0xbb, 0xbf));//Write BOM(Byte Order Mark, U+FEFF)

		foreach($aData as $sIndex => $sValue)
		{
			if(is_array($sValue))
				$sValue = implode($sSplitKey, $sValue);
			fwrite($fp, "$sIndex = $sValue\n");
		}
		fclose($fp);
		return true;
	}

	function readIn($fp, $bMustIndex = true)
	{//Read a line, skip empty, comment or inavlid and return (sName, sValue)
		static $s_sLast = '';
		$str = rtrim(fgets($fp));

		if($bMustIndex && preg_match($this->sMultipleLineKey, $str))
		{//add line for '...\+' (only Index type)
			$s_sLast .= substr($str, 0, strlen($str) - 2);
			return false;
		}
		else if($s_sLast)
		{
			$str = $s_sLast . $str;
			$s_sLast = '';
		}
		if( !($str = trim($str)) || $str[0] == '#' || $str[0] == ';' || substr($str, 0, 2) == '//')
			return false;//Remark
		if($bMustIndex && strpos($str, '=') === false)//strops 返回搜尋字串的第一個位置
			return false;
		$a = explode('=', $str, 2);//在=分割成兩組元素組
		if(count($a) == 1)
			return array(trim($a[0]));
		return array(trim($a[0]), trim($a[1]));
	}
}

class languageFile extends ioFile
{
	var $sPath = '/PDATA/vxc/';
	var $aDefaultSchema = array('big5', 'gb2312', 'eng');
	var $sStrToLowKey = '*';
	
	function getBrowserLanguage()
	{
		include("/h3/etc/language.ini");
		$sLang = $sys_language;
		
		if(isset($_COOKIE['msserver_lang_set'])) {
			include_once("CAdmin.php");
			$admin = new CAdmin;
			if(!$admin->IsRootLogin()) {
				setcookie("msserver_lang_set", "", time() - 3600);
			}
			$sLang = $_COOKIE['msserver_lang_set'];
		} else if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$a = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$aLang = explode('-', strtolower($a[0]), 2);
			switch($aLang[0])
			{
				case 'zh':
					$sLang = (isset($aLang[1]) && $aLang[1] == 'cn'? 'gb2312': 'big5');
					break;
				default:			
					$sLang = 'eng';	
					break;
			}
		}
		return $sLang;
	}
	
	function load($sFile)
	{
		global $sGlobalLang, $aShowFile;
		$aShowFile[] = $this->sPath.$sFile;
		$aLang = $aFiles = array();
		$aLang = $this->loads($sFile, $aLang, $aFiles);
		return $aLang[$sGlobalLang];
	}
	
	function loads($sFile, &$aLang, &$aFiles)
	{
		static $s_aIndexAll = array();//check for duplicate index
		$oUtf8 = new Utf8;
		if(in_array($sFile, $aFiles) || !($fp = $oUtf8->openFile($this->sPath . $sFile, $sEncoding)) )
			return $aLang;
		$aFiles[] = $sFile;//加入載入過語言檔, 避免重複
		$aSchema = $this->aDefaultSchema;
		$this->doLangType($aLang, $aSchema);
		while(!feof($fp))
		{
			if( !($aRes = $this->readIn($fp)) )
				continue;
			$sIndex = $aRes[0];
			$ax = split("\t+", $aRes[1]);
			$a = array();
			foreach($ax as $s)
				$a[] = trim($s);
			switch($sIndex)
			{
			case '[schema]':
				$aSchema = array();
				foreach($a as $s)
					$aSchema[] = strtolower($s);
				$this->doLangType($aLang, $aSchema);
				break;
			case '[include]':
				if(st_isDebugMode('io.php')) trigger_error("Include file: $aRes[1]!");
				foreach($a as $sIncludeFile)
					$this->loads($sIncludeFile, $aLang, $aFiles);
				break;
			default:
				if(st_isDebugMode('io.php'))
				{
					if(isset($s_aIndexAll[$sIndex]))
						trigger_error("[$sIndex]\t\tfind in " . $s_aIndexAll[$sIndex]." & $sFile!", E_USER_WARNING);
					$s_aIndexAll[$sIndex] = $sFile;
				}
				foreach((array)$aSchema as $n => $sLangType)
				{
					$sValue = isset($a[$n]) ? $a[$n] : (isset($a[$n-1]) ? $a[$n-1] : $a[0]);
					$aLang[$sLangType][$sIndex] = $this->parseVar($sValue, $aLang[$sLangType]);
				}
				break;
			}
		}
		fclose($fp);
		return $aLang;
	}

	function idLoad($sFile, &$aLang, &$aFiles)
	{
		$oUtf8 = new Utf8;
		if(in_array($sFile, $aFiles) || !($fp = $oUtf8->openFile($this->sPath . $sFile, $sEncoding, false)) )
			return $aLang;
		$aFiles[] = $sFile;//加入載入過ID檔, 避免重複
		while(!feof($fp))
		{
			if( !($aRes = $this->readIn($fp, false)) )
				continue;

			switch($aRes[0])
			{
			case '[include]':
				if(st_isDebugMode('io.php')) trigger_error("Include file: $aRes[1]!");
				if(isset($aRes[1]))
				{
					$a = split("\t+", $aRes[1]);
					foreach($a as $sFile)
						$this->idLoad(trim($sFile), $aLang, $aFiles);
				}
				break;
			case '[same]':
				if(st_isDebugMode('io.php')) trigger_error("Same file: $aRes[1]!");
				if(isset($aRes[1]))
				{
					$a = split("\t+", $aRes[1]);
					foreach($a as $sFile)
						$this->idLoadByLang(trim($sFile), $aLang);
				}
				break;
			default:
				$aLang[$aRes[0]] = $aRes[count($aRes) > 1 ? 1: 0];
				break;
			}
		}
		fclose($fp);
		return $aLang;
	}

	function idLoadByLang($sFile, &$aLang)
	{
		$oUtf8 = new Utf8;
		if( !($fp = $oUtf8->openFile($this->sPath . $sFile, $sEncoding)) )
			return $aLang;
		while(!feof($fp))
		{
			if( !($aRes = $this->readIn($fp)) )
				continue;
			switch($aRes[0])
			{
			case '[schema]':
			case '[include]':
				break;
			default:
				$aLang[$aRes[0]] = $aRes[0];
				break;
			}
		}
		fclose($fp);
		return $aLang;
	}

	function doLangType(&$aLang, $aSchema)
	{
		foreach((array)$aSchema as $sLangType)
		{
			if(!isset($aLang[$sLangType]))
				$aLang[$sLangType] = array();
		}
	}

	function parseVar($sString, $aLang)
	{//$sString:要處理字串, $aLang:該語系儲存陣列資料
		$sPattern = '/\|(.*)\|/U';
		if(($n = strlen($sString) - 1) > 0)
		{
			$aQuot = array('"', "'");
			$sEnd = $sString[$n];
			foreach($aQuot as $sKey)
				if($sString[0] == $sKey && $sEnd == $sKey)
					$sString = trim($sString, $sKey);
		}
		if(preg_match_all($sPattern, $sString, $aMatches))
		{
			foreach($aMatches[0] as $sVar)
			{
				$sVarName = trim($sVar, '|');
				if($sVarName[0] == $this->sStrToLowKey)
				{
					$bToLow = true;
					$sVarName = substr($sVarName, 1);
				}
				else
					$bToLow = false;

				if(!$sVarName)
					$sString = str_replace($sVar, '|', $sString);// '||' or '| |' -->'|'
				else if(isset($aLang[$sVarName]))
					$sString = str_replace($sVar, $bToLow ? strtolower($aLang[$sVarName]) : $aLang[$sVarName], $sString);
			}
		}
		return $sString;
	}
}

class Utf8
{
	function openFile($sFile, &$sEncoding, $bUtf8Only = true)
	{//檢查檔案編碼, 成功傳回檔案資源, 並給於編碼'UTF-8'或 ''
		if( !($fp = @fopen($sFile, 'r')) )
			return false;
		$sBom = fread($fp, 3);
		if(chr(0xef) == $sBom[0] && chr(0xbb) == $sBom[1] && chr(0xbf) == $sBom[2])
		{//UTF8 BOM(Byte Order Mark, U+FEFF)
			if(st_isDebugMode('io.php')) trigger_error("openFile read UTF8 BOM!");
			$sEncoding = 'UTF-8';
			return $fp;
		}
		else if(0xff == ord($sBom[0]) && 0xfe == ord($sBom[1]) )
		{//UTF-16 BOM
			if(st_isDebugMode('io.php')) trigger_error("openFile read UTF-16 BOM!");
			fclose($fp);
			return false;//Not support to parse
		}
		fseek($fp, 0);
		$sStr = fread($fp, 512);
		fseek($fp, 0);
		if(!($sEncoding = mb_detect_encoding($sStr, 'auto')) && $bUtf8Only)
		{//Not UTF-8
			if(st_isDebugMode('io.php')) trigger_error("openFile Not UTF-8 file!");
			fclose($fp);
			return false;//Not support to parse
		}
		return $fp;
	}

	function writeFile($sFile)
	{//Write utf8 file with BOM
		if( !($fp = @fopen($sFile, 'w')) )
			return false;
		fwrite($fp, pack("CCC", 0xef, 0xbb, 0xbf));//Write BOM(Byte Order Mark, U+FEFF)
		return $fp;
	}
}


//---------------------------------------------------------
//變數(複雜陣列)內容直接與檔案存取
//serialize2File($sFile, $aData, false);//將 $aData 寫入到 $sFile檔案 
//serialize2File($sFile, $aData, true);//將 $sFile檔案載入到 $aData
//
//注意浮點數(float)會儲存 52個字元，建議以字串形式儲存較省空間
//$f = 1.23;
////Save=d:1.229999999999999982236431605997495353221893310546875
////Save=s:4:"1.23"
//$a[] = (string)$f;

function serialize2File($sFile, &$aData, $bIsLoading)
{
	if($bIsLoading)
	{//Loading
		$aData = unserialize(file_get_contents($sFile));
	}
	else
	{//Storing
		return file_put_contents($sFile, serialize($aData));
	}
}

if(!function_exists('file_put_contents'))
{//for ur (php 4.x no file_put_contents function)
	function file_put_contents($sFilename, $sContents)
	{
		if(!($fp = fopen($sFilename, "w")))
			return false;
		fwrite($fp, $sContents);
		fclose($fp);
		return true;
	}
}

class stFence
{
	var $sFenceFile = '/PDATA/L7FWMODEL/fence.conf';
//Deadline = [unix time stamp(GMT)]
//demo = 1 //[trial]
//sysUpd.expire = [unix time stamp(GMT)]

	function stFence()
	{
		global $g_ioFile;
		if(!isset($g_ioFile))
			$g_ioFile = new ioFile();//for ur
	}
	function get($mLimit = '')
	{
		global $g_ioFile;
		$aData = $g_ioFile->read($this->sFenceFile);
		if(empty($mLimit))
			return $aData;
		if(isset($aData[$mLimit]))
			return $aData[$mLimit];
		return false;
	}

	function del($sLimit)
	{
		global $g_ioFile;
		$aData = $g_ioFile->read($this->sFenceFile);
		if(isset($aData[$sLimit]))
		{
			unset($aData[$sLimit]);
			$g_ioFile->save($this->sFenceFile, $aData);
		}
	}

	function set($mLimit, $mValue = '')
	{
		global $g_ioFile;
		if(is_array($mLimit))
			$aData = $mLimit;
		else
		{
			$aData = $g_ioFile->read($this->sFenceFile);
			$aData[$mLimit] = $mValue;
		}
		$g_ioFile->save($this->sFenceFile, $aData);
	}

	function getTime($sLimit)
	{//$sLimit: Deadline, sysUpd.expire, ...
		if($tTimestamp = $this->get($sLimit))
			return $this->greenwichTime($tTimestamp, $ToGreenwich=false);
		return 0;
	}

	function setTime($sLimit, $tTimestamp)
	{//$sLimit: Deadline, sysUpd.expire, ...
		$this->set($sLimit, $this->greenwichTime($tTimestamp, $ToGreenwich=true));
	}

	function isExpire($sLimit)
	{//$sLimit: Deadline, sysUpd.expire, ...
		if($tTimestamp = $this->getTime($sLimit))
			return time() > $tTimestamp;
		return false;//not set and no limit
	}

	function greenwichTime($tTime, $ToGreenwich)
	{//$ToGreenwich=true: $tTime to GMT, false: $tTime to local
		if($ToGreenwich)
			return $tTime - (int)date('Z');//from local time to Greenwich time
		return $tTime + (int)date('Z');//Greenwich to local
	}
}

$oLanguageFile = new languageFile;
$sGlobalLang = $oLanguageFile->getBrowserLanguage();
?>