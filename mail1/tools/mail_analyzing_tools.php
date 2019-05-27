<?php

function get_received_time($received)
{
	$time_str = substr($received, strrpos($received, ";") + 1);
	$time = strtotime($time_str);
	return $time;
}

function decode_mixed_string($string, $charset)//, $charset)
{
	$string = concatenate_mime($string);
	$pattern = '/=\?.*\?=/';
	$matches = array();
	preg_match_all($pattern, $string, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
	$output = "";
	$pos = 0;
	/*
	foreach ($matches as $match) {
		$string = substr_replace($string, decode_mime_string($match[0][0]), $match[0][1], strlen($match[0][0]));
	}
	return $string;
	 */
	foreach ($matches as $match) {
		$encoded = $match[0][0];
		$length  = $match[0][1];
		$not_encoded = substr($string, $pos, $length - $pos);
		$not_encoded = iconv($charset, 'UTF-8//IGNORE', $not_encoded);
		$pos = $length + strlen($encoded);
		$output .= $not_encoded;
		$output .= substr_replace($string, decode_mime_string($encoded), $length, strlen($encoded));
	}
	$not_encoded = substr($string, $pos);
	$not_encoded = iconv($charset, 'UTF-8//IGNORE', $not_encoded);
	$output .= $not_encoded;
	return $output;
}

function concatenate_mime($string)
{
	$pattern = '/\?=[\s]*=\?([^\?]+\?[^\?]+)\?/';
	$matches = array();
	preg_match_all($pattern, $string, $matches, PREG_OFFSET_CAPTURE);
	for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
		$pos = $matches[0][$i][1];
		$encoding = $matches[1][$i][0];
		$left_part = substr($string, 0, $pos);
		if (substr($left_part, -1) == "=")
			continue;
		if (strrpos($left_part, "=?") < strrpos($left_part, $encoding))
			$string = substr_replace($string, "", $pos, strlen($matches[0][$i][0]));
	}
	return $string;
}

function decode_mime_string($string)
{
	$pattern = '/^=\?([^\?]+)\?(.+)\?(.+)\?=$/';
	$match = array();
	if (preg_match($pattern, $string, $match) === false)
		return $string;
	if (strtolower($match[2]) == "q") {
		$match[3] = str_replace("_", " ", $match[3]);
		$match[3] = preg_replace("/=([A-F,0-9]{2})/i", "%\\1", $match[3]);
		return iconv(getCharsetAlias($match[1]), "UTF-8//IGNORE", urldecode($match[3]));
	} else {
		$string = str_replace('=?gb2312?', '=?GBK?', $string);
		$string = str_replace('=?GB2312?', '=?GBK?', $string);
		return iconv_mime_decode($string);
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


function edecode_mime_string($sSubject) {
	if (substr(trim($sSubject), 0, 1) != "=")
		return $sSubject;
	$sDefaultCharset = "big5";
	//$sDefaultCharset = $this->sDefaultCharset;
	$sPattern = "/=\?([A-Z0-9\-_]+)\?([A-Z0-9\-]+)\?([\x01-\x7F]+?)\?=/i";
	$sUrlPattern = '/([A-Za-z0-9\-]+)\'\'(.+)/i';
	$sLineEnd = "/^[ \t\n\r]+$/";
	$sDelimiter = "/[\t\n\r]+/";
	$sDecodedString = '';
	$bUrl = false;
	$nCount = preg_match_all($sPattern, $sSubject , $aMatches , PREG_OFFSET_CAPTURE);
	if (0 == $nCount) $bUrl = $nCount = preg_match_all($sUrlPattern, $sSubject , $aMatches , PREG_OFFSET_CAPTURE);
	$nPos = 0;

	$bSame = $nCount > 1 && !preg_match("/(={1,2})$/",$aMatches[3][0][0]);
	$sCode = $aMatches[1][0][0];
	$sUrldecode = $aMatches[2][0][0];
	$sFirstDecode = strtolower($sCode." ".$sUrldecode);
	$sTandemContents = $aMatches[3][0][0];
	if($bSame){
		for ($i = 1;$i < $nCount;$i++) {
			if($sFirstDecode === strtolower($aMatches[1][$i][0]." ".$aMatches[2][$i][0]) && (($i+1 == $nCount)? true : !preg_match("/(={1,2})$/",$aMatches[3][$i][0]))){
				$sTandemContents .= $aMatches[3][$i][0];
			}else{
				$bSame = false;
				break;
			}
		}
	}
	if($bSame){
		$sDecodedString = $bUrl ? iconv($sCode, 'UTF-8//IGNORE', urldecode($sUrldecode)) : decode(getCharsetAlias($sCode), $sUrldecode, $sTandemContents);
	}else{
		for ($i = 0;$i < $nCount;$i++) {
			$nNowPos = $aMatches[0][$i][1];
			$nLength = strlen($aMatches[0][$i][0]);
			$sAppendString = substr($sSubject, $nPos, $nNowPos - $nPos);
			if (!preg_match($sLineEnd, $sAppendString)) {
				$sAppendString = preg_replace($sDelimiter, '', $sAppendString);
				$sAppendString = $bUrl ? iconv($aMatches[1][$i][0], "UTF-8//IGNORE", urldecode($sAppendString)) : iconv(getCharsetAlias($sDefaultCharset), 'UTF-8//IGNORE', $sAppendString);
				$sDecodedString .= $sAppendString;
			}
			$nPos = $nNowPos + $nLength;
			$sDecodedString .= $bUrl ? iconv($aMatches[1][$i][0], 'UTF-8//IGNORE', urldecode($aMatches[2][$i][0])) : decode(getCharsetAlias($aMatches[1][$i][0]), $aMatches[2][$i][0], $aMatches[3][$i][0]);
		}
		if ($nPos < strlen($sSubject)) {
			$sAppendString = substr($sSubject, $nPos, strlen($sSubject) - $nPos);
			if (!preg_match($sLineEnd, $sAppendString)) {
				$sAppendString = preg_replace($sDelimiter, '', $sAppendString);
				$sAppendString = $bUrl ? iconv($aMatches[1][$i][0], "UTF-8//IGNORE", urldecode($sAppendString)) : iconv(getCharsetAlias($sDefaultCharset), 'UTF-8//IGNORE', $sAppendString);
				$sDecodedString .= $sAppendString;
			}
		}
	}
	return $sDecodedString;
}

function decode($sCharset, $sEncode, $sContent) {
	$sDecodedString = '';
	if (preg_match("/Q/i", $sEncode)) {
		$sContent = str_replace('_', ' ', $sContent);
		$sContent = preg_replace("/=([A-F,0-9]{2})/i", "%\\1", $sContent);
		$sDecodedString = urldecode($sContent);
	} else {
		$sContent = str_replace('=', '', $sContent);
		if ($sContent) {
			$sContent = base64_decode($sContent);
		}
		$sDecodedString = $sContent;
	}
	$sDecodedString = iconv($sCharset, "UTF-8//IGNORE", $sDecodedString);
	return $sDecodedString;
}
// 使用指定的字集替代原本的字集
function getCharsetAlias($sCharsetName) {
	$sCharset = $sCharsetName;
	$sCharsetName = strtolower($sCharsetName);
	$aCharsetAlias = array('cp-850' => 'cp850', 'ms950' => 'cp950', 'big5' => 'cp950', 'gb2312' => 'cp936', 'ks_c_5601-1987' => 'euc-kr');
	if (array_key_exists($sCharsetName, $aCharsetAlias)) {
		$sCharset = $aCharsetAlias[$sCharsetName];
	}
	return $sCharset;
}

