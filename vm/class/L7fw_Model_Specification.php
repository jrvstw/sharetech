<?
class L7fw_Model_Specification
{
	var $sModelFile = '/CFH3/servermodel/servermodel';//�����]�w��
	var $sModelScoreFile = '/PDATA/L7FWMODEL/L7fw_Model_Score.cfg';//�����\����
	var $sModelSpecification = '/PDATA/L7FWMODEL/L7fw_Specification.cfg';//�����\��w�q�]�w��
	
	var $sServerModel = "";
	var $nModelScore = 0;
	var $aSpec = array();
	var $isInitial = false;
	
	function isEnable($keyName)
	{
		$this->readConfig();

		if(isset($this->aSpec[$keyName]))
		{
			if($this->nModelScore >= $this->aSpec[$keyName])
				return true;
		}
		
		return false;	
	}

	function readConfig()
	{
		if($this->isInitial) 
			return true;

		//���o����
		include($this->sModelFile);
		$this->sServerModel = SERVERMODEL;
	
		//���o����
		if(file_exists($this->sModelScoreFile))
		{
			$file = file($this->sModelScoreFile);
			foreach((Array)$file as $line)
			{
				$t = explode('=', $line);
				if(trim($t[0]) == "")
					continue;
				if(trim($t[0]) == $this->sServerModel)
					$this->nModelScore = intval(trim($t[1]));
			}
		}
	
		//�N �\��w�q�]�w�� �ন�}�C
		if(file_exists($this->sModelSpecification))
		{
			$file = file($this->sModelSpecification);
			foreach((Array)$file as $line)
			{
				$line = trim($line);
				if($line[0] == "#" || $line[0] == "")
					continue;				
				list($key, $value) = explode('=', $line, 2);
				$key = trim($key);
				$value = trim($value);				
				$this->aSpec[$key] = $value;
			}
		}
	
		$this->isInitial = true;
	}
}
?>
