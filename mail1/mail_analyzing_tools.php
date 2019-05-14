<?php

function get_received_time($received)
{
	$time_str = substr($received, strrpos($received, ";") + 1);
	$time = strtotime($time_str);
	return $time;
}

function decode($subject, $charset)
{
	if (substr($subject, 0, 1) == "=") {
		if (substr($subject, 2, 6) == "gb2312")
			$subject = substr_replace($subject, "gbk", 2, 6);
		//return mb_decode_mimeheader($subject);
		return iconv_mime_decode($subject);
	} elseif (substr($charset, 0, 4) == "big5") {
		return iconv("BIG-5", "UTF-8", $subject);
	} else {
		return $subject;
	}
}

function get_charset($header)
{
	$value = $header["content-type"][0];
	if (empty($value))
		return null;
	$ptr = strpos($value, "charset=");
	if ($ptr === false)
		return null;
	return strtolower(substr($value, $ptr + strlen("charset=")));
}

function get_boundary($header)
{
	$value = $header["content-type"][0];
	if (empty($value))
		return null;
	$ptr = strpos($value, "boundary=");
	if ($ptr === false)
		return null;
	return trim(substr($value, $ptr + strlen("boundary=")), '"');
}

/*
$received = array();
foreach ($data["header"]["Received"] as $key => $value) {
	$received[$key]["time"] = date("Y-m-d H:i:s", get_received_time($value));
}
print_r($received);
 */

