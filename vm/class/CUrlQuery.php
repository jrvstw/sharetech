<?
// ----------------------------------------------------------------------------
// ©ú½X¦î¦C³B²z
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
class CUrlQuery{
        var $allvars=array();
// ----------------------------------------------------------------------------
        function CUrlQuery(){           
                //$this->getvars() ;
        }
// ----------------------------------------------------------------------------
        function getvars() {
                global $QUERY_STRING;
                reset($this->allvars);
                if(strlen($QUERY_STRING)==0) return;
                $pieces = split ("&", $QUERY_STRING);
                $i=0;
                while ($i < count($pieces)) {
                        $b = split ('=', $pieces[$i]);
                        $var = $b [0];
                        $val = $b [1];
                        $this->allvars[$var]=$val;
                        $i++;
                }
        }
// ----------------------------------------------------------------------------
        function setvars($v,$c) {
                $this->allvars[$v]=$c;  
               
        }
// ----------------------------------------------------------------------------
        function geturlstr() {
        	//$this->getvars();
                $url="";
                reset ($this->allvars);
                for($i=1;$i<=count($this->allvars);$i++) {
                     list ($key, $val) = each ($this->allvars); 
                     if ($val!=""){
                     	 $temp[$key]=$val;
                     	
                     	}
                }  
              		
                for($i=1;$i<=count($temp);$i++) {
                        list ($key, $val) = each ($temp);                        
                        if ($val!="") {
                        	
                           $url.="$key=$val";
                           if($i!=count($temp)) $url.="&";
                          
                        }
                }
                
                return $url;
        }
        
        function changurl($v,$c,$reset=array()){
        	$this->getvars();
        	$this->setvars($v,$c);
        	for($i=0;$i<count($reset);$i++) {
        		$this->setvars($reset[$i],"");	
        	}
        	return $this->geturlstr();
        }
        
        
        function regeturl($rev=array()){
        	$this->getvars();
        	$str="";
        	
        	for($i=1;$i<=count($this->allvars);$i++) {
                 list ($key, $val) = each ($this->allvars); 
                 //if ($val)  $temp[$key]=$val;
                 if(in_array($key,$rev)) {
                 	if($str) $str.="&";	
                 	//else $str.="?";
                 	$str.=$key."=".$val;
                 	
                 }
             }  
        	return $str;
        }
        
// ----------------------------------------------------------------------------
}
// ----------------------------------------------------------------------------
// ----------------------------------------------------------------------------
?>