<?php
include_once('MimeUtils.php');
include_once('simple_html_dom.php');
class CMime {
	var $_header;
	var $_body;
	var $attmap = array();
	var $output;
	var $txt_body = "";
	var $stxt_body = "";
	var $html_body = "";
	var $shtml_body = "";
	var $shtmlUtf8_body = "";
	var $aAttachPath = array();
	var $header = array();
	var $smtpway;
	var $changed = 0;
	var $mailsize;
	var $Ntification = 0;
	var $path;
	var $crlf = "\n";
	var $sDefaultCharset = 'big5';
	var $nNoAttNameOrder = 1;
	var $bBodyHasHtmlTag = false;
	var $attmap_exclude = array(); //紀錄要移除的附件檔案 PDF 轉換需使用
	var $sWebmailMessage = '';
	var $aWebmailMessage = array();

	// 記錄需要移除的區塊, 等待適當的時機再移除
	var $aMarkRemovePartIds = array();

	var $ds_forward;
	var $challengeKey;
	var $in_reply_to = false;
	var $auto_reply_key;
	var $bHasHtmlType = false; //在附加共用簽名檔與驗證簽章時才判斷
	var $attmap_embedded = false;
	var $bHasDeliveryStatusPart = false;//有郵件伺服器的退信區塊
	var $bHasListUnsubscribe = false;
	var $sReplaceSubject = null; //取代後的主旨

	function CMime($path, &$input, $crlf = "\r\n", $sDefaultCharset = 'big5', $nMailsize = 0) {
		if (!is_dir("$path/unpacked")) mkdir ("$path/unpacked");
		if (!is_dir("$path/unpacked2")) mkdir ("$path/unpacked2");
		if($nMailsize)$this->mailsize = $nMailsize;
		else $this->mailsize = strlen($input);
		$this->path = $path;
		$this->crlf = $crlf;
		$this->sDefaultCharset = $sDefaultCharset;
		list($this->_header, $this->_body) = $this->splitBodyHeader($input);
	}

	function go_decode(&$input) {
		$this->output = $this->start_decode($input);
		$this->output->id = '0';
		$this->sDefaultCharset = $this->GetCharset();
		$this->parse_output($this->output);
		$this->header = &$this->output->headers;
		preg_match_all("/received:/i", $this->_header, $matches);
		$this->smtpway = count($matches[0]);
		$this->Ntification = preg_match("/Disposition-Notification-To:/i", $this->_header);
		if(preg_match_all("/(\\nX-Message-ID:\s?(.+))/i", $this->_header, $dsmatches)) $this->ds_forward = $dsmatches[2][0];
		if(preg_match_all("/(\\nX-Message-RPGROUP:\s?(.+))/i", $this->_header, $rpymatches)) $this->do_reply_group = '1';
		if(preg_match_all("/(\\nX-Message-RPDEP:\s?(.+))/i", $this->_header, $rpymatches)) $this->do_reply_dep = '1';
		if(preg_match_all("/(\\nX-Message-RCHK:\s?(.+))/i", $this->_header, $rpymatches)) $this->do_reply_chk = '1';
		if(preg_match_all("/(\\nX-Message-AURP:\s?(.+))/i", $this->_header, $rpymatches)) $this->auto_reply_key = $rpymatches[2][0];
		if(preg_match_all("/(\\nC-Message-ID:\s?(.+))/i", $this->_header, $chmatches)) $this->challengeKey = $chmatches[2][0];
		if(preg_match_all("/(\\nIn-Reply-To:\s?(.+))/i", $this->_header, $replymatches)) $this->in_reply_to = true;
		if(preg_match_all("/(\\n\\treport-type=disposition-notification)/i", $this->_header, $notificationmatches)) $this->report_notification = true;
		if(preg_match("/(\\nList-Unsubscribe:\s)/i", $this->_header)) $this->bHasListUnsubscribe = true;
		$this->getWebmailMessage();
		$this->changed = true;//所有信都需要附加 W-Message-ID
	}

	function parse_output(&$obj) {
		if (!empty($obj->parts)) {
			for($nChildrenIndex = 0; $nChildrenIndex < count($obj->parts); $nChildrenIndex++) {
				$obj->parts[$nChildrenIndex]->id = $obj->id . ".{$nChildrenIndex}";
				$this->parse_output($obj->parts[$nChildrenIndex]);
			}
		} else {
			$ctype = strtolower($obj->ctype_primary . '/' . $obj->ctype_secondary);
			switch ($ctype) {
				case 'text/plain':
					$this->stxt_body .= $obj->body;
					$sContent = $this->htmltotext($obj->body);
					$sCharset = $obj->ctype_parameters["charset"];
					if (isset($sCharset)) {
						$sContent = $this->transformUtf8($sContent, $sCharset);
					}
					$this->txt_body .= $sContent;
					if (!empty($obj->disposition) AND preg_match("/^attachment$/i", $obj->disposition)) {
						$tmpfname = tempnam ("$this->path/unpacked", "PMS");
						if (($pos = strpos($tmpfname, 'PMS')) !== false) {
							$tmpfname = substr_replace($tmpfname, '', 0, $pos);
						}
						$name = $obj->ctype_parameters['name'];
						if (!isset($name)) $name = $obj->d_parameters['filename'];
						$this->attmap[] = array($obj->id, $name, $this->decode_mime_string($name), "$tmpfname", $obj->bIsPartitionToFile, $obj->body, "", $obj->disposition, trim($obj->headers['content-id']));

						if($obj->bIsPartitionToFile){
							if(($obj->sEncoding) == "base64")$this->attachEncodingBase64($tmpfname, $obj);
							else $this->attachEncodingOther($tmpfname, $obj);
							$obj->body = "";
						}else{
							$fp = fopen("$this->path/unpacked/$tmpfname", "w");
							fwrite($fp, $obj->body);
							fclose($fp);
							$obj->body = "";
						}
						$obj->filepath = "$this->path/unpacked/$tmpfname";
					}
					break;
				case 'message/delivery-status':
					$this->bHasDeliveryStatusPart = true;
				case 'text/html':
					if (!empty($obj->disposition) AND preg_match("/^attachment$/i", $obj->disposition)) {
						$tmpfname = tempnam ("$this->path/unpacked", "PMS");
						if (($pos = strpos($tmpfname, 'PMS')) !== false) {
							$tmpfname = substr_replace($tmpfname, '', 0, $pos);
						}
						$name = $obj->ctype_parameters['name'];
						if (!isset($name)) $name = $obj->d_parameters['filename'];
						$this->attmap[] = array($obj->id, $name, $this->decode_mime_string($name), "$tmpfname", $obj->bIsPartitionToFile, $obj->body, "", $obj->disposition, trim($obj->headers['content-id']));

						if($obj->bIsPartitionToFile){
							if(($obj->sEncoding) == "base64")$this->attachEncodingBase64($tmpfname, $obj);
							else $this->attachEncodingOther($tmpfname, $obj);
							$obj->body = "";
						}else{
							$fp = fopen("$this->path/unpacked/$tmpfname", "w");
							fwrite($fp, $obj->body);
							fclose($fp);
							$obj->body = "";
						}
						$obj->filepath = "$this->path/unpacked/$tmpfname";
					}else{
						//$sContent = $this->htmltotext($obj->body);
						$sCharset = $obj->ctype_parameters["charset"];
						if(!$sCharset)$sCharset=strtolower(trim($this->sDefaultCharset));
						$this->shtml_body .= $this->transHtmlText($obj->body, $sCharset);
						$this->shtmlUtf8_body .= $this->transformUtf8($obj->body, $sCharset);
						if(preg_match_all("/\<html[\w\W\n\r]*\>([\w\W\n\r]*)\<\/html[\w\W\n\r]*\>/Ui",$this->shtmlUtf8_body, $aMatch))$this->bBodyHasHtmlTag = true;
						$sContent = $this->html2Text($obj->body, $sCharset);
						if (isset($sCharset)) {
							$sContent = $this->transformUtf8($sContent, $sCharset);
						}
						//$this->txt_body .= $sContent;
						$this->html_body .= $sContent;
					}
					break;
				case 'message/rfc822':
					// 如果附件是email,且沒有指定附件的檔名,抓取email的主旨作為主檔名,副檔名為eml,找不到主旨的場合,檔名為unknown.eml
					if (!empty($obj->disposition) AND preg_match("/^attachment$/i", $obj->disposition)) {
						$tmpfname = tempnam ("$this->path/unpacked", "PMS");
						if (($pos = strpos($tmpfname, 'PMS')) !== false) {
							$tmpfname = substr_replace($tmpfname, '', 0, $pos);
						}
						$rawname = $obj->ctype_parameters['name'];
						if (!isset($rawname)) $rawname = $obj->d_parameters['filename'];
						if (!isset($rawname)) {
							if($obj->bIsPartitionToFile){
								$before_row = $row = $body = '';
								$fpTmp = fopen($obj->body, 'r');
								while(! feof($fpTmp)){
									$row = fgets($fpTmp);
									$body .= $row;
									if(preg_match("/^\n$/",$before_row))break;
									$before_row = $row;
								}
							}else{
								$body = $obj->body;
							}
							$oInnerMail = new CMime($this->path, $body, $this->crlf, $this->sDefaultCharset);
							$structure = $oInnerMail->start_decode($body);
							if (isset($structure->headers['subject'])) {
								$subject = $this->decode_mime_string($structure->headers['subject']);
							} else {
								$subject = 'unknown';
							}
							$name = $subject . '.eml';
							$rawname = '=?UTF-8?B?' . base64_encode($name) . '?=';
						}
						if (!isset($name)) $name = $this->decode_mime_string($rawname);
						$this->attmap[] = array($obj->id, $rawname, $name, "$tmpfname", $obj->bIsPartitionToFile, $obj->body, "", $obj->disposition, trim($obj->headers['content-id']));

						if($obj->bIsPartitionToFile){
							if(($obj->sEncoding) == "base64")$this->attachEncodingBase64($tmpfname, $obj);
							else $this->attachEncodingOther($tmpfname, $obj);
							$obj->body = "";
						}else{
							$fp = fopen("$this->path/unpacked/$tmpfname", "w");
							fwrite($fp, $obj->body);
							fclose($fp);
							$obj->body = "";
						}
						$obj->filepath = "$this->path/unpacked/$tmpfname";
					}
					break;
				default:
					// 如果附件無指定檔名, 檔名則以 ATT00001 依序表示, 如有副檔名則補尾端
					if (!empty($obj->disposition) AND preg_match("/^attachment$/i", $obj->disposition)) {
						$tmpfname = tempnam ("$this->path/unpacked", "PMS");
						if (($pos = strpos($tmpfname, 'PMS')) !== false) {
							$tmpfname = substr_replace($tmpfname, '', 0, $pos);
						}
						$name = $obj->ctype_parameters['name'];
						if (!isset($name)) $name = $obj->d_parameters['filename'];
						if (!isset($name)) {
							$order = ($this->nNoAttNameOrder < 10) ? '0'.$this->nNoAttNameOrder : $this->nNoAttNameOrder;
							$extension = MimeUtils::getFileExtension($ctype);
							$name = 'ATT000' . $order . '.' . (($extension) ? $extension : $obj->ctype_secondary);
							$this->nNoAttNameOrder++;
						}
						$this->attmap[] = array($obj->id, $name, $this->decode_mime_string($name), "$tmpfname", $obj->bIsPartitionToFile, $obj->body, "", $obj->disposition, trim($obj->headers['content-id']));

						if($obj->bIsPartitionToFile){
							if(($obj->sEncoding) == "base64")$this->attachEncodingBase64($tmpfname, $obj);
							else $this->attachEncodingOther($tmpfname, $obj);
							$obj->body = "";
						}else{
							$fp = fopen("$this->path/unpacked/$tmpfname", "w");
							fwrite($fp, $obj->body);
							fclose($fp);
							$obj->body = "";
						}
						$obj->filepath = "$this->path/unpacked/$tmpfname";
						if($obj->headers['content-id']){
							$sCid = trim(str_replace(array("<",">"),"",$obj->headers['content-id']));
							$this->aAttachPath[$sCid] = $obj->filepath;
						}
					}
			}
		}
	}

	function attachEncodingBase64($tmpfname, $obj){
		$fp = fopen("$this->path/unpacked/$tmpfname", "w");
		$fpTmp = fopen($obj->body, 'r');
		$sRemainder='';
		while(! feof($fpTmp)){
			$sText = fread($fpTmp, 32768);
			$sText = $sRemainder.str_replace("\n", "", $sText);
			$nLength= strlen($sText);
			$nRemainderLength = $nLength%4;
			list($sText, $sRemainder) = str_split($sText, $nLength - $nRemainderLength);
			fwrite($fp, $this->DecodeBody($sText, $obj->sEncoding));
		}
		fclose($fpTmp);
		fclose($fp);
	}

	function attachEncodingOther($tmpfname, $obj){
		$fp = fopen("$this->path/unpacked/$tmpfname", "w");
		$fpTmp = fopen($obj->body, 'r');
		$sRemainder='';
		while(! feof($fpTmp)){
			$sText = $sRemainder.fread($fpTmp, 32768);
			$aText = explode("\n", $sText);
			if(count($aText)>1)$sRemainder = array_pop($aText);
			else $sRemainder = "";
			fwrite($fp, $this->DecodeBody(implode("\n", $aText), $obj->sEncoding));
		}
		if($sRemainder)fwrite($fp, $this->DecodeBody($sRemainder, $obj->sEncoding));
		fclose($fpTmp);
		fclose($fp);
	}

	// parse信件,可得到一個樹狀結構; $input是一封信件,或夾帶的信件
	function start_decode($input) {
		if (!isset($this) AND isset($input)) {
			$obj = new CMime($this->path, $input, $this->crlf, $this->sDefaultCharset);
			$structure = $obj->start_decode($input);
		} elseif (!isset($this)) {
			return false;
		} else {
			$structure = $this->splitBodyPart($this->_header, $this->_body);
		}
		return $structure;
	}

	function splitBodyPart ($headers, $body, $default_ctype = 'text/plain') {
		$return = new stdClass;
		$headers = $this->splitHeaderPart($headers);
		foreach ($headers as $value) {
			if (isset($return->headers[strtolower($value['name'])]) AND !is_array($return->headers[strtolower($value['name'])])) {
				$return->headers[strtolower($value['name'])] = array($return->headers[strtolower($value['name'])]);
				$return->headers[strtolower($value['name'])][] = $value['value'];
			} elseif (isset($return->headers[strtolower($value['name'])])) {
				$return->headers[strtolower($value['name'])][] = $value['value'];
			} else {
				$return->headers[strtolower($value['name'])] = $value['value'];
			}
		}

		reset($headers);
		while (list($key, $value) = each($headers)) {
			$headers[$key]['name'] = strtolower($headers[$key]['name']);
			switch ($headers[$key]['name']) {
				case 'content-type':
					$content_type = $this->parseHeaderValue($headers[$key]['value']);

					if (preg_match('/([0-9a-z+.-]+)\/([0-9a-z+.-]+)/i', $content_type['value'], $regs)) {
						$return->ctype_primary = $regs[1];
						$return->ctype_secondary = $regs[2];
					}

					if (isset($content_type['other'])) {
						while (list($p_name, $p_value) = each($content_type['other'])) {
							$return->ctype_parameters[$p_name] = $p_value;
						}
					}
					break;

				case 'content-disposition';
					$content_disposition = $this->parseHeaderValue($headers[$key]['value']);
					$return->disposition = $content_disposition['value'];

					if (isset($content_disposition['other'])) {
						while (list($p_name, $p_value) = each($content_disposition['other'])) {
							$return->d_parameters[$p_name] = $p_value;
						}
					}
					break;

				case 'content-transfer-encoding':
					$content_transfer_encoding = $this->parseHeaderValue($headers[$key]['value']);
					break;
			}
		}

		if (isset($content_type)) {
			if(!$content_transfer_encoding['value'])$content_transfer_encoding['value'] = '7bit';
			switch (strtolower($content_type['value'])) {
				case 'text/plain':
					if (!empty($return->disposition) AND preg_match("/^attachment$/i", $return->disposition)) {
						if(!$return->disposition)$return->disposition = 'attachment';
						if(preg_match("/".str_replace("/", "\/", $this->path)."\/unpacked2\/(.*)/", $body, $aMatch) && file_exists("{$this->path}/unpacked2/".$aMatch[1])){
							$return->body = "{$this->path}/unpacked2/".$aMatch[1];
							$return->bIsPartitionToFile = true;
							$return->sEncoding = $content_transfer_encoding['value'];
						}else{
							$return->body = $this->DecodeBody($body, $content_transfer_encoding['value']);
						}
					} else {
						$return->disposition = "text";
						$return->body = $this->DecodeBody($body, $content_transfer_encoding['value']);
					}
					break;
				case 'message/delivery-status':
				case 'text/html':

					if (!empty($return->disposition) AND preg_match("/^attachment$/i", $return->disposition)) {
						if(!$return->disposition)$return->disposition = 'attachment';
						if(preg_match("/".str_replace("/", "\/", $this->path)."\/unpacked2\/(.*)/", $body, $aMatch) && file_exists("{$this->path}/unpacked2/".$aMatch[1])){
							$return->body = "{$this->path}/unpacked2/".$aMatch[1];
							$return->bIsPartitionToFile = true;
							$return->sEncoding = $content_transfer_encoding['value'];
						}else{
							$return->body = $this->DecodeBody($body, $content_transfer_encoding['value']);
						}
					} else {
						$return->disposition = "html";
						$return->body = $this->DecodeBody($body, $content_transfer_encoding['value']);
					}

					break;
				case 'multipart/report':
				case 'multipart/digest':
				case 'multipart/alternative':
				case 'multipart/related':
				case 'multipart/mixed':
				case 'multipart/signed':
				case 'multipart/encrypted':

					if (!isset($content_type['other']['boundary'])) {
						$this->_error = 'No boundary found for ' . $content_type['value'] . ' part';
					}
					$default_ctype = (strtolower($content_type['value']) === 'multipart/digest') ? 'message/rfc822' : 'text/plain';
					$parts = MimeUtils::getPartContents($body, $content_type['other']['boundary']);
					for ($i = 0; $i < count($parts); $i++) {
						list($part_header, $part_body) = $this->splitBodyHeader($parts[$i]);
						$part = $this->splitBodyPart($part_header, $part_body, $default_ctype);
						$return->parts[] = $part;
					}

					break;

				/* case 'message/rfc822':
					$obj = new CMime($this->path,$body,$this->crlf);
					$return->parts[] = $obj->start_decode($body);
					unset($obj);
					break; */

				default:
					$return->disposition = 'attachment';
					if(preg_match("/".str_replace("/", "\/", $this->path)."\/unpacked2\/(.*)/", $body, $aMatch) && file_exists("{$this->path}/unpacked2/".$aMatch[1])){
						$return->body = "{$this->path}/unpacked2/".$aMatch[1];
						$return->bIsPartitionToFile = true;
						$return->sEncoding = $content_transfer_encoding['value'];
					}else{
						$return->body = $this->DecodeBody($body, $content_transfer_encoding['value']);
					}
					break;
			}
		} else {
			$ctype = explode('/', $default_ctype);
			$return->ctype_primary = $ctype[0];
			$return->ctype_secondary = $ctype[1];
			$return->body = $this->DecodeBody($body);
		}
		return $return;
	}
	// 把$input分割為array($header, $body)
	function splitBodyHeader($input) {
		$pos = strpos($input, $this->crlf . $this->crlf);
		if ($pos === false) {
			// Could not split header and body
			return false;
		}

		$header = substr($input, 0, $pos);
		$body = substr($input, $pos + (2 * strlen($this->crlf)));

		return array($header, $body);
	}
	// 把header分解成array(array('name'=>$name, 'value'=>$value),...)的形式
	function splitHeaderPart($input) {
		if ($input !== '') {
			// Unfold the input
			$input = preg_replace('/' . $this->crlf . "(\t| )/", ' ', $input);

			$headers = explode($this->crlf, trim($input));

			foreach ($headers as $value) {
				$hdr_name = substr($value, 0, $pos = strpos($value, ':'));

				$hdr_value = substr($value, $pos + 1);

				$return[] = array('name' => $hdr_name,
					// 'value' => $this->DecodeHeader($hdr_value)
					'value' => $hdr_value
					);
			}
		} else {
			$return = array();
		}

		return $return;
	}
	/*
	* 處理header一列中有多個值的狀況
	* array(
	*     'value' = $value,
	*     'other' = array($key => $value, $key => $value, ...);
	* );
	*/
	function parseHeaderValue($input) {
		if (($pos = strpos($input, ';')) !== false) {
			$return['value'] = trim(substr($input, 0, $pos));
			$input = trim(substr($input, $pos + 1));

			if (strlen($input) > 0) {
				$tmp = explode(";", $input);
				for($i = 0;$i < count($tmp);$i++) {
					$tmp[$i] = trim($tmp[$i]);
					if (strlen($tmp[$i])) {
						if (preg_match_all('/([0-9a-z+.-]+)[\*0-9]*\s*=\s*"?([^"]*)"?/i', $tmp[$i], $matches)) {
							if (isset($return['other'][strtolower($matches[1][0])])) $return['other'][strtolower($matches[1][0])] .= $matches[2][0];
							else $return['other'][strtolower($matches[1][0])] = $matches[2][0];
						} else {
							trigger_error("parseHeaderValue {{$tmp[$i]}} failure", E_USER_WARNING);
						}
					}
				}
			}
		} else {
			$return['value'] = trim($input);
		}

		return $return;
	}
	// 將$input中含有編碼的部份解碼
	function DecodeHeader($input) {
		// Remove white space between encoded-words
		$input = preg_replace('/(=\?[^?]+\?(Q|B)\?[^?]*\?=)( |' . "\t|" . $this->crlf . ')+=\?/i', '\1=?', $input);
		// For each encoded-word...
		while (preg_match('/(=\?([^?]+)\?(Q|B)\?([^?]*)\?=)/i', $input, $matches)) {
			$encoded = $matches[1];
			$charset = $matches[2];
			$encoding = $matches[3];
			$text = $matches[4];
			switch (strtoupper($encoding)) {
				case 'B':
					$text = base64_decode($text);
					break;

				case 'Q':
					$text = str_replace('_', ' ', $text);
					preg_match_all('/=([A-F0-9]{2})/', $text, $matches);
					foreach($matches[1] as $value)
					$text = str_replace('=' . $value, chr(hexdec($value)), $text);
					break;
			}

			$input = str_replace($encoded, $text, $input);
		}

		return $input;
	}
	// 把body part解碼
	function DecodeBody($input, $encoding = '7bit') {
		switch (strtolower($encoding)) {
			case '8bit': // 純文字傳送含中文字(我增加的)
				return $input;
			case 'base64':
				return base64_decode($input);
			case 'quoted-printable':
				$input = str_replace(array("\r\n", "\r", "\n"), "\r\n", $input);
				return quoted_printable_decode($input);
			default:
			case '7bit':
				return $input;
		}
	}

	function htmltotext($html_body) {
		$search = array(
			"'&(quot|#34);'i", // Replace html entities
			"'&(amp|#38);'i",
			"'&(lt|#60);'i",
			"'&(gt|#62);'i",
			"'&(nbsp|#160);'i",
			"'&(iexcl|#161);'i",
			"'&(cent|#162);'i",
			"'&(pound|#163);'i",
			"'&(copy|#169);'i"
			);
		$replace = array(
			"\"",
			"&",
			"<",
			">",
			" ",
			chr(161),
			chr(162),
			chr(163),
			chr(169)
		);
		$html_body = preg_replace("/<style.*?<\/style>/is", "", $html_body);// remove css-style tags
		$html_body = strip_tags($html_body);// remove all other html

		return preg_replace ($search, $replace, $html_body);
	}

	// 解MIME字串的QP編碼和base64編碼
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
	// 將字串中含有編碼的部份解碼
	function decode_mime_string($sSubject) {
		$sDefaultCharset = $this->sDefaultCharset;
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
			$sDecodedString = $bUrl ? iconv($sCode, 'UTF-8//IGNORE', urldecode($sUrldecode)) : $this->decode($this->getCharsetAlias($sCode), $sUrldecode, $sTandemContents);
		}else{
			for ($i = 0;$i < $nCount;$i++) {
				$nNowPos = $aMatches[0][$i][1];
				$nLength = strlen($aMatches[0][$i][0]);
				$sAppendString = substr($sSubject, $nPos, $nNowPos - $nPos);
				if (!preg_match($sLineEnd, $sAppendString)) {
					$sAppendString = preg_replace($sDelimiter, '', $sAppendString);
					$sAppendString = $bUrl ? iconv($aMatches[1][$i][0], "UTF-8//IGNORE", urldecode($sAppendString)) : iconv($this->getCharsetAlias($sDefaultCharset), 'UTF-8//IGNORE', $sAppendString);
					$sDecodedString .= $sAppendString;
				}
				$nPos = $nNowPos + $nLength;
				$sDecodedString .= $bUrl ? iconv($aMatches[1][$i][0], 'UTF-8//IGNORE', urldecode($aMatches[2][$i][0])) : $this->decode($this->getCharsetAlias($aMatches[1][$i][0]), $aMatches[2][$i][0], $aMatches[3][$i][0]);
			}
			if ($nPos < strlen($sSubject)) {
				$sAppendString = substr($sSubject, $nPos, strlen($sSubject) - $nPos);
				if (!preg_match($sLineEnd, $sAppendString)) {
					$sAppendString = preg_replace($sDelimiter, '', $sAppendString);
					$sAppendString = $bUrl ? iconv($aMatches[1][$i][0], "UTF-8//IGNORE", urldecode($sAppendString)) : iconv($this->getCharsetAlias($sDefaultCharset), 'UTF-8//IGNORE', $sAppendString);
					$sDecodedString .= $sAppendString;
				}
			}
		}
		return $sDecodedString;
	}

	function GetAtts() {
		return $this->attmap;
	}

	function GetBodyText() {
		//return $this->txt_body;
		if($this->shtml_body == "")
			return $this->txt_body;
		else
			return $this->html_body;
	}

	function GetMsgId() {
		if (!isset($this->header["message-id"]) || empty($this->header["message-id"])) {
			$this->header["message-id"] = "<".stChallengeKeys::getRand32Key().'@'.CUtility::getLocalHostName().">";
			$this->_header .= $this->crlf . 'Message-Id: ' . $this->header["message-id"];
			$this->changed = 1;
		}
		return trim($this->header["message-id"]);
	}

	function GetFrom() {
		if(preg_match('/^(.+)\s*\<([a-zA-Z0-9._+-]+@[a-zA-Z0-9.-].*[a-zA-Z0-9.-]+)\>\s*\<([a-zA-Z0-9._+-]+@[a-zA-Z0-9.-].*[a-zA-Z0-9.-]+)\>$/', trim($this->header["from"]), $match)){
			return $match[2].$match[3];
		}else if(preg_match('/([a-zA-Z0-9._+-]+@([a-zA-Z0-9.-].)*[a-zA-Z0-9.-]+)/', trim($this->header["from"]), $match)){
			return $match[1];
		}else if(preg_match('/^(.+)\s+\((.+)\)$/', trim($this->header["from"]), $match)){
			return $match[1];
		}
	}

	function GetSubjectText($bReplaceSubject = false) {
		if($bReplaceSubject && $this->sReplaceSubject)return $this->decode_mime_string(trim($this->sReplaceSubject));

		if(is_array($this->header["subject"])){
			return $this->decode_mime_string(trim($this->header["subject"][0]));
		}else{
			return $this->decode_mime_string(trim($this->header["subject"]));
		}
	}

	function GetHdate() {
		return trim($this->header["date"]);
	}

	function GetMailSize() {
		return $this->mailsize;
	}

	function GetCharset($i = null) {
		if (isset($i)) {
			$charset = $this->output->parts[0]->parts[$i]->ctype_parameters[charset];
			if ($charset) {
				return $charset;
			}
		}

		$subject = $this->output->headers["subject"];
		if ($subject) {
			if (preg_match('/(=\?([^?]+)\?(Q|B)\?([^?]*)\?=)/i', $subject, $matches)) {
				$encoded = $matches[1];
				$charset = $matches[2];
				$encoding = $matches[3];
				$text = $matches[4];
			}
		}
		if (!$charset) {
			if($subject){
				$encode = mb_detect_encoding($subject, array('ASCII','UTF-8','BIG5','GB2312','GBK'));
				if(!$encode) $encode = mb_detect_encoding($subject, "auto");
				if($encode) $charset = $encode;
			}
		}
		if (!$charset) {
			$language_code = $this->output->ctype_parameters;
			$charset = $language_code[charset];
		}

		if (!$charset) {
			$language_code = $this->output->parts[0]->ctype_parameters;
			$charset = $language_code[charset];
		}

		if (!$charset) {
			$language_code = $this->output->parts[0]->parts[0]->ctype_parameters;
			$charset = $language_code[charset];
		}

		if (!$charset) {
			//$charset = "big5"; //暫用
			$charset = $this->sDefaultCharset;
		}

		return $charset;
	}
	// 搜尋html類型的區塊, 傳回該區塊的id
	private static function findHtmlPart($oPart) {
		$sId = null;
		if ($oPart->ctype_primary == "text" && $oPart->ctype_secondary == "html") {
			if (!(isset($oPart->disposition) &&  preg_match("/^attachment$/i", $oPart->disposition))) {
				$sId = $oPart->id;
			}
		} elseif ($oPart->ctype_primary == 'multipart') {
			$nLevel = self::getLevel($oPart->ctype_secondary);
			if ($nLevel >= 0) {
				for ($i = 0; $i < count($oPart->parts); $i++) {
					$sId = self::findHtmlPart($oPart->parts[$i]);
					if (isset($sId)) {
						break;
					}
				}
			}
		}
		return $sId;
	}
	private static function getPart($oPart, $sId) {
		$oResult = null;
		if ($oPart->id === $sId) {
			$oResult = $oPart;
		} else {
			$aPartId = explode('.', $oPart->id);
			$aTargetId = explode('.', $sId);
			if (count($aTargetId) > count($aPartId)) {
				$bYes = true;
				$oTempPart = $oPart;
				for ($nDepth = 0; $nDepth < count($aTargetId); $nDepth++) {
					if ($nDepth >= count($aPartId)) {
						$nIndex = intval($aTargetId[$nDepth]);
						if ($nIndex < count($oTempPart->parts)) {
							$oTempPart = $oTempPart->parts[$nIndex];
						} else {
							$bYes = false;
							break;
						}
					} elseif ($aPartId[$nDepth] !== $aTargetId[$nDepth]) {
						$bYes = false;
						break;
					}
				}
				if ($bYes) {
					$oResult = $oTempPart;
				}
			}
		}
		if (isset($oResult) && $oResult->id !== $sId) {
			trigger_error("target_id {{$sId}} part_id {{$oResult->id}}", E_USER_ERROR);
		}
		return $oResult;
	}
	// 取得$sId的父區塊的id, 如果不存在父區塊則傳回null
	private static function getParentId($sId) {
		$sParentId = null;
		$aIdParts = explode('.', $sId);
		if (count($aIdParts) > 1) {
			array_pop($aIdParts);
			$sParentId = implode('.', $aIdParts);
		}
		return $sParentId;
	}
	private static function getPartIndex($sId) {
		$nIndex = -1;
		$aIdParts = explode('.', $sId);
		$sIndex = array_pop($aIdParts);
		if (strlen($sIndex)) {
			$nIndex = intval($sIndex);
		}
		return $nIndex;
	}
	private function insertRelatedImageParts($oPart, $aImageParts) {
		$sCurrentPartId = self::findHtmlPart($oPart);
		if (isset($sCurrentPartId)) {
			$oParentPart = null;
			$nLevel = -1;
			do {
				$sParentId = self::getParentId($sCurrentPartId);
				if (isset($sParentId)) {
					$oParentPart = self::getPart($oPart, $sParentId);
					$nLevel = self::getLevel($oParentPart->ctype_secondary);
					if ($nLevel >= 1) {
						break;
					}
					$sCurrentPartId = $sParentId;
				}
			} while (isset($sParentId));
			if (isset($sParentId, $oParentPart)) {
				trigger_error("current_part_id {{$sCurrentPartId}} parent_part_id {{$sParentId}} level {{$nLevel}}");
				if ($nLevel == 1) {
					// 在$oParentPart附加image區塊
					$sNewBody = $this->_body;
					$sNewBody = self::appendParts($sNewBody, $oParentPart->ctype_parameters['boundary'], $aImageParts);
					$this->_body = $sNewBody;
					$this->changed = 1;
				} else {
					// 將$sCurrentPartId區塊重新包裹再附加image區塊
					$nIndex = self::getPartIndex($sCurrentPartId);
					$sNewBody = $this->_body;
					$sCurrentPartContent = MimeUtils::getPartContent($sNewBody, $oParentPart->ctype_parameters['boundary'], $nIndex);
					$aTempParts = array($sCurrentPartContent);
					$aTempParts = array_merge($aTempParts, $aImageParts);
					$sTempPart = MimeUtils::createMultipart('multipart/related', $aTempParts);
					$sNewBody = MimeUtils::replacePart($sNewBody, $oParentPart->ctype_parameters['boundary'], $nIndex, $sTempPart);
					$this->_body = $sNewBody;
					$this->changed = 1;
				}
			} else {
				// 已經在最上層的區塊, 將原本的內容重新包裹再附加image區塊
				// 需要移動所有的'Content-*'屬性
				trigger_error("current part is on top");
				$sNewHeader = $this->_header;
				$sNewBody = $this->_body;
				$aHeaders = $this->splitHeaderPart($sNewHeader);
				$aAttributeNames = array();
				foreach ($aHeaders as $aAttribute) {
					$aAttributeNames[] = $aAttribute['name'];
				}
				$aContentAttributeNames = array();
				$aOtherAttributeNames = array();
				foreach ($aAttributeNames as $sAttributeName) {
					if (preg_match('/^Content-.+/i', $sAttributeName)) {
						$aContentAttributeNames[] = $sAttributeName;
					} else {
						$aOtherAttributeNames[] = $sAttributeName;
					}
				}
				$sBoundary = uniqid('boundary_');
				// 新的body
				$oContentHeader = new HeaderHelper($sNewHeader);
				foreach ($aOtherAttributeNames as $sAttributeName) {
					$oContentHeader->setField($sAttributeName, null);
				}
				$oContentHeader->setField('Content-Transfer-Encoding', $this->header['content-transfer-encoding']? $this->header['content-transfer-encoding']:"quoted-printable");
				$sContentHeader = $oContentHeader->build();
				$sTempPart = trim($sContentHeader) . "\n\n" . trim($sNewBody) . "\n";
				$aTempParts = array_merge(array($sTempPart), $aImageParts);
				$sNewBody = MimeUtils::createMultipartBody($aTempParts, $sBoundary);
				// 新的header
				$oHeader = new HeaderHelper($sNewHeader);
				foreach ($aContentAttributeNames as $sAttributeName) {
					$oContentHeader->setField($sAttributeName, null);
				}
				$oHeader->setField('Content-Type', 'multipart/related', array('boundary' => $sBoundary));
				$oHeader->setField('Content-Transfer-Encoding', null);
				$oHeader->setField('Content-Disposition', null);
				$sNewHeader = $oHeader->build();
				$sNewHeader = trim($sNewHeader);
				// 更新
				$this->_header = $sNewHeader;
				$this->_body = $sNewBody;
				$this->changed = 1;
			}
		}
	}
	// 取得用來包裹文字區塊的multipart區塊的層級, -1表示未知的類型
	private static function getLevel($sCtypeSecondary) {
		$aLevel = array('alternative', 'related', 'mixed');
		for ($i = 0; $i < count($aLevel); $i++) {
			if ($aLevel[$i] == $sCtypeSecondary) {
				return $i;
			}
		}
		return -1;
	}
	function findTextPart($oPart) {
		$aResult = array();
		if($oPart->ctype_primary == "multipart" && in_array($oPart->ctype_secondary, array("alternative", "related", "mixed"))) {
			$sBoundary = $oPart->ctype_parameters["boundary"];
			$aTextPartIndex = array();
			for($i = 0; $i < count($oPart->parts); $i++) {
                if (!(!empty($oPart->parts[$i]->disposition) AND preg_match("/^attachment$/i", $oPart->parts[$i]->disposition))) {
                    if($oPart->parts[$i]->ctype_primary == "text" && in_array($oPart->parts[$i]->ctype_secondary, array("plain", "html"))) {
                        $aTextPartIndex[] = $i;
                    } else {
                        $aChildResult = $this->findTextPart($oPart->parts[$i]);
                        $aResult = array_merge($aResult, $aChildResult);
                    }
                }
			}
			if(count($aTextPartIndex) > 0) {
				$aResult[] = array("boundary" => $sBoundary, "part_index" => $aTextPartIndex);
			}
		}
		return $aResult;
	}

	private static function ensurePTag($sHtml) {
		$sSearch = '<p>';
		if (substr_compare(trim($sHtml), $sSearch, 0, strlen($sSearch)) !== 0) {
			return '<p>' . $sHtml . '</p>';
		}
		return $sHtml;
	}

	// 將$sHtml裡面的圖檔的url替換成cid, $aCids = array($sFileId1 => $sCid1, $sFileId2 => $sCid2)
	private static function processImage($sHtml, &$aCids, &$aSaveDir, &$aSubDir) {
		$oHtml = new simple_html_dom();
		$oHtml->load($sHtml);
		$aResult = $oHtml->find('img');
		$nCount = count($aResult);
		trigger_error("find image element {{$nCount}}");
		foreach ($aResult as $oElement) {
			if (isset($oElement->src)) {
				$sUrl = $oElement->src;
				$aMatches = array();
				if (preg_match('/\/upload.php\?id=([0-9a-zA-Z]+)/', $sUrl, $aMatches)) {
					$sFileId = $aMatches[1];
					if (!array_key_exists($sFileId, $aCids)) {
						$sCid = uniqid('inline_');
						$oElement->src = "cid:{$sCid}";
						$aCids[$sFileId] = $sCid;
						trigger_error("image file_id {{$sFileId}} cid {{$sCid}}");
					}
				}
				$aSaveDir[$sFileId] = _SHARE_SIGNATURE_IMAGES_DIR;
				if (preg_match('/\/upload\.php\?id=([0-9a-zA-Z]+)(.*?)SaveDir=(share\_signature|signature\_verification)(.*?)SubDir=([0-9]+)/', $sUrl, $aMatches)) {
					if($aMatches[3]=='signature_verification') $aSaveDir[$sFileId] =  _SIGNATURE_VERIFICATION_IMAGES_DIR;
					$aSubDir[$sFileId] = $aMatches[5];
				}
			}
		}
		return (string) $oHtml;
	}
	// 將共同簽名檔的圖檔編碼成mime區塊
	private static function createImageParts($aCids, $aSaveDir, $aSubDir , &$aImage) {
		$aImageParts = array();
		$aUrls = array_keys($aCids);
		foreach ($aCids as $sFileId => $sCid) {
			$sFile = $aSaveDir[$sFileId] . ($aSubDir[$sFileId]? '/'.$aSubDir[$sFileId]:'') . '/' . $sFileId . '.data';
			$sInfoFile = $aSaveDir[$sFileId] . ($aSubDir[$sFileId]? '/'.$aSubDir[$sFileId]:'') . '/' . $sFileId . '.info';
			if (is_file($sFile) && is_file($sInfoFile)) {
				$aImage[$sCid] = $sFile;
				$sInfo = file_get_contents($sInfoFile);
				$oInfo = json_decode($sInfo);
				$sFileContent = file_get_contents($sFile);
				if ($sFileContent !== false) {
					$aImageParts[] = MimeUtils::createFilePart($sFileContent, $oInfo->name, $oInfo->type, $sCid, false);
				} else {
					trigger_error("read file {{$sFile}} failure");
				}
			} else {
				trigger_error("file {{$sFile}} not found");
			}
		}
		return $aImageParts;
	}

	// 附加簽名檔
	function appendTextToMail($sText, $sTextType, $bPlaced = false) {
		if (!$this->_body) return;
		$bAppended = false;
		$bHtmlText = ($sTextType == "html");
		$aCids = array();
		if ($bHtmlText) {
			$sText = self::ensurePTag($sText);
			$sText = self::processImage($sText, $aCids, $aSaveDir, $aSubDir);
		}
		$aPartInfo = $this->findTextPart($this->output);
		if($bHasPart = count($aPartInfo) > 0) {
			foreach($aPartInfo as $aInfo) {
				$sBoundary = $aInfo["boundary"];
				$aPartIndex = $aInfo["part_index"];
				$sChangedBody = "";
				$sPattern = "/^--" . preg_quote($sBoundary, '/') . "(--)?$/m";
				$aBlocks = preg_split($sPattern, $this->_body);
				for($i = 0; $i < count($aBlocks); $i++) {
					$nPartIndex = $i - 1;
					if(in_array($nPartIndex, $aPartIndex)) {
						$sBlock = preg_replace("/^(\r\n|\r|\n)|(\r\n|\r|\n)$/", "", $aBlocks[$i]);
						$sBlock = $this->appendTextToPart($sBlock, $sText, $bHtmlText, $bPlaced);
						$aBlocks[$i] = $this->crlf . $sBlock . $this->crlf;
					}
					if($i > 0) {
						$sChangedBody .= "--" . $sBoundary;
					}
					if($i == count($aBlocks) - 1) {
						$sChangedBody .= "--";
					}
					$sChangedBody .= $aBlocks[$i];
				}
				$this->_body = $sChangedBody;
			}
			$bAppended = true;
		} else {
			if ($this->output->ctype_primary == 'text' && in_array($this->output->ctype_secondary, array('plain', 'html'))) {
				if (!(isset($this->output->disposition) && preg_match("/^attachment$/i", $this->output->disposition))) {
					$aPartData = $this->appendTextToPartBySplited($this->_header, $this->_body, $sText, $bHtmlText, $bPlaced, $bHasPart);
					$this->_header = $aPartData['header'];
					$this->_body = $aPartData['body'];
					$bAppended = true;
				}
			}
		}
		if ($bAppended) {
			if (count($aCids)) {
				$aImageParts = self::createImageParts($aCids, $aSaveDir, $aSubDir, $this->aAttachPath);
				$this->insertRelatedImageParts($this->output, $aImageParts);
			}
			if(!$bHasPart){
				$input = $this->_header.$this->crlf.$this->crlf.$this->_body;
				$this->txt_body = $this->stxt_body = $this->html_body = $this->shtml_body = $this->shtmlUtf8_body = "";
				$this->go_decode($input);
			}
			$this->changed = 1; //信件已經改變
		}
	}

	function appendTextToPart($sPart, $sText, $bHtmlText, $bPlaced = false) {
		list($sHeader, $sBody) = explode($this->crlf . $this->crlf, $sPart, 2);
		$aPartData = $this->appendTextToPartBySplited($sHeader, $sBody, $sText, $bHtmlText, $bPlaced);
		$sPart = $aPartData["header"] . $this->crlf . $this->crlf . $aPartData["body"];
		return $sPart;
	}

	function appendTextToPartBySplited($sHeader, $sBody, $sText, $bHtmlText, $bPlaced = false, $bHasPart = true) {
		$oPart = $this->splitBodyPart($sHeader, $sBody);

		$bHtmlBody = ($oPart->ctype_secondary == "html");
		if($bHtmlBody) $this->bHasHtmlType = true;

		$aContentTypeSecondary = $bHtmlText?array("html"):array("plain", "html");
		if($oPart->ctype_primary == "text" && in_array($oPart->ctype_secondary, $aContentTypeSecondary)) {
			$sCharset = $oPart->ctype_parameters["charset"];
			$sCharset = strtolower(trim($sCharset));
			if(!$sCharset)$sCharset=strtolower(trim($this->sDefaultCharset));
			$sEncoding = $oPart->headers["content-transfer-encoding"];
			$sEncoding = strtolower(trim($sEncoding));

			$aEncoding = array("7bit", "8bit", "quoted-printable", "base64");
			if(!in_array($sEncoding, $aEncoding)) {
				$sEncoding = "7bit";
			}

			$sSourceCharset = $this->getCharsetAlias($sCharset);
			$sDecodedBody = $this->DecodeBody($sBody, $sEncoding);
			$sUtf8DecodedBody = (strtolower($sSourceCharset) != 'utf-8') ? iconv($sSourceCharset, "UTF-8//IGNORE", $sDecodedBody):$sDecodedBody;

			$sNewDecodedBody = $this->appendTextToBody($sUtf8DecodedBody, $bHtmlBody, $sText, $bHtmlText, $bPlaced);
			if (strtolower($sSourceCharset) != 'utf-8') {
				$sTempDecodedBody = self::utf8conv2charset_c($sNewDecodedBody, $sSourceCharset);
				if ($sTempDecodedBody !== false) {
					$sNewDecodedBody = $sTempDecodedBody;
					trigger_error("signature convert to {{$sSourceCharset}} success");
				} else {
					$oPart->ctype_parameters["charset"] = 'UTF-8';
					trigger_error("signature convert to {{$sSourceCharset}} failure", E_USER_WARNING);
				}
			}
			if($sEncoding == "7bit") {
				$sBody = MimeUtils::quoted_printable_encode($sNewDecodedBody, 76, $this->crlf);
				$oPart->headers["content-transfer-encoding"] = "quoted-printable";
			} else if($sEncoding == "8bit") {
				$sBody = $sNewDecodedBody;
			} else if($sEncoding == "quoted-printable") {
				$sBody = MimeUtils::quoted_printable_encode($sNewDecodedBody, 76, $this->crlf);
			} else {
				$sBody = chunk_split(base64_encode($sNewDecodedBody), 76, $this->crlf);
				$oPart->headers["content-transfer-encoding"] = "base64";
			}
			if(!$bHasPart)$this->output->headers['content-transfer-encoding'] = $oPart->headers["content-transfer-encoding"];
			$sBody = preg_replace("/(\r\n|\r|\n)$/", "", $sBody);
			$sHeader = $this->buildPartHeader($oPart, $sHeader);
		}
		return array("header" => $sHeader, "body" => $sBody);
	}
	function appendTextToBody($sBody, $bHtmlBody, $sText, $bHtmlText, $bPlaced = false) {
		if($bPlaced){
			if($bHtmlBody && $bHtmlText) {
				if (preg_match("/<body>/i", $sBody)) {
					$sBody = preg_replace("/<body>/i", "<body>$sText<br />", $sBody);
					$this->shtmlUtf8_body = preg_replace("/<body>/i", "<body>$sText<br />", $this->shtmlUtf8_body);
				} else {
					$sBody = "<br />$sText<br />".$sBody;
					$this->shtmlUtf8_body = $this->bBodyHasHtmlTag ? "<html >$sText</html >".$sBody:$sBody;
				}
			} else if($bHtmlBody && !$bHtmlText) {
				if (preg_match("/<body>/i", $sBody)) {
					$sBody = preg_replace("/<body>/i", "<body><pre>$sText</pre>", $sBody);
					$this->shtmlUtf8_body = preg_replace("/<body>/i", "<body><pre>$sText</pre>", $this->shtmlUtf8_body);
				} else {
					$sBody = "<br /><pre>$sText</pre>".$sBody;
				}
			} else if(!$bHtmlBody && !$bHtmlText) {
				$sBody = preg_replace("/(\r\n|\r|\n)$/", "", $sBody);
				$sBody = "\r\n".$sText.$sBody ;
			}
		}else{
			$this->appendTextToBodyFooter($sBody, $sText, $bHtmlBody, $bHtmlText);

		}
		return $sBody;
	}
	function appendTextToBodyFooter(&$sBody, $sText, $bHtmlBody, $bHtmlText){
		$bMatch = false;
		$nMatch = 0;
		$aReplace = array();
		$sReplace = '';
		if(!empty($this->output->headers['in-reply-to'])){
			if(preg_match('/\sThunderbird\//', $this->header['user-agent'])){
				$nReply = $nForward = strlen($sBody);
				$sReply = $sForward = '';

				$sReplaceBody =preg_replace("/\n/", "#RN_BR#", $sBody);
				preg_match('/(\-){2,}(.+)(\-){2,}([^\<\>]*)<table([^\<\>]*)>([^\<\>]*)<tbody>([^\<\>]*)<tr>([^\<\>]*)<th([^\<\>]*)>([^\<\>]*)<\/th>([^\<\>]*)<td>([^\<\>]*)<\/td>([^\<\>]*)<\/tr>'.
								'([^\<\>]*)<tr>([^\<\>]*)<th([^\<\>]*)>([^\<\>]*)<\/th>([^\<\>]*)<td>([^\<\>]*)([a-zA-Z]+,\s\d{1,2}\s[a-zA-Z]+\s\d{4}\s\d{1,2}:\d{1,2}:\d{1,2})([^\<\>]*)<\/td>([^\<\>]*)<\/tr>'.
								'([^\<\>]*)<tr>([^\<\>]*)<th([^\<\>]*)>([^\<\>]*)<\/th>([^\<\>]*)<td>([^\<\>]*)<a([^\<\>]*)href=\"mailto:(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)\">([^\<\>]*)<\/a><\/td>([^\<\>]*)<\/tr>'.
								'([^\<\>]*)<tr>([^\<\>]*)<th([^\<\>]*)>([^\<\>]*)<\/th>([^\<\>]*)<td>(.*?)<a([^\<\>]*)href=\"mailto:(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)\">(.*?)<\/a><\/td>([^\<\>]*)<\/tr>'.
								'(.*?)<\/tbody>([^\<\>]*)<\/table>/i', $sReplaceBody, $aMatch);
				if($aMatch[0]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[0]);
					$nForward = strpos($sBody, $sMatch);
					$sForward = $sMatch;
				}

				preg_match('/<div\sclass=\"moz-cite-prefix\">([^\<\>]*)\s\d{4}\/\d{1,2}\/\d{1,2}\s(.*?)\s\d{1,2}:\d{1,2}\s([^\<\>]*)<br>([^\<\>]*)<\/div>([^\<\>]*)<blockquote([^\<\>]*)>/i', $sReplaceBody, $aMatch);
				if($aMatch[0]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[0]);
					$nReply = strpos($sBody, $sMatch);
					$sReply = $sMatch;
				}
				if($bMatch){
					if($nForward < $nReply)$sReplace = $sForward;
					else $sReplace = $sReply;
				}
			}elseif(preg_match('/WebMail(.*?)Build(.*?)|MailAPI/i', trim($this->header['x-mailer']), $aMatch)){
				$sReplaceBody =preg_replace("/\n/", "#RN_BR#", $sBody);
				$aPreg_match = array();
				$aPreg_match[0] = '<blockquote([^\<\>]*)>([^\<\>]*)<div>([^\<\>]*)<\/div>([^\<\>]*)<div>((\-){2,}([^\<\>]*)(\-){2,})([^\<\>]*)<div><b>([^\<\>]*)<\/b>([^\<\>]*)(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)([^\<\>]*)<\/div>';
				$aPreg_match[1] ='([^\<\>]*)<div><b>([^\<\>]*)<\/b>([^\<\>]*)(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)([^\<\>]*)<\/div>([^\<\>]*)';
				$aPreg_match[2] ='([^\<\>]*)<div><b>([^\<\>]*)<\/b>([^\<\>]*)(\s[a-zA-Z]+\s[a-zA-Z]+\s\d{1,2}\s\d{1,2}:\d{1,2}:\d{1,2}\s(.*?)\s\d{4})([^\<\>]*)<\/div>([^\<\>]*)';
				$aPreg_match[3] ='([^\<\>]*)<div><b>([^\<\>]*)<\/b>([^\<\>]*)<\/div>([^\<\>]*)';
				$aPreg_match[4] ='([^\<\>]*)<div>([^\<\>]*)<\/div>';

				if(!strcasecmp($aMatch[0],'MailAPI')){
					$aPreg_match[0] ='<hr>([^\<\>]*)'.$aPreg_match[0];
					$aPreg_match[2] ='([^\<\>]*)<div><b>([^\<\>]*)<\/b>([^\<\>]*)(\d{1,2}\/\d{1,2}\s\d{1,2}:\d{1,2})([^\<\>]*)<\/div>([^\<\>]*)';
					$aPreg_match[4] ='<div>([^\<\>]*)<br([^\<\>]*)>([^\<\>]*)<\/div>';
				}
				preg_match('/'.implode('', $aPreg_match).'/i', $sReplaceBody, $aMatch);
				if($aMatch[0]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[0]);
					$sReplace = $sMatch;
				}
			}elseif(preg_match('/Microsoft.?Windows.?Live.?Mail/i', trim($this->header['x-mailer']))){
				$sReplaceBody =preg_replace("/\n/", "#RN_BR#", $sBody);
				$sPreg_match ='/<DIV\sstyle=\"BACKGROUND:\s\#f5f5f5\">([^\<\>]*)<DIV([^\<\>]*)><B>From:<\/B>([^\<\>]*)<A([^\<\>]*)>([^\<\>]*)<\/A>([^\<\>]*)<\/DIV>'.
											'([^\<\>]*)<DIV><B>Sent:<\/B>([^\<\>]*)<\/DIV>'.
											'([^\<\>]*)<DIV><B>To:<\/B>([^\<\>]*)<A([^\<\>]*)>([^\<\>]*)<\/A>([^\<\>]*)<\/DIV>'.
											'([^\<\>]*)<DIV><B>Subject:<\/B>([^\<\>]*)<\/DIV><\/DIV>/i';
				preg_match($sPreg_match, $sReplaceBody, $aMatch);
				if($aMatch[0]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[0]);
					$sReplace = $sMatch;
				}
			}elseif(preg_match('/Microsoft(.*?)Outlook/i', trim($this->header['x-mailer']))){
				$sReplaceBody =preg_replace("/\n/", "#RN_BR#", $sBody);
				$sPreg_match ='/<div\sstyle=\'border:none;border-top:([^\<\>]*)>(\n#RN_BR#|#RN_BR#)*<p\sclass=MsoNormal><b><span([^\<\>]*)>From:<\/span><\/b><span([^\<\>]*)>([^\<\>]*)<br>'.
											'([^\<\>]*)<b>Sent:<\/b>([^\<\>]*)<br>'.
											'([^\<\>]*)<b>To:<\/b>([^\<\>]*)<br>'.
											'([^\<\>]*)<b>Subject:<\/b>((<o:p>|<\/o:p>|[^\<\>])*)<\/span><\/p>/i';
				preg_match($sPreg_match, $sReplaceBody, $aMatch);
				if($aMatch[0]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[0]);
					$sReplace = $sMatch;
				}
			}elseif(preg_match('/<([^\<\>]*)@mail\.gmail.com>/i', trim($this->header['message-id']))){
				$sReplaceBody =preg_replace("/\n/", "#RN_BR#", $sBody);
				$nReply = $nForward = strlen($sBody);
				$sReply = $sForward = '';

				$sPreg_match = '/(<div([^\<\>]*)>([^\<\>]*)<br>([^\<\>]*)<div([^\<\>]*)>\d{4}-\d{1,2}-\d{1,2}\s\d{1,2}:\d{1,2}([^\<\>]*)<span([^\<\>]*)>([^\<\>]*)<a([^\<\>]*)href=\"mailto:(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)\"([^\<\>]*)>([^\<\>]*)<\/a>([^\<\>]*)<\/span>([^\<\>]*)<blockquote([^\<\>]*)>)(.*?)<\/blockquote>([^\<\>]*)<\/div>([^\<\>]*)<\/div>/i';
				preg_match($sPreg_match, $sReplaceBody, $aMatch);
				if($aMatch[1]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[1]);
					$nForward = strpos($sBody, $sMatch);
					$sForward = $sMatch;
				}
				$sPreg_match = '/(<div([^\<\>]*)>((\-){2,}([^\<\>]*)(\-){2,})<br>From:([^\<\>]*)<b([^\<\>]*)>([^\<\>]*)<\/b>([^\<\>]*)<span([^\<\>]*)>([^\<\>]*)<a\shref=\"mailto:(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)\">([^\<\>]*)<\/a>([^\<\>]*)<\/span>([^\<\>]*)<br>Date:([^\<\>]*)<br>Subject:([^\<\>]*)<br>To:([^\<\>]*)<a\shref=\"mailto:(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)\">([^\<\>]*)<\/a>([^\<\>]*)<a\shref=\"mailto:(\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*)\">([^\<\>]*)<\/a>)(.*)<\/div>/i';
				preg_match($sPreg_match, $sReplaceBody, $aMatch);
				if($aMatch[1]){
					$bMatch = true;
					$sMatch =str_replace("#RN_BR#", "\n", $aMatch[1]);
					$nReply = strpos($sBody, $sMatch);
					$sReply = $sMatch;
				}
				if($bMatch){
					if($nForward < $nReply)$sReplace = $sForward;
					else $sReplace = $sReply;
				}
			}
		}
		if($bMatch && $sReplace!=''){
			if($bHtmlBody && $bHtmlText) {
				$sBody = str_replace($sReplace, "<br />$sText<br />".$sReplace, $sBody);
				$this->shtmlUtf8_body = str_replace($sReplace, "<br />$sText<br />".$sReplace, $this->shtmlUtf8_body);
			} else if($bHtmlBody && !$bHtmlText) {
				$sBody = str_replace($sReplace, "<br /><pre>$sText</pre><br />".$sReplace, $sBody);
				$this->shtmlUtf8_body = str_replace($sReplace, "<br /><pre>$sText</pre><br />".$sReplace, $this->shtmlUtf8_body);
			} else if(!$bHtmlBody && !$bHtmlText) {
				$sBody = str_replace($sReplace, "\n$sText\n".$sReplace, $sBody);
			}
		}else{
			if($bHtmlBody && $bHtmlText) {
				$sBody .= "<br />$sText<br />";
				$this->shtmlUtf8_body .= $this->bBodyHasHtmlTag ? "<html >$sText</html >":$sText;
			} else if($bHtmlBody && !$bHtmlText) {
				$sBody .= "<br /><pre>$sText</pre><br />";
				$this->shtmlUtf8_body .= $this->bBodyHasHtmlTag ?"<html ><pre>$sText</pre></html >":"<pre>$sText</pre>";
			} else if(!$bHtmlBody && !$bHtmlText) {
				$sBody .= "\n$sText\n";
			}
		}
	}
	function replaceTextToMail($sReplace, $sReplaceStr) {
		if (!$this->_body) return;
		$bReplace = false;
		$aPartInfo = $this->findTextPart($this->output);
		if(count($aPartInfo) > 0) {
			foreach($aPartInfo as $aInfo) {
				$sBoundary = $aInfo["boundary"];
				$aPartIndex = $aInfo["part_index"];
				$sChangedBody = "";
				$sPattern = "/^--" . preg_quote($sBoundary, '/') . "(--)?$/m";
				$aBlocks = preg_split($sPattern, $this->_body);
				for($i = 0; $i < count($aBlocks); $i++) {
					$nPartIndex = $i - 1;
					if(in_array($nPartIndex, $aPartIndex)) {
						$sBlock = preg_replace("/^(\r\n|\r|\n)|(\r\n|\r|\n)$/", "", $aBlocks[$i]);
						$sBlock = $this->replaceTextToPart($sBlock, $sReplace, $sReplaceStr);
						$aBlocks[$i] = $this->crlf . $sBlock . $this->crlf;
					}
					if($i > 0) {
						$sChangedBody .= "--" . $sBoundary;
					}
					if($i == count($aBlocks) - 1) {
						$sChangedBody .= "--";
					}
					$sChangedBody .= $aBlocks[$i];
				}
				$this->_body = $sChangedBody;
			}
			$bReplace = true;
		} else {
			if ($this->output->ctype_primary == 'text' && in_array($this->output->ctype_secondary, array('plain', 'html'))) {
				if (!(isset($this->output->disposition) && preg_match("/^attachment$/i", $this->output->disposition))) {
					$aPartData = $this->replaceTextToPartBySplited($this->_header, $this->_body, $sReplace, $sReplaceStr);
					$this->_header = $aPartData['header'];
					$this->_body = $aPartData['body'];
					$bReplace = true;
				}
			}
		}
		if ($bReplace) {
			$this->changed = 1; //信件已經改變
		}
	}

	function replaceTextToPart($sPart, $sReplace, $sReplaceStr) {
		list($sHeader, $sBody) = explode($this->crlf . $this->crlf, $sPart, 2);
		$aPartData = $this->replaceTextToPartBySplited($sHeader, $sBody, $sReplace, $sReplaceStr);
		$sPart = $aPartData["header"] . $this->crlf . $this->crlf . $aPartData["body"];
		return $sPart;
	}

	function replaceTextToPartBySplited($sHeader, $sBody, $sReplace, $sReplaceStr) {
		$oPart = $this->splitBodyPart($sHeader, $sBody);

		$bHtmlBody = ($oPart->ctype_secondary == "html");

		$aContentTypeSecondary = array("plain", "html");
		if($oPart->ctype_primary == "text" && in_array($oPart->ctype_secondary, $aContentTypeSecondary)) {
			$sCharset = $oPart->ctype_parameters["charset"];
			$sCharset = strtolower(trim($sCharset));
			if(!$sCharset)$sCharset=strtolower(trim($this->sDefaultCharset));
			$sEncoding = $oPart->headers["content-transfer-encoding"];
			$sEncoding = strtolower(trim($sEncoding));

			$aEncoding = array("7bit", "8bit", "quoted-printable", "base64");
			if(!in_array($sEncoding, $aEncoding)) {
				$sEncoding = "7bit";
			}

			$sSourceCharset = $this->getCharsetAlias($sCharset);
			$sDecodedBody = $this->DecodeBody($sBody, $sEncoding);
			$sUtf8DecodedBody = (strtolower($sSourceCharset) != 'utf-8') ? iconv($sSourceCharset, "UTF-8//IGNORE", $sDecodedBody):$sDecodedBody;
			$sReplace = "/" . preg_quote($sReplace, '/') . "/m";
			$sNewDecodedBody = preg_replace($sReplace, $sReplaceStr, $sUtf8DecodedBody);
			if (strtolower($sSourceCharset) != 'utf-8') {
				$sTempDecodedBody = self::utf8conv2charset_c($sNewDecodedBody, $sSourceCharset);
				if ($sTempDecodedBody !== false) {
					$sNewDecodedBody = $sTempDecodedBody;
					trigger_error("replace to {{$sSourceCharset}} success");
				} else {
					$oPart->ctype_parameters["charset"] = 'UTF-8';
					trigger_error("replace to {{$sSourceCharset}} failure", E_USER_WARNING);
				}
			}

			if($sEncoding == "7bit") {
				$sBody = MimeUtils::quoted_printable_encode($sNewDecodedBody, 76, $this->crlf);
				$oPart->headers["content-transfer-encoding"] = "quoted-printable";
			} else if($sEncoding == "8bit") {
				$sBody = $sNewDecodedBody;
			} else if($sEncoding == "quoted-printable") {
				$sBody = MimeUtils::quoted_printable_encode($sNewDecodedBody, 76, $this->crlf);
			} else {
				$sBody = chunk_split(base64_encode($sNewDecodedBody), 76, $this->crlf);
				$oPart->headers["content-transfer-encoding"] = "base64";
			}
			$sBody = preg_replace("/(\r\n|\r|\n)$/", "", $sBody);
			$sHeader = $this->buildPartHeader($oPart, $sHeader);
		}
		return array("header" => $sHeader, "body" => $sBody);
	}
	function buildPartHeader($oPart, $sHeader) {
		$sHeader = preg_replace("/^(\r\n|\r|\n)|(\r\n|\r|\n)$/", "", $sHeader);

		$sEncoding = $oPart->headers["content-transfer-encoding"];
		$sEncoding = strtolower(trim($sEncoding));

		$sContentType = "Content-Type: " . $oPart->ctype_primary . "/" . $oPart->ctype_secondary;
		foreach($oPart->ctype_parameters as $sKey => $sValue) {
			$sContentType .= ";" . $this->crlf . "\t" . $sKey . "=";
			if(strpos(" ", $sValue) !== false) {
				$sContentType .= "\"" . $sValue . "\"";
			} else {
				$sContentType .= $sValue;
			}
		}
		if(preg_match("/^Content-Type:.+$([\r\n]+^( |\t)+.+$)*/im", $sHeader)) {
			$sHeader = preg_replace("/^Content-Type:.+$([\r\n]+^( |\t)+.+$)*/im", $sContentType, $sHeader);
		} else {
			$sHeader .= $this->crlf . $sContentType;
		}

		$sContentTransferEncoding = "Content-Transfer-Encoding: " . $sEncoding;
		if(preg_match("/^Content-Transfer-Encoding:.*$/im", $sHeader)) {
			$sHeader = preg_replace("/^Content-Transfer-Encoding:.*$/im", $sContentTransferEncoding, $sHeader);
		} else {
			$sHeader .= $this->crlf . $sContentTransferEncoding;
		}

		return $sHeader;
	}

	// 更換主旨
	function Replace_Subject($sPrefixSubject)
	{
		preg_match_all("/((\\n?)Subject:(.*$([\r\n]+^( |\t)+.+$)*))/im", $this->_header, $aMatches);
		foreach($aMatches[0] as $nKey => $aMatch){
			if($nKey ==0 && $aMatches[2][$nKey]==='' && !preg_match("/^Subject/i", $this->_header))continue;
			$sRawSubject = $aMatches[3][$nKey];
			$sDecodeSubject = $this->decode_mime_string($sRawSubject);
			$sNewSubject = $sPrefixSubject . $sDecodeSubject;
			$sEncodeNewSubject = '=?UTF-8?B?' . base64_encode($sNewSubject) . '?=';
			$this->_header = str_replace($aMatches[0][$nKey], $aMatches[2][$nKey]."Subject: $sEncodeNewSubject", $this->_header);
		}
		$this->sReplaceSubject = str_replace($sRawSubject, $sEncodeNewSubject, $this->header['subject']);
		$this->changed = 1; //信件已經改變
	}

	// 取代主旨的部分內容
	function Replace_Subject_part($sReplace, $sBeReplaced) {
		preg_match_all("/((\\n?)Subject:(.*$([\r\n]+^( |\t)+.+$)*))/im", $this->_header, $aMatches);
		$sRawSubject = $aMatches[3][0];
		$sDecodeSubject = $this->decode_mime_string ($sRawSubject);
		$sNewSubject = str_replace($sReplace, $sBeReplaced, $sDecodeSubject);
		$sEncodeNewSubject = '=?UTF-8?B?' . base64_encode($sNewSubject) . '?=';
		$this->_header = str_replace($aMatches[0][0], $aMatches[2][0]."Subject: $sEncodeNewSubject", $this->_header);
		$this->changed = 1; //信件已經改變
	}
	// 移除header的某個field
	function Remove_Header($field, $key = null) {
		if(is_array($this->output->headers[$field])){
			foreach($this->output->headers[$field] as $headerKey => $value){
				if($key !== null){
					if($key !== $headerKey) continue;
					$value =  implode("(\s+)",array_filter(preg_split("/\s/",addcslashes($value, ".()+-*?[]|\"'\/"))));
					$this->_header = preg_replace("/(\s$field:(\s?)$value)/i", "", $this->_header);
				}else{
					$value =  implode("(\s+)",array_filter(preg_split("/\s/",addcslashes($value, ".()+-*?[]|\"'\/"))));
					$this->_header = preg_replace("/(\s$field:(\s?)$value)/i", "", $this->_header);
				}
			}
		}else{
			$value =  implode("(\s+)",array_filter(preg_split("/\s/",addcslashes($this->output->headers[$field], ".()+-*?[]|\"'\/"))));
			$this->_header = preg_replace("/(\s$field:(\s?)$value)/i", "", $this->_header);
		}
		$this->changed = 1; //信件已經改變
	}
	// 更改header的某個field的內容
	function Replace_Header($field, $replace) {
		if(preg_match("/\\n$/i",$this->_header))$_header = $this->_header;
		else $_header = $this->_header.$this->crlf;
		$this->_header = preg_replace("/($field:\s?(.+))\\n/i", "", $_header);
		$this->_header .= $field . ': ' . $replace;
		$this->changed = 1; //信件已經改變
	}
	// 清除檔案
	function Remove_File($atts = array()) { // 儲存副檔名為 .virus
		// $atts[]:0 -> 原檔名 , 1. 改變後的檔名 2. 是否清除檔案內容 3. unpacked 內的檔名
		for($i = 0;$i < count($atts);$i++) {
			$att = $atts[$i];
			$bFind = false;
			$sHeaderFile = $sBodyFile = '';
			foreach($this->attmap as $nKey => $attmap){
				if($attmap[0] === $att[3]){
					$sHeaderFile = dirname($attmap[5])."/attach_".basename($attmap[5]);
					$sBodyFile = $attmap[5];
					$sTempFileName = $attmap[3];
					$bFind = true;
					break;
				}
			}
			if($bFind){
				$f = "filename=\"" . $att[0] . '"';
				if ($att[0] != $att[1]) { // 更改檔名
					$sAttachPart = file_get_contents($sHeaderFile).$this->crlf.$this->crlf.($sBodyFile);
					$f2 = "name=\"" . $att[1] . '"';
					$sPatterns = "/name\s*=\s*\"?" . preg_quote($att[0], '/') . "\"?/";
					$sNewAttachPart = preg_replace($sPatterns, $f2, $sAttachPart);
					$this->_body = str_replace($sAttachPart, $sNewAttachPart, $this->_body);
					$this->changed = 1; //信件已經改變
					file_put_contents($sHeaderFile, preg_replace($sPatterns, $f2, file_get_contents($sHeaderFile)));
				}
				if ($att[2]) { // clean attach
					file_put_contents($sBodyFile, '');
					$rf = $this->path . "/unpacked/" . $sTempFileName;
					if (is_file($rf)) {
						$fp = fopen($rf, "w");
						fclose($fp);
					}
				}
			}
		}
		return;
	}

	function ReBuildMail($sDestFile, $bRestoreAttach = true) {
		if ($this->changed) {
			$this->appendWebmailMessageId();
			$this->removeHeaderInfo();
			$this->removeMarkedParts();
			if($bRestoreAttach)$aStrRow = $this->restoreAttachParts();
			$fp = fopen($sDestFile, "w");
			fwrite ($fp, $this->_header . $this->crlf . $this->crlf);
			$_body = $this->_body;
			if($nCount = count($aStrRow)){//有擷取附件內容至暫存，恢復至郵件中
				if(file_exists("{$this->path}/removeTmpPartition")){
					$aTmpPartition = json_decode(file_get_contents("{$this->path}/removeTmpPartition"));
					//若有附件，最後一個附件與結尾的邊界中的換行，已存在附件暫存檔中
					$_body = preg_replace("/(\n)(\-{0,2})(".$aTmpPartition[0].")(\-{0,2})(\n*)$/i", str_replace("$",'\$',$aTmpPartition[1]), $this->_body);
				}
				$aBody = explode("\n", $_body);
				$nRow = $nBeForeRow = $nI = 0;
				foreach($aStrRow as $nRow => $sFile){
					$nI++;
					if(file_exists($sFile) && filesize($sFile)){
						fwrite ($fp, implode("\n",  array_slice($aBody,$nBeForeRow,$nRow-$nBeForeRow))."\n");
						$fpTmp = fopen($sFile, 'r');
						while(! feof($fpTmp)){
							fwrite ($fp, fread($fpTmp, 32768));
						}
						fclose($fpTmp);
						$nBeForeRow = $nRow+1;
					}else{
						fwrite ($fp, implode("\n",  array_slice($aBody,$nBeForeRow,$nRow-$nBeForeRow)).($nI == $nCount ? "":"\n"));
						$nBeForeRow = $nRow+1;
					}
				}
				fwrite ($fp, implode("\n",  array_slice($aBody,$nBeForeRow)));
			}else{
				if(file_exists("{$this->path}/removeTmpPartition")){
					$aTmpPartition = json_decode(file_get_contents("{$this->path}/removeTmpPartition"));
					//若無附件，結尾邊界前的換行不可取代
					$_body = preg_replace("/(\n)(\-{0,2})(".$aTmpPartition[0].")(\-{0,2})(\n*)$/i", "$1".str_replace("$",'\$',$aTmpPartition[1]), $this->_body);
				}
				fwrite ($fp, $_body);
			}
			fclose($fp);
		}
	}

	function BuildMailEncryption($mailpack, $bMailEncryption, $aMailEncryption, $aAttachFileStatus, &$sNotEncryptionPdf) {
		if ($aMailEncryption !== null && !$aMailEncryption['bExceedMaxLimit'] && $bMailEncryption) {
			$this->sWebmailMessage = "bEncryptionMode=1";
			if($aMailEncryption['bEncryptionMode']==1){
				global $sys;
				if(!is_object($sys)){
					include_once('CSystem.php');
					$sys = new CSystem;
				}
				$sSubject =$this->GetSubjectText();
				$this->appendWebmailMessageId();
				$this->removeHeaderInfo();
				$sPdf = $this->path."/Pdf_".$this->random_string(6).".pdf";
				while(file_exists($sPdf))$sPdf = $this->path."/Pdf_".$this->random_string(6).".pdf";
				if($sNotEncryptionPdf && file_exists($sNotEncryptionPdf)){ //若已有處理過未加密的PDF檔，就不再重頭建立
					$sPdf = $this->encryptionPdf($aMailEncryption['sPassword'], $sNotEncryptionPdf, $sPdf);
				}else {
					if($aMailEncryption['bAttEmlFile']){
						$tmpfname = tempnam ("$this->path/unpacked", "PMS");
						if($this->changed){
							$this->ReBuildMail($mailpack);
						}
						$fpMailPack = fopen($mailpack, 'r');
						$Attachfp = fopen($tmpfname , 'a+');
						while(! feof($fpMailPack)){
							$sRow = fread($fpMailPack, 30720);
							fwrite($Attachfp, str_replace(array("\r\n", "\n"), "\r\n",$sRow));
						}
						fclose($Attachfp);
						fclose($fpMailPack);
						$this->attmap[] = array('', '', $sSubject.'.eml', basename($tmpfname));
					}
					$sPdf = $this->BuildPdf($sPdf, $aMailEncryption['sPassword'], $sNotEncryptionPdf, $this->byteConverter(filesize($mailpack)));
				}

				$aMailData = $this->ensureMixedBoundary($this->_header, $this->_body, $this->crlf);
				$sBoundary = $aMailData['sBoundary'];
				if(!isset($sBoundary)) {
					$sBoundary = "----=_Part_00_".$this->random_string(20);
					while(preg_match("/".$sBoundary."/", $this->_body))$sBoundary = "----=_Part_00_".$this->random_string(20);
					if(preg_match_all("/((\\n?)Content-Type:(.*$([\r\n]+^( |\t)+.+$)*))/im", $this->_header, $aMatches)){
						foreach($aMatches[0] as $nKey => $aMatch){
							if($nKey ==0 && $aMatches[2][$nKey]==='' && !preg_match("/^Content-Type/i", $this->_header))continue;
							$this->_header = str_replace($aMatches[0][$nKey], $aMatches[2][$nKey]."Content-Type: multipart/mixed; ".$this->crlf."	boundary=\"$sBoundary\"", $this->_header);
						}
					}else{
						$this->_header = preg_replace("/(".$this->crlf."Message-ID.*".$this->crlf.")/Ui","$1Content-Type: multipart/mixed; ".$this->crlf."	boundary=\"$sBoundary\"".$this->crlf, $this->_header);
					}
				}elseif($aMailData['sContentType']['sPrimary'] !== 'multipart' || $aMailData['sContentType']['sSecondary'] !== 'mixed'){
					if(preg_match_all("/((\\n?)Content-Type:(.*$([\r\n]+^( |\t)+.+$)*))/im", $this->_header, $aMatches)){
						foreach($aMatches[0] as $nKey => $aMatch){
							if($nKey ==0 && $aMatches[2][$nKey]==='' && !preg_match("/^Content-Type/i", $this->_header))continue;
							$this->_header = str_replace($aMatches[0][$nKey], $aMatches[2][$nKey]."Content-Type: multipart/mixed; ".$this->crlf."	boundary=\"$sBoundary\"", $this->_header);
						}
					}
				}
				$fp = fopen($mailpack, "w");
				fwrite ($fp, str_replace("\r", "",$this->_header) ."\n\n");
				$sBodyBoundary = "----=_Part_01_".$this->random_string(20);
				while(preg_match("/".$sBodyBoundary."/", $this->_body))$sBodyBoundary = "----=_Part_01_".$this->random_string(20);
				$sElogoCid = uniqid('inline_');
				$sEiconCid = uniqid('inline_');
				$sBackground = uniqid('inline_');
				$sHtmlBody =  file_get_contents("/PDATA/mailrec/script/mail_encryption_html_body.html");
				$sHtmlBody = str_replace( array("∑sElogo","∑sEicon","∑sBackground", "∑sVBackground","∑sVHeight"),
																	array("<img src=\"cid:$sElogoCid\">","<img src=\"cid:$sEiconCid\">","background-repeat: no-repeat;-moz-background-size:100% 100%;-webkit-background-size:100% 100%;-o-background-size:100% 100%;background-size:100% 100%;background-image:url('cid:$sBackground')", "cid:$sBackground",$aMailEncryption['nHeight']."px"),
																	$sHtmlBody);

				$sHtmlBody = str_replace( array("∑sMailContentCHT","∑sMailContentCHS","∑sMailContentENG"),
																	count($this->attmap) ? array($aMailEncryption['MailContentAndAttachCHT'],$aMailEncryption['MailContentAndAttachCHS'],$aMailEncryption['MailContentAndAttachENG']):array($aMailEncryption['MailContentCHT'],$aMailEncryption['MailContentCHS'],$aMailEncryption['MailContentENG']),
																	$sHtmlBody);

				if($aMailEncryption['sLink']){
					$sBody =  file_get_contents("/PDATA/mailrec/script/mail_encryption_body_link.html");
					$sHtmlBody = str_replace("∑sLink", $aMailEncryption['sLink'],$sHtmlBody);
				}else{
					$sBody =  file_get_contents("/PDATA/mailrec/script/mail_encryption_body.html");
					$sHtmlBody = str_replace("∑sLink", "",$sHtmlBody);
				}
				$sHtmlBody = str_replace("∑sMailName",htmlspecialchars((strlen($sSubject)>8 ? mb_strcut($sSubject,0,8,"UTF-8")."…":$sSubject.".pdf")),$sHtmlBody);
				$sHtmlBody = str_replace("∑sMailTitle",htmlspecialchars($sSubject.".pdf"),$sHtmlBody);

				$sBody = str_replace("∑sHtmlBody", chunk_split(base64_encode($sHtmlBody), 76, "\n"),$sBody);
				unset($sHtmlBody);
				$sBody = str_replace("∑sBoundary", $sBoundary,$sBody);
				$sBody = str_replace("∑sBodyBoundary", $sBodyBoundary,$sBody);
				$sBody = str_replace("∑sPdfName", "=?UTF-8?B?".base64_encode($sSubject.".pdf")."?=",$sBody);
				$sBody = str_replace("∑sSubject", $sSubject,$sBody);
				$sBody = str_replace( array("∑sElogo","∑sEicon","∑sBackground"),
															array($sElogoCid,$sEiconCid,$sBackground), $sBody);

				fwrite ($fp, $sBody);
				$fpMailPack = fopen($sPdf, 'r');
				while(!feof($fpMailPack)){
					fwrite ($fp, chunk_split(base64_encode(fread($fpMailPack, 32718)), 76, "\n"));
				}
				fclose($fpMailPack);
				fwrite ($fp, "\n--$sBoundary--\n");
				fclose($fp);
				unlink($sPdf);
			}else if($aMailEncryption['bEncryptionMode']==2){
				$sSubject =$this->GetSubjectText();
				$aAttmap = array();
				foreach($this->attmap as $n => $attmap){
					if(!in_array($attmap[3], $this->attmap_exclude) && !$this->is_embedded($attmap[8])){
						$aAttmap[] = $attmap;
					}
				}
				if(count($aAttmap)){
					if(!is_dir("{$this->path}/zip"))mkdir("{$this->path}/zip");
					$this->removeMarkedParts();
					$aMarkRemovePartIds = array();
					foreach($aAttmap as $n => $attmap){
						$sDecodedName = str_replace("/","",$attmap[2]);
						if(!file_exists("{$this->path}/zip/{$sDecodedName}")){
							exec('ln -s "'.$this->path.'/unpacked/'.$attmap[3].'" '.$this->path.'/zip/"'.str_replace('"','\\\\"',$sDecodedName).'"');
						}
						if(strlen($attmap[0]))$aMarkRemovePartIds[] = $attmap[0];
					}
					if(count($aMarkRemovePartIds)){
						$this->changed = true;
						$aIdList = MimeUtils::getSafeDeleteOrder($aMarkRemovePartIds);
						foreach ($aIdList as $sPartId) {
							$oInfo = MimeUtils::getPartPlaceInfo($this->output, $sPartId);
							if (false !== $oInfo) {
								$this->_body = MimeUtils::removePart($this->_body, $oInfo->boundary, $oInfo->index);
							}
						}
					}
					$sZip = $this->path."/Zip_".$this->random_string(6).".zip";
					while(file_exists($sZip))$sZip = $this->path."/Zip_".$this->random_string(6).".zip";
					$sPassword = preg_replace("/([\"\`\$])/i", '\\\\'.'$1', $aMailEncryption['sPassword']);

					exec("cd {$this->path}/zip;/PGRAM/zip/zip -r -P \"{$sPassword}\" '$sZip' './';");
					$nCount = count(glob("{$this->path}/unpacked2/attach_*"));
					$sTmpZip = "{$this->path}/unpacked2/{$nCount}";
					$fp = fopen($sTmpZip, "w");
					$fpZip = fopen($sZip, 'r');
					while(!feof($fpZip)){
						fwrite ($fp, chunk_split(base64_encode(fread($fpZip, 32718)), 76, "\n"));
					}
					fclose($fpZip);
					fclose($fp);

					$aMailData = $this->ensureMixedBoundary($this->_header, $this->_body, $this->crlf);
					$sBoundary = $aMailData['sBoundary'];
					if(isset($sBoundary)) {
						$sName = "=?UTF-8?B?".base64_encode($sSubject.".zip")."?=";
						$sAttach = "Content-Type: application/zip;\tname=\"$sName\"\n".
												"Content-Transfer-Encoding: base64\n".
												"Content-Disposition: attachment;\tfilename=\"$sName\"";
						file_put_contents("{$this->path}/unpacked2/attach_{$nCount}", $sAttach);
						$aParts[] = "$sAttach\n\n$sTmpZip";
						$this->_body = $this->appendParts($this->_body, $sBoundary, $aParts);
					}
				}
				$aMailEncryption['sLink'] = str_replace("\$sMail",htmlspecialchars($sSubject),$aMailEncryption['sLink']);
				$this->appendTextToMail($aMailEncryption['sLink'], "html");
				$this->ReBuildMail($mailpack);
				if(file_exists($sZip))unlink($sZip);
			}
			return $mailpack;
		}else{
			$this->ReBuildMail($mailpack);
			return $mailpack;
		}
	}

	function is_embedded($sCid){
		if($this->attmap_embedded === false){
			$this->attmap_embedded = array();
			$sHtml = $this->shtmlUtf8_body;
			$sHtml = preg_replace("/[\n\r]/", "",$sHtml);
			$aMatch = array();
			$sImgHtml = "\<(img|v\:image|v\:fill|v\:stroke|v\:imagedata|v\:vmlframe) [^\<\>]*src\=[\"\']cid\:[^\"\'\<\>]*[\"\'][^\<\>]*\>";
			preg_match_all("/".$sImgHtml."/Ui",$sHtml, $aMatch);
			$aMatchImg = array_unique($aMatch[0]);
			foreach($aMatchImg as $sMatchImg){
				$aMatch = array();
				if(preg_match("/src\=[\"\'](cid\:([^\"\'\<\>]*))[\"\']/Ui",$sMatchImg, $aMatch)){
					$this->attmap_embedded[] = $aMatch[2];
				}
			}
			$aMatch = array();
			preg_match_all("/(\<[^\<\>]*(style\s*\=\s*[\"\'][^\"\'\<\>]*background-image:url\([\"\']?[^\"\'\<\>]*[\"\']?\))[^\<\>]*\>)/Ui",$sHtml, $aMatch);
			$aMatchImg = array_unique($aMatch[1]);
			foreach($aMatchImg as $sMatchImg){
				$aMatch = array();
				if(preg_match("/style\s*\=\s*[\"\'][^\"\'\<\>]*url\([\"\']?(cid\:([^\"\'\<\>]*))[\"\']?\)/Ui",$sMatchImg, $aMatch)){
					$this->attmap_embedded[] = $aMatch[2];
				}
			}
		}

		if($sCid = trim(str_replace(array("<",">"),"",$sCid)))
			return in_array($sCid,$this->attmap_embedded);
		else
			return false;
	}

	function random_string($l = 10, $c = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"){
		$max = strlen($c)-1;
		for(;$l > 0;$l--) $s .= $c[rand(0,$max)];
		return str_shuffle($s);
	}

	function BuildPdf($sPdf, $sPassword, &$sNotEncryptionPdf, $sMailSize = null) {
		$aReservedCharacters = array('\\','/','?','*','<','"',':','>');
		$fp = @fopen("{$sPdf}.html" , 'a+');
		preg_match_all("/(\\nSubject:(.+$([\r\n]+^( |\t)+.+$)*))/im", $this->_header, $aMatches);
		$sRawSubject = $aMatches[2][0];
		$aAttachInfo = array();
		foreach($this->attmap as $attmap){
			$aAttachInfo[] = "<span style='white-space: nowrap;'>".str_replace($aReservedCharacters,"_",$attmap[2])." (".$this->byteConverter(filesize("{$this->path}/unpacked/{$attmap[3]}")).")</span>";
		}
		$sDecodeSubject = $this->decode_mime_string ($sRawSubject);
		fwrite($fp, "<!DOCTYPE html><html><head><title>".$sDecodeSubject."</title></head><body>".
									"<div><table style=\"border:1px #cccccc solid;width:100%;\" border=\"1\" rules=\"all\" cellpadding=\"5\"><tr><td style=\"width:20%;\">Form :</td><td style=\"width:80%;\">".trim($this->encodeHeader($this->header["from"]))."</td></tr>".
									"<tr><td>To :</td><td>".preg_replace("/(,)\s*/","$1 <br>",str_replace("$",'\$',trim($this->encodeHeader($this->header["to"]))))."</td></tr>".
									"<tr><td>Subject :</td><td>".$sDecodeSubject."</td></tr>".
									"<tr><td>Size :</td><td>".$sMailSize."</td></tr>".
									(count($aAttachInfo)? "<tr><td>Attach :</td><td>".implode(", ", $aAttachInfo)."</td></tr>":"").
									"</table></div>");
		$sCode = mb_detect_encoding($sMailInfo);
		if(!$sCode)$sCode = $this->sDefaultCharset;
		if($sCode != 'UTF-8')$sMailInfo = iconv($sCode, "UTF-8//IGNORE", $sMailInfo);

		$sHtml = $this->shtmlUtf8_body;
		if(strlen(trim($sHtml))){
			$ntime= time();
			//$sHtml = str_replace("'","\"", $sHtml);

			//remove html charset tag
			//$sHtml = preg_replace("/\<([\w\W\n\r]*)charset\=[\"\']?.+[\"\']?([\s\w\W\n\r]*)\>/Ui", "<$1$2>",$sHtml);
			$sPregPart1 = "\<([^\<\>]*)";
			$sPregPart2 = "([^\<\>]*)\>";
			$sHtml = preg_replace("/".$sPregPart1."content\=[\"\'][^\"\'\<\>]*charset=[^\"\'\<\>]*[\"\']".$sPregPart2."/Ui", "<$1$5>",$sHtml);
			$sHtml = preg_replace("/".$sPregPart1."charset\=[\"\'][^\"\'\<\>]*[\"\']".$sPregPart2."/Ui", "<$1$4>",$sHtml);
			//remove html charset tag

			$sHtml = preg_replace("/(style\=\"[^\"\<\>]*)font\-family\s*\:([^\"\<\>]*\")/Ui","$1$2",$sHtml);
			$sHtml = preg_replace("/(style\=\'[^\'\<\>]*)font\-family\s*\:([^\'\<\>]*\')/Ui","$1$2",$sHtml);
			$sHtml = preg_replace("/[\n\r]/", "",$sHtml);
			if(preg_match_all("/\<html[\w\W\n\r]*\>([\w\W\n\r]*)\<\/html[\w\W\n\r]*\>/Ui",$sHtml, $aMatch)){
				$aHtml = $aMatch[1];
			}else{
				$aHtml[] = $sHtml;
			}
			foreach($aHtml as $nKey => $sHtml){
				$aMatch = array();
				$sImgHtml = "\<(img|v\:image|v\:fill|v\:stroke|v\:imagedata|v\:vmlframe) [^\<\>]*src\=[\"\']cid\:[^\"\'\<\>]*[\"\'][^\<\>]*\>";
				preg_match_all("/".$sImgHtml."/Ui",$sHtml, $aMatch);
				$aMatchImg = array_unique($aMatch[0]);
				foreach($aMatchImg as $sMatchImg){
					$sNewMatchImg = $sMatchImg;
					$aMatch = array();
					preg_match("/src\=[\"\'](cid\:([^\"\'\<\>]*))[\"\']/Ui",$sNewMatchImg, $aMatch);
					$sImageFile = $this->aAttachPath[trim($aMatch[2])];
					if($sImageFile){
						$sNewMatchImg = str_replace($aMatch[1] , $sImageFile,$sNewMatchImg);
					}
					$sHtml = str_replace($sMatchImg, $sNewMatchImg, $sHtml);
				}
				$aMatch = array();
				preg_match_all("/(\<[^\<\>]*(style\s*\=\s*[\"\'][^\"\'\<\>]*background-image:url\([\"\']?[^\"\'\<\>]*[\"\']?\))[^\<\>]*\>)/Ui",$sHtml, $aMatch);
				$aMatchImg = array_unique($aMatch[1]);
				foreach($aMatchImg as $sMatchImg){
					$sNewMatchImg = $sMatchImg;
					$aMatch = array();
					preg_match("/style\s*\=\s*[\"\'][^\"\'\<\>]*url\([\"\']?(cid\:([^\"\'\<\>]*))[\"\']?\)/Ui",$sNewMatchImg, $aMatch);
					$sImageFile = $this->aAttachPath[trim($aMatch[2])];
					if($sImageFile){
						$sNewMatchImg = str_replace($aMatch[1] , $sImageFile,$sNewMatchImg);
					}
					$sHtml = str_replace($sMatchImg, $sNewMatchImg, $sHtml);
				}
				$sCode = mb_detect_encoding($sHtml);
				if(!$sCode)$sCode = $this->sDefaultCharset;
				if($sCode != 'UTF-8')$sHtml = iconv($sCode, "UTF-8//IGNORE", $sHtml);
				fwrite($fp, $sHtml);
			}
		}else {
			$sHtml = $this->stxt_body;
			$sCode = mb_detect_encoding($sHtml);
			if(!$sCode)$sCode = $this->sDefaultCharset;
			if($sCode != 'UTF-8')$sHtml = iconv($sCode, "UTF-8//IGNORE", $sHtml);
			fwrite($fp,str_replace("\n","<br>",$sHtml));
		}
		fwrite($fp, "</body></html>");
		fclose($fp);
		$sStr = "/PDATA/mailrec/script/MailEncryption/wkhtmltopdf --quiet --encoding 'utf-8' {$sPdf}.html $sPdf";
		exec($sStr);
		$mpdf=new mPDF('utf-8');
		$mpdf->aReservedCharacters = $aReservedCharacters;
		$mpdf->SetImportUse();
		$pagecount = $mpdf->SetSourceFile($sPdf);
		for($nI=0;$nI < $pagecount; $nI++){
			$mpdf->SetDocTemplate($sPdf, true);
			$mpdf->AddPage();
		}
		mkdir ("{$this->path}/unpacked3");
		foreach($this->attmap as $n => $attmap){
			if(!in_array($attmap[3], $this->attmap_exclude)){
				//$mpdf->Annotation($attmap[2], $mpdf->w-10, $mpdf->h - 5, $icon='test', $author='', $subject='', $opacity=0, $colarray=false, $popup='', $file="{$this->path}/unpacked/{$attmap[3]}", $name = $attmap[2]);
				$mpdf->Annotation($attmap[2], 0, 0, $icon='test', $author='', $subject='', $opacity=0, $colarray=false, $popup='', $file="{$this->path}/unpacked/{$attmap[3]}", $name = $attmap[2]);
			}
		}
		if(!is_dir($sPdfDir = "/tmp/procmail/pdf"))mkdir($sPdfDir);
		$sNotEncryptionPdf = $sPdfDir."/Pdf_".$this->random_string(6).".pdf";
		while(file_exists($sNotEncryptionPdf))$sNotEncryptionPdf = $sPdfDir."/Pdf_".$this->random_string(6).".pdf";
		$mpdf->Output($sNotEncryptionPdf, 'F');
		return $this->encryptionPdf($sPassword, $sNotEncryptionPdf, $sPdf);
	}

	function byteConverter($nSize){
		$aUnit = 'B';
		$nDecimals = 0;
		if ($nSize > 1048576) {
			$nSize = $nSize / 1048576;
			$aUnit = 'MB'; $nDecimals = 1;
		} else if ($nSize > 1024) {
			$nSize = $nSize / 1024;
			$aUnit = 'KB';
			$nDecimals = 1;
		}
		return number_format($nSize, $nDecimals) . ' ' . $aUnit;
	}

	function encryptionPdf($sPassword, $sPdfFile, $sEncryptionPdfFile){
		$sPassword = preg_replace("/([\`\!\@\#\$\%\^\&\*\(\)\_\+\-\=\|\{\}\:\<\>\?\~\'\"\.\,\/])/i", '\\\\'.'$1', $sPassword);
		exec("/PDATA/mailrec/script/MailEncryption/qpdf --encrypt $sPassword $sPassword 128 -- $sPdfFile $sEncryptionPdfFile");
		return $sEncryptionPdfFile;
	}

	function encodeHeader($sHeaderInfo){
		if(preg_match_all("/=\?([A-Z0-9\-_]+)\?([A-Z0-9\-]+)\?([\x01-\x7F]+?)\?=/Ui",$sHeaderInfo)){
			$sHeaderInfo = $this->decode_mime_string($sHeaderInfo);
		}
		$sHeaderInfo = str_replace(array("<",">"), array("&lt;","	&gt;"), $sHeaderInfo);
		$sCode = mb_detect_encoding($sHeaderInfo);
		if(!$sCode)$sCode = $this->sDefaultCharset;
		if($sCode != 'UTF-8')$sHeaderInfo = iconv($sCode, "UTF-8//IGNORE", $sHeaderInfo);
		return $sHeaderInfo;
	}

	function appendDsForward($sDsForwardMd5) {
		$this->Replace_Header('X-Message-ID',$sDsForwardMd5);
		$this->changed = 1;
	}

	function appendWebmailMessageId() {
		$this->Replace_Header('W-Message-ID', EncryptorAES::encryptAES(uniqid().":".$this->sWebmailMessage));
	}

	function getWebmailMessage() {
		$sWebmailMessage = EncryptorAES::decryptAES(trim($this->header["w-message-id"]));
		$aWebmailMessage = explode(",",substr($sWebmailMessage, strpos($sWebmailMessage,":")+1));
		foreach($aWebmailMessage as $sWebmailMessage){
			$pos = strpos($sWebmailMessage,"=");
			$name = substr($sWebmailMessage, 0, $pos);
			if(strlen($name))$this->aWebmailMessage[$name] = substr($sWebmailMessage, $pos+1);
		}
	}

	function removeHeaderInfo(){
		if($this->header["encryptmode"])$this->Remove_Header("encryptMode");
		if($this->header["encryptpassword"])$this->Remove_Header("encryptPassword");
		if($this->header["encryptrandomsetting"])$this->Remove_Header("encryptRandomSetting");
		if($this->header["encryptnotifty"])$this->Remove_Header("encryptNotifty");
	}

	function appendDsReply($sType, $replyAddr='1') {
		switch($sType) {
			case 'group':
				$this->_header .= $this->crlf . "X-Message-RPGROUP: {$replyAddr}";
				break;
			case 'dep':
				$this->_header .= $this->crlf . "X-Message-RPDEP: {$replyAddr}";
				break;
			case 'alias':
				$this->_header .= $this->crlf . "X-Message-RCHK: {$replyAddr}";
				break;
		}
		$this->changed = 1;
	}

	function appendXspamHeader($aXspamHeader) {
		foreach($aXspamHeader as $sName => $sValue)
		{
			$this->_header .= $this->crlf . $sValue;
			$this->changed = 1;
		}
	}

	// 增加html類型的附件用來指出原本的附件的下載連結, 並且以簽名檔的方式顯示下載連結, 同時把原本的附件標記為刪除
	// 參數 $aAttachFiles[] = new stdClass, 屬性: {sPartId, sDecodedName, sTempFilePath}
	// 傳回值 $aFileInfo[] = array(basename($sTempnam), $sDecodedName);
	function addAttachFileHyperlink($aAttachFiles, $sUrl, $sDescription, $att_dir, $bChange=true, $aOriginMailUrl=null) {
		$aMailData = $this->ensureMixedBoundary($this->_header, $this->_body, $this->crlf);
		$sBoundary = $aMailData['sBoundary'];
		if(isset($sBoundary)) {  // 如果抓不到邊界字串, 就放棄更換附件檔案的動作
			$this->_header = $aMailData['sHeader'];
			$this->_body = $aMailData['sBody'];
			$this->changed = $aMailData['bChanged'];
			$aParts = array();
			$aPartIds = array();
			$sHtml = "<hr>$sDescription";
			foreach ($aAttachFiles as $key => $att) {
				$sFilename = $att[1];
				$sTempnam = "{$att_dir}/{$att[0]}";
				$sHyperlink = $this->createHyperlink($sUrl, $sTempnam, $sFilename);
				$sHtml .= "<br>{$sHyperlink}";
				$aParts[] = $this->createHyperlinkPart($sFilename, $sHyperlink, $sDescription);
				$aPartIds[] = $att[2];
			}
			if (!is_null($aOriginMailUrl)) {
				$sHtml .= "<br><br>{$aOriginMailUrl[0]} :<br>{$aOriginMailUrl[3]}";
				$aParts[] = $this->createHyperlinkPart($aOriginMailUrl[2], $aOriginMailUrl[3], "{$aOriginMailUrl[0]} :");
			}
			if (count($aParts) > 0) {
				if ($bChange) {
					trigger_error("add hyperlinks");
					$this->appendTextToMail($sHtml, "html");
					$this->_body = $this->appendParts($this->_body, $sBoundary, $aParts);
					$this->markRemoveParts($aPartIds);
				}
				$this->changed = 1;
			} else {
				trigger_error("no add hyperlink");
			}
		} else {
			trigger_error("not found multipart/mixed boundary");
		}
	}

	function copyAttachToDir($aAttachFiles, $att_dir, $time) {
		$aFileInfo = array();
		if (!count($aAttachFiles)) return $aFileInfo;
		$aMailData = $this->ensureMixedBoundary($this->_header, $this->_body, $this->crlf);
		$sBoundary = $aMailData['sBoundary'];
		if(isset($sBoundary)) {  // 如果抓不到邊界字串, 就放棄複製
			$this->_header = $aMailData['sHeader'];
			$this->_body = $aMailData['sBody'];
			$this->changed = $aMailData['bChanged'];
			$aParts = array();
			$aPartIds = array();
			foreach ($aAttachFiles as $key => $att) {
				$sFilename = $att->sDecodedName;
				if($att->sTempnam){
					$sTempnam = $att_dir."/".$att->sTempnam;
				}else{
					$sTempnam = tempnam($att_dir, $time . "-");
				}
				$info[0] = basename($sTempnam);
				$info[1] = $sFilename;
				$info[2] = $att->sPartId;
				$aFileInfo[$key] = $info;
				copy($att->sTempFilePath, $sTempnam);
			}
		} else {
			trigger_error("not found multipart/mixed boundary");
		}
		return $aFileInfo;
	}

	// 標記要移除的附件, 等待適當的時機再移除
	function markRemoveParts($aPartIds) {
		if (count($aPartIds) > 0) {
			foreach ($aPartIds as $sId) {
				$this->aMarkRemovePartIds[] = $sId;
			}
			$this->changed = 1;
		}
	}

	// 移除先前做記號附件
	private function removeMarkedParts() {
		if (count($this->aMarkRemovePartIds) > 0) {
			$aIdList = MimeUtils::getSafeDeleteOrder($this->aMarkRemovePartIds);
			foreach($this->attmap as $attmap){
				if(in_array($attmap[0], $aIdList))$this->attmap_exclude[] = $attmap[3];
			}
			foreach ($aIdList as $sPartId) {
				$oInfo = MimeUtils::getPartPlaceInfo($this->output, $sPartId);
				if (false !== $oInfo) {
					$this->_body = MimeUtils::removePart($this->_body, $oInfo->boundary, $oInfo->index);
				}
			}
			$this->aMarkRemovePartIds = array();
			$this->changed = 1;
		} else {
			trigger_error("no remove attach file");
		}
	}

	// 還原原本郵件附件內容
	private function restoreAttachParts() {
		$aStrRow = array();
		$aFiles = glob("{$this->path}/unpacked2/attach_*");
		foreach($aFiles as $sFile){
			$sAttach = file_get_contents($sFile);
			preg_match("/unpacked2\/attach_(\d+)$/i",$sFile, $aMatch);
			$sAttachContensFile = "{$this->path}/unpacked2/{$aMatch[1]}";
			if(is_int($nStrPosition = stripos($this->_body, $sAttach."\n\n{$sAttachContensFile}\n"))){
				$nStrRow = substr_count(substr($this->_body, 0, $nStrPosition).$sAttach."\n\n{$sAttachContensFile}", "\n");
				$aStrRow[$nStrRow] = $sAttachContensFile;
			}
		}
		ksort($aStrRow);
		return $aStrRow;
	}

	function transformUtf8($sContent, $sCharset) {
		$sCharset = $this->getCharsetAlias($sCharset);
		$sConverted = (strtolower($sCharset) != 'utf-8')? iconv($sCharset, "UTF-8//IGNORE", $sContent) : $sContent;
		return $sConverted;
	}

	// 產生[大檔連結]功能的超連結
	function createHyperlink($sUrl, $sFile, $sFilename) {
		clearstatcache();
		$nSize = filesize($sFile);
		$sSize = round(floatval($nSize) / (1024 * 1024), 2) . " MB";
		$sLink = "<a href=\"$sUrl?id=" . basename($sFile) . "&name=" . rawurlencode($sFilename) . "\" target=\"_blank\">" . $sFilename . " ($sSize)</a>";
		return $sLink;
	}
	function createHyperlinkPart($sFilename, $sHyperlink, $sDescription) {
		$sName = "=?UTF-8?B?" . base64_encode($sFilename . ".html") . "?=";
		$sLinkHtml = "<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\" /></head><body>$sDescription<br><br>{$sHyperlink}</body></html>";
		$sHtmlPart = "Content-Type: text/html;\n\tname=\"$sName\"\n";
		$sHtmlPart .= "Content-Transfer-Encoding: quoted-printable\n";
		$sHtmlPart .= "Content-Disposition: attachment;\n\tfilename=\"$sName\"\n";
		$sHtmlPart .= "\n";
		$sHtmlPart .= MimeUtils::quoted_printable_encode($sLinkHtml, 76, $this->crlf) . "\n";

		$tmpfname = tempnam ("$this->path/unpacked", "PMS");
		if (($pos = strpos($tmpfname, 'PMS')) !== false) {
			$tmpfname = substr_replace($tmpfname, '', 0, $pos);
		}
		file_put_contents("$this->path/unpacked/$tmpfname", $sLinkHtml);
		$this->attmap[] = array('', '', $sFilename . ".html", "$tmpfname");

		return $sHtmlPart;
	}
	// *尚未完成的function*
	// 確保信件的最外層的'content-type'為'multipart/mixed'類型
	// 如果最外層不是'multipart/mixed',自動包裝一層'multipart/mixed'類型的區塊
	// 傳回'multipart/mixed'區塊的邊界字串(boundary)
	function ensureMixedBoundary($sHeader, $sBody, $sCrlf = "\n") {
		$return = new stdClass;
		$headers = $this->splitHeaderPart($sHeader);
		while (list($key, $value) = each($headers)) {
			if ("content-type" == strtolower($headers[$key]['name'])) {
				$content_type = $this->parseHeaderValue($headers[$key]['value']);
				if (preg_match('/([0-9a-z+.-]+)\/([0-9a-z+.-]+)/i', $content_type['value'], $regs)) {
					$return->ctype_primary = $regs[1];
					$return->ctype_secondary = $regs[2];
				}
				if (isset($content_type['other'])) {
					while (list($p_name, $p_value) = each($content_type['other'])) {
						$return->ctype_parameters[$p_name] = $p_value;
					}
				}
				break;
			}
		}
		$sBoundary = null;
		if($return->ctype_primary == 'multipart' && in_array($return->ctype_secondary, array("alternative", "related", "mixed"))) {
			$sBoundary = $return->ctype_parameters['boundary'];
		}
		// 傳回結果
		$aResult = array();
		$aResult['sHeader'] = $sHeader; // 信件的標頭
		$aResult['sBody'] = $sBody; // 信件的內容
		$aResult['sContentType']['sPrimary'] = $return->ctype_primary; // content-type
		$aResult['sContentType']['sSecondary'] = $return->ctype_secondary; // content-type
		$aResult['sBoundary'] = $sBoundary; // 最外層的'multipart/mixed'類型的邊界字串
		$aResult['bChanged'] = false; // 表示信件的標頭或內容是否有改變
		return $aResult;
	}
	// 新增多個part在指定的邊界的尾端
	public static function appendParts($sBody, $sBoundary, $aParts, $sCrlf = "\n") {
		$sTail = $sCrlf . '--' . $sBoundary . '--' . $sCrlf;
		$nPos = strpos($sBody, $sTail);
		if($nPos === false) {
			return false;
		}
		$sNewData = '';
		foreach($aParts as $sPart) {
			$sNewData .= $sCrlf .'--' . $sBoundary . $sCrlf;
			$sNewData .= $sPart;
		}
		$sNewBody = substr($sBody, 0, $nPos);
		$sNewBody .= $sNewData;
		$sNewBody .= substr($sBody, $nPos, strlen($sBody));
		return $sNewBody;
	}
	// 移除指定的區塊
	// $aParts[] = array('boundary' => $boundary, 'index' => $index);
	static private function removeParts($sBody, $aParts, $sCrlf = "\n") {
		$aBoundaryMap = array();
		foreach($aParts as $aPart) {
			$sBoundary = $aPart['boundary'];
			$nIndex = $aPart['index'];
			if(!isset($aBoundaryMap[$sBoundary])) {
				$aBoundaryMap[$sBoundary] = array();
			}
			$aBoundaryMap[$sBoundary][] = $nIndex;
		}
		$sNewBody = $sBody;
		foreach($aBoundaryMap as $sBoundary => $aIndices) {
			$aIndices = array_unique($aIndices);
			rsort($aIndices);
			foreach($aIndices as $nIndex) {
				$sNewBody = MimeUtils::removePart($sNewBody, $sBoundary, $nIndex, $sCrlf);
			}
		}
		return $sNewBody;
	}


	// 把utf-8字串轉換為其他編碼, 轉換成功則傳回新的字串, 失敗則傳回false
	// 參考資料: http://www.ps3w.net/modules/psbb/?op=openthr&pos_id=1101
	static private function utf8conv2charset_c($utf8str, $charset='BIG5') {
	    $stf8Charset = 'UTF-8'; $charset.= '//IGNORE';
	    $newCharsetStr = '';
	    $prefix = '&#';
	    $len = strlen($utf8str);
	    for($i=0;$i<$len;$i++){
	        $chrPos0 = substr($utf8str, $i, 1);
	        $chrASCII = ord($chrPos0);
	        if( $chrASCII < 0x80 ){            // 單一字碼 ASCII < 128 ; 二進制碼 1000 0000
	            $newCharsetStr.= $chrPos0;    // 免轉碼 ...
	        }elseif( $chrASCII >= 0xc0 && $chrASCII < 0xe0 ){ // 雙字元碼 ASCII 192 to 223 ; 二進制碼 1100 0000 - 1101 1111
	            $chrBytes = 2; $addBytes = $chrBytes-1;
				$newChrs = iconv($stf8Charset, $charset, substr($utf8str, $i, $chrBytes));
	            if(!$newChrs){                              // 若 缺碼, 改轉換成 UnicodeHTML ...
	                return false;
	            }
	            $newCharsetStr.= $newChrs;
	            $i+= $addBytes;
	        }elseif( $chrASCII >= 0xe0 && $chrASCII < 0xf0 ){ // 三字元碼 ASCII 224 to 239 ; 二進制碼 1110 0000 - 1110 1111
	            $chrBytes = 3; $addBytes = $chrBytes-1;
				$newChrs = iconv($stf8Charset, $charset, substr($utf8str, $i, $chrBytes));
	            if(!$newChrs){
	                return false;
	            }
	            $newCharsetStr.= $newChrs;
	            $i+= $addBytes;
	        }elseif( $chrASCII >= 0xf0 && $chrASCII < 0xf8 ) { // 四字元碼 ASCII 240 to 247 ; 二進制碼 1111 0000 - 1111 0111
	            $chrBytes = 4; $addBytes = $chrBytes-1;
				$newChrs = iconv($stf8Charset, $charset, substr($utf8str, $i, $chrBytes));
	            if(!$newChrs){
	                return false;
	            }
	            $newCharsetStr.= $newChrs;
	            $i+= $addBytes;
	        }elseif($chrASCII>=0xf8 && $chrASCII<0xfb){ // 五字元碼 ASCII 248 to 251 ; 二進制碼 1111 1000 - 1111 1011
	            $chrBytes = 5; $addBytes = $chrBytes-1;
				$newChrs = iconv($stf8Charset, $charset, substr($utf8str, $i, $chrBytes));
	            if(!$newChrs){
	                return false;
	            }
	            $newCharsetStr.= $newChrs;
	            $i+= $addBytes;
	        }elseif($chrASCII>=0xfb && $chrASCII<0xfd){ // 六字元碼 ASCII 252 to 253 ; 二進制碼 1111 1100 - 1111 1101
	            $chrBytes = 6; $addBytes = $chrBytes-1;
				$newChrs = iconv($stf8Charset, $charset, substr($utf8str, $i, $chrBytes));
	            if(!$newChrs){
	                return false;
	            }
	            $newCharsetStr.= $newChrs;
	            $i+= $addBytes;
	        }else{ // 通常, 若輸入的是標準 UTF-8 編碼文字, 就不應該會有這個錯誤狀況發生; 這部份是預防輸入的UTF-8編碼文字資料內容本身的錯誤
	            return false;
	        }
	    }
	    return $newCharsetStr;
	}

	function transHtmlText($sHtmlText, $sCode = "UTF-8")
	{//Html 特殊字元傳換
		$sFindKey = '&';
		$aSpecHtmlChar = array('&quot;' => '"', '&apos;' => "'", '&amp;' => '&', '&lt;' => '<', '&gt;' => '>', '&nbsp;' => ' ', '&copy;' => '(c)', '&reg;' => '(R)',
								'&#xFF0E;' => '.', '&#65294;' => '.', '&#x002e;' => '.', '&#x3002;' => '.', '&#12290;' => '.');
		$nKeySize = strlen($sFindKey);
		$sRet = '';
		for($n = 0, $l = strlen($sHtmlText); $n < $l; ++$n)
		{
			if( ($i = strpos($sHtmlText, $sFindKey, $n)) === false)
				break;
			if($n < $i)
			{
				$sRet .= substr($sHtmlText, $n, $i - $n);
				$n = $i;
			}
			if($i = $this->transHtmlText_findEnd($sHtmlText, $i + $nKeySize, $l))
			{
				$sStr = substr($sHtmlText, $n, $i + $nKeySize);
				if(isset($aSpecHtmlChar[$sStr]))
					$sRet .= $aSpecHtmlChar[$sStr];
				else
					$sRet .= mb_convert_encoding($sStr, $sCode, 'HTML-ENTITIES');
				$n += ($i + $nKeySize - 1);
			}
			else
			{
				$sRet .= substr($sHtmlText, $n, $nKeySize);
				$n += ($nKeySize - 1);
			}
		}
		if($n < $l)
			$sRet .= substr($sHtmlText, $n);
		return $sRet;
	}

	function transHtmlText_findEnd($sHtmlText, $nStart, $nMax)
	{
		$nMaxNo = 8;
		if(($l = $nStart + $nMaxNo) > $nMax)
			$l = $nMax;
		for($n = $nStart; $n < $l; ++$n)
		{
			if(';' == $sHtmlText[$n])
				return $n - $nStart + 1;
		}
		return 0;
	}

	function html2Text($sHtmlText, $sCode = "UTF-8")
	{
		$sText = $this->transHtmlText($sHtmlText, $sCode);
		$aHtmlSpecWords = array('&quot;', '&apos;', '&amp;', '&lt;', '&gt;', '&nbsp;', '&copy;', '&reg;');
		$aHtmlSpecRealWords = array('"' , "'"     , '&'    , '<'   , '>'   , ' '     , '(c)'   , '(R)');

		$sText = preg_replace("/<style.*?<\/style>/is", "", $sText);
		$sText = strip_tags($sText);// remove all other html
		return str_replace($aHtmlSpecWords, $aHtmlSpecRealWords, $sText);
	}
}
class popupAttach {
	var $nI = 0;
	var $sDir = '';
	var $sTrimPartition = '';
	var $bIsAttach = false;
	function popupAttachFileToTmp($mailpack, $sDir) {
		/*
		為節省附件占用的記憶體，將可判斷的附件暫存至檔案，以暫存路徑取代原附件內容。
		此暫存附件 在 CMine::splitBodyPart 轉換附件內容的編碼改至 CMine::parse_output 一併轉換
		若信件有需變動 CMine::restoreAttachParts 將會把原附件內容還原
		*/
		if (!is_dir("$sDir/unpacked")) mkdir ("$sDir/unpacked");
		if (!is_dir("$sDir/unpacked2")) mkdir ("$sDir/unpacked2");
		$nI = 0;
		$this->sDir = $sDir;
		$sPartition = $before_row = $row = $input = '';
		$sRemainder = '';
		$fpMailPack = fopen($mailpack, 'r');
		while(! feof($fpMailPack)){
			$bEnd = false;
			$sRow = fread($fpMailPack, 32768);
			if (strlen($sRow) < 32768) $bEnd = true;
			$sRow = str_replace("\r", "", $sRow);
			$sRow = $sRemainder.$sRow;
			if($sPartition == ""){
				$aRow = explode("\n", $sRow);
				if(count($aRow)> 1 )$sLastRow = array_pop($aRow);//最後一筆可能非完整，故POP到下一次
				else $sLastRow = null;
				$sRemainder = $this->getMailPartition($aRow, $sPartition, $input, $before_row, $sLastRow);
			}else{
				$aRow = explode("\n", $sRow);
				$sListRow = array_pop($aRow);
				$aRow = explode("--".$sPartition."\n", implode("\n", $aRow));
				foreach($aRow as $nKey => $row)if($nKey)$aRow[$nKey] = "--".$sPartition."\n".$row;
				if($aRow[0] == "")array_shift($aRow);
				if(count($aRow)> 1 ){
					$sRemainder = array_pop($aRow).(is_null($sListRow)?"":"\n".$sListRow);//最後一筆可能非完整，故POP到下一次
				}else{
					$sRemainder = (is_null($sListRow)?"":"\n".$sListRow);
				}

				if($bEnd && preg_match("/\n(--".$this->sTrimPartition."\-{0,2})(\n*)$/i", $aRow[count($aRow)-1],$aMatch)){
					$aRow[count($aRow)-1] = preg_replace("/(\n{1,2})--".$this->sTrimPartition."(\-{0,2})(\n*)$/i","\n", $aRow[count($aRow)-1]);
					$sEnd = $aMatch[1].$aMatch[2];
				}

				$this->getAndReplaceMailAttach($sDir, $aRow, $sPartition, $input, $nI);
				if($bEnd)$input .= $sEnd;
			}
		}
		if($sRemainder){
			if($sPartition == ""){
				$aRow = explode("\n", $sRemainder);
				$sRemainder = $this->getMailPartition($aRow, $sPartition, $input, $before_row);
			}
			if($sPartition){
				$sEnd = "";
				$aRow = explode("--".$sPartition."\n", $sRemainder);
				foreach($aRow as $nKey => $row)if($nKey)$aRow[$nKey] = "--".$sPartition."\n".$row;
				if(preg_match("/\n(--".$this->sTrimPartition."\-{0,2})(\n*)$/i", $aRow[count($aRow)-1],$aMatch)){
					$aRow[count($aRow)-1] = preg_replace("/(\n{1,2})--".$this->sTrimPartition."(\-{0,2})(\n*)$/i","\n", $aRow[count($aRow)-1]);
					$sEnd = $aMatch[1].$aMatch[2];
				}
				$this->getAndReplaceMailAttach($sDir, $aRow, $sPartition, $input, $nI);
				$input .= $sEnd;
			}
		}
		fclose($fpMailPack);
		$this->nI = $nI;
		$sMailContents = $input;
		if($sPartition){
			if(preg_match("/\n(\-{0,2})".$this->sTrimPartition."(\-{0,2})\n*$/i", $sMailContents, $aMatch)){
				$sMailContents = preg_replace("/(\n)(\-{0,2})(".$this->sTrimPartition.")(\-{0,2})(\n*)$/i",'$1--$3--$5'."\n", $sMailContents);
				file_put_contents("$sDir/removeTmpPartition", json_encode(array($this->sTrimPartition,$aMatch[0].(preg_match("/\n$/i",$sMailContents) ?"":"\n"))));
			}else{
				$sMailContents .= "--".$sPartition."--\n";
				file_put_contents("$sDir/removeTmpPartition", json_encode(array($this->sTrimPartition,"")));
			}
		}
		return $sMailContents;
	}

	function getMailPartition($aRow, &$sPartition, &$input, &$before_row, $sLastRow = null){
		while(($row = array_shift($aRow)) !== null){
			$input .= $row."\n";
			if(preg_match("/^Content\-Type\: multipart\/(mixed|report|digest|alternative|related);/i", $row) ||
				preg_match("/^Content\-Type\: multipart\/(mixed|report|digest|alternative|related);/i", $before_row)
			){
				if(preg_match("/boundary\=(.*)/i", $row, $aMatch)){
					$sPartition = trim($aMatch[1]);
					if(preg_match("/^\"(.*)\"/i", $sPartition, $aMatch))$sPartition = $aMatch[1];
					else if(preg_match("/^'(.*)'/i", $sPartition, $aMatch))$sPartition = $aMatch[1];
					$this->sTrimPartition = $sPartition;
					preg_match_all("/(\W)/", $this->sTrimPartition,$aMatch);
					$aMatch[1]=array_unique($aMatch[1]);
					foreach($aMatch[1] as $sMatch)$this->sTrimPartition = str_replace($sMatch,"\\".$sMatch,$this->sTrimPartition);
					break;
				}
			}
			if(!preg_match("/^\s/i",$row))$before_row = $row;
		}
		if($sLastRow!==null) $aRow[] = $sLastRow;
		return implode("\n", $aRow);
	}

	function getAndReplaceMailAttach($sDir, $aRow, $sPartition, &$input, &$nI){
		$bNotFirtRow = false;
		while(($row = array_shift($aRow)) !== null){
			if(preg_match("/^--".$sPartition."\n/i", $row))$this->bIsAttach = false;
			$aAttachRow = explode("\n\n", $row);
			if(!$this->bIsAttach){
				if(preg_match("/^--".$sPartition."\n/i", $aAttachRow[0]) && ((preg_match("/Content-Disposition:(.*);/", $aAttachRow[0], $aMatch) && $aMatch[1] !=='inline') || preg_match("/Content-Type: application\/octet-stream;/", $aAttachRow[0], $aMatch))){
					$this->bIsAttach = true;
					while(file_exists("$sDir/unpacked2/$nI"))$nI++;
					touch("$sDir/unpacked2/$nI");
					$sAttach = array_shift($aAttachRow);
					file_put_contents("$sDir/unpacked2/attach_$nI", $sAttach);
					$input .= $sAttach;
					$row = implode("\n\n", $aAttachRow);
					$input .= "\n\n$sDir/unpacked2/$nI\n";
					if(!$row)continue;
				}else{
					$input .= $row;
				}
			}
			if($this->bIsAttach){
				$Attachfp = fopen("$sDir/unpacked2/$nI" , 'a+');
				fwrite($Attachfp, $row);
				fclose($Attachfp);
			}
		}
	}

	function addMailAttach($sFile){
		$nI = $this->nI;
		$sDir = $this->sDir;
		while(file_exists("$sDir/unpacked2/$nI"))$nI++;
		touch("$sDir/unpacked2/$nI");
		$fpMailPack = fopen($sFile, 'r');
		$Attachfp = fopen("$sDir/unpacked2/$nI" , 'a+');
		while(! feof($fpMailPack)){
			$sRow = fread($fpMailPack, 30720);
			fwrite($Attachfp, chunk_split(base64_encode($sRow), 76, "\n"));
		}
		fclose($Attachfp);
		fclose($fpMailPack);
		$this->nI = $nI;
		return "$sDir/unpacked2/$nI";
	}
}

class EncryptorAES //與 webmail 共通的 AES 加密解密
{
	private static $enc = MCRYPT_RIJNDAEL_128;
	private static $mode = MCRYPT_MODE_CBC;
	private static $key = "CDDEDABDCCDECDCFAEFEBDACBAECACFD";
	private static $iv = "32112341039980135100035571427801";
	static function encryptAES($value, $key = null, $iv = null)
	{
		if(is_null($key))$key = self::$key;
		if(is_null($iv))$iv = self::$iv;
		$key = pack('H*', $key);
		$iv = pack('H*', $iv);

		//Open
		$module = mcrypt_module_open(self::$enc, '', self::$mode, '');
		mcrypt_generic_init($module, $key, $iv);

		//Padding
		$block = mcrypt_get_block_size(self::$enc, self::$mode);
		//Get Block Size
		$pad = $block - (strlen($value) % $block);
		//Compute how many characters need to pad
		$value .= str_repeat(chr($pad), $pad);
		// After pad, the str length must be equal to block or its integer multiples

		//Encrypt
		$encrypted = bin2hex(mcrypt_generic($module, $value));

		//Close
		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);
		// return base64_encode($encrypted);
		return $encrypted;
	}

	static function decryptAES($value, $key = null, $iv = null)
	{
		if(is_null($key))$key = self::$key;
		if(is_null($iv))$iv = self::$iv;
		$key = pack('H*', $key);
		$iv = pack('H*', $iv);

		//Open
		$module = mcrypt_module_open(self::$enc, '', self::$mode, '');
		mcrypt_generic_init($module, $key, $iv);
		//Decrypt
		// $value = mdecrypt_generic($module, hex2bin(base64_decode($value)));
		$value = mdecrypt_generic($module, hex2bin($value));
		//Get original str

		//Close
		mcrypt_generic_deinit($module);
		mcrypt_module_close($module);

		//Depadding
		$slast = ord(substr($value, -1));
		//pad value and pad count
		$value = substr($value, 0, strlen($value) - $slast);

		return $value;
	}
}
?>
