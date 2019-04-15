<?
	class Export_csv{
		var $maxRowLimit = 5000;
		/**
			* $title as txt field name
			* $fields as sql's field name
			* $allitems as sql's all value
			* $filename as csv's file name
			*
			* echo csv's function
			*/
		function record($fields,$allitems,$filename,$title = array()){
			if($this->maxRowLimit < count($allitems)){
				echo "<script>alert(\"Amount in more than : ".$this->maxRowLimit." amount\");history.go(-1);</script>";
			}
			else{
				if($allitems){
					foreach($allitems as $id => $value){
						$keys = array_keys($value);
						foreach($keys as $keys_value){
							if(strrpos($value[$keys_value],",")){
								$allitems[$id][$keys_value] = $this->convert_string("\"".$value[$keys_value]."\"");
							}
							else{
								$allitems[$id][$keys_value] = $this->convert_string($value[$keys_value]);
							}
						}
					}
					
					/*
					 * catch filed'name then add "," to auto columns
					 */
					for($i=0;$i<count($allitems);$i++){
						foreach($fields as $id => $value){
							if($id=="0")
								$view .= $allitems[$i][$value];
							else
								$view .="," . $allitems[$i][$value];
						}
						$view .="\r\n";
					}
				}
				$today = "_".date("Y/m/d_h:i:s_A");
				$ext = ".csv";
				if(!empty($title)){
					$export = implode(",", $title)."\n".$view;
				}
				else{
					$export = $view;
				}
				$filename = str_replace(" ","",$filename); // because firefox's csv not eat " "
				header('Content-Type: text/csv; charset=UTF-8');
				header('Content-Type: application/octetstream');
				header('Content-Disposition: attachment; filename='.$filename.$today.$ext);
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				echo(chr(239).chr(187).chr(191));
				echo $export;exit;
			}
		}
		
		/*
		 * 1. because if string has " , " then csv auto columns so "," replace ""
		 * 2. if string has &#xxxxx; then turn of UTF-8
		 */
		function convert_string($string){
			if(strpos($string, "&#") !== false) {
				preg_match_all("/\&\#.{2,5};/",$string,$regex_value, PREG_PATTERN_ORDER);
				for($i=0;$i<count($regex_value[0]);$i++){
					$string = str_replace($regex_value[0][$i],mb_convert_encoding($regex_value[0][$i], 'UTF-8', 'HTML-ENTITIES'),$string);
				}
				return $string;
			}
			else{
				return $string;
			}
		}
	}
?>