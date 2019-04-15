<?
Class sort_class{
	function sort_select($key , $allgroup , $order) {
		usort($allgroup, "cmp".$key.$order);
		/*
		if($key == "name"&& $order == "DESC") usort($allgroup, "cmpnameDESC"); 
		else if($key == "name" && $order == "ASC") usort($allgroup, "cmpnameASC");
		else if($key == "NAME"&& $order == "DESC") usort($allgroup, "cmpname1DESC");
		else if($key == "NAME" && $order == "ASC") usort($allgroup, "cmpname1ASC");
		else if($key == "alias"&& $order == "DESC") usort($allgroup, "cmpaliasDESC");
		else if($key == "alias" && $order == "ASC") usort($allgroup, "cmpaliasASC"); 
		else if($key == "ip"&& $order == "DESC") usort($allgroup, "cmpipDESC");
		else if($key == "ip" && $order == "ASC") usort($allgroup, "cmpipASC");
		else if($key == "mac"&& $order == "DESC") usort($allgroup, "cmpmacDESC");
		else if($key == "mac" && $order == "ASC") usort($allgroup, "cmpmacASC");
		else if($key == "interface"&& $order == "DESC") usort($allgroup, "cmpinterfaceDESC");
		else if($key == "interface" && $order == "ASC") usort($allgroup, "cmpinterfaceASC");
		else if($key == "status"&& $order == "DESC") usort($allgroup, "cmpstatusDESC");
		else if($key == "status" && $order == "ASC") usort($allgroup, "cmpstatusASC");
		else if($key == "clientname"&& $order == "DESC") usort($allgroup, "cmpclientnameDESC");
		else if($key == "clientname" && $order == "ASC") usort($allgroup, "cmpclientnameASC");
		else if($key == "clientaddress"&& $order == "DESC") usort($allgroup, "cmpclientaddressDESC");
		else if($key == "clientaddress" && $order == "ASC") usort($allgroup, "cmpclientaddressASC");
		else if($key == "userstarttime"&& $order == "DESC") usort($allgroup, "cmpuserstarttimeDESC");
		else if($key == "userstarttime" && $order == "ASC") usort($allgroup, "cmpuserstarttimeASC"); 
		else if($key == "userspeaktime"&& $order == "DESC") usort($allgroup, "cmpuserspeaktimeDESC");
		else if($key == "userspeaktime" && $order == "ASC") usort($allgroup, "cmpuserspeaktimeASC");     
		*/
		return $allgroup; 
	}
}

function cmpupdatetimeDESC($a, $b) {
	return strnatcmp($b["updatetime"],$a["updatetime"]);
}

function cmpupdatetimeASC ($a, $b) {
	return strnatcmp($a["updatetime"] , $b["updatetime"]);
}

function cmpnameDESC ($a, $b) {
	return strnatcmp($b["name"],$a["name"]);
}
	
function cmpnameASC ($a, $b) {
	return strnatcmp($a["name"] , $b["name"]);
}

function cmpname1DESC ($a, $b) {
	return strnatcmp($b["NAME"],$a["NAME"]);
}
	
function cmpname1ASC ($a, $b) {
	return strnatcmp($a["NAME"] , $b["NAME"]);
}

function cmpaliasASC ($a, $b) {
	return strnatcmp($a["alias"], $b["alias"]);
}

function cmpaliasDESC ($a, $b) {
	return strnatcmp($b["alias"],$a["alias"]);
}
	
function cmpipDESC ($a, $b) {
	return strnatcmp($b["ip"],$a["ip"]);
}

function cmpipASC ($a, $b) {
	return strnatcmp($a["ip"], $b["ip"]);
}

function cmpip_addrDESC ($a, $b) {
	return strnatcmp($b["ip_addr"],$a["ip_addr"]);
}

function cmpip_addrASC ($a, $b) {
	return strnatcmp($a["ip_addr"], $b["ip_addr"]);
}
	
function cmpmacDESC ($a, $b) {
	return strnatcmp($b["mac"],$a["mac"]);
}

function cmpmacASC ($a, $b) {
	return strnatcmp($a["mac"], $b["mac"]);
}

function cmpinterfaceDESC ($a, $b) {
	return strnatcmp($b["interface"],$a["interface"]);
}

function cmpinterfaceASC ($a, $b) {
	return strnatcmp($a["interface"], $b["interface"]);
}
	
function cmpstatusDESC ($a, $b) {
	return strnatcmp($b["status"],$a["status"]);
}

function cmpstatusASC ($a, $b) {
	return strnatcmp($a["status"], $b["status"]);
}

function cmphostnameDESC ($a, $b) {
	return strnatcmp($b["hostname"],$a["hostname"]);
}

function cmphostnameASC ($a, $b) {
	return strnatcmp($a["hostname"], $b["hostname"]);
}

function cmpclientnameDESC ($a, $b) {
	return strnatcmp($b["clientname"],$a["clientname"]);
}

function cmpclientnameASC ($a, $b) {
	return strnatcmp($a["clientname"], $b["clientname"]);
}

function cmpclientaddressDESC ($a, $b) {
	return strnatcmp($b["clientaddress"] , $a["clientaddress"]);
}

function cmpclientaddressASC ($a, $b) {
	return strnatcmp($a["clientaddress"], $b["clientaddress"]);
}

function cmpuserstarttimeDESC ($a, $b) {
	return strnatcmp($b["userstarttime"] , $a["userstarttime"]);
}

function cmpuserstarttimeASC ($a, $b) {
	return strnatcmp($a["userstarttime"], $b["userstarttime"]);
}

function cmpuserendtimeDESC ($a, $b) {
	return strnatcmp($b["userendtime"] , $a["userendtime"]);
}

function cmpuserendtimeASC ($a, $b) {
	return strnatcmp($a["userendtime"], $b["userendtime"]);
}

function cmpuserspeaktimeDESC ($a, $b) {
	return strnatcmp($b["userspeaktime"] , $a["userspeaktime"]);
}

function cmpuserspeaktimeASC ($a, $b) {
	return strnatcmp($a["userspeaktime"], $b["userspeaktime"]);
}

function cmpidDESC ($a, $b) {
	return strnatcmp($b["id"] , $a["id"]);
}

function cmpidASC ($a, $b) {
	return strnatcmp($a["id"], $b["id"]);
}

function cmpdateDESC ($a, $b) {
	return strnatcmp($b["date"] , $a["date"]);
}

function cmpdateASC ($a, $b) {
	return strnatcmp($a["date"], $b["date"]);
}

function cmpwith_idDESC ($a, $b) {
	return strnatcmp($b["with_id"] , $a["with_id"]);
}

function cmpwith_idASC ($a, $b) {
	return strnatcmp($a["with_id"], $b["with_id"]);
}

function cmpfrom_idDESC ($a, $b) {
	return strnatcmp($b["from_id"] , $a["from_id"]);
}

function cmpfrom_idASC ($a, $b) {
	return strnatcmp($a["from_id"], $b["from_id"]);
}

function cmpto_idDESC ($a, $b) {
	return strnatcmp($b["to_id"] , $a["to_id"]);
}

function cmpto_idASC ($a, $b) {
	return strnatcmp($a["to_id"], $b["to_id"]);
}

function cmptsDESC ($a, $b) {
	return strnatcmp($b["ts"] , $a["ts"]);
}

function cmptsASC ($a, $b) {
	return strnatcmp($a["ts"], $b["ts"]);
}

function cmpaccountDESC ($a, $b) {
 return strnatcmp($b["account"] , $a["account"]);
}

function cmpaccountASC ($a, $b) {
 return strnatcmp($a["account"], $b["account"]);
}

function cmpmaskDESC ($a, $b) {
 return strnatcmp($b["mask"] , $a["mask"]);
}

function cmpmaskASC ($a, $b) {
 return strnatcmp($a["mask"], $b["mask"]);
}
?>
