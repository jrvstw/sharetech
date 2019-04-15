<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

switch($_GET["name"])
{
	case "ssh":
		openSSH($_GET["enable"]);
	break;

	case "phpmyadmin":
		openPHPMyAdmin($_GET["enable"]);
	break;

	case "release":
		releaseMemory();
	break;

	case "info":
		showInfo();
	break;

	case "bridge":
		checkBridgeMAC();
	break;

	case "upgradelog":
		@unlink("/PDATA/upgradeLog");
		echo "OK";
	break;

	case "rsynctime":
		echo time();
	break;

	case "ISP_SOURCE":
		OEMsetting("ISP_SOURCE", $_GET["enable"]);
		if($_GET["enable"] == "on")	{
			exec("/PDATA/apache/start_local/ISP_import.php go");
		}
	break;

	case "WATCHLAN_VPN":
		OEMsetting("WATCHLAN_VPN", $_GET["enable"]);
	break;

	case "model":
		changeModel($_GET["enable"]);
	break;

	case "qos_debug":
		qos_debug($_GET["enable"]);
	break;

	case "mail_debug_procmail":
		mail_debug("mail_debug_procmail", $_GET["enable"]);	
	break;

	case "mail_debug_cptohdd":
		mail_debug("mail_debug_cptohdd", $_GET["enable"]);	
	break;

	case "mail_exchange":
		mail_debug("mail_exchange", $_GET["enable"]);	
	break;

	case "mail_debug_info":
		mail_debug("mail_debug_info");	
	break;
	
	case "mail_procmail_show":
		mail_debug("mail_procmail_show");	
	break;	

	case "smtp_debug":
		mail_debug("smtp_debug", $_GET["enable"]);
	break;

	default:
		/*
		echo "<table border=0>";
		echo "	<tr><td><b>ssh</b></td><td>on|off SSH Service</td></tr>";
		echo "	<tr><td><b>phpmyadmin</b></td><td>on|off phpMyAdmin</td></tr>";
		echo "	<tr><td><b>release</b></td><td>release memory</td></tr>";
		echo "	<tr><td><b>info</b></td><td>display revision , rdmemo ...</td></tr>";
		echo "	<tr><td><b>bridge</b></td><td>fix br0 interface MAC</td></tr>";
		echo "	<tr><td><b>upgradelog</b></td><td>delete /PDATA/upgradeLog</td></tr>";
		echo "</table>";
		*/
}

/**
 * Function
 */
function getPHPMyAdminState()
{
	if(file_exists("/PDATA/apache/ChaosTheory"))
		return true;
	else
		return false;
}
function getSSHState()
{
	exec("netstat -tln | grep 0.0.0.0:22", $ret);
	if(!empty($ret[0]))
		return true;
	else
		return false;
}
function openSSH($enable)
{
	if($enable == "on")
	{
		if(getSSHState() == false)
			exec("/etc/init.d/sshd start");
		echo "SSH is ON.<br/>";
	}
	else
	{
		exec("/etc/init.d/sshd stop");
		echo "SSH is OFF.<br/>";
	}
}
function openPHPMyAdmin($enable)
{
	if($enable == "on")
	{
		if(getPHPMyAdminState() == false)
		{
			exec("ln -s /PGRAM/apache/phpMyAdmin /PDATA/apache/ChaosTheory");
		}
		$protocol = $_SERVER["HTTPS"] == "on" ? '"https://' : '"http://';
		echo "<a href=".$protocol . $_SERVER["HTTP_HOST"] . "/ChaosTheory/index.php\">phpMyAdmin</a><br/>";
		echo "phpMyAdmin is ON.<br/>";
	}
	else
	{
		exec("rm /PDATA/apache/ChaosTheory");
		echo "phpMyAdmin is OFF.<br/>";
	}
}
function releaseMemory()
{
	exec("free", $ret);
	echo "<pre>";
	foreach($ret as $line)	echo $line."<br/>";
	echo "</pre>";

	exec("echo 3 > /proc/sys/vm/drop_caches"); //Disable memory cache
	sleep(1);
	exec("echo 0 > /proc/sys/vm/drop_caches"); //Enable memory cache

	echo "<br/>";
	echo "---------------------------------------------------";
	echo "---------------------------------------------------";
	echo "<br/>";

	unset($ret);
	exec("free", $ret);
	echo "<pre>";
	foreach($ret as $line)	echo $line."<br/>";
	echo "</pre>";
}
function showInfo()
{
	$filelist = array(
		"/PDATA/relatedInfo",
		"/PDATA/upgradeLogHide"
	);

	foreach($filelist as $filename)
	{
		echo "### $filename ###";
		if(file_exists($filename))
			echo "<pre>".file_get_contents($filename)."</pre>";
		else
			echo "<pre>File does not exist</pre>";
	}
}
function checkBridgeMAC()
{
	$br0MAC = get_MACAddress("br0");
	$eth3MAC = get_MACAddress("eth3");

	if($br0MAC !=	"" && $eth3MAC != "" && $br0MAC != $eth3MAC)
	{//更改br0介面MAC位址
		exec("/sbin/ifconfig br0 hw ether $eth3MAC");
	}

	echo "br0 = $br0MAC <br/>";
	echo "eth3 = $eth3MAC <br/>";
}
function get_MACAddress($dev)
{
	exec("/sbin/ip addr show $dev | grep ether", $ret);
	$elt = split("[ \t]+", trim($ret[0]));
	return $elt[1];
}
function changeModel($targetModel)
{
	$indexConf = parse_ini_file("/PDATA/L7FWMODEL/INDEX");
	$targetModel = stripslashes($targetModel);
	if(isset($indexConf[$targetModel]))
	{
		$fp = fopen("/CFH3/servermodel/servermodel", "w");
		fwrite($fp, "<?\n");
		fwrite($fp, "define(\"SERVERMODEL\",\"$targetModel\");\n");
		fwrite($fp, "?>\n");
		fclose($fp);

		exec("/PDATA/apache/start_local/produce_rc3d.php go");

		echo "Changes $targetModel OK !!";
	}
	else
	{
		echo "Changes $targetModel failed !!";
	}
}
function OEMsetting($key, $value)
{
	$filename = "/PDATA/L7FWMODEL/OEM";

	if(file_exists($filename)) {
		$conf = parse_ini_file($filename);
	} else {
		$conf = array();
	}

	if($value == "on") {
		$conf[$key] = 1;
		echo "$key is ON.<br/>";
	}	else {
		$conf[$key] = 0;
		echo "$key is OFF.<br/>";
	}

	$fp = fopen($filename, "w");
	foreach($conf as $idx => $val) {
		fwrite($fp, "$idx = $val\n");
	}
	fclose($fp);
}

function qos_debug($enabled)
{
	if($enabled == "on"){
		@touch("/ram/tmp/sq_debug.log");
		echo "qos_debug Start Ok";
	}else{
		@unlink("/ram/tmp/sq_debug.log");
		echo "qos_debug Close Ok";
	}	
}
function mail_debug($targetModel, $enabled='off')
{
	switch($targetModel){
		case "mail_debug_procmail":
			if($enabled == "on"){
				@touch("/ram/tmp/mail_debug_procmail");
				echo "/ram/tmp/mail_debug_procmail Gen Ok";
			}else{
				@unlink("/ram/tmp/mail_debug_procmail");
				echo "/ram/tmp/mail_debug_procmail Del Ok";
			}	
		break;
		case "mail_debug_cptohdd":
			if($enabled == "on"){
				@touch("/ram/tmp/mail_debug_cptohdd");
				if(!file_exists("/HDD/Mail_Changed")) {
					exec("mkdir /HDD/Mail_Changed");
					exec("chmod 777 /HDD/Mail_Changed");
				}
				echo "/ram/tmp/mail_debug_cptohdd Gen Ok";
			}else{
				@unlink("/ram/tmp/mail_debug_cptohdd");
				echo "/ram/tmp/mail_debug_cptohdd Del Ok";
			}	
		break;
		case "mail_exchange":
			if($enabled == "on"){
				@touch("/PDATA/MAILMANAGE/Replace_Subject_Exchange");
				echo "/PDATA/MAILMANAGE/Replace_Subject_Exchange Gen Ok";
			}else{
				@unlink("/PDATA/MAILMANAGE/Replace_Subject_Exchange");
				echo "/PDATA/MAILMANAGE/Replace_Subject_Exchange Del Ok";
			}
		break;
		case "mail_debug_info":
			if(file_exists("/ram/tmp/mail_debug_procmail"))
				echo "mail_debug_procmail is ON.<br>";
			if(file_exists("/ram/tmp/mail_debug_cptohdd"))
				echo "mail_debug_cptohdd is ON.<br>";
			if(file_exists("/PDATA/MAILMANAGE/Replace_Subject_Exchange"))
				echo "mail_exchange is ON.<br>";
		break;
		case "mail_procmail_show":
			if(file_exists("/HDD/MRC_DATA/procmail.log")) 
			{
				$filemsg = file("/HDD/MRC_DATA/procmail.log");
			}
			echo "<pre>".implode("\n", (array)$filemsg)."</pre>";
		break;
		case "smtp_debug":
			if($enabled == "on"){
				@touch("/ram/tmp/smtp_debug");
				echo "SMTP Debug Log On<br />Loged at : /HDD/Smtp_Log/";
			}else{
				@unlink("/ram/tmp/smtp_debug");
				echo "SMTP Debug Log Off";
			}
		break;
	}
}
?>