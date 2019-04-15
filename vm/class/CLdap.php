<?
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// CLdap LDAP 通訊物件
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
class CLdap
{
	var $bDebugMode = false;
	var $rConn;
	var $aInfo;

	function connect($sIPs, $sAccount, $sPassword, $nLdapPort = 389)
	{//Connect LDAP server.
		$aIPs = split("[ ,]+", trim($sIPs));//maybe multiple IPs ('1.1.1.2, 1.2.2.2')
		foreach($aIPs as $sIP)
		{
			$ssl = false;
			if( !($this->rConn = @ldap_connect($sIP, $nLdapPort))) {
				$ssl = true;
			} else {
				@ldap_set_option($this->rConn, LDAP_OPT_PROTOCOL_VERSION, 3);
				@ldap_set_option($this->rConn, LDAP_OPT_REFERRALS, 0);
				if(@ldap_bind($this->rConn, $sAccount, $sPassword)) {
					return $sIP;
				} else {
					$ssl = true;
				}
				$this->close();
			}
			if($ssl == true) {
				if(!($this->rConn = @ldap_connect("ldap://".$sIP, $nLdapPort))) {
					continue;
				}
				@ldap_set_option($this->rConn, LDAP_OPT_PROTOCOL_VERSION, 3);
				@ldap_set_option($this->rConn, LDAP_OPT_REFERRALS, 0);
				@ldap_start_tls($this->rConn);
				if (@ldap_bind($this->rConn, $sAccount, $sPassword)) {
					return $sIP;
				}
				$this->close();
			}
		}
		//$this->debugMsg("Login LDAP server ({$sAccount} / {$sPassword}) FAIL!");
		return false;
	}

	function search($sBasedn, $sFilter, $aUseAttrib = false)
	{//Base on $sBasedn to search with $sFilter result.
		if(is_array($aUseAttrib))
			$sr = ldap_search($this->rConn, $sBasedn, $sFilter, $aUseAttrib);
		else
			$sr = ldap_search($this->rConn, $sBasedn, $sFilter);
		$this->aInfo = ldap_get_entries($this->rConn, $sr);
		return $this->aInfo["count"];
	}

	function close()
	{
		@ldap_close($this->rConn);
	}

	function debugMsg($sMsg)
	{
		if(!$this->bDebugMode)
			return;
		echo "$sMsg\n";
	}
}

//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
// ADserver AD 通訊物件
//________________________________________________________________________________________
//----------------------------------------------------------------------------------------
class ADserver extends CLdap
{//Ad
	var $sConfigFile = '/PCONF/auth/ad.cfg';
	var $aSystemGroupIgnore;
	var $aSystemUserIgnore;

	var $aConfig;

	function getConfig(&$sIPs, &$sBasedn)
	{
		$this->aConfig = parse_ini_file($this->sConfigFile);
		$sIPs = $this->aConfig['ip'];
		$sBasedn = 'dc=' . str_replace('.', ',dc=', $this->aConfig['domain']);
		$this->aSystemGroupIgnore = split(",+", $this->aConfig['aSystemGroupIgnore']);
		$this->aSystemUserIgnore = split(",+", $this->aConfig['aSystemUserIgnore']);
	}

	function login($sAccount, $sPassword, &$aResult)
	{
		$aUseAttrib = array('dn', 'memberof', 'physicaldeliveryofficename', 'department', 'company');//all use attrib
		$nInitItem = 2;// from 'physicaldeliveryofficename' position

		$this->getConfig($sIPs, $sBasedn);
		$sFilter = "samaccountname=$sAccount";
		if(!$this->connect($sIPs, "{$sAccount}@{$this->aConfig[domain]}", $sPassword))
			return false;
		$nLength = $this->search($sBasedn, $sFilter, $aUseAttrib);
		$this->close();
		if($nLength < 1)
			return false;
		$this->getOuAndMemberOf($aResult, 0);
		$l = count($aUseAttrib);
		for(; $nInitItem < $l; ++$nInitItem)
		{
			$sIndex = $aUseAttrib[$nInitItem];
			$aResult[$sIndex] = $this->aInfo[0][$sIndex][0];
		}
		return true;
	}
	
	function sslvpn_ldap_login()
	{
		$aUseAttrib = array('dn', 'memberof', 'physicaldeliveryofficename', 'department', 'company', 'samaccountname', 'displayname');//all use attrib
		$nInitItem = 2;// from 'physicaldeliveryofficename' position

		$this->getConfig($sIPs, $sBasedn);
		$sFilter = "samaccountname=*";
		if(!$this->connect($sIPs, "{$this->aConfig[admin]}@{$this->aConfig[domain]}", $this->aConfig['password']))
			return false;
		$nLength = $this->search($sBasedn, $sFilter, $aUseAttrib);
		$this->close();
		if($nLength < 1)
			return false;
		$aRedata = array();
		for($a=0;$a<$nLength;$a++){
			$this->getOuAndMemberOf($aResult, $a);
			$aRedata[$a]['displayname'] = $this->aInfo[$a]['displayname'][0];
			$aRedata[$a]['samaccountname'] = $this->aInfo[$a]['samaccountname'][0];
			$aRedata[$a]['company'] = $this->aInfo[$a]['company'][0];
			$aRedata[$a]['department'] = $this->aInfo[$a]['department'][0];
			$aRedata[$a]['physicaldeliveryofficename'] = $this->aInfo[$a]['physicaldeliveryofficename'][0];
			$aRedata[$a]['aOU'] = $aResult['aOU'];
			$aRedata[$a]['aMemberOf'] = $aResult['aMemberOf'];
		}
		return $aRedata;
	}
	
	function sslvpn_group_another_name($groupname)
	{
		$aUseAttrib = array('dn', 'memberof', 'physicaldeliveryofficename', 'department', 'company', 'samaccountname', 'displayname');//all use attrib
		$nInitItem = 2;// from 'physicaldeliveryofficename' position

		$this->getConfig($sIPs, $sBasedn);
		$sFilter = "get-adgroupmember -Identity \"".$groupname."\"";
		if(!$this->connect($sIPs, "{$this->aConfig[admin]}@{$this->aConfig[domain]}", $this->aConfig['password']))
			return false;
		$nLength = $this->search($sBasedn, "cn=" . $groupname, $aUseAttrib);
		$this->close();
		return $this->aInfo;
	}
	function getAllOu(&$aResult)
	{
		$sFilter = "ou=*";
		$aUseAttrib = array('dn', 'ou');

		$aResult = array();
		$this->getConfig($sIPs, $sBasedn);
		if(!$this->connect($sIPs, "{$this->aConfig[admin]}@{$this->aConfig[domain]}", $this->aConfig['password']))
			return;
		$nLength = $this->search($sBasedn, $sFilter, $aUseAttrib);
		$this->close();
		for($i = 0; $i < $nLength; $i++)
		{
			$this->getOuAndMemberOf($a, $i);
			$sUnit = '';
			foreach((array)$a['aOU'] as $sItem)
			{
				if('Domain Controllers' != $sItem)
					$sUnit = ($sUnit ? "$sItem/$sUnit" : $sItem);
			}
			if($sUnit)
				$aResult[] = $sUnit;
		}
		usort($aResult, sortByLang);
	}

	function getAllGroup(&$aResult, $bDisplayname)
	{
		$this->getAllSamaccountname($aResult, true, $bDisplayname);
	}

	function getAllAccount(&$aResult, $bDisplayname)
	{
		$this->getAllSamaccountname($aResult, false, $bDisplayname);
	}

	function getAllSamaccountname(&$aResult, $bGroup, $bDisplayname)
	{
		$aUseAttrib = array('samaccountname', 'displayname');//all use attrib
		if($bGroup)
			$sFilter = "(| (samaccounttype=268435456) (samaccounttype=268435457) )";
		else
			$sFilter = "samaccounttype=805306368";

		$aResult = array();
		$this->getConfig($sIPs, $sBasedn);
		if(!$this->connect($sIPs, "{$this->aConfig[admin]}@{$this->aConfig[domain]}", $this->aConfig['password']))
			return;
		$nLength = $this->search($sBasedn, $sFilter, $aUseAttrib);
		$this->close();
		for($i=0; $i < $nLength; $i++)
		{
			
			if(!($sName = $this->aInfo[$i]['samaccountname'][0]) ||
					($bGroup && in_array($sName, $this->aSystemGroupIgnore)) || (!$bGroup && in_array($sName, $this->aSystemUserIgnore)) )
				continue;
			$aResult[] = $sName . ($bDisplayname && $this->aInfo[$i]['displayname'] ? " ({$this->aInfo[$i][displayname][0]})" : "");
		}
		usort($aResult, sortByLang);
	}
	
	function getAccountOpexchange(&$aResult, $bGroup, $bDisplayname)
	{
		$aUseAttrib = array('samaccountname', 'displayname', 'proxyaddresses');//all use attrib
		if($bGroup)
			$sFilter = "(| (samaccounttype=268435456) (samaccounttype=268435457) )";
		else
			$sFilter = "samaccounttype=805306368";
		$aResult = array();
		$this->getConfig($sIPs, $sBasedn);
		if(!$this->connect($sIPs, "{$this->aConfig[admin]}@{$this->aConfig[domain]}", $this->aConfig['password']))
			return;
		$nLength = $this->search($sBasedn, $sFilter, $aUseAttrib);
		$this->close();
		for($i=0; $i < $nLength; $i++)
		{		
			if(!($sName = $this->aInfo[$i]['samaccountname'][0]) || !$this->aInfo[$i]['proxyaddresses'] ||
					($bGroup && in_array($sName, $this->aSystemGroupIgnore)) || (!$bGroup && in_array($sName, $this->aSystemUserIgnore)) )
				continue;
			unset($aMail);
			foreach($this->aInfo[$i]['proxyaddresses'] as $spak => $spav){
				if(stristr($spav , "smtp:") || stristr($spav , "SMTP:")) {				
					$aSearch = array("smtp:","SMTP:");
					$aMail[] = trim(str_replace($aSearch, "", $spav));
				}
			}
			if(empty($aMail) == false) {
				sort($aMail);
				$sMail = implode(",", $aMail);
			}
			if($bGroup) $aResult[] = array($sName , ($bDisplayname && $this->aInfo[$i]['displayname'] ? "{$this->aInfo[$i][displayname][0]}" : "") , "Group" , $sMail);
			else $aResult[] = array($sName , ($bDisplayname && $this->aInfo[$i]['displayname'] ? "{$this->aInfo[$i][displayname][0]}" : "") , "Account" , $sMail);		
		}
		usort($aResult, sortByLang);
	}

	function getOuAndMemberOf(&$aResult, $nPos)
	{//取得所有隸屬組織與群組 //-===Private===-
	//'dn' => 'CN=syncs32,OU=工程部,OU=xyz.com,DC=sharetech,DC=com,DC=tw'
	//'memberof' => array ('count' => 1, 0 => 'CN=Group23,CN=Users,DC=sharetech,DC=com,DC=tw', 1 => 'CN=Group23,CN=Users,DC=sharetech,DC=com,DC=tw',...)
		$aMemberOf = array();
		$aOU = array();
		$aMix = explode(',', $this->aInfo[$nPos]['dn']);
		foreach((array)$aMix as $sItem)
		{//Get all belong organizational-unit
			$a = explode('=', $sItem);
			if($a[0] == 'OU')
				$aOU[] = $a[1];
		}
		if($aRow = $this->aInfo[$nPos]['memberof'])
		{//Get all member of group
			for($n = 0; $n < $aRow['count']; $n++)
			{
				$aMix = explode(',', $aRow[$n]);
				foreach((array)$aMix as $sItem)
				{
					$a = explode('=', $sItem);
					if($a[0] == 'CN')
					{
						$aMemberOf[] = $a[1];
						break;
					}
				}
			}
		}
		$aResult['aOU'] = $aOU;
		$aResult['aMemberOf'] = $aMemberOf;
	}
	
	function get_adinfo(){
		$this->getConfig($sIPs, $sBasedn);
		$info['ip'] = $this->aConfig['ip'];	
		$info['domain'] = $this->aConfig['domain'];
		$info['admin'] = $this->aConfig["admin"];
		$info['password'] = $this->aConfig["password"];
		$info['aSystemGroupIgnore'] = implode("\n" , $this->aSystemGroupIgnore);
		$info['aSystemUserIgnore'] = implode("\n" , $this->aSystemUserIgnore);
		return $info;
	}
}//---------------------------------------------------END class ADserver

if(!defined('PAGE_LANGUAGE'))
	include_once('/CFH3/servermodel/serverlanguage');

function sortByLang($a, $b)
{
	switch(PAGE_LANGUAGE)
	{
		case 'big5': 
			$a = mb_convert_encoding($a, "BIG-5", "UTF-8");
			$b = mb_convert_encoding($b, "BIG-5", "UTF-8");
			break;
		case 'gb2312'://GB2312 的 utf8 碼已照筆劃順序編碼
		case 'eng':
		default: break;
	}
	return strcmp($a, $b);
}

?>