<?
class CString {
	
	function cuttingstr($str,$ct,$addstr="") {
		$len=strlen($str);
		if(strlen($str) > $ct) {
			for($i=0;$i<$ct;$i++) {
				$ch=substr($str,$i,1);
				if(ord($ch)>127) $i++;
			}
		  $str= substr($str,0,$i);
		} 
		if(strlen($str) < $len) $str.=$addstr;
		return $str;
	}
	
	function midcuttstr($str,$ct,$addstr=""){
		$len=strlen($str);
		if(strlen($str) > $ct) {
			$ars=explode(".",$str);
			if(count($ars)>1){
				$ext=".".$ars[count($ars)-1];
				
				$ct=$ct-strlen($ext);
				$str=substr($str,0,$len-strlen($ext));	
			}
			$str=$this->cuttingstr($str,$ct);
			$str.=$addstr.$ext;		
		}
		return $str;
	}
	
	function special_www($pbody) {
		$pbody =preg_replace( "/\[www *\]([\\x0-\\xff]*?)\[\/www *\]/", '<a href="\\1"  target="_blank">\\1</a>', $pbody ); 
		$pbody =preg_replace( "/\[img *\]([\\x0-\\xff]*?)\[\/img *\]/", '<img src="\\1">', $pbody ); 
		//$pbody =preg_replace( "/\[<<<<", '<font color=red>\\1</font>', $pbody ); 
					// /\[PMOD_[^\[\:]+\]/
		//$pbody =preg_replace( "/\[www +([a-zA-Z0-9\.:\/_\-]+)\]([\\x0-\\xff]*?)\[\/www *\]/", '<a href="\\1"  target="_blank">\\2</a>', $pbody ); 	
		//$pbody =preg_replace( "/\[gray]([\\x0-\\xff]*)\[\/gray]/", '<font color=gray>\\1</font>', $pbody ); 
		
		//$pbody=str_replace("[gray]","",$pbody);
		//$pbody=str_replace("[/gray]","",$pbody);
		$search = array ("'\[###PREV_DOC###'","'###DOC_PREV###\]'");
		$replace = array ("<font color=#999999>","</font>");
		$pbody=preg_replace ($search, $replace, $pbody);
		
		
		
		return $pbody;
	}
	
	function FitString($str,$num){
		$len=strlen($str);
		if($len >= $num) return $str;
		$nulls=$num-$len;
		$hn=(int)($nulls/2);
		$hn2=$hn;
		if(($hn*2)<$nulls) $hn2++;
		$str=str_repeat(".",$hn).$str.str_repeat(".",$hn2);
		return $str;	
	}
	
	function trimbody($str) {
		$body=explode("\n",$str);
		for($i=0;$i<count($body);$i++) {
			$body[$i]=trim($body[$i]);
		}
		return join("",$body);
	}

	function CutBodyTag($body) {
		
		return  preg_replace ("/(<body[^>]*?>)|(<\/body>)/i",'',$body);
	}
	
	
	function htmltotext($html_body){
		$search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
                 "'<[\/\!]*?[^<>]*?>'si",  // Strip out html tags
                 "'([\r\n])[\s]+'",  // Strip out white space
                 "'&(quot|#34);'i",  // Replace html entities
                 "'&(amp|#38);'i",
                 "'&(lt|#60);'i",
                 "'&(gt|#62);'i",
                 "'&(nbsp|#160);'i",
                 "'&(iexcl|#161);'i",
                 "'&(cent|#162);'i",
                 "'&(pound|#163);'i",
                 "'&(copy|#169);'i",
                 "'&#(\d+);'e");  // evaluate as php

				$replace = array ("",
				                  "",
				                  "\\1",
				                  "\"",
				                  "&",
				                  "<",
				                  ">",
				                  " ",
				                  chr(161),
				                  chr(162),
				                  chr(163),
				                  chr(169),
				                  "chr(\\1)");
				
				return  preg_replace ($search, $replace, $html_body);
	}
	
	function tripjavascript($html_body){
		$search = array ("'<script[^>]*?>.*?</script>'si"  // Strip out javascript
                 );  // evaluate as php

				$replace = array (""
				                  );
				
				return  preg_replace ($search, $replace, $html_body);
	}
	
	function specialhtmltotext($html_body){
		$search = array (
				 "'<script[^>]*?>.*?</script>'si",  // Strip out javascript
                 "'<[\/\!]*?[^<>]*?>'si",  // Strip out html tags
                 "'([\r\n])[\s]+'",  // Strip out white space
                 "'&(quot|#34);'i",  // Replace html entities
                 "'&(amp|#38);'i",
                 "'&(lt|#60);'i",
                 "'&(gt|#62);'i",
                 "'&(nbsp|#160);'i",
                 "'&(iexcl|#161);'i",
                 "'&(cent|#162);'i",
                 "'&(pound|#163);'i",
                 "'&(copy|#169);'i",
                 "'&#(\d+);'e");  // evaluate as php

				$replace = array ("",
				                  "",
				                  "\\1",
				                  "\"",
				                  "&",
				                  "<",
				                  ">",
				                  " ",
				                  chr(161),
				                  chr(162),
				                  chr(163),
				                  chr(169),
				                  "chr(\\1)");
				
				return  preg_replace ($search, $replace, $html_body);
	}
	
}



?>