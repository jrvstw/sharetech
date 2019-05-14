<?php
include_once "parse_mail.php";

/*
 * 1. setup
 */
$path    = $argv[1];
//$pattern = '/.eml$/';
$pattern = '/^[0-9]{2,}$/';
$table   = "mails";


/*
 * 2. fetch
 */
$output = array();
parse_dir($path, $pattern, $output);


/*
 * 3. output
 */
	print_r($output);
return;

/*
 * functions overview
 * ------------------------------------
 * parse_dir($path, $pattern, &$output)
 *  |- collect_info($file, &$output)
 * 		|- verify_epaper($header)
 * 		|- decode($subject, $charset)
 * 		|- get_charset($mail)
 */
function parse_dir($path, $pattern, &$output)
{
	if (is_dir($path) == false) {
		if (preg_match($pattern, basename($path)))
			collect_info($path, $output);
	} elseif ($handle = opendir($path)) {
		while (($entry = readdir($handle)) !== false)
			if (substr($entry, 0, 1) != ".")
				parse_dir("$path/$entry", $pattern, $output);
		closedir($handle);
	}
}

function collect_info($file, &$output)
{
	echo "\n$file:  ";
	$mail = parse_mail($file);
	echo $mail["header"]["content-type"][0];
}

