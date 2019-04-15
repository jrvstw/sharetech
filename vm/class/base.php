<?
//include_once("base.php");
//$nst_ErrorHandler = 2;//Output mode combine: File log(1)| Echo(2)| Trace(4); (default=5, file log+trace)
//$nst_DebugMode = 0;//User debug level: Notice(0), Warning(1), Error(2 default); [Debug file = '/tmp/phpdebug.on']
//Debug file '/tmp/phpdebug.on': 1st line=debug level; 2nd line=output mode; After 2nd line=debug php file(s)
//trigger_error("My Notice!");//Notice
//trigger_error("My Warning!", E_USER_WARNING);
//trigger_error("My Error!", E_USER_ERROR);
$ast_ErrExceptHandlers = array();//ex: ('my.php' => 'my_except1'), or  ('my.php' => 'myclass::my_except1')
$ast_ErrorLogFunctions = array();//ex: ('my_func1',), or  ('myclass::my_func1',)

if(file_exists('/h3/etc/version.ini')) include_once('postfix_system.ini');
set_error_handler('st_ErrorHandler');
st_DebugMsgInitStatus();//Initial loading debug mode status

function st_isDebugMode($debugFile)
{//Is $debugFile in Debug php file(s) ?
	global $ast_ErrorLogFiles;
	if(count($ast_ErrorLogFiles) < 1)
		return false;
	if(($i = strrpos($debugFile, '/')) !== false)
		$debugFile = substr($debugFile, $i + 1);
	return in_array($debugFile, $ast_ErrorLogFiles);
}

function st_DebugMsgDefaultStatus()
{
	global $nst_DebugMode, $nst_ErrorHandler, $ast_ErrorLogFiles;
	$nst_DebugMode = 2;//User debug level: Notice(0), Warning(1), Error(2 default)
	$nst_ErrorHandler = 1;//Output mode combine: File log(1)| Echo(2)| Trace(4); (default=5, file log)
	$ast_ErrorLogFiles = array();
}
function st_DebugMsgInitStatus()
{//Initial or reloading debug mode status
	$sDebugFile = '/tmp/phpdebug.on';//1st line=debuglevel; 2nd line=output combine; After 2nd line=debug php file(s)
	global $nst_DebugMode, $nst_ErrorHandler, $ast_ErrorLogFiles;
	static $nDebugFileDate = 0;
	clearstatcache();//for file_exists(), filemtime() to get new stat.
	if(file_exists($sDebugFile) && ($fp = @fopen($sDebugFile, 'r')) )
	{
		if( ($nDate = filemtime($sDebugFile)) <= $nDebugFileDate)
		{
			if($fp)
				fclose($fp);
			return;//file not update
		}
		$nDebugFileDate = $nDate;
	}
	else
	{
		st_DebugMsgDefaultStatus();
		return;
	}

	st_DebugMsgDefaultStatus();
	for($n = 0; !feof($fp); $n++)
	{
		if('' === ($s = trim(fgets($fp))))
			continue;
		$nVal = intval($s);
		switch($n)
		{
			case 0://user debug level
				$nst_DebugMode = $nVal;
				break;
			case 1://output mode
				$nst_ErrorHandler = $nVal;
				break;
			default://monitor debug php file(s)
				$ast_ErrorLogFiles[] = $s;
				break;
		}
	}
	fclose($fp);
}

function st_DebugNow($sDeubgFile, $nMode, $nOutput)
{
	global $nst_ErrorHandler, $nst_DebugMode, $ast_ErrorLogFiles;
	if($nOutput !== false) $nst_ErrorHandler = $nOutput;
	if($nMode !== false) $nst_DebugMode = $nMode;
	if($sDeubgFile !== false) $ast_ErrorLogFiles[] = basename($sDeubgFile);
}

function st_DebugDump2String($mMixVar, $nMaxDeep = 3)
{//dump $mMixVar(any type) to a string, $nMaxDeep=(if) array max deep dimension
	if(is_array($mMixVar))
		return st_DebugDumpArray2String($mMixVar, $nMaxDeep);
	else if(is_string($mMixVar))
		return "'$mMixVar'";
	else if($mMixVar === true)
		return '<true>';
	else if($mMixVar === false)
		return '<false>';
	return $mMixVar;
}

function st_DebugDumpArray2String($aArray, $nMaxDeep)
{//-===Private===-dump $aArray to a string, $nMaxDeep=array max deep dimension
	$sRet = '{';
	$bInit = true;
	foreach($aArray as $sKey => $mMix)
	{
		if(is_array($mMix))
		{
			if($nMaxDeep > 0)
				$mMix = st_DebugDumpArray2String($mMix, $nMaxDeep - 1);
			else
				$mMix = '<Array>';
		}
		else
			$mMix = st_DebugDump2String($mMix);

		if($bInit)
			$bInit = $sPrefix = '';
		else
			$sPrefix = ',';
		$sRet .= "$sPrefix$sKey=$mMix";
	}
	return $sRet . '}';
}

function st_ErrorHandler($errno, $errstr, $errfile, $errline)
{//-===Private===-
	$sErrLogFile = '/var/log/phpErr.log';
	$aErrno = array(E_ERROR => 'Error', E_WARNING => 'Warning', E_NOTICE => 'Notice', E_PARSE => 'Parse error'
			, E_CORE_ERROR => 'Core error', E_CORE_WARNING => 'Core warning', 0 => 'Other Error'
			, E_USER_ERROR => '*E', E_USER_WARNING => '*W', E_USER_NOTICE => '*N');
	global $nst_ErrorHandler, $nst_DebugMode, $ast_ErrorLogFiles, $ast_ErrExceptHandlers, $ast_ErrorLogFunctions;

	$aErrs = array(E_ERROR, E_CORE_ERROR, E_PARSE, E_USER_ERROR);
	$aWarnings = array(E_WARNING, E_CORE_WARNING, E_USER_WARNING);

	$debugFile = ($i = strrpos($errfile, '/')) !== false ? substr($errfile, $i + 1) : $errfile;
	$sError = (isset($aErrno[$errno]) ? $aErrno[$errno]: $aErrno[0]);
	$sFile = $errfile ? "$debugFile($errline) " : '';
	$sErrorMsg = "$sFile$sError: $errstr";

	if(!empty($ast_ErrExceptHandlers[$debugFile]) && $errno != E_USER_ERROR  && $errno != E_USER_WARNING  && $errno != E_USER_NOTICE)
	{//$ast_ErrExceptHandlers ex: ('my.php' => 'my_except1'), or  ('my.php' => 'myclass::my_except1')
		st_ErrorHandlerExcept($ast_ErrExceptHandlers[$debugFile], array('nEerrno' => $errno, 'sMsg' => $errstr, 'nLine' => $errline));
	}

	if(in_array($errno, $aErrs))
		$nErrorNo = 2;
	else if(in_array($errno, $aWarnings))
		$nErrorNo = 1;
	else
		$nErrorNo = 0;
	if($nErrorNo < $nst_DebugMode)
		return true;// Error type lower than the debug level, No output.
	if($errfile && count($ast_ErrorLogFiles) > 0)
	{//Debug file filter
		if(!in_array($debugFile, $ast_ErrorLogFiles))
			return true;// Error/Warning is non-debug php file, No output.
	}
	if($ast_ErrorLogFunctions)
	{
		$aTrace = debug_backtrace();
		for($bFunc = false, $n = count($aTrace) - 1; $n > -1; --$n)
		{
			if(isset($aTrace[$n]['class']) && isset($aTrace[$n]['type']))
				$aTrace[$n]['function'] = $aTrace[$n]['class'] . $aTrace[$n]['type'] . $aTrace[$n]['function'];
			if(in_array($aTrace[$n]['function'], $ast_ErrorLogFunctions))
			{
				$bFunc = true;
				break;
			}
		}
		if(!$bFunc) return true;
	}
	if($nst_ErrorHandler & 4)
	{//trace
		$aTrace = debug_backtrace();
		$sErrorMsg = $sErrorMsg . ' >';
		for($n = count($aTrace) - 1; $n > -1; --$n)
		{
			if('st_ErrorHandler' != $aTrace[$n]['function'] && 'trigger_error' != $aTrace[$n]['function'])
			{
				if(isset($aTrace[$n]['class']) && isset($aTrace[$n]['type']))
					$aTrace[$n]['function'] = $aTrace[$n]['class'] . $aTrace[$n]['type'] . $aTrace[$n]['function'];
				$sErrorMsg .= sprintf('> %s[%d]%s() ', basename($aTrace[$n]['file']), $aTrace[$n]['line'], $aTrace[$n]['function']);
			}
		}
	}
	$sErrorMsg .= "\n";

	$sErrorMsg = date('m-d H:i:s ') . '[' .getmypid(). '] ' . $sErrorMsg;
	if(2 == ($nst_ErrorHandler & 2))
		echo $sErrorMsg;
	if(1 == ($nst_ErrorHandler & 1))
	{
		global $sst_SepcErrLogFile;
		if($fp = @fopen($sErrLogFile, 'a+'))
		{
			fwrite($fp, $sErrorMsg);
			fclose($fp);
		}
		if(!empty($sst_SepcErrLogFile) && ($fp = @fopen($sst_SepcErrLogFile, 'a+')) )
		{
			fwrite($fp, $sErrorMsg);
			fclose($fp);
		}
	}
	return true;//Don't execute PHP internal error handler ?
}

function st_ErrorHandlerExcept($sErrExceptFunc, $aErr)
{//$aErr = ['nEerrno', 'sMsg', 'nLine']
	$aTrace = debug_backtrace();
	$sErrorMsg = $sErrorMsg . ' >';
	for($n = count($aTrace) - 1; $n > -1; --$n)
	{
		if(empty($aTrace[$n]['function']))
			continue;
		$sFunc = $aTrace[$n]['function'];
		if('st_ErrorHandler' == $sFunc || 'trigger_error' == $sFunc || 'st_ErrorHandlerExcept' == $sFunc)
			continue;
		$aErr['function'] = $sFunc;
		break;
	}
	$aFunc = explode('::', $sErrExceptFunc, 2);
	if(count($aFunc) == 1)
	{
		if(function_exists($sErrExceptFunc))
			return call_user_func($sErrExceptFunc, $aErr);
	}
	else if(method_exists($aFunc[0], $aFunc[1]))
		return call_user_func($aFunc, $aErr);
	return false;
}

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// stChallengeKeys class for challenge key define
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//$sMyKey = stChallengeKeys::_key('my.salt & param & special(I like)', '', $nTimeStep=24 * 10);//get challenge key by(your param value, ['' for get mode], 10 days expire)
//if(stChallengeKeys::_key('my.salt & param & special(I like)', $sChallengeKey, $nTimeStep=24 * 10))//test challenge key ($sChallengeKey)
//stChallengeKeys::_DES($mData, $bEncrypt=true|false);
//stChallengeKeys::_DESb64($mData, $bEncrypt=true|false);
class stChallengeKeys
{
	var $sSalt1 = 'UipmJLp:xR~>|c.4hlgealc{^kug)z9R';
	var $sSalt2 = '/pN*#$p?G/XvWQ?=A\'t{m|7]!Y2=&,k~^@*:D';
	var $sSaltIv = '8#U:i_D^KxaV-d{%E+ZW)Y3k\'Mar}CbKZ0ivjcDg';
	var $aJumpList = array(3 => 19, 8 => 3, 11 => 16, 15 => 32);

	function _getRandkey($nSize = 16)
	{//$nSize: rand key size.
		for($sValue = '', $n = 0; $n < $nSize; ++$n)
			$sValue .= sprintf('%02x', mt_rand(0, 255));
		return $this->getNewValue($sValue);
	}

	function getRand32Key($nSize = 16)
	{
		$aRands = array(0x1f, 0xfffff, 0x3fffffff, 0x1f, 0x1ffffff, 0x3fffffff, 0x1f, 0x1ffffff, 0xfffff, 0,);
		$nUpsize = count($aRands);
		$sRandKey = '';
		for($n = 0; ; ++$n)
		{
			if( !($v = $aRands[$n % $nUpsize]) )
				$v = mt_rand(1, time());
			else
				$v = mt_rand(0, $v);
			if(strlen($sRandKey .= base_convert($v, 10, 32)) >= $nSize)
				return substr($sRandKey, 0, $nSize);
		}
	}

	function _key($sParam, $sKey='', $nTimeStep = 0, $nShiftTimeKey = 352956757, $nShiftStepKey = 27483647)
	{//$sParam: special value; $sKey: challenge key, or '' get key for $sParam; $nTimeStep: valid range(hour), 0 mean all valid.
		if($nShiftTimeKey > 352956757 || $nShiftTimeKey < 0) $nShiftTimeKey = 352956757;
		if($nShiftStepKey > 27483647 || $nShiftStepKey < 0) $nShiftStepKey = 27483647;
		if($sKey)
		{
			list($sKey, $nTimeStep) = $this->mix($sKey);
			$nTimeStep = $this->timeStepEnc($nTimeStep, $bEncode=false, $nShiftStepKey);
		}
		$aSaltTick = $this->getTimeTick($nTimeStep, $nShiftTimeKey);
		$sChallengeKeys = $this->getNewValue(md5($aSaltTick[0] . md5( md5($this->sSalt1 . $sParam) . $this->sSalt2) ));
		$sChallengeKeys2 = $this->getNewValue(md5($aSaltTick[1] . md5( md5($this->sSalt1 . $sParam) . $this->sSalt2) ));
		if($sKey)
			return ($sChallengeKeys === $sKey || $sChallengeKeys2 === $sKey);//challenge key now
		$sTimeStepEnc = $this->timeStepEnc($nTimeStep, $bEncode=true, $nShiftStepKey);
		return $this->mix($sChallengeKeys, $sTimeStepEnc);//new challenge key
	}
	function timeStepEnc($nTimeStep, $bEncode, $nShiftStepKey)
	{
		$aXorKeys = array(
			0xb69441, 0xbd9955, 0x9a2d55, 0x534016, 0x6f002b, 0xc583ee, 0x5371f5, 0x836887, 0xd9f306, 0x76fa44, 0x00901e, 0x6d83f3, 0xbafd32, 0xa4a082, 0xfff035,
			0xd6c472, 0xf6cf9b, 0x7b8c37, 0xc4f5f2, 0xc84391, 0x4829d6, 0xd544f6, 0x6e6bc1, 0xc27b66, 0xd8b066, 0x468cc9, 0x7078fb, 0xd82fb6, 0x37e32b, 0x811381,
			0x963781, 0x634730, 0x154977, 0x6ebe0c, 0x378652, 0x3b56c5, 0xec722b, 0x9bd5a3, 0xbf5074, 0x1395d3, 0x530faa, 0x997695, 0x840699, 0x2df373, 0xc4f5b2,
			0x47a314, 0x0aad01, 0xe35653, 0xe3a716, 0x7b0c3d, 0xac4a44, 0x69468c, 0x7dfc2d, 0x677c75, 0x0c4dd8, 0xbd923f, 0x2a8386, 0x029cc8, 0x529a37, 0x30b33b,
			0x566bd4, 0xca5c66, 0xb5543b, 0x230b39, 0x890f13, 0x4d2c45, 0x566d1e, 0x820938, 0x2efd35, 0x59481b, 0xdc89b8, 0x064e50, 0x5cde99, 0xee2e4d, 0xd3a988,
			0x65045b, 0x3c3108, 0xe37b11, 0x0af934, 0xaaeac5, 0x103c7f, 0x4bcc45, 0x666bce, 0xa6a1b0, 0x523d54, 0x88e553, 0xef6ccb, 0x1578ec, 0x5d6df5, 0x947738,
			0x801785, 0x954114, 0x4623d1, 0xefe76a, 0xb11783, 0x31ab8f, 0xe65b20, 0x692a22, 0xa9013e, 0x719f05, 0xbf6bef, 0x0a895d, 0xae84da, 0x6dbca2, 0xf8f55b,
		);
		$nXorKeysSize = count($aXorKeys);
		$sDebugTitleMsg = sprintf("timeStepEnc(t=%d, %s, s=%d):", $nTimeStep, $bEncode ? 'enc': 'dec', $nShiftStepKey);
		if($bEncode)
		{
			$nRand = mt_rand(0, 255);
			$nTimeStep += $nShiftStepKey;
			$sEnc = sprintf('%02x%08x', $nRand, $nTimeStep ^ $aXorKeys[$nRand % $nXorKeysSize]);
			if(st_isDebugMode('base.php')) trigger_error("$sDebugTitleMsg rand $nRand=$sEnc");
			return $sEnc;
		}
		$nRand = hexdec(substr($nTimeStep, 0, 2));
		$nTimeStep = (hexdec(substr($nTimeStep, 2)) ^ $aXorKeys[$nRand % $nXorKeysSize]);
		$nDec = $nTimeStep - $nShiftStepKey;
		if(st_isDebugMode('base.php')) trigger_error("$sDebugTitleMsg rand $nRand=$nDec");
		return $nDec;
	}
	function mix($sChallengeKeys, $sEncTimeStep = '')
	{//$sEncTimeStep: ''= get time step, other to mix
		$aMixPos2 = array(3 => 1, 6 => 3, 12 => 4, 17 => 6, 23 => 8, 28 => 10);//MUST: max index < 32, end value = 10
		$n1 = $n2 = 0;
		$sMix = $sDebugMsg = '';
		if($sEncTimeStep)
		{
			$n1 = $n2 = 0;
			foreach($aMixPos2 as $nPos1 => $nPos2)
			{
				$sMix .= substr($sChallengeKeys, $n1, $nPos1 - $n1) . substr($sEncTimeStep, $n2, $nPos2 - $n2);
				$sDebugMsg .= substr($sChallengeKeys, $n1, $nPos1 - $n1) . ' ' . substr($sEncTimeStep, $n2, $nPos2 - $n2) . ' ';
				$n1 = $nPos1;
				$n2 = $nPos2;
			}
			//trigger_error('stChallengeKeys::mix() mix=' . $sDebugMsg . substr($sChallengeKeys, $n1));
			return ($sMix . substr($sChallengeKeys, $n1));
		}
		foreach($aMixPos2 as $nPos1 => $nPos2)
		{
			$sMix .= substr($sChallengeKeys, $nPos1 + $n2, $nPos2 - $n2);
			$sEncTimeStep .= substr($sChallengeKeys, $n1 + $n2, $nPos1 - $n1);
			$n1 = $nPos1;
			$n2 = $nPos2;
		}
		return array($sEncTimeStep . substr($sChallengeKeys, $n1 + $n2), $sMix);
	}

	function mixkey($sKeys, $aKeys, $bMix = true)
	{//
		$sRetKeys = $sKeys;
		$nMax = strlen($sKeys);
		foreach($aKeys as $nSrc => $nTarget)
		{
			if($nSrc < $nMax && $nTarget < $nMax)
				$sRetKeys[$bMix ? $nSrc : $nTarget] = $sKeys[$bMix ? $nTarget : $nSrc];
		}
		return $sRetKeys;
	}
	function getTimeTick($nTimeStep, $nShiftTimeKey)
	{
		if($nTimeStep < 1) return array('~.xV!', '~.xV!');
		list($nHour, $nMonth, $nDay, $nYear) = explode('-', $sNow = date('H-m-d-Y', time() - date('Z')));
		$nTimeStep *= 3600;
		$nTimeTick = mktime($nHour, 0, 0, $nMonth, $nDay, $nYear);
		$nTimeTick = floor($nTimeTick / $nTimeStep) * $nTimeStep - $nShiftTimeKey;
		$sTimeTick = sprintf('%x', $nTimeTick);
		$nTimeTick -= $nTimeStep;
		$sTimeLastTick = sprintf('%x', $nTimeTick);
		if(st_isDebugMode('base.php')) trigger_error("getTimeTick($nTimeStep, $nShiftTimeKey) [$sTimeTick]-[$sTimeLastTick]");
		if(($l = strlen($sTimeLastTick) - 1) > 2) 
			return array(substr($sTimeTick, 0, $l), substr($sTimeLastTick, 0, $l));
		return array($sTimeTick, $sTimeLastTick);
	}
	function getNewValue($sValue)
	{
		$sMagicList = 'sFw5fK7d6Gr8Dkz4xWSZypTaLHNgPn9mBRAXQMVo3uhE2YeCtjbJivqc';
		$nMax = strlen($sMagicList);
		$nPos = 0;
		$sNewValue = '';
		for($n = 0, $l= strlen($sValue), $sLast = ''; $n < $l; ++$n)
		{
			if(isset($this->aJumpList[$n]))
				$sNewValue .= $sMagicList[($nPos += $this->aJumpList[$n]) % $nMax];
			if( ($sChr = $sMagicList[($nPos += hexdec($sValue[$n])) % $nMax]) == $sLast)
				$sNewValue .= $sMagicList[++$nPos % $nMax];
			else
				$sNewValue .= $sChr;
			$sLast = $sChr;
		}
		return $sNewValue;
	}

	function transSize(&$mValue)
	{
		if(is_int($mValue))
			return pack('V', $mValue);
		$a = unpack('V', $mValue);
		$mValue = substr($mValue, 4);
		return $a[1];
	}
	function _DES($mData, $bEncrypt, $sSalt = '', $nTight = 0)
	{//Encrypt: stChallengeKeys::_DES(plainTextToEncrypt, $bEncrypt=true);
	//Decrypt: stChallengeKeys::_DES(toDecryptData, $bEncrypt=false);
		$aTights = array(
			array(MCRYPT_DES , 'ecb'), //0=des
			array(MCRYPT_RIJNDAEL_128 , 'cbc'), //1=rijndael-128
			array(MCRYPT_RIJNDAEL_128 , 'ofb'), //2=rijndael-128
			array(MCRYPT_RIJNDAEL_192 , 'cbc'), //3=rijndael-192
			array(MCRYPT_RIJNDAEL_192 , 'ofb'), //4=rijndael-192
			array(MCRYPT_RIJNDAEL_256 , 'ofb'), //5=rijndael-256
			array(MCRYPT_BLOWFISH , 'cbc'), //6=blowfish
			array(MCRYPT_BLOWFISH , 'ofb'), //7=blowfish
		);
		if($nTight < 0) $nTight = 0;
		else if($nTight > ($n = count($aTights) - 1)) $nTight = $n;
		$sKey = $sSalt . $this->sSalt2;
		$td = mcrypt_module_open($aTights[$nTight][0], '', $aTights[$nTight][1], '');
		//if(strlen($sKey) > ($l = mcrypt_enc_get_key_size($td)))
		$sKey = substr($sKey, 0, mcrypt_enc_get_key_size($td));
		$iv_size = mcrypt_enc_get_iv_size($td);
		if(0 == $nTight)
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		else
			$iv = substr($this->sSaltIv, 0, $iv_size);
		if(mcrypt_generic_init($td, $sKey, $iv) == -1)
		{
			trigger_error(sprintf('stChallengeKeys::_DES() use [%s], mode [%s] Fail !!', $aTights[$nTight][0], $aTights[$nTight][1]), E_USER_ERROR);
			return false;
		}
		else if(st_isDebugMode('base.php')) trigger_error(sprintf('stChallengeKeys::_DES() use [%s], mode [%s]', $aTights[$nTight][0], $aTights[$nTight][1]), E_USER_ERROR);
		if($bEncrypt)
		{//Encrypt data
			$nSize = strlen($mData);
			$sSize = $this->transSize($nSize);
			$mData = mcrypt_generic($td, $sSize . $mData);
		}
		else
		{//Decrypt data
			$mData = mdecrypt_generic($td, $mData);
			$nSize = $this->transSize($mData);
			$mData = substr($mData, 0, $nSize);
		}
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return $mData;
	}
	function _DESb64($mData, $bEncrypt, $sSalt = '', $nTight = 0)
	{
		if($bEncrypt)
			return $this->_base64url($this->_DES($mData, $bEncrypt, $sSalt, $nTight), $bEncrypt);
		return $this->_DES($this->_base64url($mData, $bEncrypt), $bEncrypt, $sSalt, $nTight);
	}
	function _base64url($mData, $bEncrypt)
	{
		$a64 = array('+', '/', '=');
		$a64Url = array('-', '_', '');
		if($bEncrypt)
			return str_replace($a64, $a64Url, base64_encode($mData));

		//Decrypt
		$sBase64 = str_replace($a64Url, $a64, $mData);
		if($n = 4 - (strlen($sBase64) % 4))
			$sBase64 .= str_repeat('=', $n);
		return base64_decode($sBase64);
	}
}

?>