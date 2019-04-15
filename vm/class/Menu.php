<?
function produceMenu($SVar, $LVar, $username = "")
{
	$Menus = array();

	//特殊開關
	$purchase = 0;
	if(isset($SVar["PURCHASE"])) $purchase = 1;
	if(isset($SVar["OPTIONS"])) $purchase = 1;
	$dnsserver = $SVar["DNS_SERVER"] ? "Services/CDns.php" : "Services/CDnsQuery.php";
	$is4WAN = ($SVar["WAN"] == 4) ? 1 : 0;
	$dns_lang = ($SVar["DNS_SERVER"] == 1) ? $LVar["DNS"] : $LVar["DNS_Proxy"];
	if($SVar["WEB_RECORDER_UI"] == 1 || $SVar["FTP_RECORDER_UI"] == 1 || $SVar["MAIL_RECORDER_UI"] == 1 || $SVar["CONNTRACK"] == 1) {
		$dataExportImport = 1;
	} else {
		$dataExportImport = 0;
	}
	$isVM = (is_file("/PDATA/L7FWMODEL/_VXC_DOM") || is_file("/PDATA/L7FWMODEL/_CD_DOM")) ? 1 : 0;

	$Menus[] = array(//系統設定
		0	=> $LVar["CONFIGURATION"],
		1	=> array(
			array($LVar["DATEANDTIME"], "Configuration/Date_Time.php", 1),
			array($LVar["ADMINISTRATOR"], "Configuration/Rootaccount_List.php", 1),
			array($LVar["BACKUPANDUPGRADE"], "Configuration/Backup_Update.php", 1),		
			array($LVar["PURCHASE"], "Configuration/Purchase.php", $purchase),
			array($LVar["LANGUAGE"], "Configuration/Language.php", 1),
			array($LVar["NOTIFY"], "Configuration/AdminNotify.php", 1),
			array($LVar["REPORTER"], "Configuration/Reporter_Basic.php", $SVar["REPORT"]),
			array($LVar["DATA_EXPORT_IMPORT"], "Configuration/Data_export.php", $dataExportImport),
			array($LVar["SignatureUpdate"], "Configuration/Update_Server.php" , ($SVar["BLACK_LIST_DB"] == 1 || $SVar["IDP"] == 1)),
			array("CMS", "Configuration/CMS_Setting.php", $SVar["CMS"]),
			array($LVar["Ap_Management"], "Configuration/Ap_Manage.php",  $SVar["AP"]),
			array($LVar["SSL_Certification"], "Configuration/SSL_Certificate_Set.php",  1),
			array($LVar["AUTHORIZATION_INFO"], "Configuration/Authorization_Info.php", $isVM)
	));
	$Menus[] = array(//網路介面及路由
		0	=> $LVar["NETWORKING"],
		1 => array(
			array($LVar["INTERFACES"], "Network/Lan_Inte_List.php", 1),
			array($LVar["INTERFACES"]." (IPv6)", "Network/Lan_Inte_List_IPv6.php?i=1", 1),
			array($LVar["ROUTING"], "Network/Route_List.php", 1),
			array("802.1Q", "Network/Vlan_8021Q_List.php", 1)			
	));
	$Menus[] = array(//管制條例
		0	=> $LVar["POLICY"],
		1 => array(
			array($LVar["LAN_TO"], "Rule/Outgoing_List.php", 1),
			array($LVar["DMZ_TO"], "Rule/DMZ_Outgoing_List.php", 1),
			array($LVar["WAN_TO"], "Rule/L_Incoming_List.php", 1)
	));
	$Menus[] = array(//管理目標
		0	=> $LVar["OBJECTS"],
		1 => array(
			array($LVar["ADDRESS"], "Object/Ipmac_List.php", 1),
			array($LVar["SERVICES"], "Object/Basic_Service.php", 1),
			array($LVar["SCHEDULE"], "Object/Schedule_List.php", 1),
			array($LVar["QOS"], "Object/Qos_List.php", 1),
			array($LVar["APPLICATION"], "Object/L7group_List.php", 1),
			array($LVar["URL"], "Object/Url_Set.php", 1),
			array($LVar["VIRTUAL_SERVER"], "Object/Ipmap_List.php", 1),
			array($LVar["FIREWALL_FUNCTION"], "Object/Firewall_Function.php", 1),
			array($LVar["AUTHENTICATION"], "Object/Auth_Setup_All.php", 1),
			array($LVar["E_BOARD"], "Object/E_Board_List.php", 1),
			array($LVar["LB_Group"], "Object/WAN_Group_List.php", $is4WAN)
	));
	$Menus[] = array(//網路服務
		0	=> $LVar["NETWORK_SERVICES"],
		1 => array(
			array($LVar["DHCP"], "Services/Dhcpd_List.php?func=lan", 1),
			array($LVar["DDNS"], "Services/ddns.php", 1),
			array($dns_lang, $dnsserver, 1),
			array($LVar["WPROXY"], "Services/web_proxy.php", $SVar["WEB_RECORDER"]),
			array($LVar["FPROXY"], "Services/ftp_proxy.php", $SVar["FTP_RECORDER"]),
			array($LVar["MSNSETUP"], "ContentRecorder/msn-proxy/conf.php", $SVar["MSN_RECORDER_UI"]),
			array($LVar["QQ"], "Services/QQ_Setup_All.php?func=tab", $SVar["QQ_RECORDER_UI"]),
			array($LVar["Skype"], "Services/SkypeIpmac_List.php", $SVar["SKYPE_RECORDER_UI"]),
			array($LVar["ANTI_VIRUS"], "mailrec/CVirus_Engine.php?func=clamupdate", $SVar["CLAMAV"]),
			array($LVar["HA"], "Services/Ha_config.php", 1),
			array("SNMP", "Services/SnmpSetup.php", 1),
			array($LVar["SYSLOGD"], "Services/SyslogdOutput.php", 1)
	));
	$Menus[] = array( //進階防護
		0 => $LVar["Advanced_Protection"],
		1 => array(
			array($LVar["ANOMALY"], "Services/AnomalySetup.php", $SVar["ANOMALY"]),
			array($LVar["Switch"], "Services/SwitchSetup.php", $SVar["SWITCH"]),
			array($LVar["Intranet_protect"], "Services/Switch_Arp_protect.php", $SVar["ARP_PROTECT"])
	));
	$Menus[] = array(//郵件管理
		0	=> $LVar["MS"],
		1 => array(
			array($LVar["PROXY"], "mailrec/CMailProxyService.php", $SVar["MAIL_RECORDER"]),
			array($LVar["ANTIVIRUS"], "mailrec/CVirus_Engine.php", $SVar["CLAMAV"]),
			array($LVar["ANTISPAM"], "mailrec/CSP_base.php", $SVar["SPAM"]),
			array($LVar["AUDIT_FILTER"], "mailrec/AuditFilter.php", $SVar["AUDIT"]),
			array($LVar["RECORDER_SEARCH"], "mailrec/Cont_Mail_report.php", $SVar["MAIL_RECORDER"]),
			array($LVar["SMTP_SEARCH"], "mailrec/CMrtg_report2.php?do=smtp", $SVar["MAIL_RECORDER"])
	));
	$Menus[] = array(//IDP
		0	=> $LVar["IDP"],
		1 => array(
			array($LVar["IDP_SETUP"], "Idp/Idp_Pre_Defined.php", $SVar["IDP"]),
			array($LVar["LOG_SEARCH"], "Idp/Idp_Pre_Log.php", $SVar["IDP"]),
			array($LVar["BotnetSetup"], "Idp/OperationMode.php", $SVar["IDP"]),
			array($LVar["BotnetLog"], "Idp/BotnetLog.php", $SVar["IDP"])
	));
	$Menus[] = array(//SSL VPN
		0	=> $LVar["SSL_VPN"],
		1 => array(
			array($LVar["SSL_VPN_SETU"], "Sslvpn/Ssl_Vpn_Setu.php", $SVar["SSL_VPN"]),
			array($LVar["SSL_VPN_LOG"], "Sslvpn/Ssl_Vpn_Log.php", $SVar["SSL_VPN"]),
			array($LVar["VPN_POLICY"], "Vpn/V2L_Outgoing_List.php", $SVar["SSL_VPN"])
	));
	$Menus[] = array(//內容記錄
		0	=> $LVar["RECORDER"],
		1 => array(
			array($LVar["WEBRECORDER"], "ContentRecorder/CWebRecorderList.php", $SVar["WEB_RECORDER_UI"]),
			array($LVar["WEBRECORDER_VIRUS"], "ContentRecorder/CWebRecVirus.php", ($SVar["WEB_RECORDER_UI"] == 0 && $SVar["HTTP_VIRUS"] == 1) ),
			array($LVar["FTPRECORDER"], "ContentRecorder/CFtpRecorderList.php", $SVar["FTP_RECORDER_UI"]),
			array($LVar["FTPRECORDER_VIRUS"], "ContentRecorder/CFtpRecVirus.php", ($SVar["FTP_RECORDER_UI"] == 0 && $SVar["FTP_VIRUS"] == 1) ),
			array($LVar["MSNRECORDER"], "ContentRecorder/Msn_Rec_List.php", $SVar["MSN_RECORDER_UI"]),
			array($LVar["IMRECORDER"], "ContentRecorder/Im_List.php", $SVar["IM_RECORDER_UI"]),
			array($LVar["QQRECORDER"], "ContentRecorder/QQ_Rec_List.php", $SVar["QQ_RECORDER_UI"]),
			array($LVar["SkypeRECORDER"], "ContentRecorder/Skype_Rec_List.php", $SVar["SKYPE_RECORDER_UI"]),
			array($LVar["MSRECORDER"], "mailrec/Cont_Mail_report.php", $SVar["MAIL_FILE_BACKUP"])
	));
	$Menus[] = array(//VPN
		0	=> $LVar["VPN"],
		1 => array(
			array($LVar["IPSEC_TUNNEL"], "Vpn/OpenSwanList.php", 1),
			array($LVar["PPTPSERVER"], "Vpn/Pptpserveraccount_List.php", 1),
			array($LVar["PPTPCLIENT"], "Vpn/Pptpclient_List.php", 1),
			array($LVar["VPN_POLICY"], "Vpn/V2L_Outgoing_List.php", 1)
	));
	$Menus[] = array(//網路工具
		0	=> $LVar["TOOLS"],
		1 => array(
			array($LVar["CONNECT_TEST"], "Tools/ping_ip.php", 1),
			array("WatchLAN VPN", "Tools/watchlan_vpn.php", $SVar["WATCHLAN_VPN"]),
			array($LVar["TCPDUMP"], "Tools/Tcpdump_Schedule_List.php", $SVar["HDD"])
	));
	$Menus[] = array(//日誌
		0	=> $LVar["LOGS"],
		1 => array(
			array($LVar["LOGINEVENT"], "Logs/login_event.php", 1)
	));
	$Menus[] = array(//系統狀態
		0	=> $LVar["STATUS"],
		1 => array(
			array($LVar["PERFORMANCE"], "Status/SystemStatus.php", 1),
			array($LVar["Connection_Track"], "Logs/Lanipmac_List.php", 1),
			array($LVar["USER_FLOW"], "Status/Top10List.php", $SVar["CONNTRACK"])
	));
	
	$reomveChk = array();
	$myProfile = "/PCONF/rootaccount/profile_{$username}";
	if($username != "" && file_exists($myProfile))
	{//載入個人化選單設定		
		$reomveChk = unserialize(file_get_contents($myProfile));
	}
	
	//依權項及機器型號功能自訂選單
	foreach($Menus as $i => $subject)
	{
		foreach($subject[1] as $j => $item)
		{
			if($item[2] == 0 || in_array($item[1], $reomveChk))
			{//Remove 細項
				unset($Menus[$i][1][$j]);
			}
		}
		if(count($Menus[$i][1]) == 0)
		{//Remove 大項
			unset($Menus[$i]);		
		}
	}

	return $Menus;
}	
?>