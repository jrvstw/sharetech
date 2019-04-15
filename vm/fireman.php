<?
$iptableFile = "/tmp/123456";
$ipsetFile = "/tmp/123789";

exec("/PGRAM/ipt4/sbin/iptables-save > $iptableFile");
exec("/PGRAM/ipsets/sbin/ipset -L > $ipsetFile");

$defaultChain = array("PREROUTING", "INPUT", "FORWARD", "OUTPUT", "POSTROUTING");
$userChain = array(
	"outgoing.pre",	"outgoing.post",
	"L2D_outgoing.pre",	"L2D_outgoing.post",
	"L2L_outgoing.pre",	"L2L_outgoing.post",
	"DMZ_outgoing.pre",	"DMZ_outgoing.post",
	"D2L_outgoing.pre",	"D2L_outgoing.post",
	"o2i_bridging.pre",	"o2i_bridging.post",
	"o2i_bri01ing.pre",	"o2i_bri01ing.post",
	"o2i_bri02ing.pre",	"o2i_bri02ing.post",
	"i2o_bridging.pre",	"i2o_bridging.post",
	"i2o_bri01ing.pre",	"i2o_bri01ing.post",
	"i2o_bri02ing.pre",	"i2o_bri02ing.post",
	"L2B_outgoing.pre",	"L2B_outgoing.post",
	"L2V_outgoing.pre",	"L2V_outgoing.post",
	"V2L_outgoing.pre",	"V2L_outgoing.post",
	"L2L_outgoing.pre", "L2L_outgoing.post",
	"D2D_outgoing.pre", "D2D_outgoing.post",
	"incoming.pre",	"incoming.post",
	"incoming_routing.pre",	"incoming_routing.post",
	"incoming_L",	"incoming_D"
);
$markKeyword = array("--mark", "--set-xmark", "--nfmask", "--ctmask");
include("mark_comment.php");

$iptableArray = array();

$tableActivity = 0;

/** iptable 進行分類 **/
$file = file($iptableFile);
foreach($file as $line)
{
	$line = str_replace("\n", "", $line);
	if(trim($line) == "")
		continue; //blank line

	if($line[0] == "*")
	{
		if($line == "*mangle")
			$tableActivity = 1;
		else if($line == "*nat")
			$tableActivity = 2;
		else if($line == "*filter")
			$tableActivity = 3;
		else
			$tableActivity = 0; //other		
	}
		
	$elt = split("[ \t]+", $line);
	if($elt[0] == "-A")
	{
		$iptableArray[$tableActivity][$elt[1]][] = $line;
	}
}

/** ipset 進行分類 **/
$file = file($ipsetFile);
$tableActivity = 4;
foreach($file as $line)
{
	$line = str_replace("\n", "", $line);
	if(trim($line) == "")
		continue; //blank line
	if(strpos($line, "Name:") !== false) {
		$ipset_name = trim(str_replace("Name:", "", $line));
	}
	if(preg_match('/^[0-9]+/', $line)) {
		$iptableArray[$tableActivity][$ipset_name][] = $line;
	}
}

/** 刪掉檔案 **/
unlink($iptableFile);
unlink($ipsetFile);


/**
 * Function
 */
function formatRuleDisplay($rules)
{
	global $iptableArray, $table, $tableName, $markKeyword, $markList;
	
	$lineNum = 1;
	foreach($rules as $line)
	{
		$elt = split("[ \t]+", $line);
		
		for($i = 0; $i < count($elt); $i++)
		{
			if($elt[$i] == "-j" && isset($table[$elt[$i+1]]))
			{//TARGET
				$elt[$i+1] = "<a href=\"javascript:expand('{$tableName}_" . $elt[$i+1] . "')\">" . $elt[$i+1] . "</a>";
			}
			else if($elt[$i] == "--set" && isset($iptableArray[4][$elt[$i+1]]))
			{//IPSET
				$elt[$i+1] = "<a href=\"javascript:expand('ipset_" . $elt[$i+1] . "')\">" . $elt[$i+1] . "</a>";
			}		
			else if(in_array($elt[$i], $markKeyword) && isset($markList[$elt[$i+1]]))
			{//MARK
				$elt[$i+1] = "<font color=\"green\" title=\"" . $markList[$elt[$i+1]] . "\">" . $elt[$i+1] . "</font>";
			}
			else if($elt[$i] == "--string")
			{//GGYY
				$elt[$i+1] = $elt[$i+1];			
			}			
		} 

		//array_shift($elt);
		//array_shift($elt);
		
		$context.= "<b class=\"displayOFF\">".sprintf("%02d", $lineNum)."</b>&nbsp;&nbsp;".implode("&nbsp;&nbsp;", $elt)."<br/>\n";
		$lineNum++;
	}
	
	return $context;
}
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>Google Map</title>
<style type="text/css">
html, body {
	margin: 0px;
	padding: 0px;
}
.tBlock {
	padding-left: 16px;
	font-size: 16px;
	display: none;
}
.defaultChain span{
	font-weight: bold;
	color: #000;
	cursor: pointer;
}
.userColorChain_1 span{
	font-weight: bold;
	color: #FF0000;
	cursor: pointer;
}
.userColorChain_2 span{
	font-weight: bold;
	color: #797979;
	cursor: pointer;
}
.policyRule {
	padding-left: 16px;	
	white-space: nowrap;
	background-color: #E8E8E8;
	display: none;
}
a {
	color: blue;
	text-decoration: none;	
	font-weight: bold;
}
h2 span{
	cursor: pointer;
}
.displayON {
	display: inline;
	color: gray;
}
.displayOFF {
	display: none;
}
</style>
</head>
<body>
<div style="background-color: #E8E8E8;">
口 勾選者, 每次Refresh時固定展開. <span style="color: blue; cursor: pointer;" onclick="clearExpand()">取消所有選取</span>&nbsp;&nbsp;&nbsp;&nbsp;
<span style="color: blue; cursor: pointer;" onclick="showLineNum()">顯示行號ON/OFF</span><br/>
</div>
<?
foreach($iptableArray as $idx => $table)
{
	if($idx == 0)
		continue; //no treat
	
	if($idx == 1)
		$tableName = "mangle";
	else if($idx == 2)
		$tableName = "nat";
	else if($idx == 3)
		$tableName = "filter";
	else if($idx == 4)
		$tableName = "ipset";
		
	echo "<h2><span onclick=\"showTable('{$tableName}', true)\">***{$tableName}</span></h2>\n";
	echo "<div class=\"tBlock\" id=\"tBlock_{$tableName}\">\n";

	foreach($table as $name => $policy)
	{
		if(in_array($name,$defaultChain))
			$titleStyle = "defaultChain";
		else if(in_array($name,$userChain))
			$titleStyle = "userColorChain_1";
		else
			$titleStyle = "userColorChain_2";
		
		echo "	<div class=\"$titleStyle\"><input type=\"checkbox\" value=\"{$tableName}_{$name}\" /><span id=\"{$tableName}_{$name}\">$name</span></div>\n";
		echo "	<div class=\"policyRule\" id=\"policy_{$tableName}_{$name}\">\n";
		echo formatRuleDisplay($policy);
		echo "	</div>\n";	
		echo "	<div style=\"height: 4px;\"><!-- black line --></div>\n";	
	}
	
	echo "</div>\n";
}
?>
<script type="text/javascript">
window.onload = function(){
	var elts = document.getElementsByTagName("span");
	for(var x = 0; x < elts.length; x++)
	{
		if(elts[x].id)
		{
			elts[x].onclick = function(){
				var obj = document.getElementById("policy_" + this.id);
				if(obj.style.display == "none" || obj.style.display == "")
					obj.style.display = "block";
				else
					obj.style.display = "none";					
			};
		}
	}

	/** 處理 cookie **/
	var myString = readCookie("expandChain");
	var myRec = (myString == null) ? new Array() : myString.split("##");
	
	var elts = document.getElementsByTagName("input");
	for(var x = 0; x < elts.length; x++)
	{
		if(elts[x].type == "checkbox")
		{
			for(yy in myRec)
			{
				if(elts[x].value == myRec[yy])
				{
					elts[x].checked = true;
					expandX(elts[x].value);
					break;
				}
			}			
			
			elts[x].onclick = recExpand;
		}
	}
};

var myTimer;
var myFlash;

function expand($id)
{
	var elt = $id.split("_");
	showTable(elt[0], false);
	
	document.getElementById($id).scrollIntoView();
	var obj = document.getElementById("policy_" + $id);
	obj.style.display = "block";
	obj.style.backgroundColor = "#FFD9D9";
	
	myTimer = setTimeout(clearFlash, 1500);
	myFlash = obj;
}
function expandX($id)
{
	var elt = $id.split("_");
	showTable(elt[0], false);
	
	document.getElementById($id).scrollIntoView();
	var obj = document.getElementById("policy_" + $id);
	obj.style.display = "block";
}
function clearFlash()
{
	myFlash.style.backgroundColor = "#E8E8E8";
}
function showTable($id, toggle)
{
	var obj = document.getElementById("tBlock_" + $id);
	if(toggle)
	{
		if(obj.style.display == "none" || obj.style.display == "")
			obj.style.display = "block";
		else
			obj.style.display = "none";
	}
	else
		obj.style.display = "block";		
}

function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}
function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}
function eraseCookie(name) {
	createCookie(name,"",-1);
}

function recExpand()
{
	var myString = "";
	var elts = document.getElementsByTagName("input");
	for(var x = 0; x < elts.length; x++)
	{
		if(elts[x].type == "checkbox" && elts[x].checked)
			myString += elts[x].value + "##";
	}
	createCookie("expandChain", myString);
}
function clearExpand()
{
	var myString = "";
	var elts = document.getElementsByTagName("input");
	for(var x = 0; x < elts.length; x++)
	{
		if(elts[x].type == "checkbox" && elts[x].checked)
			elts[x].checked = false;
	}
	eraseCookie("expandChain");
}
function showLineNum()
{
	var elts = document.getElementsByTagName("b");
	for(var x = 0; x < elts.length; x++)
	{
		if(elts[x].className == "displayON")
			elts[x].className = "displayOFF";
		else if(elts[x].className == "displayOFF")
			elts[x].className = "displayON";
	}
}
</script>
</body>
</html>