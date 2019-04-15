<?
Class funcTabControl_class{
	var $openNum;
	var $LVar;
	var $SVar;
	function init($type, $openNum, $LVar, $SVar=""){
		$this->openNum = $openNum;
		$this->LVar = $LVar;
		if($SVar) $this->SVar = $SVar;
		switch($type){
			case "INTERFACES":
				return $this->getInterFaces();
				break;
			case "INTERFACES_v6":
				return $this->getInterFaces_v6();
				break;
			case "LAN_TO":
				return $this->getLanTo();
				break;
			case "DMZ_TO":
				return $this->getDmzTo();
				break;
			case "WAN_TO":
				return $this->getWanTo();
				break;
			case "MS_PROXY":
				return $this->getMsProxy();	
				break;
			case "AUDIT_FILTER":
				return $this->getAuditFilter();	
				break;
			case "WEBRECORDER":
				return $this->getWebRecorder();	
				break;
			case "FTPRECORDER":
				return $this->getFtpRecorder();	
				break;
		}
	}
	
	function getInterFaces(){
		$tabber = array();

		$tabber[] = array("/Program/Network/Lan_Inte_List.php" , $this->LVar["LAN_Setup"], ($this->openNum == "L1"));				
		$tabber[] = array("/Program/Network/Wan_Interface.php" , $this->LVar["WAN1_Setup"], ($this->openNum == "W1"));
		$tabber[] = array("/Program/Network/Wan2_Interface.php" , $this->LVar["WAN2_Setup"], ($this->openNum == "W2"));

		if($this->SVar['WAN'] == 4) {
			$tabber[] = array("/Program/Network/Wan3_Interface.php" , $this->LVar["WAN3_Setup"], ($this->openNum == "W3"));
			$tabber[] = array("/Program/Network/Wan4_Interface.php" , $this->LVar["WAN4_Setup"], ($this->openNum == "W4"));
		}

		$tabber[] = array("/Program/Network/Dmz_Inte_List.php" , $this->LVar["DMZ"], ($this->openNum == "L2"));
		
		if($this->SVar['LANs'] == 2) {
			$tabber[] = array("/Program/Network/Lan_Inte_List_1.php" , $this->LVar["LAN1_Setup"], ($this->openNum == "L1A"));				
			$tabber[] = array("/Program/Network/Lan_Inte_List_2.php" , $this->LVar["LAN2_Setup"], ($this->openNum == "L1B"));				
		}

		return $tabber;
	}
	
	function getInterFaces_v6(){
		$tabber = array();

		$tabber[] = array("/Program/Network/Lan_Inte_List_IPv6.php?i=1" , $this->LVar["LAN_Setup"], ($this->openNum == "L1v6"));
		$tabber[] = array("/Program/Network/Wan_Interface_IPv6.php?i=1" , $this->LVar["WAN1_Setup"], ($this->openNum == "W1v6"));
		$tabber[] = array("/Program/Network/Wan_Interface_IPv6.php?i=2" , $this->LVar["WAN2_Setup"], ($this->openNum == "W2v6"));

		if($this->SVar['WAN'] == 4) {
			$tabber[] = array("/Program/Network/Wan_Interface_IPv6.php?i=3" , $this->LVar["WAN3_Setup"], ($this->openNum == "W3v6"));
			$tabber[] = array("/Program/Network/Wan_Interface_IPv6.php?i=4" , $this->LVar["WAN4_Setup"], ($this->openNum == "W4v6"));
		}

		$tabber[] = array("/Program/Network/Lan_Inte_List_IPv6.php?i=2" , $this->LVar["DMZ"], ($this->openNum == "L2v6"));
		$tabber[] = array("/Program/Network/Dns_IPv6.php" , $this->LVar["DNS"], ($this->openNum == "Dv6"));

		return $tabber;
	}
	
	function getLanTo(){
		global $wic;
		for($i=1;$i<=5;$i++){
			${"item".$i} = 0;
		}	
		${"item".$this->openNum} = 1;	

		$tab_list = array();

		//LAN to WAN(NAT,ByPass)
		if($wic->get_bri_g1()){
			$tab_list[] = array("/Program/Rule/B1_Outgoing_List.php" , $this->LVar["LAN_TO_WAN"], $item1);
		} else {
			$tab_list[] = array("/Program/Rule/Outgoing_List.php" , $this->LVar["LAN_TO_WAN"], $item1);
		}
		//LAN to DMZ(NAT,BRI,ByPass)
		if($wic->get_bri()) {
			$tab_list[] = array("/Program/Rule/L2B_Outgoing_List.php" , $this->LVar["LAN_TO_BRI"], $item2);			
		} else if($wic->get_bri_g1() || $wic->get_bri_g2()) {
			//NONE
		} else {
			$tab_list[] = array("/Program/Rule/L2D_Outgoing_List.php" , $this->LVar["LAN_TO_DMZ"], $item2);		
		}
		//LAN to LAN(NAT,ByPass)
		if($wic->get_bri_g1()){
			//NONE
		} else {
			$tab_list[] = array("/Program/Rule/L2L_Outgoing_List.php" , $this->LVar["LAN_TO_LAN"], $item5);
		}
		//LAN to WAN(ipv6)
		if($wic->get_bri_g1()){
			//NONE
		} else {
			$tab_list[] = array("/Program/Rule/Outgoing_List_v6.php" , $this->LVar["LAN_TO_WAN_v6"], $item3);
		}
		
		return $tab_list;		
	}
	
	function getDmzTo(){
		global $wic;
		for($i=1;$i<=5;$i++){
			${"item".$i} = 0;
		}	
		${"item".$this->openNum} = 1;	

		$tab_list = array();

		//DMZ to WAN(NAT,BRI,ByPass)
		if($wic->get_bri()) {
			$tab_list[] = array("/Program/Rule/B_Outgoing_List.php", $this->LVar["Bridge_DMZ_TO_WAN1"], $item1);		
		} else if($wic->get_bri_g2()){
			$tab_list[] = array("/Program/Rule/B2_Outgoing_List.php", $this->LVar["Bridge_DMZ_TO_WAN1"], $item1);
		} else {
			$tab_list[] = array("/Program/Rule/DMZ_Outgoing_List.php" , $this->LVar["DMZ_TO_WAN"], $item1);
		}
		//DMZ to LAN(NAT,BRI,ByPass)
		if($wic->get_bri() || $wic->get_bri_g1() || $wic->get_bri_g2()) {
			//NONE			
		} else {
			$tab_list[] = array("/Program/Rule/D2L_Outgoing_List.php" , $this->LVar["DMZ_TO_LAN"], $item2);		
		}
		//DMZ to DMZ(NAT,BRI,ByPass)
		if($wic->get_bri() || $wic->get_bri_g2()) {
			//NONE
		} else {
			$tab_list[] = array("/Program/Rule/D2D_Outgoing_List.php" , $this->LVar["DMZ_TO_DMZ"], $item5);
		}
		//DMZ to WAN(ipv6)
		if($wic->get_bri()){
			$tab_list[] = array("/Program/Rule/B_Outgoing_List_v6.php", $this->LVar["Bridge_DMZ_TO_WAN1_v6"], $item3);
		} else if($wic->get_bri_g2()) {
			//NONE
		} else {
			$tab_list[] = array("/Program/Rule/DMZ_Outgoing_List_v6.php", $this->LVar["DMZ_TO_WAN_v6"], $item3);
		}
		
		return $tab_list;		
	}
	
	function getWanTo(){
		global $wic;
		for($i=1;$i<=5;$i++){
			${"item".$i} = 0;
		}	
		${"item".$this->openNum} = 1;	

		$tab_list = array();

		//WAN to LAN(NAT,ByPass)
		if($wic->get_bri_g1()) {
			$tab_list[] = array("/Program/Rule/B1_Incoming_List.php", $this->LVar["WAN_TO_LAN"], $item1);			
		} else {
			$tab_list[] = array("/Program/Rule/L_Incoming_List.php", $this->LVar["WAN_TO_LAN"], $item1);			
		}
		//WAN to DMZ(NAT,ByPass,BRI)
		if($wic->get_bri()) {
			$tab_list[] = array("/Program/Rule/B_Incoming_List.php", $this->LVar["Bridge_WAN1_TO_DMZ"], $item2);
		} else if($wic->get_bri_g2()) {
			$tab_list[] = array("/Program/Rule/B2_Incoming_List.php", $this->LVar["Bridge_WAN1_TO_DMZ"], $item2);		
		} else {
			$tab_list[] = array("/Program/Rule/D_Incoming_List.php", $this->LVar["WAN_TO_DMZ"], $item2);			
		}
		//Incoming of routing
		if($wic->get_ROUTE_MODE_START() || $wic->get_bri_routing()) {
			$tab_list[] = array("/Program/Rule/Incoming_List.php", $this->LVar["Incoming"], $item3);
		}
		//Incoming (IPV6) 
		$tab_list[] = array("/Program/Rule/Incoming_List_v6.php", $this->LVar["Incoming_v6"], $item4);
		//Incoming (BRI IPV6) 		
		if($wic->get_bri()) {
			$tab_list[] = array("/Program/Rule/B_Incoming_List_v6.php", $this->LVar["Bridge_WAN1_TO_DMZ_v6"], $item5);
		}
		
		return $tab_list;		
	}

	function getMsProxy(){
		for($i=1;$i<=5;$i++){
			${"item".$i} = 0;
		}
		${"item".$this->openNum} = 1;			
		$tab_list = array();
		$tab_list[] = array("/Program/mailrec/CMailProxyService.php" , $this->LVar["Mail_Proxy_Service"], $item1);
		if($this->SVar["SPAM"] == 1) {
			$tab_list[] = array("/Program/mailrec/ValidAccountConf.php" , $this->LVar["Valid_Account_Conf"], $item2);
			$tab_list[] = array("/Program/mailrec/GrayListConf.php" , $this->LVar["Gray_IPResolved_Conf"], $item3);
			$tab_list[] = array("/Program/mailrec/FlowBlockConf.php" , $this->LVar["Flow_Block_Conf"], $item4);
		}		

		return $tab_list;
	}
	
	function getAuditFilter(){
		for($i=1;$i<=3;$i++){
			${"item".$i} = 0;
		}	
		${"item".$this->openNum} = 1;	
		if($this->SVar["ONLY_SPAM_SUBJECT"] != 1) {
			return array(
					array("/Program/mailrec/AuditFilter.php" , $this->LVar["Audit_Filter"], $item1),
					array("/Program/mailrec/AuditSetup.php" , $this->LVar["Audit_Setup"], $item2),
					array("/Program/mailrec/AuditFilter.php?do=spelog" , $this->LVar["Audit_Filter_Log"], $item3)
			);	
		}else{
			return array(
					array("/Program/mailrec/AuditFilter.php" , $this->LVar["Audit_Filter"], $item1),
					array("/Program/mailrec/AuditSetup.php" , $this->LVar["Audit_Setup"], $item2)
			);	
		}
	}
	
	function getWebRecorder(){
		for($i=1;$i<=7;$i++){
			${"item".$i} = 0;
		}	
		${"item".$this->openNum} = 1;
		return array(
				array("/Program/ContentRecorder/CWebRecorderList.php" , $this->LVar["Web_Recorder"], $item1),
				array("/Program/ContentRecorder/CWebRecorderSearch.php" , $this->LVar["Web_Recorder_Search"], $item2),
				
				array("/Program/ContentRecorder/CWebCacheList.php" , $this->LVar["Web_Cache_List"], $item4),
				array("/Program/ContentRecorder/CWebCacheSearch.php" , $this->LVar["Web_Cache_Search"], $item5),
				
				array("/Program/ContentRecorder/CWebRecVirus.php" , $this->LVar["Web_Recorder_Virus"], $item7)
		);	
	}
	
	function getFtpRecorder(){
		for($i=1;$i<=4;$i++){
			${"item".$i} = 0;
		}	
		${"item".$this->openNum} = 1;
		return array(
				array("/Program/ContentRecorder/CFtpRecorderList.php" , $this->LVar["Ftp_Recorder"], $item1),
				array("/Program/ContentRecorder/CFtpRecorderSearch.php" , $this->LVar["Ftp_Recorder_Search"], $item2),
				
				array("/Program/ContentRecorder/CFtpRecVirus.php" , $this->LVar["Ftp_Recorder_Virus"], $item4)
		);	
	}

}
?>