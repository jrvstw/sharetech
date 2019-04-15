<?
/*
$abc = new Connmark_Policy_Finger();
echo $abc->query("1", "111") . "\n";
echo $abc->query("1", "453") . "\n";
echo $abc->query("1", "659") . "\n";
*/

class Connmark_Policy_Finger {
	var $routing_connmark = array();
	var $typeruleing_name = array();
	var $cache01 = array();
	var $cache02 = array();
	var $cache03 = array();
	var $cache04 = array();
		
	function Connmark_Policy_Finger() {
		$this->routing_connmark[1] = array("outgoing.pre", "DMZ_outgoing.pre", "i2o_bridging.pre", "L2B_outgoing.pre", "L2L_outgoing.pre", "D2D_outgoing.pre");
		$this->routing_connmark[2] = array("incoming.pre", "incoming_routing.pre", "o2i_bridging.pre");
		$this->routing_connmark[3] = array("L2D_outgoing.pre");
		$this->routing_connmark[4] = array("D2L_outgoing.pre");
		$this->routing_connmark[5] = array("L2V_outgoing.pre");
		$this->routing_connmark[6] = array("incoming.pre", "incoming_routing.pre");
		$this->routing_connmark[7] = array("V2L_outgoing.pre", "outgoing.pre");
	
		$this->typeruleing_name["outgoing.pre"] = "LAN to WAN";
		$this->typeruleing_name["DMZ_outgoing.pre"] = "DMZ to WAN";
		$this->typeruleing_name["i2o_bridging.pre"] = "DMZ to WAN";
		$this->typeruleing_name["L2B_outgoing.pre"] = "LAN to DMZ";
		$this->typeruleing_name["L2L_outgoing.pre"] = "LAN to LAN";
		$this->typeruleing_name["D2D_outgoing.pre"] = "DMZ to DMZ";
		$this->typeruleing_name["incoming.pre"] = "WAN to LAN,DMZ";
		$this->typeruleing_name["incoming_routing.pre"] = "incoming of routing";
		$this->typeruleing_name["o2i_bridging.pre"] = "WAN to DMZ";
		$this->typeruleing_name["L2D_outgoing.pre"] = "LAN to DMZ";
		$this->typeruleing_name["D2L_outgoing.pre"] = "DMZ to LAN";		
		$this->typeruleing_name["L2V_outgoing.pre"] = "Inside to VPN";
		$this->typeruleing_name["V2L_outgoing.pre"] = "VPN to Inside";
	}
	
	function query($x, $yyy) {
		if(!isset($this->routing_connmark[$x])) {
			return "";
		}	
		
		foreach($this->routing_connmark[$x] as $typeruleing) {
			$table = $this->get_table($typeruleing);
			foreach((array)$table as $val) {
				if($val["pid"] == $yyy) {
					if($typeruleing == "incoming.pre") {
						if($qq = $this->incoming_L($val["sn"])) {
							return sprintf("%s [%s] %s", "WAN to LAN", $qq["idx"], $this->get_policy_comment($qq["sn"]));												
						} else if($qq = $this->incoming_D($val["sn"])) {
							return sprintf("%s [%s] %s", "WAN to DMZ", $qq["idx"], $this->get_policy_comment($qq["sn"]));												
						}
					} else {
						//LAN to WAN [1] test222
						return sprintf("%s [%s] %s", $this->typeruleing_name[$typeruleing], $val["idx"], $this->get_policy_comment($val["sn"]));						
					}
				} 
			}
		}
		
		return "";	//default
	}
	
	function get_table($typeruleing) {
		if(!isset($this->cache01[$typeruleing])) {
			$tmp = array();
			exec("/PGRAM/ipt4/sbin/iptables -t mangle -S $typeruleing", $ret);			
			for($i = 0; $i < count($ret); $i++) {
				unset($match);
				preg_match('/\-comment "n(.+)" \-j/', $ret[$i], $match);
				$elt = explode("_", $match[1]);
				$tmp[] = array("idx" => $i, "sn"	=> $elt[0],	"pid" => $elt[38]);
			}			
			$this->cache01[$typeruleing] = $tmp;
		}
	
		return $this->cache01[$typeruleing];	
	}
	
	function get_policy_comment($sn) {
		if(count($this->cache02) == 0) {
			if(is_file("/PCONF/outgoingrule/outgoingrule")) {
				$txt = file("/PCONF/outgoingrule/outgoingrule");
				foreach((Array)$txt as $line) {
					$elt = explode(",", trim($line));
					$this->cache02[$elt[0]]	= $elt[1];
				}
			}
			if(is_file("/PCONF/incomingrule/incomingrule")) {
				$txt = file("/PCONF/incomingrule/incomingrule");
				foreach((Array)$txt as $line) {
					$elt = explode(",", trim($line));
					$this->cache02[$elt[0]]	= $elt[1];
				}
			}
			$this->cache02["zero"] = "zero";
		}

		return $this->cache02[$sn];
	}	

	function incoming_L($sn) {
		if(count($this->cache03) == 0) {
			$classifying = array();
			if(is_file("/PCONF/incomingrule/incoming_classifying")) {
				$txt = file("/PCONF/incomingrule/incoming_classifying");
				foreach((Array)$txt as $line) {
					$elt = explode(",", trim($line));
					$classifying[$elt[0]] = $elt[1];
				}
			}

			exec("/PGRAM/ipt4/sbin/iptables -t nat -S incoming_L", $ret);			
			for($i = 0; $i < count($ret); $i++) {
				unset($match);
				preg_match('/\-comment "n(.+)" \-j/', $ret[$i], $match);
				$elt = explode("_", $match[1]);
				$member = array();
				foreach((Array)$classifying as $k => $v) {
					if($v == $elt[0]) $member[] = $k;
				}
				$this->cache03[]	= array("idx" => $i, "sn"	=> $elt[0], "member" => $member);
			}
		}

		foreach((Array)$this->cache03 as $rule) {
			if(in_array($sn, $rule["member"])) {	
				return array("idx" => $rule["idx"], "sn" => $rule["sn"]);
			}
		}
		
		return false;
	}	

	function incoming_D($sn) {
		if(count($this->cache04) == 0) {
			$classifying = array();
			if(is_file("/PCONF/incomingrule/incoming_classifying")) {
				$txt = file("/PCONF/incomingrule/incoming_classifying");
				foreach((Array)$txt as $line) {
					$elt = explode(",", trim($line));
					$classifying[$elt[0]] = $elt[1];
				}
			}

			exec("/PGRAM/ipt4/sbin/iptables -t nat -S incoming_D", $ret);			
			for($i = 0; $i < count($ret); $i++) {
				unset($match);
				preg_match('/\-comment "n(.+)" \-j/', $ret[$i], $match);
				$elt = explode("_", $match[1]);
				$member = array();
				foreach((Array)$classifying as $k => $v) {
					if($v == $elt[0]) $member[] = $k;
				}
				$this->cache04[]	= array("idx" => $i, "sn"	=> $elt[0], "member" => $member);
			}
		}

		foreach((Array)$this->cache04 as $rule) {
			if(in_array($sn, $rule["member"])) {	
				return array("idx" => $rule["idx"], "sn" => $rule["sn"]);
			}
		}
		
		return false;
	}
}
?>