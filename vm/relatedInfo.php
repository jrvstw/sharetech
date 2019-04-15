<?
$relatedInfo = "/PDATA/relatedInfo";

if($HTTP_POST_VARS)
{
	$row = $HTTP_POST_VARS;
	if($row["relatedInfo_txet"] != ""){
		$fp = fopen($relatedInfo, "a");
		fwrite($fp, "=== " . date("Y-m-d H:i:s") . " ===\n");
		fwrite($fp, $row[relatedInfo_txet]."\n\n");
		fclose($fp);
		exec("/bin/sync; /bin/sync;");
	}
	header('Location: /serviceman.php?name=info');
	exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>RD Memo</title>
<style type="text/css">
html {
	font-size: 12px;
}
</style>
</head>
<body>
<center>
	<form name="relatedInfo" method="post" action="relatedInfo.php">
		<table>
			<tr>
				<td><textarea name="relatedInfo_txet" cols="50" rows="20"></textarea></td>
			</tr>
			<tr>
				<td align="center">
					<input type="submit" value="Submit" />
				</td>
			</tr>	
		</table>
	</form>
</center>
</body>
</html>