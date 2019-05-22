<?php
#!/PGRAM/php/bin/php -q

include_once("CMime.php");

$sTempDir = "/tmp/procmail/inf_" . date('mdHis') . posix_getpid();
mkdir($sTempDir);
$sMailFile = dosFormatToUnixFormat($argv[1]);
// read mail from stdin and save it to file
$fpStdin = fopen($sMailFile, 'r');
$fpMailpack = fopen("$sTempDir/mailpack", 'w');
while (!feof($fpStdin)) {
  $sBuff = fgets($fpStdin);
  fputs($fpMailpack, $sBuff);
} 
fclose($fpStdin);
fclose($fpMailpack);
$mydir = $sTempDir;
$mailpack = "$mydir/mailpack";
$mailpack_changed = $mailpack . '_changed';
$fpMailPack = fopen($mailpack, 'r');
$input = fread($fpMailPack, filesize($mailpack));
fclose($fpMailPack);
//$gmime = new CMime($mydir, &$input); // for windows
$gmime = new CMime($mydir, $input, "\n", 'big5'); // for linux
$gmime->go_decode($input);

var_dump($gmime->output->headers);

unlink($sMailFile);

removeDir($sTempDir);

exit;

function dosFormatToUnixFormat($sFile) {
	$sNewFile = tempnam('/tmp', 'unix');
	$aInfo = @file($sFile);
	$sText = '';
	if(is_array($aInfo)) {
		foreach($aInfo as $sKey => $sLine) {
			if(($nWrap = strrpos($sLine, "\r\n")) !== false) // only replace each line's end
				$sText .= substr_replace($sLine, "\n", $nWrap);
			else $sText .= $sLine;
		}
		if($sText != '') {
			$fp = fopen($sNewFile, "w");
			fwrite($fp, $sText);
			fclose($fp);
			return $sNewFile;
		}
	}
}

function removeDir($sDir) {
  if (is_dir($sDir)) {
    if ($dhDir = opendir($sDir)) {
      while (($sFile = readdir($dhDir)) !== false) {
        if ($sFile != '.' && $sFile != '..') {
          if (is_dir("$sDir/$sFile")) {
            removeDir("$sDir/$sFile");
          } else {
            unlink("$sDir/$sFile");
          } 
        } 
      } 
      closedir($dhDir);
    } 
    rmdir($sDir);
  } 
}
?>
