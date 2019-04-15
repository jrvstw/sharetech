<?

class CJavaScript {
	function CJavaScript() {
			
	}
	function RndSrc($src){
		$t=time();
		if(preg_match("/\?/",$src)) {
			$src=$src."&"."ranload=$t";
		} else $src=$src."?"."ranload=$t";	
		return $src;
	}
	
	function UpdateThisFrame($src,$cache=1){
		if(!$cache)	$src=$this->RndSrc($src);
		echo "\n<script language=javascript>\n";
		echo "window.location.href='$src';\n";
		echo "</script>\n"; 	
	}
	
	function alert($msg) {
		echo "<script language=javascript>";
		echo "Javascript:alert('$msg')";
		echo "</script>";
	}
	
	function Back() {
		echo "<script language=javascript>";
		echo "Javascript:history.go(-1)";
		echo "</script>";
	}
	
	function Close() {
		echo "<script language=javascript>";
		echo "window.close()\n";
		echo "</script>";
	}
	
}
?>