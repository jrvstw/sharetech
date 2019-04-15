<?
include_once("base.php");
include_once("io.php");
include_once("XorFileHeadEncode.php");
include_once('dsSetup.php');
include_once('dsSyncs.php');
include_once('dsUpd.php');
include_once('dsSyncStateLog.php');
include_once('Bitwise.php');

$oChallengeKeys = new stChallengeKeys;

class stNetxPack
{
	var $aTarget;
	var $sData = '';
	var $sReceiveData = '';
	var $aError = array();
	var $aRunStepTime = array();

	var $rSock = null;
	var $nId = 0;
	var $nWaitId = -1;
	var $sTargetData = '';
	var $nMaxStep = 5;
	var $oDsSync;
	var $sLastedAccpet;
	var $bStartTestRemote = false;

	var $aWaitSends = array('nStep' => 0, 'nDataFlag' => 0, 'nPos' => 0);

	var $nAccMoveNowInitialStep = 100;
	var $bDataCompress = true;
	var $nTimeout = 60;
	var $nDataItemMaxSize = 0xf700;
	var $sLocalAppName = '.';
	var $sFwrpcSock = '/tmp/fwrpc.sock';
	var $sAuthAccount = 'dsMs.fr';
	var $aWaitSendCodes = array(
			'bDataSend' => 0x01, 'bDataStart' => 0x02, 'bDataEnd' => 0x04, 'bCompresss' => 0x08, 'bStepEnd' => 0x40, 'bWaitStepEndAccept' => 0x80,
		);
	var $nClearDataTmpFlag;
	var $aCodes = array(
		'IP' => 0x02, 'Port' => 0x03, 'ApName' => 0x04, 'SrcIP' => 0x06, 'SrcPort' => 0x07, 'SrcApName' => 0x08, 'Data' => 0x10, //Command
		'Set' => 0x20, 'MyApname' => 0x21, 'Timeout' => 0x22, 'Interval' => 0x23, 'Account' => 0x28, 'Password' => 0x29, //Set
		'r_Accept' => 0x80, 'r_InvalidCommand' => 0x85, 'r_ErrorMsg' => 0x86, 'r_OK' => 0xa0, 'r_ApRetFail' => 0xa1, //Return
		'r_ConnectFwrpcFail' => 0xa2, 'r_ConnectApFail' => 0xa3, 'r_Timeout' => 0xa4, 'r_InvalidData' => 0xa5, 'r_InvalidTarget' => 0xa6, 'r_AuthFail' => 0xa7, //Return
		'ExecShellCmd' => 0x41, 'Disconnect' => 0x42, 'ConnectTest' => 0x12,
		);
	var $aCodeType = array(//cmd byte ushort ulong float double string binary
		'IP' => 'ip', 'Port' => 'uport', 'ApName' => 'string', 'SrcIP' => 'ip', 'SrcPort' => 'uport', 'SrcApName' => 'string', 'Data' => 'binary', //Command
		'Set' => 'cmd', 'MyApname' => 'string', 'Timeout' => 'ulong', 'Interval' => 'ushort', 'Account' => 'string', 'Password' => 'string', //Set
		'r_Accept' => 'cmd', 'r_InvalidCommand' => 'cmd', 'r_ErrorMsg' => 'string', 'r_OK' => 'byte', 'r_ApRetFail' => 'byte', //Return
		'r_ConnectFwrpcFail' => 'byte', 'r_ConnectApFail' => 'byte', 'r_Timeout' => 'byte', 'r_InvalidData' => 'byte', 'r_InvalidTarget' => 'byte', 'r_AuthFail' => 'byte', //Return
		'ExecShellCmd' => 'none', 'Disconnect' => 'none', 'ConnectTest' => 'none',
		);
	var $nClientFirstStepWait = 1200000;//1000000 = 1 sec
	var $sInitialStampFile = '/ram/tmp/dsSyncs.now';
	var $sInitialChkStampFile = '/ram/tmp/dsSyncs.now.chk';
	var $sRemoteAppFailStampFile = '/ram/tmp/dsSyncs.now.fail';

	function isLocalApp() { return $this->aTarget['ApName'] == $this->sLocalAppName;}
	function stNetxPack($sHostname = '')
	{
		$this->aError['bError'] = false;
		$this->aRunStepTime[0] = time();
		if(!$sHostname)
		{//run localhost mode
			$this->aTarget = array('ApName' => $this->sLocalAppName, 'ConnectPw' => '', 'TargetIP' => '');
			return;
		}
		$this->getSyncNowStatus($sHostname);
		$this->setSyncNowStatus();
		$oRm = new dsRemotemap();
		$this->aTarget = $oRm->getLocalConnectInfo();
		//{ApName TargetIP}+SingleConnectInfo: HostName SyncDataPort0 SyncDataPort1 ConnectPw useCommSendMailPort bParentt bLinkFromMain SyncDataAddr
		//id SendMailAddr SendMailPort Enable OutgoingMailFromParent nFlag bAutoMoveAcc UseMx bSyncDataEqualSendMail useCommSyncDataPor note
		$this->nClearDataTmpFlag = ~($this->aWaitSendCodes['bDataStart']);
		$this->aTarget['ApName'] = $sHostname;
		$this->aTarget['bClient'] = false;
		$aConnect = array();
		if($this->aTarget['bParent'])
		{
			$aAlls = $oRm->getNeedConnectInfo();
			foreach($aAlls as $aConnect)
			{//find $sHostname=HostName
				if(!empty($aConnect['HostName']) && $aConnect['HostName'] == $sHostname)
				{
					$this->aTarget['HostName'] = $aConnect['HostName'];
					break;
				}
			}
			$this->aTarget['bClient'] = !empty($aConnect['bLinkFromMain']);
			$this->oDsSync = new ds_syncs_set($aConnect);
		}
		else
		{
			$this->bStartTestRemote = true;
			$this->oDsSync = new ds_syncs_set(array());
			$aAlls = $oRm->getNeedConnectInfo();//$bCheckLinkFromMain=true
			if($aAlls)
			{
				foreach($aAlls as $a)
					$aConnect = $a;
				$this->aTarget['bClient'] = empty($this->aTarget['bLinkFromMain']);
			}
		}
		if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::__construct() bClient = " .$this->aTarget['bClient']);
		if($this->aTarget['bClient'])
		{
			if($sIP = $this->realIP($aConnect['SyncDataAddr']))
				$this->addRemoteData($aConnect);
			else
				$this->sendError(sprintf("Error: dns query %s fail !", $aConnect['SyncDataAddr']));
		}
	}
	function sendError($sErr, $bReturn = false)
	{
		$this->aError['bError'] = true;
		$this->aError['sErrMsg'] = $sErr;
		$this->aWaitSends['nStep'] = $this->nMaxStep + 1;
		$this->preSendData();
		return $bReturn;
	}
	function init($bAccMoveNow = false, $bRunGetNewMail = false)
	{
		if($this->aError['bError'])
			return false;
		if( !($this->rSock = stream_socket_client('unix://' . $this->sFwrpcSock, $errno, $errstr, 15, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT)) )
			return $this->sendError(sprintf("Error: initial stream_socket_client fail $errstr($errno) !"));
		if($this->aTarget['ApName'] != $this->aTarget['HostName'])
		{
			trigger_error(sprintf("stNetxPack::init() fail: [%s] not match local hostnname [%s]", $this->aTarget['ApName'], $this->aTarget['HostName']), E_USER_WARNING);
			return $this->sendError(sprintf("[%s] not match local hostnname [%s]", $this->aTarget['ApName'], $this->aTarget['HostName']));
		}
		stream_set_blocking($this->rSock , 0); //no blocking
		stream_set_timeout($this->rSock , $this->nTimeout);
		$this->send(array('MyApname' => $this->aTarget['ApName'], 'Account' => $this->sAuthAccount, 'Password' => $this->aTarget['ConnectPw'], 'Timeout' => 360), $bSetting=true);
		if(file_exists($this->sRemoteAppFailStampFile))
			@unlink($this->sRemoteAppFailStampFile);
		$this->oDsSync->bAutoMoveAccState = $bRunGetNewMail;
		if($bAccMoveNow)
			$this->nMaxStep = $this->nAccMoveNowInitialStep;
		if($this->aTarget['bClient'])
		{
			if($bAccMoveNow)
				$this->aWaitSends['nStep'] = $this->nAccMoveNowInitialStep;
			else
				$this->aWaitSends['nStep'] = 1;
			usleep($this->nClientFirstStepWait);//wait set MyApname,Account,...
			$this->runStep();
		}

		if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::init() OK !");
		return true;
	}
	function setCompress($bCompress) {$this->bDataCompress = $bCompress;}
	function connectTest($aInfo, $sRemoteApp = '')
	{
		if( !($this->rSock = stream_socket_client('unix://' . $this->sFwrpcSock, $errno, $errstr, 15, STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT)) )
		{
			trigger_error("stNetxPack::init() stream_socket_client: $errstr($errno) !", E_USER_WARNING);
			return false;
		}
		stream_set_blocking($this->rSock , 0); //no blocking
		stream_set_timeout($this->rSock , $this->nTimeout);
		if(st_isDebugMode('stNetx.php')) trigger_error(sprintf('Connect test %s:%s (%s/%s)', $aInfo['SyncDataAddr'], (string)$aInfo['SyncDataPort0']
			, $this->sAuthAccount, $aInfo['ConnectPw']) );
		$this->send(array('MyApname' => '--CONNECT TEST--', 'Account' => $this->sAuthAccount, 'Password' => $aInfo['ConnectPw'], 'Timeout' => 360), $bSetting=true);
		$this->aTarget['bClient'] = true;
		$this->aTarget['TargetIP'] = ip2long($this->realIP($aInfo['SyncDataAddr']));
		$this->aTarget['SyncDataPort0'] = intval($aInfo['SyncDataPort0']);
		if(!isset($this->aTarget['TargetIP'])) return '';
		if($sRemoteApp)
			$aTest = array('IP' => $this->aTarget['TargetIP'], 'Port' => $this->aTarget['SyncDataPort0'], 'ApName' => $sRemoteApp, 'ConnectTest' => '');
		else
			$aTest = array('IP' => $this->aTarget['TargetIP'], 'Port' => $this->aTarget['SyncDataPort0'], 'ConnectTest' => '');
		$this->send($aTest, $bSetting='cmmmand');
		$this->sLastedAccpet = $this->sTargetData = 'r_ConnectFail';
		$this->receive();
		if('r_OK' == $this->sLastedAccpet)
			$this->sLastedAccpet = '';
		return $this->sLastedAccpet;
	}
	function preSendData()
	{
		if($this->aWaitSends['nStep'] > $this->nMaxStep)
			$this->aWaitSends['nDataFlag'] = $this->aWaitSendCodes['bStepEnd'];
		else if(!($this->aWaitSendCodes['bDataSend'] & $this->aWaitSends['nDataFlag']) && $this->sData)
		{
			$this->aWaitSends['nDataFlag'] = $this->aWaitSendCodes['bDataSend'] | $this->aWaitSendCodes['bDataStart'];
			$this->aWaitSends['nPos'] = 0;
		}
		else
			$this->aWaitSends['nDataFlag'] = 0;
		if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetxPack::preSendData() step=%d, DataFlag=0x%x, size=%d", $this->aWaitSends['nStep']
			, $this->aWaitSends['nDataFlag'], strlen($this->sData)));
	}
	function sendData()
	{
		$this->updateTimeStamp();
		if(0 == $this->aWaitSends['nDataFlag'] || $this->aWaitSendCodes['bWaitStepEndAccept'] == $this->aWaitSends['nDataFlag'])
		{
			//if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::sendData() idle." . (time() % 3600));
			if($this->aTarget['ApName'] == $this->sLocalAppName)
				return true;
			clearstatcache();
			if(file_exists($this->sRemoteAppFailStampFile))
			{
				$a = @file($this->sRemoteAppFailStampFile);
				@unlink($this->sRemoteAppFailStampFile);
				$sError = trim($a[0]);
				trigger_error("stNetxPack::sendData() connect remote fail = " . $sError, E_USER_WARNING);
				return $this->sendError($sError);
			}
			return true;//now is idle or wait fwprc accpet return
		}

		$bSendEnd = false;
		if(!$this->sendData1($bSendEnd))
			return false;
		//if(!$this->receive()) return false;
		while(!$bSendEnd)
		{
			if(!$this->sendData1($bSendEnd))
				return false;
			//usleep(250000);
		}
		return true;
	}
	function sendData1(&$bSendEnd)
	{
		$nDataFlag = $this->aWaitSends['nDataFlag'];
		$bSendEnd = true;
		if($this->aWaitSendCodes['bStepEnd'] & $this->aWaitSends['nDataFlag'])
		{//send all step end.
			$oBitxUtls = new bitxUtls;
			$sData = $oBitxUtls->dataTrans($nDataFlag, 'byte', $bLoading=false, $nPos) . serialize($this->aError);
			$this->aWaitSends['nDataFlag'] = $this->aWaitSendCodes['bWaitStepEndAccept'];
		}
		else if($this->aWaitSendCodes['bDataSend'] & $this->aWaitSends['nDataFlag'])
		{
			$sData = substr($this->sData, $this->aWaitSends['nPos'], $this->nDataItemMaxSize);//cut out single to send data
			if($this->bDataCompress && strlen($sData) > 512)
			{//use compress mode
				if(($nSize = strlen($sGzData = gzcompress($sData, 9))) <= $this->nDataItemMaxSize)
				{//compress fit to max data size
					$nSize2 = strlen($sData);
					if(($nSize = $this->nDataItemMaxSize * ($this->nDataItemMaxSize / ($nSize * 1.35))) > 1.2)
					{//can be compress more data to fit max data size
						if($nSize > 78) $nSize = 78;
						$sGzData1 = gzcompress(substr($this->sData, $this->aWaitSends['nPos'], ($nSize1 = $nSize * $this->nDataItemMaxSize)));
						//trigger_error(sprintf("stNetxPack::sendData() gzcompress more=%d < %d ? (%d)", strlen($sGzData1), $this->nDataItemMaxSize, $nSize1));
						if(strlen($sGzData1) <= $this->nDataItemMaxSize)
						{
							$nSize2 = $nSize1;
							$this->aWaitSends['nPos'] += ($nSize1 - $this->nDataItemMaxSize);
							$sGzData = $sGzData1;
							//trigger_error(sprintf("stNetxPack::sendData() gzcompress more %d & move %d", strlen($sGzData1), $this->aWaitSends['nPos']));
						}
					}
					//if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetxPack::sendData() [compress] from %d to %d", $nSize2, strlen($sGzData)));
					$nDataFlag |= $this->aWaitSendCodes['bCompresss'];
					$sData = $sGzData;
				}
			}

			if($this->aWaitSends['nDataFlag'] == $this->aWaitSendCodes['bWaitStepEndAccept'])
				$bSendEnd = true;//for all step end
			if( ($this->aWaitSends['nPos'] += $this->nDataItemMaxSize) >= strlen($this->sData))
				{
					$this->aWaitSends['nDataFlag'] = 0;
					$nDataFlag |= $this->aWaitSendCodes['bDataEnd'];
				}
			else
				$bSendEnd = false;
			//prepare data
			$oBitxUtls = new bitxUtls;
			$sData = $oBitxUtls->dataTrans($nDataFlag, 'byte', $bLoading=false, $nPos)
				. $oBitxUtls->dataTrans($this->aWaitSends['nStep'], 'byte', $bLoading=false, $nPos) . $sData;
			if(st_isDebugMode('stNetx.php'))
				trigger_error(sprintf("stNetxPack::sendData1() pos=%d, size=%d %s", $this->aWaitSends['nPos'], strlen($sData), $bSendEnd ? ' (End)' : ''));
		}
		else
			return false;//something is wrong

		if(!$this->send(array('Data' => $sData)))
			return false;
		if($bSendEnd)
			$this->sData = '';//clear all data
		else
			$this->aWaitSends['nDataFlag'] &= $this->nClearDataTmpFlag;
		return true;
	}

	function receive()
	{
		$this->updateTimeStamp();
		static $sData = '';
		$write = $except = null;
		$fds = array($this->rSock);
		$read_fds = $fds;
		$num = @stream_select($read_fds, $write, $except, $this->nTimeout, $tv_usec = 0);
		if(false === $num)
			return false;
		else if($num < 1)
			return true;//idle
		stream_set_timeout($this->rSock , $this->nTimeout);
		while($sData1 = fread($this->rSock, 0x11000))
		{
			$sData .= $sData1;
			stream_set_timeout($this->rSock , 3);
		}
		if(empty($sData))
		{//nothing
			//if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive() idle!");
			return true;
		}
		if(st_isDebugMode('stNetx.php')) trigger_error('stNetxPack::receive() receive size = ' . strlen($sData));
		$nPos = 0;
		while($nPos !== false)
		{
			if(!$this->receive1($sData, $nPos))
				return false;
		}
		return true;
	}
	function receive1(&$sData, &$nPos)
	{
		if(($nDataSize = strlen($sData)) < ($nPos + 3) )
		{
			$sData = substr($sData, $nPos);
			$nPos = false;
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() packet not end!");
			return true;
		}
		$aRecData = array();
		$oBitxUtls = new bitxUtls;
		$aRecData['bReturn'] = (0x0 != $oBitxUtls->dataTrans($sData, 'byte', $bLoading=true, $nPos));
		$aRecData['nId'] = $oBitxUtls->dataTrans($sData, 'byte', $bLoading=true, $nPos);
		$bSinglePacketEnd = false;
		$nPosInit = $nPos;
		if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetxPack::receive1() [%s packet] id=%d!", $aRecData['bReturn'] ? 'return' : 'data', $aRecData['nId']));
		while(isset($sData[$nPos]))
		{
			if( !($nCmd = $oBitxUtls->dataTrans($sData, 'cmd', $bLoading=true, $nPos)) )
			{//single packet end
				$bSinglePacketEnd = true;
				break;
			}
			if(!($sKey = $this->findCode($nCmd)))
				return $this->sendError(sprintf('Error: invalid data code 0x%x', $nCmd), true);
			$aRecData[$sKey] = $oBitxUtls->dataTrans($sData, $this->aCodeType[$sKey], $bLoading=true, $nPos);
		}
		if(!$bSinglePacketEnd)
		{
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() packet not end!");
			$sData = substr($sData, $nPosInit);
			$nPos = false;
			return true;
		}
		else if($nDataSize > $nPos)
		{
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() data not end!");
		}
		else
		{
			$sData = '';
			$nPos = false;
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() data & packet end!");
		}
		if(!$this->sTargetData && !empty($aRecData['SrcIP']))
		{//add target ip for server
			$this->addRemoteData(array('RemotexIP' => $aRecData['SrcIP'], 'SrcPort' => $aRecData['SrcPort']));
		}
		if($aRecData['bReturn'])
		{//last send returns the result (by fwrpc)
			$sMsgHeads = "stNetxPack::receive1() [return] ";
			$this->sLastedAccpet = 'unknow';
			if(isset($aRecData['r_Accept']))
			{
				$this->sLastedAccpet = $this->findCode($aRecData['r_Accept']);
				if($this->aCodes['r_OK'] == $aRecData['r_Accept'])
				{
					if($this->aTarget['ApName'] != $this->sLocalAppName && $this->aTarget['bParent'])
						$this->setSyncNowStatus($this->aTarget['ApName'], $this->aTarget['SyncDataPort0']);
//					if(!$this->bStartTestRemote && $this->aWaitSends['nStep'] > 0)
//						$this->bStartTestRemote = true;
					if($this->nWaitId == $aRecData['nId']) $this->nWaitId = -1;
					if(st_isDebugMode('stNetx.php')) trigger_error($sMsgHeads . 'accept ' . $this->sLastedAccpet);
				}
				else
				{
					$sError = sprintf("r_Accept=%s (0x%x) !", $this->sLastedAccpet, $aRecData['r_Accept']);
					trigger_error($sMsgHeads . $sError, E_USER_ERROR);
					return $this->sendError('Error: ' . $sError);
				}
			}
			else if(isset($aRecData['r_ErrorMsg']))
			{
				$this->sLastedAccpet = $aRecData['r_ErrorMsg'];
//				trigger_error($sMsgHeads . 'r_ErrorMsg :' .$aRecData['r_ErrorMsg'], E_USER_WARNING);
//				return false;
				$sError = sprintf("r_ErrorMsg=%s", $aRecData['r_ErrorMsg']);
				trigger_error($sMsgHeads . $sError, E_USER_ERROR);
				return $this->sendError('Error: ' . $sError);
			}
			else
			{
				if(isset($aRecData['r_InvalidCommand']))
					trigger_error($sMsgHeads. "r_InvalidCommand !", E_USER_WARNING);
			}

			if(($this->aWaitSendCodes['bStepEnd'] | $this->aWaitSendCodes['bWaitStepEndAccept']) & $this->aWaitSends['nDataFlag'])
				return false;//end loop for all step
			return true;
		}
		//receive data
		if(empty($aRecData['Data']))
		{
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() idle!");
			return true;//nothing
		}
		$nPos2 = 0;
		$nDataFlag = $oBitxUtls->dataTrans($aRecData['Data'], 'byte', $bLoading=true, $nPos2);
		if($this->aWaitSendCodes['bStepEnd'] & $nDataFlag)
		{
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() all step end!");
			$this->aError = unserialize(substr($aRecData['Data'], $nPos2));
			return false;//end loop for all step
		}
		else if(!($this->aWaitSendCodes['bDataSend'] & $nDataFlag))
		{
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::receive1() not bDataSend flag nothing here!");
			return true;//nothing
		}

		$this->aWaitSends['nStep'] = $oBitxUtls->dataTrans($aRecData['Data'], 'byte', $bLoading=true, $nPos2);
		if($this->aWaitSendCodes['bCompresss'] & $nDataFlag)
			$sData2 = gzuncompress(substr($aRecData['Data'], $nPos2));
		else
			$sData2 = substr($aRecData['Data'], $nPos2);
		if($this->aWaitSendCodes['bDataStart'] & $nDataFlag)
			$this->sReceiveData = $sData2;
		else
			$this->sReceiveData .= $sData2;
		if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetxPack::receive1() data size=%d, DataFlag=0x%x!", strlen($sData2), $nDataFlag));

		if(($this->aWaitSendCodes['bDataEnd'] & $nDataFlag) && $this->sReceiveData)
			return $this->runStep();
		return true;
	}
	function runStep()
	{
		global $oTimeStamp;
		if(!$this->aTarget['bClient'] && $this->nAccMoveNowInitialStep == $this->aWaitSends['nStep'])//child
			$this->nMaxStep = $this->nAccMoveNowInitialStep;
		if($this->aWaitSends['nStep'] > $this->nMaxStep)
			return false;//end of all step
		if(st_isDebugMode('stNetx.php') && $this->sReceiveData)
			trigger_error(sprintf("stNetxPack::runStep() step %d receive data size=%d", $this->aWaitSends['nStep'], strlen($this->sReceiveData)), E_USER_WARNING);
		$this->dsSyncsSetIt();//for child to set parent ip & rsync port
		while($this->aWaitSends['nStep'] <= $this->nMaxStep)
		{
			$nStep = 'dsSyncs' . $this->aWaitSends['nStep'];
			$this->aRunStepTime[$this->aWaitSends['nStep']] = time();
			if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetxPack::runStep() $nStep(size=%d)", strlen($this->sReceiveData)));
			if($this->sData = $this->oDsSync->$nStep($this->sReceiveData, $oTimeStamp))
				break;
			else if(false === $this->sData)
			{
				$sError = sprintf("Error step %d: %s", $this->aWaitSends['nStep'], st_DebugDump2String($this->oDsSync->sError));
				trigger_error('stNetxPack::runStep()' . $sError, E_USER_ERROR);
				$this->sendError($sError);
				return true;
			}
			$this->sReceiveData = '';
			$this->aWaitSends['nStep']++;
		}

		$this->preSendData();
		return true;
	}
	function shellExec($sApName)
	{
		$this->send(array('ApName' => $sApName, 'ExecShellCmd' => ''), $bSetting='cmmmand');
		return true;
	}
	function send($aData, $bSetting = false)
	{
		$sData = $this->preSend($aData, $bSetting);
		$this->nWaitId = $bSetting ? -1 : $this->nId;
		if(++$this->nId > 0xff) $this->nId = 0;
		$nPos = 0;
		$read_fds = $except = null;
		$write_fds = array($this->rSock);
		$num  = 0;
		while($num < 1)
		{
			$num = @stream_select($read_fds, $write_fds, $except, $this->nTimeout, $tv_usec = 0);
		}
		while($nWr = fwrite($this->rSock, substr($sData, $nPos)))
		{
			if(false === $nWr)
				return false;
			$nPos += $nWr;
		}
		return true;
	}
	function preSend($aData, $bSetting)
	{
		$nPos = 0;
		$oBitxUtls = new bitxUtls;
		$sData = $oBitxUtls->dataTrans(0x0, 'byte', $bLoading=false, $nPos) . $oBitxUtls->dataTrans($this->nId, 'byte', $bLoading=false, $nPos);
		if(!$bSetting) $sData .= $this->sTargetData;
		$sDatas = '';
		foreach($aData as $sKey => $sValue)
		{
			$sDatas .= ( $oBitxUtls->dataTrans($this->aCodes[$sKey], 'cmd', $bLoading=false, $nPos)
				. $oBitxUtls->dataTrans($sValue, $this->aCodeType[$sKey], $bLoading=false, $nPos) );
		}
		if($bSetting === true)
		{
			$n = strlen($sDatas);
			if($n < 0x100)
				$sSize = $oBitxUtls->dataTrans(1, 'byte', $bLoading=false, $nPos) . $oBitxUtls->dataTrans($n, 'byte', $bLoading=false, $nPos);
			else if($n < 0x10000)
				$sSize = $oBitxUtls->dataTrans(2, 'byte', $bLoading=false, $nPos) . $oBitxUtls->dataTrans($n, 'ushort', $bLoading=false, $nPos);
			else
				$sSize = $oBitxUtls->dataTrans(4, 'byte', $bLoading=false, $nPos) . $oBitxUtls->dataTrans($n, 'ulong', $bLoading=false, $nPos);
			$sData .= ($oBitxUtls->dataTrans($this->aCodes['Set'], $this->aCodeType['Set'], $bLoading=false, $nPos) . $sSize . $sDatas);
			$sDataType = 'setting';
		}
		else
		{
			$sDataType = $bSetting ? 'command' : ('data='. strlen($sDatas));
			$sData .= $sDatas;
		}
		if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::preSend() [$sDataType] size=" . strlen($sData));//bitxUtls::string2hex($sData, 128));
		return $sData . chr(0);
	}
	function findCode($nCmd)
	{
		foreach($this->aCodes as $sKey => $nCode)
		{
			if($nCmd == $nCode)
				return $sKey;
		}
		trigger_error(sprintf("stNetxPack::findCode() Error: code 0x%x not find!", $nCmd), E_USER_ERROR);
		return '';
	}
	function __destruct()
	{//disconect
		if($this->aTarget['ApName'] != $this->sLocalAppName)
		{
			$this->setSyncNowStatus();
			if(!empty($this->aTarget['bClient']) && $this->rSock && isset($this->aTarget['TargetIP']))
				$this->send(array('IP' => $this->aTarget['TargetIP'], 'Port' => $this->aTarget['SyncDataPort0'], 'Disconnect' => ''), $bSetting='cmmmand');
			sleep(3);
		}
		if($this->rSock) fclose($this->rSock);
		$nSyncCount = 0;
		$nSyncSize = 0;
		if($this->aError['bError'])
		{
			$sSyncMsg = $this->aError['sErrMsg'];
			trigger_error("stNetxPack::__destruct() {$this->aTarget['ApName']} " . $sSyncMsg, E_USER_ERROR);
		}
		else
		{
			$sSyncMsg = '';
			if($this->aTarget['ApName'] != $this->sLocalAppName)
			{
				$nSyncCount = $this->oDsSync->aRsyncTrans['num'];
				$nSyncSize = $this->oDsSync->aRsyncTrans['size'];
				$sResult = 'success';
			}
			else
				$sResult = 'test';
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::__destruct() $sResult end !");
		}
		if($this->aTarget['ApName'] != $this->sLocalAppName && $this->aWaitSends['nStep'] < $this->nAccMoveNowInitialStep)
		{
			$this->aRunStepTime[$this->aWaitSends['nStep']] = time();
			$oDSSLog = new dsSyncStateLog;
			$oDSSLog->setLog($this->aTarget['ApName'], $this->aRunStepTime[0], $sSyncMsg, $nSyncCount, $nSyncSize);
		}
		//log count: $this->oDsSync->aLogNum['mail_log'] $this->oDsSync->aLogNum['shared_folder_log']
	}
	function addRemoteData($aConnect)
	{
		if(isset($aConnect['SyncDataAddr'])) $this->aTarget['TargetIP'] = ip2long($this->realIP($aConnect['SyncDataAddr']));
		if(!empty($aConnect['SyncDataPort0']))
			$this->aTarget['SyncDataPort0'] = $aConnect['SyncDataPort0'];
		else if(!empty($aConnect['SrcPort']))
			 $this->aTarget['SyncDataPort0'] = $aConnect['SrcPort'];
		if(isset($aConnect['RemotexIP']))
		{
			if($this->aTarget['ApName'] != $this->sLocalAppName && !$this->aTarget['bParent'])
				$this->setSyncNowStatus($this->aTarget['ApName'], $this->aTarget['SyncDataPort0']);
			$this->aTarget['TargetIP'] = $aConnect['RemotexIP'];
			$this->aTarget['SyncDataAddr'] = $aConnect['SyncDataAddr'] = long2ip($aConnect['RemotexIP']);
		}
		$aTarget = array('IP' => $this->aTarget['TargetIP'], 'ApName' => $this->aTarget['ApName']);
		if(!empty($aConnect['SyncDataPort1'])) $this->aTarget['SyncDataPort1'] = intval($aConnect['SyncDataPort1']);
		if(!empty($this->aTarget['SyncDataPort0']))
			$aTarget['Port'] = intval($this->aTarget['SyncDataPort0']);
		$this->sTargetData = '';
		$oBitxUtls = new bitxUtls;
		foreach($aTarget as $sKey => $sValue)
		{
			$this->sTargetData .= ( $oBitxUtls->dataTrans($this->aCodes[$sKey], 'cmd', $bLoading=false, $nPos)
				. $oBitxUtls->dataTrans($sValue, $this->aCodeType[$sKey], $bLoading=false, $nPos) );
		}
		$aTarget['RsyncPort'] = $this->aTarget['SyncDataPort1'];
		if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::addRemoteData(): " .$aConnect['SyncDataAddr']. st_DebugDump2String($aTarget));
	}
	function realIP($sIP, $bGetAllIP = false)
	{
		$sDigCmd = '/addpkg/bin/dig';
		$sPattern = '/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/';
		if(preg_match($sPattern, $sIP, $aMatches))
			return $sIP;
		for($n = 0; $n < 3; ++$n)
		{
			exec("$sDigCmd +short $sIP", $aRet, $nRet);
			if(!$nRet && count($aRet) > 0)
				return ($bGetAllIP ? $aRet : $aRet[0]);
		}
		return '';
	}
	function dsSyncsSetIt()
	{
		if($this->oDsSync->SyncDataPort1)
			return;
		$oRm = new dsRemotemap();
		$aAlls = $oRm->getNeedConnectInfo();
		if(!$aAlls)
			$a = $this->aTarget;
		else
		{
			$a = false;
			foreach($aAlls as $a)
				break;
		}
		if($a && isset($this->aTarget['SyncDataAddr']) && ($a['SyncDataAddr'] = $this->aTarget['SyncDataAddr']))
		{
			if(st_isDebugMode('stNetx.php')) trigger_error("stNetxPack::dsSyncsSetIt(): " . st_DebugDump2String($a));
			$this->oDsSync->set($a);
		}
	}
	function updateTimeStamp()
	{
		global $oTimeStamp;
		if(isset($oTimeStamp) && is_object($oTimeStamp))
			$oTimeStamp->update();
	}
	function setSyncNowStatus($sHostname = '', $nTargetPort = 0)
	{
		if(!$sHostname)
		{
			if(file_exists($this->sInitialChkStampFile))
				@unlink($this->sInitialChkStampFile);
			return;
		}
		if( !($fp = @fopen($this->sInitialChkStampFile, 'w')) )
			return false;
		fwrite($fp, $sHostname . "\n". $nTargetPort);
		fclose($fp);
		return true;
	}
	function getSyncNowStatus($sHostname = true)
	{
		if(false === $sHostname)
		{
			if(file_exists($this->sInitialStampFile))
				@unlink($this->sInitialStampFile);
			return;
		}
		else if(true === $sHostname)
		{
			if( !file_exists($this->sInitialStampFile) || !($fp = @fopen($this->sInitialStampFile, 'r')) )
				return false;
			$sSyncHostname = trim(fgets($fp));
			fclose($fp);
			clearstatcache();
			$tModTime = filemtime($this->sInitialStampFile);
			return array('HostName' => $sSyncHostname, 'StartTime'=> $tModTime);
		}
		if( !($fp = @fopen($this->sInitialStampFile, 'w')) )
			return false;
		fwrite($fp, $sHostname);
		fclose($fp);
		return true;
	}
}

class bitxUtls
{
	function md5b($sData)
	{//return md5 16 chars
		$sMd5 = md5($sData);
		$sMd5b = '';
		$nLen = strlen($sMd5);
		for($n = 0; $n < $nLen; $n += 2)
			$sMd5b .= pack('C', hexdec(substr($sMd5, $n, 2)));
		return $sMd5b;
	}

	function swapBits($cChr, $nPos, $bUnSwap = false)
	{//$nPos(0-8)
		if($bUnSwap) $nPos = 8 - $nPos;
		if($nPos < 1 || $nPos > 7)
			return $cChr;
		$cMask = (0xff >> $nPos) << $nPos;
		$cChr = ord($cChr);
		return pack('C', (($cChr & $cMask) >> $nPos) | (($cChr & ~$cMask) << (8 - $nPos)) );
	}

	function unpack1($sKey, $sData)
	{
		$a = unpack($sKey, $sData);
		return $a[1];
	}
	function dataTrans($sData, $sType, $bLoading, &$nPos)
	{//cmd byte ushort ulong float double string binary
		if(!isset($sData))
			return '';
		$n = $nPos;
		switch ($sType)
		{
		case 'none':
			return '';
		case 'byte':
		case 'cmd':
			$nPos++;
			if($bLoading)
				return $this->unpack1('C', substr($sData, $n, 1));
			return pack('C', $sData);
		case 'uport':
			$nPos += 2;
			if($bLoading)
				return $this->unpack1('n', substr($sData, $n, 2));
			return pack('n', $sData);
		case 'ushort':
			$nPos += 2;
			if($bLoading)
				return $this->unpack1('v', substr($sData, $n, 2));
			return pack('v', $sData);
		case 'long':
			$nPos += 4;
			if($bLoading)
				return $this->unpack1('l', substr($sData, $n, 4));
			return pack('l', $sData);
		case 'ulong':
			$nPos += 4;
			if($bLoading)
				return $this->unpack1('V', substr($sData, $n, 4));
			return pack('V', $sData);
		case 'ip':
			$nPos += 4;
			if($bLoading)
				return $this->unpack1('N', substr($sData, $n, 4));
			return pack('N', $sData);
		case 'float':
			$nPos += 4;
			if($bLoading)
				return $this->unpack1('f', substr($sData, $n, 4));
			return pack('f', $sData);
		case 'double':
			$nPos += 8;
			if($bLoading)
				return $this->unpack1('d', substr($sData, $n, 8));
			return pack('d', $sData);
		case 'string':
			if($bLoading)
			{
				$nLength = $this->unpack1('C', substr($sData, $n++, 1));
				$nPos += ($nLength + 1);
				return substr($sData, $n, $nLength);
			}
			$nLength = strlen($sData);
			return pack('C', $nLength) . $sData;
		case 'binary':
			if($bLoading)
			{
				$nLength = $this->unpack1('v', substr($sData, $n, 2));
				$n += 2;
				$nPos += ($nLength + 2);
				return substr($sData, $n, $nLength);
			}
			$nLength = strlen($sData);
			return pack('v', $nLength) . $sData;
		}
		return '';
	}

	function string2hex($sStr, $nMax = 0)
	{
		$s = '';
		$l = strlen($sStr);
		if($nMax > 0 && $l > $nMax)
			$l = $nMax;
		for($n = 0; $n < $l; ++$n)
		{
			if(0 == $n || $n % 4)
				$s .= sprintf('%02x', ord($sStr[$n]));
			else
				$s .= sprintf('-%02x', ord($sStr[$n]));
		}
		return $s;
	}
}

class stNetLitePack
{
	var $oFlag;
	var $bEndData;
	var $sNextData;

	var $sKeySalt;
	var $nUseVer;
	var $aPackSepcs = array(
		'sKeySalt' => "\xacSTnET@lITE,c23\x02Ko*w\x0bz:8hD", 'sSalt' => "\xcaY^j8E.\t\x9aq#M\x070X!u", 'nShiftTimeKey' => 107345023, 'nShiftStepKey' => 3051287,
		'nHeadKeySize' => 46, 'oFlag' => 'bCompress,bDES,bSimpleEnc,bEndData,bLongSize',
		'nVer' => 2,
		);
	function setDES($bOn) {$this->setFlag('bDES', $bOn);}
	function setCompress($bOn) {$this->setFlag('bCompress', $bOn);}
	function setSimpleEnc($bOn) {$this->setFlag('bSimpleEnc', $bOn);}
	function setFlag($sFlagTypeName, $bOn)
	{
		if($bOn)
			$this->oFlag->setOn($sFlagTypeName);
		else
			$this->oFlag->setOff($sFlagTypeName);
	}

	function stNetLitePack($sSalt = '')
	{
		$this->sKeySalt = $sSalt . $this->aPackSepcs['sKeySalt'];
		$this->nUseVer = $this->aPackSepcs['nVer'];
		$this->oFlag = new Bitwise();
		$this->oFlag->setName($this->aPackSepcs['oFlag']);
		$this->oFlag->setOn('bCompress');
		$this->oFlag->setOn('bSimpleEnc');
	}
	function preSendData($sData)
	{
		global $oChallengeKeys;
		$oFlag = new Bitwise();
		$oFlag->setName($this->aPackSepcs['oFlag']);
		$this->sNextData = '';
		$nMaxData1 = 0x7ffffff0;//size use unsigned long or short
		$bEncrypt = true;
		$oBitxUtls = new bitxUtls;
		$sRand = $oBitxUtls->dataTrans(mt_rand(0, 65535), 'ushort', $bLoading=false, $nPos);
		$oFlag->set($this->oFlag->get());
		if($oFlag->isOn('bCompress') && strlen($sData) > 23)
		{
			$nScale = 1;
			$nSize = $nSrcSize = strlen($sData);
			do
			{
				if($nScale < 1)
					$nSize = intval($nSrcSize * ($nMaxData1 / $nGzSize) * $nScale);
				$sGzData = gzcompress(substr($sData, 0, $nSize));
				$nGzSize = strlen($sGzData);
				$nScale -= 0.05;
			}while($nGzSize > $nMaxData1);
			$this->sNextData = substr($sData, $nSize);
			$sData = $sGzData;
		}
		else
			$oFlag->setOff('bCompress');
		
		if($oFlag->isOn('bDES')) $sData = $oChallengeKeys->_DES($sData, $bEncrypt, $this->aPackSepcs['sSalt'], $this->nUseVer > 1 ? 1 : 0);
		if($oFlag->isOn('bSimpleEnc')) $sData = $this->simpleEnc($sData, $bEncrypt, $sRand . $this->aPackSepcs['sSalt']);
		if(($nSize = strlen($sData)) > $nMaxData1)
		{
			$this->sNextData = substr($sData, $nMaxData1);
			$sData = substr($sData, 0, $nSize = $nMaxData1);
		}
		if($nSize > 0xffff)
		{
			$oFlag->setOn('bLongSize');
			$sDatasize = $oBitxUtls->dataTrans($nSize, 'ulong', $bLoading=false, $nPos);
		}
		else
			$sDatasize = $oBitxUtls->dataTrans($nSize, 'ushort', $bLoading=false, $nPos);
		if(!$this->sNextData) $oFlag->setOn('bEndData');//send end

		$sKey = $oChallengeKeys->_key($this->sKeySalt . $sRand, '', 1, $this->aPackSepcs['nShiftTimeKey'], $this->aPackSepcs['nShiftStepKey']);
		if(st_isDebugMode('stNetx.php'))
		{
			$sSize = sprintf('%d / %d s=%d,f=0x%x', strlen($sData), strlen($this->sNextData), $nSize, $oFlag->get());
			$sMd5 = md5($sData);
			$sKeyX = $sKey . $oBitxUtls->string2hex($sRand);
			trigger_error("stNetLitePack::preSendData() size=$sSize; md5=$sMd5 key=($sKeyX)");
		}
		return $sKey . $sRand . $oBitxUtls->dataTrans($this->nUseVer, 'byte', $bLoading=false, $nPos)
				. $oBitxUtls->dataTrans($oFlag->get(), 'byte', $bLoading=false, $nPos)
				. $sDatasize
				. $sData;
	}

	function parseData($sData, $aContinue)
	{
		global $oChallengeKeys;
		$oFlag = new Bitwise();
		$oFlag->setName($this->aPackSepcs['oFlag']);
		$bEncrypt = false;
		$this->bEndData = false;
		$this->sNextData = '';
		$oBitxUtls = new bitxUtls;
		if(!empty($aContinue['data']))
		{
			$sData = $aContinue['data'] . $sData;
			$aContinue['data'] = '';
		}
		$nDataSize = strlen($sData);
		if(empty($aContinue['sRand']))
		{
			if($nDataSize < ($this->aPackSepcs['nHeadKeySize'] + 2))
				return array('data' => $sData);
			$aContinue['sRand'] = substr($sData, $this->aPackSepcs['nHeadKeySize'], 2);
			if(!$oChallengeKeys->_key($this->sKeySalt . $aContinue['sRand'], substr($sData, 0, $this->aPackSepcs['nHeadKeySize']), 1,
						$this->aPackSepcs['nShiftTimeKey'], $this->aPackSepcs['nShiftStepKey'])
				)
			{
				$sKey = substr($sData, 0, $this->aPackSepcs['nHeadKeySize']) . $oBitxUtls->string2hex($aContinue['sRand']);
				trigger_error("stNetLitePack::parseData() loading data fail in headkey ! ($sKey)", E_USER_WARNING);
				return false;
			}
//trigger_error('*key*=' . substr($sData, 0, self::$aPackSepcs['nHeadKeySize']) . bitxUtls::string2hex($aContinue['sRand']));
			$nDataSize -= ($nPos = $this->aPackSepcs['nHeadKeySize'] + 2);
		}
		else
			$nPos = 0;
		
		if(empty($aContinue['nVer']))
		{
			if($nDataSize < 1) return $aContinue;
			$nDataSize -= 1;
			$aContinue['nVer'] = $oBitxUtls->dataTrans($sData, 'byte', $bLoading=true, $nPos);
			if($this->nUseVer > $aContinue['nVer']) $this->nUseVer = $aContinue['nVer'];
		}
		if(empty($aContinue['oFlag']))
		{
			if($nDataSize < 1) return $aContinue;
			$nDataSize -= 1;
			$aContinue['oFlag'] = $oBitxUtls->dataTrans($sData, 'byte', $bLoading=true, $nPos);
		}
		$oFlag->set($aContinue['oFlag']);
		if(!isset($aContinue['nSize']))
		{
			$nMinBytes = $oFlag->isOn('bLongSize') ? 4 : 2;
			$sSizeType = $oFlag->isOn('bLongSize') ? 'ulong' : 'ushort';
			if($nDataSize < $nMinBytes) { $aContinue['data'] = $sData; return $aContinue; }
			$nDataSize -= $nMinBytes;
			$aContinue['nSize'] = $oBitxUtls->dataTrans($sData, $sSizeType, $bLoading=true, $nPos);
		}

		$this->sNextData = substr($sData, $nPos + $aContinue['nSize']);//next incomplete data
		$sData = substr($sData, $nPos, $aContinue['nSize']);
		if(strlen($sData) < $aContinue['nSize'])
		{//incomplete data
			$aContinue['data'] = $sData;
//			if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetLitePack::parseData() load=%d / %d (v=%d,f=0x%x,s=%d)", strlen($sData)
//				, strlen($this->sNextData), $aContinue['nVer'], $aContinue['oFlag'], $aContinue['nSize']));
			return $aContinue;
		}
		$this->bEndData = $oFlag->isOn('bEndData');
		if(st_isDebugMode('stNetx.php'))
			trigger_error("stNetLitePack::parseData() load = " . strlen($sData) . ', ' . strlen($this->sNextData) . sprintf(' 0x%x', $oFlag->get()).' md5='. md5($sData) );
		if($oFlag->isOn('bSimpleEnc')) $sData = $this->simpleEnc($sData, $bEncrypt, $aContinue['sRand'] . $this->aPackSepcs['sSalt']);
		if($oFlag->isOn('bDES')) $sData = $oChallengeKeys->_DES($sData, $bEncrypt, $this->aPackSepcs['sSalt'], $this->nUseVer > 1 ? 1 : 0);
		if($oFlag->isOn('bCompress')) $sData = gzuncompress($sData);
		return $sData;
	}

	function simpleEnc($sDataIn, $bEncrypt, $sSalt)
	{
		$sSwap = array();
		for($n = 0, $l = strlen($sSalt); $n < $l; ++$n)
		{
			$aSwap[] = $sSalt[$n] >> 4;
			$aSwap[] = $sSalt[$n] & 0x0f;
		}
		$nSwapSize = count($aSwap);
		$nShift = strlen($sSalt) * 64;
		$bUnSwap = !$bEncrypt;
		for($n = 0, $sKey = ''; $n < 64; ++$n)
			$sKey .= $sSalt;
		for($sData ='', $n = 0, $l = strlen($sDataIn); $n < $l; $n += $nShift)
		{
			$sShift = substr($sDataIn, $n, $nShift);
			if(!$bEncrypt)
				$sShift ^= $sKey;
			for($i = $m = 0, $nSlen = strlen($sShift); $i < $nSlen && $m < $nSwapSize; $i += 3, ++$m)
			{
				$oBitxUtls = new bitxUtls;
				$sShift[$i] = $oBitxUtls->swapBits($sShift[$i], $aSwap[$m], $bUnSwap);
			}
			if($bEncrypt)
				$sShift ^= $sKey;
			$sData .= $sShift;
		}
		return $sData;
	}

	function ddnsCfgFile($nId, &$aData, $bLoading)
	{
		$sDdnsIniFile = '/HDD/PCONF/cfg/ddns_' .$nId. '.cfg';
		//if(st_isDebugMode('stNetx.php')) trigger_error("stNetLitePack::ddnsCfgFile $sDdnsIniFile");
		if(!$bLoading)
		{//storing
			if(!isset($aData['.Command'])) $aData['.Command'] = 'ddns';
			$aSaves = array();
			foreach($aData as $sIndex => $a)
			{
				if('.' == $sIndex[0])
				{
					$aSaves[$sIndex] = $a;
					continue;
				}
				$s = ';';
				foreach($a['A'] as $sDomain)
					$s .= " $sDomain";
				$aSaves[$sIndex] = $a['nUpdFalg'] . $s;
			}
			return ioFile::save($sDdnsIniFile, $aSaves);
		}

		//loading
		static $s_nFilemtime = 0;
		clearstatcache();
		if($s_nFilemtime)
		{//loaded before
			if(!file_exists($sDdnsIniFile))
				return -2;
			if(($nFilemtime = filemtime($sDdnsIniFile)) == $s_nFilemtime)
				return -1;//file no update
		}
		else
			$aData = array();
		if(!file_exists($sDdnsIniFile) || !($aDatas = ioFile::read($sDdnsIniFile)) )
			return 0;//fail
		foreach($aDatas as $sIndex => $s)
		{
			if('.' == $sIndex[0])
			{
				$aData[$sIndex] = $s;
				continue;
			}
			$a = explode(';', $s, 2);
			$nUpdFalg = $a[0];
			$a = preg_split("/\s+/", trim(isset($a[1]) ? $a[1] : ''));
			$aData[$sIndex] = array('nUpdFalg' => $nUpdFalg, 'A' => $a);
		}
		$oDs = new dsSetup;
		$aData['.HostName'] = $oDs->get('HostName');
		$s_nFilemtime = filemtime($sDdnsIniFile);
		return 1;//loading ok
	}
}

class stNetLiteCsPack
{
	var $sExtPhp = '';

	var $nSelectWaitTime = 60;
	var $nTimeoutMicsec = 0;//50000;// (= 0.05 second)
	var $nSockTimeout = 30;
	var $nIdelTime2Ping = 120;
	var $aStatusFlag = array('data' => 0x01, 'file' => 0x02, 'sendDirect' => 0x04, 'srvAppInit' => 0x10, 'ping' => 0x40, 'bEnd' => 0x80);//unsigned short

	var $bWaitSend = false;
	var $aSockets = array();
	var $aStatus = array();

	var $aSelfData = array();
	var $oPack;
	var $aRead;
	var $sPeerIP = '';
	var $sTmpClient = '';
	var $oErrHandler = null;
	var $aRejectIPs = array('192.168.188.1');//---!---debug

	function stNetLiteCsPack($oPack, $sExtPhp = '', $sAppFunc = '')
	{
		$this->oErrHandler = $this;
		$this->oPack = $oPack;
		$this->sExtPhp = $sExtPhp;
		if(!empty($sAppFunc)) $this->aSelfData['sAppFunc'] = $sAppFunc;
	}

	function initServer($nPort, $nInitialKillTime = 0)
	{
		$bIPv6 = file_exists('/HDD/ipv6');
		$sBindIP = $bIPv6 ? '[::]' : '0.0.0.0';
		if( !($rSocket = stream_socket_server("tcp://$sBindIP:$nPort", $errno , $errstr)) )
			return false;//create fail
		$this->aSelfData['bServer'] = true;
		$this->aSelfData['nInitialKillTime'] = $nInitialKillTime;
		$this->aSelfData['sSrvResName'] = $sSockResName = $this->add($rSocket, "$sBindIP:$nPort");
		$this->aStatus[$sSockResName]['aStatus']['bInitAuth'] = true;
		return true;
	}

	function initClient($sSrvIP, $nPort, $nTimeout = 0)
	{
		$errno = $errstr = '';
		$sSrvIp = strpos($sSrvIP, ':') !== false ? "[$sSrvIP]" : "$sSrvIP";//ipv6 or v4
		if($nTimeout < 1) $nTimeout = $this->nSockTimeout;
		if(!($rSocket = fsockopen("tcp://$sSrvIp", $nPort, $errno , $errstr, $nTimeout)))
			return false;//create fail
		$this->aSelfData['bServer'] = false;
		return $this->aSelfData['sSrvResName'] = $this->add($rSocket, "$sSrvIP:$nPort");
	}

	function isAccept($rSocket, $sClient)
	{
		if(!$this->aSelfData['bServer'] || $sClient != $this->aSelfData['sSrvResName'])
			return false;
		$rCltSocket = stream_socket_accept($rSocket, $this->nSockTimeout, $sPeerIP);
		if($this->add($rCltSocket, $sPeerIP, $this->aRejectIPs))
			if(st_isDebugMode('stNetx.php')) trigger_error("$sPeerIP connected. (" .$this->getCount(). ")");
		flush();
		return true;
	}

	function add($rSocket, $sPeerIP)
	{
		stream_set_blocking($rSocket , 0); //no blocking
		stream_set_timeout($rSocket , $this->nSockTimeout);
		$sSockResName = strval($rSocket);//like: 'Resource id #10'
		if(($i = strrpos ($sPeerIP, ':')) !== false)
		{//for ipv4/v6
			$sPattern = '/^::ffff:(.+)/';
			$sPeerPort = substr($sPeerIP, $i + 1);
			$sPeerIP = substr($sPeerIP, 0, $i);
			if(preg_match($sPattern, $sPeerIP, $aMatches))
				$sPeerIP = $aMatches[1];//ipv4 in ipv6 mode
		}
		else
			$sPeerPort = 0;
		if(in_array($sPeerIP, $this->aRejectIPs))
		{
			if(version_compare(PHP_VERSION, '5.0.0') >= 0) 
				@stream_socket_shutdown($rSocket, STREAM_SHUT_RDWR);
			@fclose($rSocket);
			return '';
		}
		$aStatus = array('init' => time(), 'tLast' => time(), 'bInitAuth' => false, 'sRemote' => '', 'bWaitData' => true, 'bClose' => false, 'nStopCountdown' => 0,
			'sPeerIP' => $sPeerIP, 'sPeerPort' => $sPeerPort);
		$aSend = array('aContinue' => array(), 'mData' => '', 'mData1' => '', 'nType' => 0);
		$aReceive = array('aContinue' => array(), 'mData' => '', 'mData1' => '');
		$this->aStatus[$sSockResName] = array('aStatus' => $aStatus, 'aSend' => $aSend, 'aReceive' => $aReceive);
		$this->aSockets[$sSockResName] = $rSocket;
		return $sSockResName;
	}

	function srvAppInitData($sRemote)
	{
		$aData = array('sRemote' => $sRemote, '.srvAppInit' => true);
		$this->preareData($aData, $this->aSelfData['sSrvResName']);
	}

	function waitReceive()
	{
		$aWrite = $aExcept = null;
		$this->aRead = $this->aSockets;
		return @stream_select($this->aRead, $aWrite, $aExcept, $this->bWaitSend ? 0 : $this->nSelectWaitTime, $this->nTimeoutMicsec);
	}
	function lists()
	{
		$sMsg = '';
		foreach($this->aStatus as $a)
		{
			$sMsg .= sprintf(" [%s:%s] send=%d rec=%d;", $a['aStatus']['sPeerIP'], $a['aStatus']['sPeerPort']
				, strlen($a['aSend']['mData']), strlen($a['aReceive']['mData']) );
		}
		//if(st_isDebugMode('stNetx.php')) trigger_error($sMsg);
	}
	function receive()
	{
		$oBitxUtls = new bitxUtls;
		foreach($this->aRead as $rSocket)
		{
			$sClient = strval($rSocket);
			if($this->isAccept($rSocket, $sClient))
				continue;//new connection
			$aStatus = &$this->aStatus[$sClient]['aStatus'];
			$aReceive = &$this->aStatus[$sClient]['aReceive'];//'aContinue' => array(), 'mData' => '', 'mData1' => ''
			$sIP = $aStatus['sPeerIP'];
			$sPort = $aStatus['sPeerPort'];
			$this->sPeerIP = "$sIP:$sPort";
			$sRecData = @fread($rSocket, 4096);

			if(empty($sRecData))
			{
				if(++$aStatus['nStopCountdown'] > 3)
					$this->close($rSocket);
				continue;
			}
			else if(false === $sRecData)
			{
				$this->close($rSocket);
				if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::receive close $this->sPeerIP!");
				continue;
			}
			else if($this->sendDirect($sRecData, $aStatus))
				continue;
			else if(false === ($mData = $this->oPack->parseData($sRecData, $aReceive['aContinue'])))
			{
				$this->close($rSocket);
				if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::receive $this->sPeerIP auth fail!");
				continue;
			}

			if($this->aSelfData['bServer'] && is_array($mData))
			{//stNetLitePack::parseData data not end for single pack.
				if(!$aStatus['bInitAuth'] && empty($mData['aContinue']['sRand']))
				{
					$this->close($rSocket);
					if(st_isDebugMode('stNetx.php')) trigger_error("invalid data for auth!");
					continue;
				}
			}
			$aStatus['bInitAuth'] = true;

			if(is_array($mData))
			{
				$aReceive['aContinue'] = $mData;
//				if(st_isDebugMode('stNetx.php'))
//					trigger_error(sprintf("stNetLiteCsPack::receive [$this->sPeerIP] receive = %d-%d", strlen($sRecData), strlen($aReceive['aContinue']['data'])) );
				continue;
			}

			if(!$this->oPack->bEndData)
			{
				if(is_array($mData))
				{
					if(empty($mData['data']))
						$sSize = '-head-';
					else
						$sSize = strlen($mData['data']) . '*';
				}
				else
				{
					$aReceive['mData'] .= $mData;
					$aReceive['aContinue'] = array();
					$sSize = strlen($mData) .'/'. strlen($aReceive['mData']);
				}
				if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::receive from $this->sPeerIP not end [$sSize] ! --------------- ");
				continue;//stNetLitePack::parseData data not end.
			}

			$aReceive['aContinue'] = array('data' => $this->oPack->sNextData);
			$aReceive['mData'] .= $mData;
			$aStatus['tLast'] = time();
			$nPos = 0;
			$nStatusFlag = $oBitxUtls->dataTrans($aReceive['mData'], 'ushort', $bLoading=true, $nPos);
			$mData = substr($aReceive['mData'], $nPos);//shfit
			$bClearRecData = true;
			if($nStatusFlag & $this->aStatusFlag['data'])
			{
				if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::receive data " . strlen($mData) );
				$aData = unserialize($mData);

				$bRun = false;
				if(!empty($aData['.Command']))
					$bRun = $this->runFunc($aData['.Command'], $aData, $sIP, $sClient, $rSocket);
				else if(isset($aData['.sExtPhp']) && file_exists($aData['.sExtPhp']))
				{
					if($this->aSelfData['bServer'])
						$bRun = $this->runPhp($aData, $sClient, $sIP, $bClearRecData);
					else
						$bRun = $this->runFunc($this->aSelfData['sAppFunc'], $aData, $sIP, $sClient, $rSocket);
				}
				if(!$bRun && st_isDebugMode('stNetx.php')) trigger_error("[data mode] run fail !");
			}
			else if($nStatusFlag & $this->aStatusFlag['file'])
			{
			}
			else if($nStatusFlag & $this->aStatusFlag['ping'])
			{
				;//nothing to do
			}
			else if(st_isDebugMode('stNetx.php')) trigger_error("unknow stNetLiteCsPack data!");

			if($bClearRecData) $aReceive['mData'] = '';
		}
	}

	function preareData($aData, $sSockResName, $nType = 0)
	{
		$aSend = &$this->aStatus[$sSockResName]['aSend'];//'aContinue' => array(), 'mData' => '', 'mData1' => '', 'nType' => 0
		if(is_array($aData) && $this->sExtPhp) $aData['.sExtPhp'] = $this->sExtPhp;
		if($nType & $this->aStatusFlag['sendDirect'])
			$aSend['mData1'] .= $aData;
		else
			$aSend['mData'] = is_array($aData) ? serialize($aData) : $aData;
		$aSend['nType'] = $nType ? $nType : $this->aStatusFlag['data'];
		$this->aStatus[$sSockResName]['aStatus']['bWaitData'] = false;
		$this->bWaitSend = true;
	}

	function send()
	{
		$this->bWaitSend = false;
		//if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::send count=". count($this->aStatus));
		$tIdelUseTime = time() - $this->nIdelTime2Ping;
		$oBitxUtls = new bitxUtls;
		foreach($this->aStatus as $sSockResName => $x)
		{
			if(!$this->checkResource($sSockResName))
				continue;
			$aStatus = &$this->aStatus[$sSockResName]['aStatus'];
			$aSend = &$this->aStatus[$sSockResName]['aSend'];//'aContinue' => array(), 'mData' => '', 'mData1' => '', 'nType' => 0
			$this->sPeerIP = "{$aStatus['sPeerIP']}:{$aStatus['sPeerPort']}";
			if(!empty($aStatus['bClose']))
			{
				usleep(350000);
				$this->closeNow($sSockResName);
				continue;
			}
			else if(empty($aSend['mData']) && empty($aSend['mData1']))
			{
				if(!$aStatus['bInitAuth'] && $this->aSelfData['nInitialKillTime'] && (time() - $this->aSelfData['nInitialKillTime']) > $aStatus['tLast'])
				{//kill un-initial ok
					usleep(350000);
					$this->closeNow($sSockResName);
				}
				else if(!empty($aStatus['bWaitData']) && !$this->aSelfData['bServer'])// && $tIdelUseTime > $aStatus['tLast'])
				{
					$this->ping($sSockResName);
					$aStatus['tLast'] = time();
				}
				//if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::send continue ". $this->sPeerIP);
				continue;
			}
			$sIP = $aStatus['sPeerIP'];
			$aStatus['tLast'] = time();
			if(empty($aSend['mData1']))
			{
				$nType = $aSend['nType']; //empty($aSend['mData']) ? $aSend['nType'] | self::$aStatusFlag['bEnd'] : $aSend['nType'];
				$sType = $oBitxUtls->dataTrans($nType, 'ushort', $bLoading=false, $nPos);
				$nSize = strlen($aSend['mData']);
				$mData = $this->oPack->preSendData($sType . $aSend['mData']);
				$aSend['mData'] = $this->oPack->sNextData;
				if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetLiteCsPack::send mData=%d (type=0x%x), next=%d", $nSize, $nType, strlen($aSend['mData'])));
			}
			else
			{
				$mData = $aSend['mData1'];
				//if(st_isDebugMode('stNetx.php')) trigger_error(sprintf("stNetLiteCsPack::send next mData1=%d", strlen($aSend['mData1'])));
			}
			if(!$this->write($sSockResName, $mData, $aSend['mData1']))
			{
				$this->close($this->aSockets[$sSockResName]);
				continue;
			}
			if($aSend['mData'] || $aSend['mData1'])
				$this->bWaitSend = true;
			else
				$aStatus['bWaitData'] = true;
		}
	}

	function getCount()
	{
		$nMinus = $this->aSelfData['bServer'] ? 1 : 0;
		return count($this->aSockets) - $nMinus;
	}

	function close($rSocket)
	{
		$sClient = strval($rSocket);//like: 'Resource id #10'
		$aStatus = &$this->aStatus[$sClient]['aStatus'];
		$aStatus['bClose'] = true;
		$aStatus['tLast'] = time();
	}

	function closeNow($sClient)
	{
		$aStatus = &$this->aStatus[$sClient]['aStatus'];
		if($this->aSelfData['bServer'] && !empty($aStatus['sRemote']) && isset($this->aSockets[$aStatus['sRemote']]))
			$this->close($this->aSockets[$aStatus['sRemote']]);
		$this->sPeerIP = $aStatus['sPeerIP'] .':'. $aStatus['sPeerPort'];
		if(st_isDebugMode('stNetx.php')) trigger_error("close socket [$this->sPeerIP]");
		if(isset($this->aSockets[$sClient]))
		{
			if(version_compare(PHP_VERSION, '5.0.0') >= 0) 
				@stream_socket_shutdown($this->aSockets[$sClient], STREAM_SHUT_RDWR);
			@fclose($this->aSockets[$sClient]);
			flush();
			unset($this->aSockets[$sClient]);
		}
		if(isset($this->aStatus[$sClient])) unset($this->aStatus[$sClient]);
		//usleep(500000);//0.5 sec
	}

	function sendDirect($sRecData, $aStatus)
	{
		if(empty($aStatus['sRemote']))
			return false;
		$sRemote = $aStatus['sRemote'];
		$a = $this->aStatus[$sRemote]['aStatus'];
		$sFrom = $aStatus['sPeerIP'] .':'. $aStatus['sPeerPort'];
		$sTo = $a['sPeerIP'] .':'. $a['sPeerPort'];
		//if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::sendDirect from $sFrom to $sTo size=" . strlen($sRecData));
		$this->preareData($sRecData, $sRemote, $this->aStatusFlag['sendDirect']);
		return true;
	}
	function runPhp($aData, $sClient, $sIP, &$bClearRecData)
	{
		$aStatus = &$this->aStatus[$sClient]['aStatus'];
//		if(!empty($aStatus['sRemote']))
//		{
//			if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::runPhp to " . $aStatus['sRemote']);
//			$this->preareData($aData, $aStatus['sRemote']);
//			return true;
//		}
//		else
		if(isset($aData['.srvAppInit']))
		{
			if(!empty($aData['sRemote']))
			{
				$sRemote = $aData['sRemote'];
				$aStatus['sRemote'] = $sRemote;
				$this->aStatus[$sRemote]['aStatus']['sRemote'] = $sClient;
				$aReceive = &$this->aStatus[$sRemote]['aReceive'];

				$this->preareData($aReceive['mData'], $sClient);
				if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::runPhp srvAppInit to [$sRemote] data " . strlen($aReceive['mData']));
				$aReceive['mData'] = '';
				return true;
			}
			return false;
		}

		$aReceive = &$this->aStatus[$sClient]['aReceive'];
		$aReceive['mData'] = serialize($aData);
		$bClearRecData = false;
		$sCmd = sprintf("/etc/init.d/fork_php.sh %s '%s'", $aData['.sExtPhp'], $sClient);
		exec($sCmd, $aRet, $nRet);
		if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::runPhp $sCmd & save data in " .$sClient. ' ' .strlen($aReceive['mData']));
		if(!$nRet)
			return true;
		trigger_error(sprintf("stNetLiteCsPack::runPhp $sCmd fail %d: %s", $nRet, st_DebugDump2String($aRet)), E_USER_WARNING);
		return false;
	}

	function checkResource($sSockResName)
	{
		if(isset($this->aStatus[$sSockResName]) && isset($this->aSockets[$sSockResName]))
			return true;
		if(isset($this->aStatus[$sSockResName])) unset($this->aStatus[$sSockResName]);
		if(isset($this->aSockets[$sSockResName])) unset($this->aSockets[$sSockResName]);
		return false;
	}
	function runFunc($sFunc, $aData, $sIP, $sClient, $rSocket)
	{
		if(!function_exists($sFunc))
			return false;
		else if(is_bool($aData = call_user_func($sFunc, $aData, $sIP)))
			$this->close($rSocket);
		else
			$this->preareData($aData, $sClient);
		return true;
	}

	function ping($sSockResName)
	{
//		if(!isset($this->aSockets[$sSockResName]) || !is_resource($rSocket = $this->aSockets[$sSockResName]))
//		{
//			$this->close($rSocket);
//			trigger_error("stNetLiteCsPack::ping $sSockResName invalid resource [$this->sPeerIP] !", E_USER_ERROR);
//			return false;
//		}
//		if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::ping to $this->sPeerIP");
//		$this->preareData(array(), $sSockResName, self::$aStatusFlag['ping']);
//		$this->aStatus[$sSockResName]['aStatus']['bWaitData'] = true;
//		$aSend = &$this->aStatus[$sSockResName]['aSend'];
//		$nType = $aSend['nType']; //empty($aSend['mData']) ? $aSend['nType'] | self::$aStatusFlag['bEnd'] : $aSend['nType'];
//		$sType = bitxUtls::dataTrans($nType, 'ushort', $bLoading=false, $nPos);
//		$mData = $this->oPack->preSendData($sType . $aSend['mData']);
//		$aSend['mData'] = '';
//		$this->write($sSockResName, $mData, $aSend['mData1']);
	}

	function write($sSockResName, $mData, &$sSend_Data1)
	{
		$nMaxBuffer = 4096;
		if(!isset($this->aSockets[$sSockResName]) || !is_resource($rSocket = $this->aSockets[$sSockResName]))
		{
			trigger_error("stNetLiteCsPack::write $sSockResName invalid resource !", E_USER_ERROR);
			return false;
		}
		$aRead = $aExcept = null;
		$aWrite = array($rSocket);
		$num = $nPos = 0;
		$nSize = strlen($mData);
		while($num < 1)
		{
			$num = @stream_select($aRead, $aWrite, $aExcept, $this->nSockTimeout);
		}
		//if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::write stream_select $num");
		$this->sTmpClient = $sSockResName;
		$nWr = fwrite($rSocket, substr($mData, $nPos, $nMaxBuffer));
		$sSend_Data1 = substr($mData, $nWr);
//		while($nWr = fwrite($rSocket, substr($mData, $nPos, $nMaxBuffer)))
//		{
//			if(false === $nWr || 0 == $nWr)
//			{
//				trigger_error("-------------stNetLiteCsPack::write fail !-----------*******!!!!!!!!", E_USER_ERROR);
//				return false;
//			}
//			$nPos += $nWr;
//		}
//		for($nPos = 0, $nSize = strlen($mData); $nPos < $nSize; )
//		{
//			$sData = substr($mData, $nPos, $nMaxBuffer);
//			trigger_error("stNetLiteCsPack::write data start $nPos - " .strlen($sData));
//			$nWr = fwrite($rSocket, $sData);
//			$nPos += $nWr;
//		}
//		$sSend_Data1 = substr($mData, $nPos);
		//if(st_isDebugMode('stNetx.php')) trigger_error("stNetLiteCsPack::write data($nPos - $nWr / $nSize) to $this->sPeerIP");
		flush();
		return true;
	}

}

function exceptWriteNetwork($aErr)
{//!---php cannot work with this exception---!
	$sPattern = '/failed with errno=\d+ Broken pipe/';
	$aFail = array('tRangTime' => 300, 'nMaxFail' => 3, );
	static $s_aLogs = array();
	if(preg_match($sPattern, $aErr['sMsg']))
	{
		$tFail = time() - $aFail['tRangTime'];
		foreach($s_aLogs as $n => $tTime)
		{
			if($tTime < $tFail) unset($s_aLogs[$n]);
		}
		if(count($s_aLogs) >= $aFail['nMaxFail'])
		{
			trigger_error("exceptWriteNetwork() --[!!! STOP NOW !!!]--- ", E_USER_ERROR);
			exit(0);
		}
		$s_aLogs[] = time();
	}
}

$ast_ErrExceptHandlers['stNetx.php'] = 'exceptWriteNetwork';//!---php cannot work with this exception---!
?>