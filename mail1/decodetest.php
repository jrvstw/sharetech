<?php

// warning:
//$string = "=?UTF-8?Q?You_know_what's_hot_in_China??=";

// notice:
//$string = "QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ";
//         C 049343  葉  秋  枝   0 4月  獎  金   -  個  人  獎  金  通  知
/*
$string = "Subject: =?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6?=
 =?utf-8?B?uueNjumHkemAmuefpQ==?=";
$string = "Subject: =?utf-8?B?SjAwMzM1Oeadjua3keWNvyAwNOaciOeNjumHkSAtIOWAi+S6?=
 =?utf-8?B?uueNjumHkemAmuefpQ==?=";
$string = "Subject: =?UTF-8?B?44CQ5oqY5YO55Yi455m86YCB6YCa55+l44CR5pyD5ZOh5pel5pyA6auY54++?=
 =?UTF-8?B?5oqYMjAw77yB5aSP5aSp5Yiw5LqG546p5rC06YCZ5qij546p5LiA5aSP77yB?=
 =?UTF-8?B?6YCg5Z6L5rWu5o6S44CA5rOz5rGg5pyA5pyJ5Z6LL+OAkOael+S4ieebiuWI?=
 =?UTF-8?B?t+WFt+e1hOOAke+8jeWkj+aXpeS/nemkiumdoOmAmee1hO+8gS/lqr3lqr3l?=
 =?UTF-8?B?ronlv4PluavmiYvjgJDml6XmnKzms6Hms6HnjonjgJE=?=";
Subject: Fwd: SFExpress | Invoice | Shipping | Tracking |
 */
$string = "Subject: =?big5?B?qm+u5Krhtn2kRqFJqKvFb36sS7lDpm6uyaX6oUGyb6TiuqmoQr3gruSmbqVos0Kh?=
 =?big5?B?SQ==?=";

$string = "Subject: hihi";

//echo mb_convert_encoding($string, 'UTF-8', 'GB2312');
//echo base64_decode($string);
//echo iconv('GBK', 'UTF-8', $string);
echo decode_mime_string($string);
echo "\n";

	function decode_mime_string($sSubject) {
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
