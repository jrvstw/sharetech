<?php
include_once("tools/mail_analyzing_tools.php");

$case = parse_ini_file("decode_test_case_utf8.ini");

foreach ($case as $type => $instance) {
	//echo mb_convert_encoding($string, 'UTF-8', 'GB2312');
	//echo base64_decode($string);
	//echo iconv('GBK', 'UTF-8', $string);
	//echo $key . ":\n" . concatenate_mime($string) . "\n\n";
	foreach ($instance as $key => $string) {
		echo "[$type][$key]:\n" . decode_mixed_string($string, 'BIG5') . "\n\n";
		//echo "[$type][$key]:\n" . edecode_mime_string($string) . "\n\n";
	}
}

return;

