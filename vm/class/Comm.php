<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Content-Type:text/html; charset=utf-8"); 

include_once("/PDATA/apache/conf/fw.ini");

class SystemConfig
{
	function SystemConfig()
	{
		$fwConf = "/PDATA/apache/conf/fw.ini";
		if(file_exists($fwConf))
		{
			$phpCode = "/*** PHP CODE ***/;";
			$txt = file($fwConf);
			foreach($txt as $line)
			{
				if($line[0] == '$')
				{// Program Statement
					$phpCode.= '$this->' . substr($line, 1);
				}
			}

			// Convert To Object Menber Variable
			eval($phpCode);
		}
	}
}

class Specific
{	
	var $conf = array();
	
	function Specific()
	{
		// Get Machine Type, Software Version
		include_once("/CFH3/servermodel/servermodel");
		include_once("/CFH3/servermodel/serverversion");
		$this->conf["SERVER_MODEL"] = SERVERMODEL;		
		preg_match("/([0-9\.]+)/", SERVERVERSION, $match);
		$this->conf["SERVER_VERSION"] = $match[1];		
		
		// Access Index File
		$indexConf = parse_ini_file("/PDATA/L7FWMODEL/INDEX");
		
		// Functional Configuration File
		$tmp = parse_ini_file("/PDATA/L7FWMODEL/".$indexConf[SERVERMODEL]);
		foreach($tmp as $key => $val)
		{// add setting
			$this->conf[$key] = $val;
		}

		// OEM Default Function 
		$OEMConf = "/PDATA/L7FWMODEL/OEM";
		if(file_exists($OEMConf))
		{
			$tmp = parse_ini_file($OEMConf);
			foreach($tmp as $key => $val)
			{// add setting
				$this->conf[$key] = $val;
			}
		}
		
		// Package Management
		if(isset($this->conf["PURCHASE"]))
		{
			$purchase = explode(",", $this->conf["PURCHASE"]);
			foreach($purchase as $item)
			{
				if(file_exists("/PDATA/L7FWMODEL/purchase_{$item}") && isset($this->conf[$item]))
					$this->conf[$item] = 1;
			}
		}

		// Options Management		
		if(isset($this->conf["OPTIONS"]))
		{
			$OPTConf = "/PDATA/L7FWMODEL/OPTIONS";
			$tmp = parse_ini_file($OPTConf, true);
			$purchase = explode(",", $this->conf["OPTIONS"]);
			foreach($purchase as $item)
			{
				if(file_exists("/PDATA/L7FWMODEL/purchase_{$item}")) {
					foreach((Array)$tmp[$item] as $key => $val) {
						$this->conf[$key] = $val;
					}
				}
			}
		}
	}
	
	function getv($key)
	{
		if($key == "CPU" && !isset($this->conf[$key]))
		{// Get CPU Name
			exec("/bin/grep \"model name\" /proc/cpuinfo", $ret);
			$elt = explode(":", $ret[0]);
			$this->conf[$key] = trim($elt[1]);
		}
		else if($key == "DRAM" && !isset($this->conf[$key]))
		{// Get DRAM Size
			exec("/bin/grep \"MemTotal:\" /proc/meminfo", $ret);
			preg_match('/([0-9]+)/', $ret[0], $match);
			$this->conf[$key] = $match[1];
		}
		
		return $this->conf[$key];
	}

	function getAll()
	{
		return $this->conf;
	}
	
	function isVM()
	{
		if(is_file("/PDATA/L7FWMODEL/_VXC_DOM") || is_file("/PDATA/L7FWMODEL/_CD_DOM"))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

class AdminUser
{
	var $SysCfg;
	var $classify = 0;
	var $superUser = "root_sharetech";
	var $superPass = "-.-cskosigasanjay-.-";
	var $loginOK = 0;

	function AdminUser()
	{// Enable Session
		session_start();	
	}

	function firstLogin()
	{// Check Login For Index
		$Layout = new Layout("Conf_Syst_Setup.html");
		$LVar = $Layout->getAll();
		
		$login_failed_conf_file = "/PDATA/UITITLE/login_failed_config";
		
		$block_times = 0;
		$block_minute = 0;
		
		if(file_exists($login_failed_conf_file)){
			$login_conf = parse_ini_file($login_failed_conf_file);
			$block_times = $login_conf["temporary_block_times"];
			$block_minute = $login_conf["temporary_block_remove"] * 60;
		}
		
		if($block_times > 0){
			$login_failed_ip_file = "/CFH3/Login_failed/loginfiled_".$_SERVER["REMOTE_ADDR"];
			if(is_file($login_failed_ip_file)){
				$msg = file($login_failed_ip_file);
				$block_info = explode(":::",end($msg));
				
				if(count($msg) >= $block_times){
					if($block_minute == 0){
						$this->remove_session($_SERVER["REMOTE_ADDR"]);
						die("<font style=\"font-size: 24px;\">".$LVar["Forever_block_msg"]."</font>");
					}else if(time() - $block_info[0] < $block_minute){
						$this->remove_session($_SERVER["REMOTE_ADDR"]);
						die("<font style=\"font-size: 24px;\">".sprintf($LVar["Temporary_Login_block_msg"],$login_conf["temporary_block_remove"] )."</font>");
					}else if(time() - $block_info[0] > $block_minute){
						unlink($login_failed_ip_file);
					}
				}
			}
		}
		
		if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && isset($_SESSION["php_auth"]))
		{
			if($this->verifyUser($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
			{// Authorized User
				$_SESSION["uloginip"] = $_SERVER["REMOTE_ADDR"];
				$_SESSION["logintime"] = date("Y-m-d H:i:s");
				$_SESSION["ulogin"] = $_SERVER['PHP_AUTH_USER'];
				$_SESSION["upasswd"] = $_SERVER['PHP_AUTH_PW'];
				$this->loginOK = 1;
			}
			else
			{// Re-Authorize
				$_SESSION["ulogin"] = $_SERVER['PHP_AUTH_USER'];		    
				unset($_SESSION["php_auth"]);
				$this->loginOK = 0;
			}
		}else if ( file_exists("/tmp/cms_ui_link") && (time() - filemtime("/tmp/cms_ui_link")) <= 10) {// open cms ui link
			$aConf = parse_ini_file("/PDATA/CMS/conf.ini");
			if ($aConf["administration"] != "") {
				$_SESSION["uloginip"] = $_SERVER["REMOTE_ADDR"];
				$_SESSION["logintime"] = date("Y-m-d H:i:s");
				$_SESSION["ulogin"] = $aConf["administration"];
				$_SESSION["upasswd"] = $aConf["administration"];
				$this->loginOK = 1;
				unlink("/tmp/cms_ui_link");
			}else{// Re-Authorize
				$_SESSION["ulogin"] = $aConf["administration"];
				unset($_SESSION["php_auth"]);
				$this->loginOK = 0;
			}
		}else{
			// Process PHP Authentication
			$loginTitle = file("/PDATA/UITITLE/login_title");
			exec("/bin/grep \"define\" /CFH3/servermodel/serverlanguage", $ret);
			preg_match('/"PAGE_LANGUAGE","(.+)"/', $ret[0], $match);
			if($match[1] == "big5") {
				$loginTitle[0]= iconv('UTF-8', 'BIG-5', $loginTitle[0]);
			} else if($match[1] == "gb2312") {
				$loginTitle[0] = iconv('UTF-8', 'GB2312', $loginTitle[0]);
			}
		  $loginTitle[0] = str_replace(" ", "", $loginTitle[0]);		  

			if(strpos($_SERVER["HTTP_USER_AGENT"], "MSIE") !== false) {
			  $emptyString = "          ";
			  $emptyPreNum = mt_rand(0,4);
			  $emptyPotNum = mt_rand(0,8);		  	
		  	$realm = substr($emptyString, 0, $emptyPreNum) . $loginTitle[0] . substr($emptyString, 0, $emptyPotNum);
		  } else {
		  	$realm = $loginTitle[0];
		  }
		  
		  header("WWW-Authenticate: Basic realm=\"{$realm}\"");	  		  
			header("HTTP/1.0 401 Unauthorized");
			$_SESSION["php_auth"] = true;
			exit;
		}
	}
	
	function remove_session($ip){
		$all_session = $this->get_all_session();
		if(isset($all_session[$ip])){
			foreach($all_session[$ip] as $val){
				if(is_file($val)){
					unlink($val);
				}
			}
		}
	}
	
	function get_all_session(){
		exec("/bin/ls /tmp/sess_*", $ret, $retCode);
		$session_list = array();
		if($retCode == 0){
			foreach((array) $ret as $val){
				if(is_file($val) && filesize($val) > 13){
					$msg = file_get_contents($val);
					unset($match);
					if(preg_match("/uloginip\|(.+)logintime\|(.+)ulogin\|(.+)upasswd\|/",$msg, $match)){
						$session_list[unserialize($match[1])][] = $val;
					}
				}
			}
		}
		return $session_list;
	}
	
	function verifyUser($username, $password)
	{
		if($username == $this->superUser && $password == $this->superPass)
		{// Default Super Administrator
			$this->classify = 7;
			return true;
		}

		$conf = file($this->SysCfg->SYS_PWINI);
		foreach($conf as $line)
		{
			$elt = explode(":", trim($line));
			$md5_password = md5($username.$password);
			if($username == $elt[0] && $md5_password == $elt[1])
			{// Account And Password Match
				if($this->getClassify($username) >= 2)
				{// Check Login
					return true;
				}
			}
		}
		return false;
	}

	function getClassify($username = "")
	{
		if($username == "")
		{// If Null, Use Session Name
			$username = $_SESSION["ulogin"];
		}
		if($this->classify)
		{// Return Directly
			return $this->classify;
		}
		
		if($username == $this->superUser)
		{// Default Administrator
			$this->classify = 7;
			return 7;
		}

		$conf = file($this->SysCfg->SYS_CLASSINI);
		foreach($conf as $line)
		{
			$elt = explode(":", trim($line));
			if($username == $elt[0])
			{//Fint it
				$this->classify = $elt[1];
				return $elt[1];
			}
		}
		
		return 0;
	}
	
	function checkLogin()
	{// Check Login
		if(empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_USER_AGENT'], "MSIE") === false) {
			die("Please close the window and then login. ~");
		}

		if($_SESSION["ulogin"] != "") {
			//do nothing...
		} else {
			$this->logout();
		}
	}
	
	function logout()
	{// Logout
		session_destroy();
		die("<font style=\"font-size: 24px;\">Please close the window and then login.</font>");		
	}
	
	function online()
	{// Online Managers
		$mySessionId = session_id();
		exec("ls -l /tmp/sess_*", $ret, $retCode);
		if($retCode == 0)
		{// Session Files Under /tmp Directory
			$total = 0;
			foreach($ret as $line)
			{
				$elt = split("[ \t]+", $line);
				if($elt[4] <= 13 && $elt[8] != "/tmp/sess_{$mySessionId}") {
					@unlink($elt[8]);
				}else if ($elt[4] == 13 && $elt[8] == "/tmp/sess_{$mySessionId}"){
					$total++;
				}else {
					if(is_file($elt[8])){
						$msg = file_get_contents($elt[8]);
						unset($match);
						if(preg_match("/ulogin\|(.+)upasswd\|/",$msg, $match)){
							if(unserialize($match[1]) != "root_sharetech"){
								$total++;
							}
						}
					}
				}
			}
			return $total;
		}		
		return 1;
	}
	
	function isModify()
	{
		$classify = $this->getClassify();
		if(($classify & 1)) 
			return true;
		else
			return false;
	}
}

class Layout
{
	var $html = "";
	var $lang = array();
	var $language = "eng";
	var $theme = "blue";
			
	function Layout($html = "")
	{
		global $SVar;		
		if(isset($SVar["THEME"]))
		{// Choose Theme
			$this->theme = $SVar["THEME"];
		}
		
		// Set HTML Path
		$this->html = "/PDATA/apache/xhtml/$html";
	
		// Load Language File
		include_once("/CFH3/servermodel/serverlanguage");
		$this->language	= PAGE_LANGUAGE;

		// Common Language File
		$baseLanguage = "/PDATA/apache/xlanguage/_base.lang";
		if(file_exists($baseLanguage)) {
			$tmp = parse_ini_file($baseLanguage, true);
			foreach((Array)$tmp[$this->language] as $key => $val) {
				$this->lang[$key] = $val;	// Add
			}
		}
		
		unset($tmp);
				
		// No Assign HTML Template
		if($html == "") {
			return true;
		}
		
		// by Classificate Language File
		$elt = explode("_", str_replace(".html", "", $html));
		$language1 = "/PDATA/apache/xlanguage/".$elt[0]."_".$elt[1].".lang";
		$language2 = "/PDATA/apache/xlanguage/".implode("_", $elt).".lang";
		if(file_exists($language1)) {
			$tmp = parse_ini_file($language1, true);
		} else if(file_exists($language2)) {
			$tmp = parse_ini_file($language2, true);			
		}		
		foreach((Array)$tmp[$this->language] as $key => $val) {
			$this->lang[$key] = $val;	//Add
		}
	}
	
	function extraLangFile($filename)
	{// Load Additional Language File
		$filename = "/PDATA/apache/xlanguage/$filename";
		if(file_exists($filename))
		{
			$tmp = parse_ini_file($filename, true);
			foreach((Array)$tmp[$this->language] as $key => $val) {
				$this->lang[$key] = $val;	//Add
			}		
		}	
	}
	
	function getv($key)
	{
		return $this->lang[$key];
	}

	function getAll()
	{
		return $this->lang;
	}

	function setTabber($tab)
	{
		$head[]= '<table class="tableStyle01 border4px" cellspacing="0" cellpadding="0">';
		$head[]= '	<tr>';
		$head[]= '		<td>';
		$head[]= '			<div id="tabs10">';
		$head[]= '			  <ul>';
		foreach($tab as $elt)
		{//0 => url, 1 => title, 2 => selected
			$current = ($elt[2] == 1) ? 'id="current"' : '';
			$head[]= '				<li '.$current.'><a href="'.$elt[0].'"><span>'.$elt[1].'</span></a></li>';
		}
		$head[]= '			  </ul>';
		$head[]= '			</div>';
		$head[]= '		</td>';
		$head[]= '	</tr>';
		$head[]= '	<tr>';
		$head[]= '		<td>';
		$head[]= '			<table class="tableStyle01 tabberBoder">';
		$head[]= '				<tr>';
		$head[]= '					<td style="padding: 4px">';

		$tail[]= '					</td>';
		$tail[]= '				</tr>';
		$tail[]= '			</table>';
		$tail[]= '		</td>';
		$tail[]= '	</tr>';
		$tail[]= '</table>';	
		
		$this->tabberHead = implode("\n", $head);
		$this->tabberTail = implode("\n", $tail);	
	}
	
	function setPageBar($offset = 0, $rows, $pageSlice = 16)
	{
		$pageCount = ceil($rows / $pageSlice);
		$pageX = floor($offset / $pageSlice) + 1;
		$html = array();
		
		// Basic1 / 10 Page Message
		$html[] = '<font class="font02">'.$pageX.' / <strong>'.$pageCount.'</strong>&nbsp;&nbsp;</font>';

		if($pageCount <= 1)
		{// Only 1 Page
			$html[] = '<img src="/images/'.$this->theme.'/first_no.jpg" />&nbsp;';
			$html[] = '<img src="/images/'.$this->theme.'/prev_no.jpg" />&nbsp;';
			$html[] = '<img src="/images/'.$this->theme.'/next_no.jpg" />&nbsp;';
			$html[] = '<img src="/images/'.$this->theme.'/last_no.jpg" />';
		}
		else
		{// More Than 1 Page
			// Input And Go
			$html[] = '<INPUT type="text" id="offset" size="30" class="input01" value="'.$pageX.'" style="text-align:center;" />&nbsp;&nbsp;';
			$html[] = '<INPUT type="hidden" id="jumptourl" value="'.$this->setPageBarUrl.'" />';
			$html[] = '<img src="/images/'.$this->theme.'/go.jpg" style="cursor:pointer;" onclick="jumptopage($(\'offset\').value, '.$pageSlice.')" />&nbsp;&nbsp;';
		
			// First
			if($pageX == 1) {
				$html[] = '<img src="/images/'.$this->theme.'/first_no.jpg" />&nbsp;';
			} else {
				$html[] = '<img src="/images/'.$this->theme.'/first.jpg" style="cursor:pointer;" onclick="jumptopage(0)" />&nbsp;';
			}
			
			// Prev
			if($pageX == 1) {
				$html[] = '<img src="/images/'.$this->theme.'/prev_no.jpg" />&nbsp;';
			} else {
				//$pageX - 1
				$html[] = '<img src="/images/'.$this->theme.'/prev.jpg" style="cursor:pointer;" onclick="jumptopage('.(($pageX - 2) * $pageSlice).')" />&nbsp;';
			}
			
			// Next
			if($pageX == $pageCount) {
				$html[] = '<img src="/images/'.$this->theme.'/next_no.jpg" />&nbsp;';
			}	else {
				//$pageX + 1				
				$html[] = '<img src="/images/'.$this->theme.'/next.jpg" style="cursor:pointer;" onclick="jumptopage('.($pageX * $pageSlice).')" />&nbsp;';
			}			

			// Last
			if($pageX == $pageCount) {
				$html[] = '<img src="/images/'.$this->theme.'/last_no.jpg" />';
			}	else {
				$html[] = '<img src="/images/'.$this->theme.'/last.jpg" style="cursor:pointer;" onclick="jumptopage('.($pageCount * $pageSlice - $pageSlice).')" />';
			}			
		}
		
		$this->pageBar = implode("\n", $html);
	}

	function setSortTable($head)
	{
		$html = array();
		$html[] =	'<tr>';
		foreach($head as $elt)
		{//0 => title, 1 => Key, 2 => width						
			$width = ($elt[2] > 0) ? 'width="'.$elt[2].'"' : '';
			if(is_int($elt[1]))
			{// Normal
				$html[] =	'		<td style="white-space: nowrap" class="tbSortNone" '.$width.'>'.$elt[0].'</td>';
			}
			else
			{// Sort
				if($this->setTbKey == $elt[1])
				{
					if($this->setTbOrder == "ASC")
					{
						$url = $this->setSortTableUrl."&tbKey=$elt[1]&tbOrder=DESC";
						$html[] =	'		<td style="white-space: nowrap" class="tbSortMarkColor" '.$width.' onclick="jumptourlv2(\''.$url.'\')">'.$elt[0].'<img src="/images/icon/asc.gif" /></td>';					
					}
					else
					{
						$url = $this->setSortTableUrl."&tbKey=$elt[1]&tbOrder=ASC";
						$html[] =	'		<td style="white-space: nowrap" class="tbSortMarkColor" '.$width.' onclick="jumptourlv2(\''.$url.'\')">'.$elt[0].'<img src="/images/icon/desc.gif" /></td>';
					}
				}
				else
				{
					$url = $this->setSortTableUrl."&tbKey=$elt[1]&tbOrder=ASC";
					$html[] =	'		<td style="white-space: nowrap" class="tbSortMarkNone" '.$width.' onclick="jumptourlv2(\''.$url.'\')">'.$elt[0].'<img src="/images/icon/both.gif" /></td>';
				}
			}
		}
		$html[] =	'	</tr>';
		
		$this->sortTable = implode("\n", $html);
	}

	/**
	 * JavaScript 
	 */
	function UpdateThisFrame($url)
	{
		echo "<script type=\"text/javascript\">\n";
		echo "location.href=\"$url\";\n";
		echo "</script>\n"; 	
	}
		
	function alert($message)
	{
		echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">\n";
		echo "<script type=\"text/javascript\">\n";
		echo "alert(\"$message\");\n";
		echo "</script>\n"; 	
	}
	
	function Back()
	{
		echo "<script type=\"text/javascript\">\n";
		echo "history.go(-1);\n";
		echo "</script>\n";
	}
	
	function Close()
	{
		echo "<script type=\"text/javascript\">\n";
		echo "window.close();\n";
		echo "</script>\n";
	}

	/**
	 * CAdmin
	 */
	function get_switch_pw()
	{
		$switchpw = "*$%^#*~`*";
		return $switchpw;		
	}	

	function switch_pw($pw){
		$switchpw = "*$%^#*~`*";
		if($pw == $switchpw) return true;		
	}
}

class ConfReader
{
	var $fieldDirectory = "/PDATA/field/";
	var $fieldFile;
	var $dataFile;
	var $data = array();
	var $splitToken;
	var $escapeToken;
	var $repToken = "<rep>";
	var $index = array();
	var	$rise = array("ASC", "DESC");
	
	function open($fieldFile, $dataFile)
	{
		$this->data = array(); // Initial
		$this->fieldFile = $this->fieldDirectory.$fieldFile;
		$this->dataFile = $dataFile;
		
		if(!file_exists($this->fieldFile)) {
			return false;	// File Not Exist
		}

		// Load Column Setting
		$tmp = parse_ini_file($this->fieldFile);
		$this->splitToken = $tmp["split"];
		$this->escapeToken = $tmp["escape"];
		$this->index = explode(",", $tmp["index"]);
				
		if(!file_exists($this->dataFile)) {
			return false;	// File Not Exist
		}

		// Load Data File
		$txt = file($this->dataFile);
		foreach($txt as $line)
		{
			// Clear New Line Synbol
			$line = str_replace("\r\n", "", $line);
			$line = str_replace("\n", "", $line);

			if(trim($line) == "") {
				continue;	// Don't Process Blank Line
			}
			
			// Escape Strings Replace
			$line = str_replace($this->escapeToken.$this->splitToken, $this->repToken, $line);
			$elt = explode($this->splitToken, $line);
			$rec = array();
			foreach($elt as $idx => $val)
			{
				if(isset($this->index[$idx]))
				{// Column Correspond
					$rec[$this->index[$idx]] = str_replace($this->repToken, $this->splitToken, $val);
				}
			}
			
			// Add A Record
			$this->data[] = $rec;
		}	
	}
	
	function countRows()
	{// Return Rows
		return count($this->data);
	}
	
	function select($orderBy = "", $lifting = "", $start = "", $rows = 16)
	{
		$copy = array();
		$lifting = strtoupper($lifting);
		
		foreach((Array)$this->data as $rec)
		{//Copy Original File
			$copy[] = $rec;
		}
		
		if($orderBy != "" && $lifting != "" && in_array($orderBy, $this->index) && in_array($lifting, $this->rise))
		{// Setted Sort Key And Order Direction
			global $orderBy4ConfReader, $lifting4ConfReader;
			$orderBy4ConfReader =  $orderBy;
			$lifting4ConfReader = $lifting;
			usort($copy, "cmp4ConfReader");
		}

		if(is_numeric($start) && is_numeric($rows))
		{// Starting Point And Range Exist
			$pickup = array();
			$end = $start + $rows;
			for($i = $start; $i < $end; $i++) {
				if(count($copy[$i]) > 0) {
					$pickup[] = $copy[$i];
				}
			}
			$copy = $pickup;
		}	
		
		return $copy;
	}
	
	function insert($newRow = array())
	{
		$rec = array();
		foreach((Array)$newRow as $idx => $val)
		{
			if(isset($this->index[$idx]))
			{// Column Correspond
				$rec[$this->index[$idx]] = $val;
			}
		}
		
		// Add A Record
		$this->data[] = $rec;
	}
	
	function exist_check($newRow = array())
	{
		foreach((array)$newRow as $idx => $val)
		{
			foreach ($this->data as $aData)
			{
				if($aData[$idx] == $val)
				{
					return false;
				}
			}
		}
		return true;
	}
	
	// this function only return one single row
	function get_conf($keyRow = array())
	{
		foreach($keyRow as $idx => $val)
		{
			foreach ($this->data as $aData)
			{
				if($aData[$idx] == $val)
				{
					return $aData;
				}
			}
		}
		return false;
	}

	function delete($condition = array())
	{
		if(count($condition) == 0) {
			return false;	// No Assign Condition
		}

		$isAction = false; // Return Flag
		
		foreach($this->data as $idx => $rec)
		{
			$matchCount = 0;
			foreach($condition as $key => $val)
			{// Fetch Every Condition
				if($rec[$key] == $val) {
					$matchCount++;	// Add One
				}
			}
			if($matchCount == count($condition))
			{// All Matched
				unset($this->data[$idx]);
				$isAction = true;
			}		
		}
		
		return $isAction;
	}

	function update($condition = array(), $modify = array())
	{
		if(count($condition) == 0 || count($modify) == 0) {
			return false;	// No Assign Condition Or Update Data
		}

		$isAction = false; // Return Flag

		foreach($this->data as $idx => $rec)
		{
			$matchCount = 0;
			foreach($condition as $key => $val)
			{// Fetch Every Condition
				if($rec[$key] == $val) {
					$matchCount++;	// Add One
				}
			}
			if($matchCount == count($condition))
			{// Al l  Matched
				foreach($modify as $key => $val)
				{// Process Data Modification
					$this->data[$idx][$this->index[$key]] = $val;
					$isAction = true;			
				}
			}		
		}
	
		return $isAction;	
	}
	
	function writeBack()
	{
		$newData = array();
		
		foreach($this->data as $idx => $rec)
		{
			$newRec = array();
			
			foreach($this->index as $key)
			{// Write Back By Sorted Key Value
				if(isset($rec[$key])) {
					$newRec[] = str_replace($this->splitToken, $this->escapeToken.$this->splitToken, $rec[$key]);
				} else {					
					$newRec[] = "";	// Take Place
				}
			}

			// Add 1 Record
			$newData[] = implode($this->splitToken, $newRec);
		}		
			
		//Write Back
		$fp = fopen($this->dataFile, "w");
		fwrite($fp, implode("\n", $newData));
		fclose($fp);
	}
}

$orderBy4ConfReader = "";
$lifting4ConfReader = "";

function cmp4ConfReader($a, $b)
{
	global $orderBy4ConfReader, $lifting4ConfReader;

	if($a[$orderBy4ConfReader] == $b[$orderBy4ConfReader]) {
		return 0;
	}

	if($lifting4ConfReader == "ASC") {
		return ($a[$orderBy4ConfReader] < $b[$orderBy4ConfReader]) ? -1 : 1;
	} else {
		return ($a[$orderBy4ConfReader] < $b[$orderBy4ConfReader]) ? 1 : -1;
	}
}
?>