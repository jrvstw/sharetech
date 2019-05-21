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
		$subject = str_replace('=?gb2312?', '=?GBK?', $subject);
		$subject = str_replace('=?GB2312?', '=?GBK?', $subject);
		/*
		$subject = str_replace('==?=', 'AA?=', $subject);
		$subject = str_replace('=?=', 'A?=', $subject);
		$subject = preg_replace('/\?=[\s]*=\?[^\?]+\?.\?/', '', $subject);
		 */
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
	if (empty($header["content-type"][0]))
		return null;
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
	$ret = trim(substr($value, $ptr + strlen("boundary=")), '"');
	return $ret;
}

/*
$received = array();
foreach ($data["header"]["Received"] as $key => $value) {
	$received[$key]["time"] = date("Y-m-d H:i:s", get_received_time($value));
}
print_r($received);
 */

