<?php

$conf_location = "/var/www/html/statuslog/conf.ini";
$refresh = 5;
modify_ini($refresh, $conf_location);

/*
function modify_ini($refresh, $conf_location)
{
	$conf = parse_ini_file($conf_location);
	$content = "refresh = " . $refresh .
		'\nline_limit = ' . $conf["line_limit"] . '\n';
	$command = "printf 'refresh = " . $refresh . '\nline_limit = ' .
		$conf["line_limit"] . "\\n' > " . $conf_location;
	exec($command);
}
 */
function modify_ini($refresh, $conf_location)
{
	$conf = parse_ini_file($conf_location);
	$content = "refresh = " . $refresh .
		"\nline_limit = " . $conf["line_limit"];
	$handle = fopen($conf_location, "w+");
	fwrite($handle, $content);
}

