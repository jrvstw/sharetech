<?
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$tmpFile = "/tmp/serviceon";

$number1 = substr(time(), -6); //¨ú«á¤»½X
$number2 = $number1 + 27050888 + date("Ymd");

$fp = fopen($tmpFile, "w");
fwrite($fp, $number2);
fclose($fp);

$serviceList = array(
	"phpMyAdmin" 	=> getPHPMyAdminState(),
	"SSH" 				=> getSSHState()
);

/*
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
?>
<html>
<head>
<style type="text/css">
.StatusTable {
	border: solid 1px #8F8F8F;
}
.StatusTable td {
	border: solid 1px #8F8F8F;
}
.home_table_title {
	font-size: 13px;
	font-weight: Bold;
	text-align:center;
	color: #000000;
	padding-top: 0.5px;
	padding-bottom: 0.5px;
	border: solid 1px #8F8F8F;
	background-color: #dae6da;
	font-family: sans-serif;
}
.home_t {
	font-size: 11px;
	font-family: sans-serif; 
}
.home_c {
	font-size: 11px;
	font-family: serif;
	height: 15px; 
}
</style>
<script type="text/javascript">
window.onload = function(){
	document.myForm.number2.focus();
};
function setAction(service, enable)
{
	var fm = document.myForm;
	if(fm.number2.value == "")
	{
		alert("Please key in Number 2");
	}
	else
	{
		fm.service.value = service;
		fm.enable.value = enable;		
		fm.submit();
	}
}
</script>
</head>
<body>
<center>
<form name="myForm" method="POST" action="servicedo.php">
	<input type="hidden" name="service" />
	<input type="hidden" name="enable" />
	<table width="320" border="0" cellpadding="3" cellspacing="0" class="StatusTable">
		<tr>
			<td class="home_table_title" colspan="3"><?=date("Y-m-d H:i:s"); ?></td>
		</tr>
		<?
		foreach($serviceList as $name => $value) {
			if($value) {
		?>
			<tr>
				<td class="home_t">
				<?
					if($name == "phpMyAdmin")
						echo "<a href=\"http://" . $_SERVER["HTTP_HOST"] . "/ChaosTheory/index.php\">$name</a>";
					else
						echo $name;
				?>
				</td>
				<td class="home_c" align="center">
					<img src="ui/img/apply.gif" />
				</td>
				<td class="home_c" align="center">
					<span style="color: blue; cursor: pointer;" onclick="setAction('<?=$name; ?>', 'OFF')">OFF</sapn>&nbsp;&nbsp;
				</td>
			</tr>
		<?
			} else {		
		?>
			<tr>
				<td class="home_t"><?=$name; ?></td>
				<td class="home_c" align="center">
					<img src="ui/img/cancel.gif" />
				</td>
				<td class="home_c" align="center">
					<span style="color: blue; cursor: pointer;" onclick="setAction('<?=$name; ?>', 'ON')">ON</sapn>&nbsp;&nbsp;
				</td>
			</tr>
		<?			
			}
		}
		?>
		<tr>
			<td class='home_t' colspan="3" style="text-align: left;">
				Number 1 : <?=$number1; ?><br/>
				Number 2 : <input type="text" name="number2" />
			</td>
		</tr>
	</table>	
</form>
</center>
</body>
</html>