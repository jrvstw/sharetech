<?

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// Bitwise 位元處理運用物件定義
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
//include_once("Bitwise.php");
//$oFlag = new Bitwise;
//$oFlag->setOn(9);
//if($oFlag->isOn(3) == 3);
//$oFlag->setAll(false);
//$oFlag->setName('a,b,c,,d,  ,e,f');//result only=a,b,c,d,e,f
//$oFlag->setName(array('a'=>1,'b'=>2,'c'=>4));
//$oFlag->setOn('a');
//$n = $oFlag->isOn('b,c');
//$oFlag->setOff(array('d', 'c'));
//$oFlag->getBit('f,c,d');
//$oFlag->set(20);
//$nFlag = $oFlag->get();//$nFlag = 20

class baseBitwise
{
	var $ncFlag = 0;

	function baseBitwise($ncFlag = 0) {$this->set($ncFlag);}
	function set($ncFlag) {$this->ncFlag = $ncFlag;}
	function get() {return $this->ncFlag;}
	function setOn($ncFlag) {return ($this->ncFlag |= $ncFlag);}
	function setOff($ncFlag) {return ($this->ncFlag &= ~$ncFlag);}
	function isOn($ncFlag) {return ($this->ncFlag & $ncFlag);}
	function setAll($bOn) {$this->ncFlag = $bOn ? 0xffffffff: 0;}
}

class Bitwise extends baseBitwise
{
	var $aIndexName = array();

	function setName($mIndexName)
	{//set all use bits for each name (max 32 items)
		if(st_isDebugMode('Bitwise.php'))
			trigger_error("Initial Bitwise::setName \$mIndexName=" . st_DebugDump2String($mIndexName));
		if(is_array($mIndexName))
		{
			$this->aIndexName = $mIndexName;
			return true;
		}
		if(!is_string($mIndexName))
			return !trigger_error("Not array or string in setName::\$mIndexName=" . st_DebugDump2String($mIndexName), E_USER_ERROR);
		$this->aIndexName = array();
		$mIndexName = split(',+', $mIndexName);
		$nId = 1;
		foreach($mIndexName as $sName)
		{
			if( !($sName = trim($sName)) )
				continue;
			$this->aIndexName[$sName] = $nId;
			if($nId > 0x40000000)
				break;//Over 32 bits.
			$nId *= 2;
		}
		if(st_isDebugMode('Bitwise.php'))
			trigger_error("End Bitwise::setName \$aIndexName=" . st_DebugDump2String($this->aIndexName));
		return true;
	}

	function setOn($mIndexName)
	{//set (combine) bit(s) on, $mIndexName can be integer, string or array; return new combine interger
		if(is_int($mIndexName))
			return parent::setOn($mIndexName);
		else if(is_string($mIndexName))
			$mIndexName = split(',+', $mIndexName);
		else if(!is_array($mIndexName))
			return $this->ncFlag;

		foreach($mIndexName as $name)
		{
			if(isset($this->aIndexName[$name]))
				parent::setOn($this->aIndexName[$name]);
		}
		return $this->ncFlag;
	}

	function setOff($mIndexName)
	{//set (combine) bit(s) off, $mIndexName can be integer, string or array; return new combine interger
		if(is_int($mIndexName))
			return parent::setOff($mIndexName);
		else if(is_string($mIndexName))
			$mIndexName = split(',+', $mIndexName);
		else if(!is_array($mIndexName))
			return $this->ncFlag;

		foreach($mIndexName as $name)
		{
			if(isset($this->aIndexName[$name]))
				parent::setOff($this->aIndexName[$name]);
		}
		return $this->ncFlag;
	}

	function isOn($mIndexName)
	{//test (combine) bit(s) on, $mIndexName can be integer, string or array; return a combine interger result
		if(is_int($mIndexName))
			return parent::isOn($mIndexName);
		else if(is_string($mIndexName))
			$mIndexName = split(',+', $mIndexName);
		else if(!is_array($mIndexName))
			return 0;

		$nId = 0;
		foreach($mIndexName as $name)
		{
			if(isset($this->aIndexName[$name]))
				$nId |= $this->aIndexName[$name];
		}
		return parent::isOn($nId);
	}

	function isAllOn($mIndexName)
	{
		return (Bitwise::isOn($mIndexName) == Bitwise::getBit($mIndexName));
	}

	function getBit($mIndexName)
	{//get (combine) bit(s) , $mIndexName can be integer, string or array; return a combine interger result
		if(is_int($mIndexName))
			return $mIndexName;
		if(is_string($mIndexName))
			$mIndexName = split(',+', $mIndexName);
		else if(!is_array($mIndexName))
			return 0;

		$nId = 0;
		foreach($mIndexName as $name)
		{
			if(isset($this->aIndexName[$name]))
				$nId |= $this->aIndexName[$name];		
		}
		return $nId;
	}
}

?>