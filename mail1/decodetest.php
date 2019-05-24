<?php

$case["usual"] = "=?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ==?=";

$case["plain_text"] = "Fwd: SFExpress | Invoice | Shipping | Tracking |";

$case["mixed"] = "Subject: =?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6uueNjumHkemAmuefpQ==?=";

$case["q_mark_included"] = "=?UTF-8?Q?You_know_what's_hot_in_China??=";

$case["big-5_encoded"] = "???????ī~?M?橱";

//         C 049343  葉  秋  枝   0 4月  獎  金   -  個  人  獎  金  通  知
$case["incomplete_byte_1"] = "=?utf-8?B?QzA0OTM0M+iRieeni+aenSAwNOaciOeNjumHkSAtIOWAi+S6?=
 =?utf-8?B?uueNjumHkemAmuefpQ==?=";

$case["incomplete_byte_2"] = "=?UTF-8?B?44CQ5oqY5YO55Yi455m86YCB6YCa55+l44CR5pyD5ZOh5pel5pyA6auY54++?=
 =?UTF-8?B?5oqYMjAw77yB5aSP5aSp5Yiw5LqG546p5rC06YCZ5qij546p5LiA5aSP77yB?=
 =?UTF-8?B?6YCg5Z6L5rWu5o6S44CA5rOz5rGg5pyA5pyJ5Z6LL+OAkOael+S4ieebiuWI?=
 =?UTF-8?B?t+WFt+e1hOOAke+8jeWkj+aXpeS/nemkiumdoOmAmee1hO+8gS/lqr3lqr3l?=
 =?UTF-8?B?ronlv4PluavmiYvjgJDml6XmnKzms6Hms6HnjonjgJE=?=";

// org_spam_20190521/860/2019/05/03/957292
$case["incomplete_byte_3"] = "=?UTF-8?B?UmU64piF6L2J6IG35r2u5b615YSq6LOq5Lq65omN54++5Zyo5Y+q6KaBMTgw?=
 =?UTF-8?B?MOWFgyzoq4vmtL3lsIjlsazlvrXmiY3poafllY/kuIHntJToloflhY3ku5jo?=
 =?UTF-8?B?srvlsIjnt5o6MDgwMC04ODExODY=?=";

//echo mb_convert_encoding($string, 'UTF-8', 'GB2312');
//echo base64_decode($string);
//echo iconv('GBK', 'UTF-8', $string);
foreach ($case as $key => $string)
	echo $key . ":\n" . edecode_mime_string(combine($string)) . "\n\n";
	//echo edecode_mime_string($string) . "\n\n";

function combine($string)
{
	$pattern = '/\?=[\s]*=\?(.+\?.)\?/';
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

function decode_mime_string($subject)//, $charset)
{
		$subject = str_replace('=?gb2312?', '=?GBK?', $subject);
		$subject = str_replace('=?GB2312?', '=?GBK?', $subject);
		return iconv_mime_decode($subject);
		//return iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR);
	if (substr($subject, 0, 2) == "=?") {
		$pattern = '/=\?([^\?]+)\?(.)\?(.+)\?=/';
		$match = array();
		preg_match($pattern, $subject, $match);
		//$subject = str_replace('==?=', 'AA?=', $subject);
		//$subject = str_replace('=?=', 'A?=', $subject);
		//$subject = preg_replace('/\?=[\s]*=\?[^\?]+\?.\?/', '', $subject);
		//return mb_decode_mimeheader($subject);
	//} elseif (substr($charset, 0, 4) == "big5") {
		//return iconv("BIG-5", "UTF-8", $subject);
	} else {
		return $subject;
	}
}

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

