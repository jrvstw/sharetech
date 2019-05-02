<?php

	exec("arp -n", $arpResult);
	$list = show_arp($arpResult);

	exec("ifconfig eth0", $eth0Result);
	$eth0Range = ip_range($eth0Result);

	exec("ifconfig eth3", $eth3Result);
	$eth3Range = ip_range($eth3Result);

	function show_arp($result) { //ip,mac,iface
		for ($i = 0; $i < count($result); $i++) { 
			$aList[$i] = preg_split("/[\s,]+/", $result[$i], -1, PREG_SPLIT_NO_EMPTY);
			if ($aList[$i][1] == "(incomplete)"){
				// pass
			} else if ($aList[$i][4] == "eth0" || $aList[$i][4] == "eth3") {
				$sList = $sList."<tr><td class='ip'>".$aList[$i][0]."</td><td>".$aList[$i][2]."</td><td>".$aList[$i][4]."</td>";
				$sList = $sList."<td><button class='delButton' onclick=\"del('".trim($aList[$i][0])."')\" >Delete</button></td></tr>";
			} else if ($i != 0) {
				$sList = $sList."<tr><td class='ip'>".$aList[$i][0]."</td><td>".$aList[$i][2]."</td><td>".$aList[$i][4]."</td>";
				$sList = $sList."<td> Permission denied </td></tr>";
			}
		}
		return $sList;
	}

	function ip_range($interFace) {
		$inet = preg_split("/[\s,]+/", $interFace[1], -1, PREG_SPLIT_NO_EMPTY);
		$addr = trim(str_replace("addr:", "", $inet[1]));
		$bcast = trim(str_replace("Bcast:", "", $inet[2]));
		$mask = trim(str_replace("Mask:", "", $inet[3]));
		$addr = preg_split("/[.]+/", $addr, -1, PREG_SPLIT_NO_EMPTY);
 		$bcast = preg_split("/[.]+/", $bcast, -1, PREG_SPLIT_NO_EMPTY);
 		$mask = preg_split("/[.]+/", $mask, -1, PREG_SPLIT_NO_EMPTY);
 		for ($i = 0; $i < count($addr); $i++) {
 			$addr[$i] = bindec(decbin($addr[$i]) & decbin($mask[$i]));
 		}
		return array($addr, $bcast, $mask);
	}

	include("xhtml/showarp.html");
