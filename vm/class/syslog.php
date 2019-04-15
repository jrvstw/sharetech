<?
include_once("/PDATA/apache/class/Comm.php");
include_once("/PDATA/apache/conf/fw.ini");
include_once("/PDATA/apache/Program/Logs/syslog_constants.php");
include_once("/PDATA/apache/conf/postfix_system.ini");
if(!class_exists("CDbshell")) {
	include_once("$sIncludeClassPath/CDbshell.php"); 
}
if(!is_object($db)) {
	$db = new CDbshell;
}

Class syslog {

  function syslog(){
  
  }
  
	function log($ID, $type, $subtype = -1, $thing = -1, $title_parameters = 0) {
    global $REMOTE_ADDR , $db;
   	if($db->db != "postfix"){
   		$db->db = "postfix";
   	}
    //$rec = $this->getvalu(trim($ID)); 
    //if ($rec) {
      if ($title_parameters) {
        $has_title = 1;
      } else {
        $has_title = 0;
      }
      //$who = sess_getVar("ulogin");  
      $who =$_SESSION["ulogin"];
      $date = date("Y:m:d H:i:s");
      $fields = array("date", "type", "subtype", "who", "ip", "thing", "has_title");
      $values = array($date, $type, $subtype, $who, $REMOTE_ADDR, $thing, $has_title);
      if ($who != "root_sharetech") {
      	$this->INSERT("syslogn", $fields, $values);
			
	      if ($title_parameters) {
	        $id = $db->insert_id();
	        for($i = 0; $i < count($title_parameters); $i++) {
	        	//$db->debug = 1;
	          $db->query("INSERT INTO `log_title_parameter` (`log_id`, `order`, `value`) VALUES ($id, $i, '$title_parameters[$i]')");
	       	}
	      }
	    }
    //} 
  }
  
  function getvalu($param,$default="") {
		$this->query("select * from `System` where param='$param'");
		if($this->num_rows()) {
			$row=$this->fetch_array();
			return $row['valu'];
		} else {
			//$this->SetValu($param,$default);
			return $default;
			
		}
  }
  
  function insert($table, $field, $value, $option = '') {
  	global $db;
    if (!is_array($field)) return 0;
    if (!is_array($value)) return 0; 
    count($field) == count($value) or die(count($field) . ":" . count($value));
    $sql = "INSERT " . $option . " INTO $table ( ";
    for($i = 1;$i <= count($field);$i++) {
      $sql .= $field[$i-1];
      if ($i != count($field)) $sql .= ",";
    } 
    $sql .= ") values(";

    for($i = 1;$i <= count($value);$i++) {
      $sql .= "'" . $value[$i-1] . "'";
      if ($i != count($value)) $sql .= ",";
    } 
    $sql .= ")"; 
     //echo $sql."<BR>\n";exit;
    $db->query($sql);
    // echo $sql."\n\n";
  }
  /*
  function insert_id () {
    $id = -1;
    if (!$this->rs) return 0;
    return mysql_insert_id();
  }
  */
  
  function get_content($type, $subtype, $thing) {
	  switch ($type) {
	    case 0:
	      switch ($subtype) {
	        case CONFIGURATION_DATEANDTIME:
	          switch ($thing) {
	            case DATEANDTIME_SETUP:
	              return DATEANDTIME_SETUP_CONTENT;
	              break;
	            case DATEANDTIME_SETUP2:
	              return DATEANDTIME_SETUP2_CONTENT;
	              break;
	          } 
	          break;
	        case CONFIGURATION_ADMINISTRATOR:
	        	switch ($thing) {
							case ADMINISTRATOR_ADD:
								return ADMINISTRATOR_ADD_CONTENT;
								break;
							case ADMINISTRATOR_EDIT:
								return ADMINISTRATOR_EDIT_CONTENT;
								break;
							case ADMINISTRATOR_DEL:
								return ADMINISTRATOR_DEL_CONTENT;
								break;
							case SYSTEM_SETUP:
								return SYSTEM_SETUP_CONTENT;
								break;
							case SYSTEM_SETUP_REBOOT:
								return SYSTEM_SETUP_REBOOT_CONTENT;
								break;
							case PERMITTED_IPS_ADD:
								return PERMITTED_IPS_ADD_CONTENT;
								break;
							case PERMITTED_IPS_EDIT:
								return PERMITTED_IPS_EDIT_CONTENT;
								break;
							case PERMITTED_IPS_DEL:
								return PERMITTED_IPS_DEL_CONTENT;
								break;
							case RECORDER_CLEAR:
								return RECORDER_CLEAR_CONTENT;
								break;
							case RECORDER_STORING:
								return RECORDER_STORING_CONTENT;
								break;
							case PERMITTED_IPS_CHANGE:
								return PERMITTED_IPS_CHANGE_CONTENT;
								break;
							case PERMITTED_IPS_SAVE:
								return PERMITTED_IPS_SAVE_CONTENT;
								break;
							case SYSTEM_SETUP_UNBLOCK:
								return SYSTEM_SETUP_UNBLOCK_CONTENT;
								break;
							case FSCK_HDD_SAVE:
								return FSCK_HDD_SAVE_CONTENT;
								break;
							case FSCK_HDD_NOW:
								return FSCK_HDD_NOW_CONTENT;
								break;
						}
	          break;
	        case CONFIGURATION_BACKUPANDUPGRADE:
	          switch ($thing) {
	            case SYSTEM_BACKUP_BACK:
	              return SYSTEM_BACKUP_BACK_CONTENT;
	              break;
	            case SYSTEM_BACKUP_REDUCE_UPGRADE:
	              return SYSTEM_BACKUP_REDUCE_CONTENT;
	              break;
	            case SOFTWARE_UPGRADE:
	              return SOFTWARE_UPGRADE_CONTENT;
	              break;
							case AUTO_BACKUP_DOWNLOAD:
								return AUTO_BACKUP_DOWNLOAD_CONTENT;
								break;
							case AUTO_BACKUP_RESTORE:
								return AUTO_BACKUP_RESTORE_CONTENT;
								break;
							case AUTO_BACKUP_DELETE:
								return AUTO_BACKUP_DELETE_CONTENT;
								break;
							case AUTO_BACKUP:
								return AUTO_BACKUP_CONTENT;
								break;
							case FIRMWARE_UPDATE:
								return FIRMWARE_UPDATE_CONTENT;
								break;
							case FIRMWARE_SETUP:
								return FIRMWARE_SETUP_CONTENT;
								break;
							case FIRMWARE_DOWNLOAD:
								return FIRMWARE_DOWNLOAD_CONTENT;
								break;
							case FIRMWARE_UPGRADE:
								return FIRMWARE_UPGRADE_CONTENT;
								break;
							case FIRMWARE_LOG_DOWNLOAD:
								return FIRMWARE_LOG_DOWNLOAD_CONTENT;
								break;
	          } 
	          break;
	        case CONFIGURATION_LANGUAGE:
	          switch ($thing) {
	            case LANGUAGE_SETUP:
	              return LANGUAGE_SETUP_CONTENT;
	              break;
	          } 
	          break;
	        case CONFIGURATION_SIGNATUREUPDATE:
	          switch ($thing) {
	            case SIGNATURE_UPDATE_SETUP:
	              return SIGNATURE_UPDATE_SETUP_CONTENT;
	              break;
	          } 
	          break;
	        case CONFIGURATION_REPORT:
	          switch ($thing) {
	            case REPORT_BASIC_SETUP:
	              return REPORT_BASIC_SETUP_CONTENT;
	              break;
	            case REPORT_RECIPIENT_ADD_DEF:
	              return REPORT_RECIPIENT_ADD_DEF_CONTENT;
	              break;
	            case REPORT_RECIPIENT_EDIT_DEF:
	              return REPORT_RECIPIENT_EDIT_CONTENT;
	              break;
	            case REPORT_RECIPIENT_DELETE:
	              return REPORT_RECIPIENT_DELETE_CONTENT;
	              break;
	            case REPORT_QUERY:
	              return REPORT_QUERY_CONTENT;
	              break;
	            case REPORT_BASIC_PREVIEW:
	            	return REPORT_BASIC_PREVIEW_CONTENT;
	              break;
 							case REPORT_RECIPIENT_ADD_USER:
	              return REPORT_RECIPIENT_ADD_DEF_CONTENT;
	              break;
	           case REPORT_RECIPIENT_EDIT_USER:
	              return REPORT_RECIPIENT_EDIT_CONTENT;
	              break;
	          } 
	          break;
	           case CONFIGURATION_CMS:
	          switch ($thing) {
	            case CMS_BASIC_SETUP_CLIENT:
	              return CMS_BASIC_SETUP_CLIENT_CONTENT;
	              break;
	            case CMS_BASIC_SETUP_SERVER:
	              return CMS_BASIC_SETUP_CLIENT_CONTENT;
	              break;
	            case CMS_MONITOR_ADD:
	            	return CMS_MONITOR_ADD_CONTENT;
	              break;
	            case CMS_MONITOR_EDIT:
	            	return CMS_MONITOR_EDIT_CONTENT;
	              break;
	            case CMS_MONITOR_DELETE:
	            	return CMS_MONITOR_DELETE_CONTENT;
	              break;
	          } 
	          break;
	           case CONFIGURATION_AP:
	          switch ($thing) {
	            case AP_SETTING_SAVE:
	              return AP_SETTING_SAVE_CONTENT;
	              break;
	            case AP_MONITOR_ADD:
	              return AP_MONITOR_ADD_CONTENT;
	              break;
	            case AP_MONITOR_EDIT:
	            	return AP_MONITOR_EDIT_CONTENT;
	              break;
	            case AP_MONITOR_DELETE:
	            	return AP_MONITOR_DELETE_CONTENT;
	              break;
							case AP_MONITOR_RQT_ADD:
	            	return AP_MONITOR_RQT_ADD_CONTENT;
	              break;
							case AP_MONITOR_RQT_DELETE:
	            	return AP_MONITOR_RQT_DELETE_CONTENT;
	              break;
	            case AP_MONITOR_DELIVERY:
	            	return AP_MONITOR_DELIVERY_CONTENT;
	              break;
	           case AP_MONITOR_LIST_DELIVERY:
	            	return AP_MONITOR_LIST_DELIVERY_CONTENT;
	              break;
	          }
	          break;
	      } 
	      break;
	    case 1:
	      switch ($subtype) {
	        case NETWORKING_INTERFACES:
	          switch ($thing) {
	            case LAN_SETUP:
	              return LAN_SETUP_CONTENT;
	              break;
	            case WAN1_SETUP:
	              return WAN1_SETUP_CONTENT;
	              break;
	            case WAN2_SETUP:
	              return WAN2_SETUP_CONTENT;
	              break;
	            case DMZ_SETUP:
	              return DMZ_SETUP_CONTENT;
	              break;
	            case WAN3_SETUP:
	              return  WAN3_SETUP_CONTENT;
	              break;
	            case WAN4_SETUP:
	              return  WAN4_SETUP_CONTENT;
	              break;
	            case LAN_SETUP_v6:
	             return LAN_SETUP_v6_CONTENT;
	              break;
	            case WAN1_SETUP_v6:
	              return WAN1_SETUP_v6_CONTENT;
	              break;
	          } 
	          break;
	        case NETWORKING_ROUTING:
	          switch ($thing) {
	            case ROUTING_TABLE_ADD:
	              return ROUTING_TABLE_ADD_CONTENT;
	              break;
	            case ROUTING_TABLE_EDIT:
	              return ROUTING_TABLE_EDIT_CONTENT;
	              break;
	            case ROUTING_TABLE_DEL:
	              return ROUTING_TABLE_DEL_CONTENT;
	              break;
	            case ROUTING_TABLE_v6_ADD:
	              $title = ROUTING_TABLE_v6_ADD_CONTENT;
	              break;
	            case ROUTING_TABLE_v6_EDIT:
	              $title = ROUTING_TABLE_v6_EDIT_CONTENT;
	              break;
	            case ROUTING_TABLE_v6_DEL:
	              $title = ROUTING_TABLE_v6_DEL_CONTENT;
	              break;
	          } 
	          break;
	          case NETWORKING_8021Q:
	          switch ($thing) {
	            case Vlan_8021Q_ADD:
	              return Vlan_8021Q_ADD_CONTENT;
	              break;
	            case Vlan_8021Q_EDIT:
	              return Vlan_8021Q_EDIT_CONTENT;
	              break;
	            case Vlan_8021Q_DELETE:
	              return Vlan_8021Q_DELETE_CONTENT;
	              break;
	          }
	          break;
	        case NETWORKING_INTERFACES_v6:
	          switch ($thing) {
	            case LAN_v6:
	              return LAN_v6_CONTENT;
	              break;
	            case WAN1_v6:
	              return WAN1_v6_CONTENT;
	              break;
	            case WAN2_v6:
	              return WAN2_v6_CONTENT;
	              break;
	            case WAN3_v6:
	              return WAN3_v6_CONTENT;
	              break;
	            case WAN4_v6:
	              return WAN4_v6_CONTENT;
	              break;
	            case DMZ_v6:
	              return DMZ_v6_CONTENT;
	              break;
	            case DNS_v6:
	              return DNS_v6_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 2:
	      switch ($subtype) {
	        case BRIDGE:
	          switch ($thing) {
	            case BRIDGE_OUT_ADD:
	              return BRIDGE_OUT_ADD_CONTENT;
	              break;
	            case BRIDGE_OUT_EDIT:
	              return BRIDGE_OUT_EDIT_CONTENT;
	              break;
	            case BRIDGE_OUT_DEL:
	              return BRIDGE_OUT_DEL_CONTENT;
	              break;
	            case BRIDGE_IN_ADD:
	              return BRIDGE_IN_ADD_CONTENT;
	              break;
	            case BRIDGE_IN_EDIT:
	              return BRIDGE_IN_EDIT_CONTENT;
	              break;
	            case BRIDGE_IN_DEL:
	              return BRIDGE_IN_DEL_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 3:
	      switch ($subtype) {
	        case POLICY_LAN:
	          switch ($thing) {
	            case LAN_TO_WAN_ADD:
	              return LAN_TO_WAN_ADD_CONTENT;
	              break;
	            case LAN_TO_WAN_EDIT:
	              return LAN_TO_WAN_EDIT_CONTENT;
	              break;
	            case LAN_TO_WAN_DEL:
	              return LAN_TO_WAN_DEL_CONTENT;
	              break;
	            case LAN_TO_DMZ_ADD:
	              return LAN_TO_DMZ_ADD_CONTENT;
	              break;
	            case LAN_TO_DMZ_EDIT:
	              return LAN_TO_DMZ_EDIT_CONTENT;
	              break;
	            case LAN_TO_DMZ_DEL:
	              return LAN_TO_DMZ_DEL_CONTENT;
	              break;
	            case LAN_TO_LAN_ADD:
	              return LAN_TO_LAN_ADD_CONTENT;
	              break;
	            case LAN_TO_LAN_EDIT:
	              return LAN_TO_LAN_EDIT_CONTENT;
	              break;
	            case LAN_TO_LAN_DEL:
	              return LAN_TO_LAN_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case POLICY_DMZ:
	          switch ($thing) {
	            case DMZ_TO_WAN_ADD:
	              return DMZ_TO_WAN_ADD_CONTENT;
	              break;
	            case DMZ_TO_WAN_EDIT:
	              return DMZ_TO_WAN_EDIT_CONTENT;
	              break;
	            case DMZ_TO_WAN_DEL:
	              return DMZ_TO_WAN_DEL_CONTENT;
	              break;
	            case DMZ_TO_LAN_ADD:
	              return DMZ_TO_LAN_ADD_CONTENT;
	              break;
	            case DMZ_TO_LAN_EDIT:
	              return DMZ_TO_LAN_EDIT_CONTENT;
	              break;
	            case DMZ_TO_LAN_DEL:
	              return DMZ_TO_LAN_DEL_CONTENT;
	              break;
	            case BRIDGE_OUT_ADD:
	              return BRIDGE_OUT_ADD_CONTENT;
	              break;
	            case BRIDGE_OUT_EDIT:
	              return BRIDGE_OUT_EDIT_CONTENT;
	              break;
	            case BRIDGE_OUT_DEL:
	              return BRIDGE_OUT_DEL_CONTENT;
	              break;
	            case DMZ_TO_DMZ_ADD:
	              return DMZ_TO_DMZ_ADD_CONTENT;
	              break;
	            case DMZ_TO_DMZ_EDIT:
	              return DMZ_TO_DMZ_EDIT_CONTENT;
	              break;
	            case DMZ_TO_DMZ_DEL:
	              return DMZ_TO_DMZ_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case POLICY_WAN:
	          switch ($thing) {
	            case WAN_TO_LAN_ADD:
	              return WAN_TO_LAN_ADD_CONTENT;
	              break;
	            case WAN_TO_LAN_EDIT:
	              return WAN_TO_LAN_EDIT_CONTENT;
	              break;
	            case WAN_TO_LAN_DEL:
	              return WAN_TO_LAN_DEL_CONTENT;
	              break;
	            case WAN_TO_DMZ_ADD:
	              return WAN_TO_DMZ_ADD_CONTENT;
	              break;
	            case WAN_TO_DMZ_EDIT:
	              return WAN_TO_DMZ_EDIT_CONTENT;
	              break;
	            case WAN_TO_DMZ_DEL:
	              return WAN_TO_DMZ_DEL_CONTENT;
	              break;
	            case BRIDGE_IN_ADD:
	              return BRIDGE_IN_ADD_CONTENT;
	              break;
	            case BRIDGE_IN_EDIT:
	              return BRIDGE_IN_EDIT_CONTENT;
	              break;
	            case BRIDGE_IN_DEL:
	              return BRIDGE_IN_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case POLICY_LAN_v6:
	          switch ($thing) {
	            case LAN_TO_WAN_ADD_v6:
	              return LAN_TO_WAN_ADD_v6_CONTENT;
	              break;
	            case LAN_TO_WAN_EDIT_v6:
	              return LAN_TO_WAN_EDIT_v6_CONTENT;
	              break;
	            case LAN_TO_WAN_DEL_v6:
	              return LAN_TO_WAN_DEL_v6_CONTENT;
	              break;
	            case LAN_TO_DMZ_ADD_v6:
	              return LAN_TO_DMZ_ADD_v6_CONTENT;
	              break;
	            case LAN_TO_DMZ_EDIT_v6:
	              return LAN_TO_DMZ_EDIT_v6_CONTENT;
	              break;
	            case LAN_TO_DMZ_DEL_v6:
	              return LAN_TO_DMZ_DEL_v6_CONTENT;
	              break;
	          } 
	          break;
	        case POLICY_DMZ_v6:
	          switch ($thing) {
	            case DMZ_TO_WAN_ADD_v6:
	              return DMZ_TO_WAN_ADD_v6_CONTENT;
	              break;
	            case DMZ_TO_WAN_EDIT_v6:
	              return DMZ_TO_WAN_EDIT_v6_CONTENT;
	              break;
	            case DMZ_TO_WAN_DEL_v6:
	              return DMZ_TO_WAN_DEL_v6_CONTENT;
	              break;
	            case DMZ_TO_LAN_ADD_v6:
	              return DMZ_TO_LAN_ADD_v6_CONTENT;
	              break;
	            case DMZ_TO_LAN_EDIT_v6:
	              return DMZ_TO_LAN_EDIT_v6_CONTENT;
	              break;
	            case DMZ_TO_LAN_DEL_v6:
	              return DMZ_TO_LAN_DEL_v6_CONTENT;
	              break;
	            case BRIDGE_OUT_ADD_v6:
	              return BRIDGE_OUT_ADD_v6_CONTENT;
	              break;
	            case BRIDGE_OUT_EDIT_v6:
	              return BRIDGE_OUT_EDIT_v6_CONTENT;
	              break;
	            case BRIDGE_OUT_DEL_v6:
	              return BRIDGE_OUT_DEL_v6_CONTENT;
	              break;
	          } 
	          break;
	        case POLICY_WAN_v6:
	          switch ($thing) {
	            case WAN_TO_LAN_ADD_v6:
	              return WAN_TO_LAN_ADD_v6_CONTENT;
	              break;
	            case WAN_TO_LAN_EDIT_v6:
	              return WAN_TO_LAN_EDIT_v6_CONTENT;
	              break;
	            case WAN_TO_LAN_DEL_v6:
	              return WAN_TO_LAN_DEL_v6_CONTENT;
	              break;
	            case WAN_TO_DMZ_ADD_v6:
	              return WAN_TO_DMZ_ADD_v6_CONTENT;
	              break;
	            case WAN_TO_DMZ_EDIT_v6:
	              return WAN_TO_DMZ_EDIT_v6_CONTENT;
	              break;
	            case WAN_TO_DMZ_DEL_v6:
	              return WAN_TO_DMZ_DEL_v6_CONTENT;
	              break;
	            case BRIDGE_IN_ADD_v6:
	              return BRIDGE_IN_ADD_v6_CONTENT;
	              break;
	            case BRIDGE_IN_EDIT_v6:
	              return BRIDGE_IN_EDIT_v6_CONTENT;
	              break;
	            case BRIDGE_IN_DEL_v6:
	              return BRIDGE_IN_DEL_v6_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 4:
	      switch ($subtype) {
	        case OBJECTS_ADDRESS:
	          switch ($thing) {
	            case ADDRESS_LAN_IP_ADD:
	              return ADDRESS_LAN_IP_ADD_CONTENT;
	              break;
	            case ADDRESS_LAN_IP_EDIT:
	              return ADDRESS_LAN_IP_EDIT_CONTENT;
	              break;
	            case ADDRESS_LAN_IP_DEL:
	              return ADDRESS_LAN_IP_DEL_CONTENT;
	              break;
	            case ADDRESS_DMZ_IP_ADD:
	              return ADDRESS_DMZ_IP_ADD_CONTENT;
	              break;
	            case ADDRESS_DMZ_IP_EDIT:
	              return ADDRESS_DMZ_IP_EDIT_CONTENT;
	              break;
	            case ADDRESS_DMZ_IP_DEL:
	              return ADDRESS_DMZ_IP_DEL_CONTENT;
	              break;
	            case ADDRESS_LAN_GROUP_ADD:
	              return ADDRESS_LAN_GROUP_ADD_CONTENT;
	              break;
	            case ADDRESS_LAN_GROUP_EDIT:
	              return ADDRESS_LAN_GROUP_EDIT_CONTENT;
	              break;
	            case ADDRESS_LAN_GROUP_DEL:
	              return ADDRESS_LAN_GROUP_DEL_CONTENT;
	              break;
	            case ADDRESS_DMZ_GROUP_ADD:
	              return ADDRESS_DMZ_GROUP_ADD_CONTENT;
	              break;
	            case ADDRESS_DMZ_GROUP_EDIT:
	              return ADDRESS_DMZ_GROUP_EDIT_CONTENT;
	              break;
	            case ADDRESS_DMZ_GROUP_DEL:
	              return ADDRESS_DMZ_GROUP_DEL_CONTENT;
	              break;
	            case ADDRESS_WAN_IP_ADD:
	              return ADDRESS_WAN_IP_ADD_CONTENT;
	              break;
	            case ADDRESS_WAN_IP_EDIT:
	              return ADDRESS_WAN_IP_EDIT_CONTENT;
	              break;
	            case ADDRESS_WAN_IP_DEL:
	              return ADDRESS_WAN_IP_DEL_CONTENT;
	              break;
	            case ADDRESS_WAN_GROUP_ADD:
	              return ADDRESS_WAN_GROUP_ADD_CONTENT;
	              break;
	            case ADDRESS_WAN_GROUP_EDIT:
	              return ADDRESS_WAN_GROUP_EDIT_CONTENT;
	              break;
	            case ADDRESS_WAN_GROUP_DEL:
	              return ADDRESS_WAN_GROUP_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case OBJECTS_ADDRESS_v6:
	          switch ($thing) {
	            case ADDRESS_LAN_IP_ADD_v6:
	              return ADDRESS_LAN_IP_ADD_v6_CONTENT;
	              break;
	            case ADDRESS_LAN_IP_EDIT_v6:
	              return ADDRESS_LAN_IP_EDIT_v6_CONTENT;
	              break;
	            case ADDRESS_LAN_IP_DEL_v6:
	              return ADDRESS_LAN_IP_DEL_v6_CONTENT;
	              break;
	            case ADDRESS_DMZ_IP_ADD_v6:
	              return ADDRESS_DMZ_IP_ADD_v6_CONTENT;
	              break;
	            case ADDRESS_DMZ_IP_EDIT_v6:
	              return ADDRESS_DMZ_IP_EDIT_v6_CONTENT;
	              break;
	            case ADDRESS_DMZ_IP_DEL_v6:
	              return ADDRESS_DMZ_IP_DEL_v6_CONTENT;
	              break;
	            case ADDRESS_LAN_GROUP_ADD_v6:
	              return ADDRESS_LAN_GROUP_ADD_v6_CONTENT;
	              break;
	            case ADDRESS_LAN_GROUP_EDIT_v6:
	              return ADDRESS_LAN_GROUP_EDIT_v6_CONTENT;
	              break;
	            case ADDRESS_LAN_GROUP_DEL_v6:
	              return ADDRESS_LAN_GROUP_DEL_v6_CONTENT;
	              break;
	            case ADDRESS_DMZ_GROUP_ADD_v6:
	              return ADDRESS_DMZ_GROUP_ADD_v6_CONTENT;
	              break;
	            case ADDRESS_DMZ_GROUP_EDIT_v6:
	              return ADDRESS_DMZ_GROUP_EDIT_v6_CONTENT;
	              break;
	            case ADDRESS_DMZ_GROUP_DEL_v6:
	              return ADDRESS_DMZ_GROUP_DEL_v6_CONTENT;
	              break;
	            case ADDRESS_WAN_IP_ADD_v6:
	              return ADDRESS_WAN_IP_ADD_v6_CONTENT;
	              break;
	            case ADDRESS_WAN_IP_EDIT_v6:
	              return ADDRESS_WAN_IP_EDIT_v6_CONTENT;
	              break;
	            case ADDRESS_WAN_IP_DEL_v6:
	              return ADDRESS_WAN_IP_DEL_v6_CONTENT;
	              break;
	            case ADDRESS_WAN_GROUP_ADD_v6:
	              return ADDRESS_WAN_GROUP_ADD_v6_CONTENT;
	              break;
	            case ADDRESS_WAN_GROUP_EDIT_v6:
	              return ADDRESS_WAN_GROUP_EDIT_v6_CONTENT;
	              break;
	            case ADDRESS_WAN_GROUP_DEL_v6:
	              return ADDRESS_WAN_GROUP_DEL_v6_CONTENT;
	              break;
	          } 
	          break;
	        case OBJECTS_SERVICES:
	          switch ($thing) {
	            case SERVICES_CUSTOM_DEFINE_ADD:
	              return SERVICES_CUSTOM_DEFINE_ADD_CONTENT;
	              break;
	            case SERVICES_CUSTOM_DEFINE_EDIT:
	              return SERVICES_CUSTOM_DEFINE_EDIT_CONTENT;
	              break;
	            case SERVICES_CUSTOM_DEFINE_DEL:
	              return SERVICES_CUSTOM_DEFINE_DEL_CONTENT;
	              break;
	            case SERVICES_GROUP_ADD:
	              return SERVICES_GROUP_ADD_CONTENT;
	              break;
	            case SERVICES_GROUP_EDIT:
	              return SERVICES_GROUP_EDIT_CONTENT;
	              break;
	            case SERVICES_GROUP_DEL:
	              return SERVICES_GROUP_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case OBJECTS_QOS:
	          switch ($thing) {
	            case QOS_MODE:
	              return QOS_MODE_CONTENT;
	              break;
	            case QOS_SETUP_ADD:
	              return QOS_SETUP_ADD_CONTENT;
	              break;
	            case QOS_SETUP_EDIT:
	              return QOS_SETUP_EDIT_CONTENT;
	              break;
	            case QOS_SETUP_DEL:
	              return QOS_SETUP_DEL_CONTENT;
	              break;
	          } 
	          break;
	      	case OBJECTS_SCHEDULE:
	          switch ($thing) {
	            case SCHEDULE_ADD:
	              return SCHEDULE_ADD_CONTENT;
	              break;
	            case SCHEDULE_EDIT:
	              return SCHEDULE_EDIT_CONTENT;
	              break;
	            case SCHEDULE_DEL:
	              return SCHEDULE_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case OBJECTS_APPLICATION:
	          switch ($thing) {
	            case APPLICATION_BLOCKING_ADD:
	              return APPLICATION_BLOCKING_ADD_CONTENT;
	              break;
	            case APPLICATION_BLOCKING_EDIT:
	              return APPLICATION_BLOCKING_EDIT_CONTENT;
	              break;
	            case APPLICATION_BLOCKING_DEL:
	              return APPLICATION_BLOCKING_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case OBJECTS_URL:
	          switch ($thing) {
	            case URL_BLOCKING_ADD:
	              return URL_BLOCKING_ADD_CONTENT;
	              break;
	            case URL_BLOCKING_EDIT:
	              return URL_BLOCKING_EDIT_CONTENT;
	              break;
	            case URL_BLOCKING_DEL:
	              return URL_BLOCKING_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case OBJECTS_VIRTUAL_SERVER:
	          switch ($thing) {
	            case VIRTUAL_IPMAP_ADD:
	              return VIRTUAL_IPMAP_ADD_CONTENT;
	              break;
	            case VIRTUAL_IPMAP_EDIT:
	              return VIRTUAL_IPMAP_EDIT_CONTENT;
	              break;
	            case VIRTUAL_IPMAP_DEL:
	              return VIRTUAL_IPMAP_DEL_CONTENT;
	              break;
	            case VIRTUAL_PORTMAP_ADD:
	              return VIRTUAL_PORTMAP_ADD_CONTENT;
	              break;
	            case VIRTUAL_PORTMAP_EDIT:
	              return VIRTUAL_PORTMAP_EDIT_CONTENT;
	              break;
	            case VIRTUAL_PORTMAP_DEL:
	              return VIRTUAL_PORTMAP_DEL_CONTENT;
	              break;
	            case VIRTUAL_MAPIP_ADD:
	              return VIRTUAL_MAPIP_ADD_CONTENT;
	              break;
	            case VIRTUAL_MAPIP_EDIT:
	              return VIRTUAL_MAPIP_EDIT_CONTENT;
	              break;
	            case VIRTUAL_MAPIP_DEL:
	              return VIRTUAL_MAPIP_DEL_CONTENT;
	              break;
	            case VIRTUAL_SLB_ADD:
								return VIRTUAL_SLB_ADD_CONTENT;
								break;
							case VIRTUAL_SLB_EDIT:
								return VIRTUAL_SLB_EDIT_CONTENT;
								break;
							case VIRTUAL_SLB_DEL:
								return VIRTUAL_SLB_DEL_CONTENT;
								break;
	          } 
	          break;
	        case OBJECTS_AUTHENTICATION:
	          switch ($thing) {
	            case AUTHENTICATION_SETUP_ALL:
	              return AUTHENTICATION_SETUP_ALL_CONTENT;
	              break;
	            case AUTHENTICATION_USER_ADD:
	              return AUTHENTICATION_USER_ADD_CONTENT;
	              break;
	            case AUTHENTICATION_USER_EDIT:
	              return AUTHENTICATION_USER_EDIT_CONTENT;
	              break;
	            case AUTHENTICATION_USER_DEL:
	              return AUTHENTICATION_USER_DEL_CONTENT;
	              break;
	            case AUTHENTICATION_GROUP_ADD:
	              return AUTHENTICATION_GROUP_ADD_CONTENT;
	              break;
	            case AUTHENTICATION_GROUP_EDIT:
	              return AUTHENTICATION_GROUP_EDIT_CONTENT;
	              break;
	            case AUTHENTICATION_GROUP_DEL:
	              return AUTHENTICATION_GROUP_DEL_CONTENT;
	              break;
	            case AUTHENTICATION_SETUP_OTHER:
	              return AUTHENTICATION_SETUP_OTHER_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 5:
	      switch ($subtype) {
	        case SERVICES_DHCP:
	          switch ($thing) {
	            case DHCP_LAN:
	              return DHCP_LAN_CONTENT;
	              break;
	            case DHCP_DMZ:
	              return DHCP_DMZ_CONTENT;
	              break;
	            case DHCPHOST_ADD:
	              return DHCPHOST_ADD_CONTENT;
	              break;
	            case DHCPHOST_EDIT:
	              return DHCPHOST_EDIT_CONTENT;
	              break;
	            case DHCPHOST_DEL:
	              return DHCPHOST_DEL_CONTENT;
	              break;
	            case DHCP_LAN01:
	              return DHCP_LAN01_CONTENT;
	              break;
	            case DHCP_LAN02:
	              return DHCP_LAN02_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_DNSMASQ:
	          switch ($thing) {
	            case DNSMASQ_SETUP:
	              return DNSMASQ_SETUP_CONTENT;
	              break;
	            case DNSMASQ_ROUTE_ADD:
	              return DNSMASQ_ROUTE_ADD_CONTENT;
	              break;
	            case DNSMASQ_ROUTE_EDIT:
	              return DNSMASQ_ROUTE_EDIT_CONTENT;
	              break;
	            case DNSMASQ_ROUTE_DEL:
	              return DNSMASQ_ROUTE_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_DNS:
	          switch ($thing) {
	            case DNS_SERVER_ADD:
	              return DNS_SERVER_ADD_CONTENT;
	              break;
	             case DNS_SERVER_EDIT:
	              return DNS_SERVER_EDIT_CONTENT;
	              break;
	             case DNS_SERVER_EDIT_2:
	              return DNS_SERVER_EDIT_2_CONTENT;
	              break;
	             case DNS_SERVER_DEL:
	              return DNS_SERVER_DEL_CONTENT;
	              break;
	             case DNS_SERVER_DNSREC_EDIT:
	              return DNS_SERVER_DNSREC_EDIT_CONTENT;
	              break;
	             case SLAVE_ADD:
	              return SLAVE_ADD_CONTENT;
	              break;
	             case SLAVE_EDIT:
	              return SLAVE_EDIT_CONTENT;
	              break;
	             case SLAVE_DEL:
	              return SLAVE_DEL_CONTENT;
	              break;
					case VIEW_ADD:
	              return VIEW_ADD_CONTENT;
	              break;
					case VIEW_EDIT:
	              return VIEW_EDIT_CONTENT;
	              break;
					case VIEW_DEL:
	              return VIEW_DEL_CONTENT;
	              break;
					case WAN_RECURSION_EDIT:
	              return WAN_RECURSION_EDIT_CONTENT;
	              break;
					case WAN_TRANSFER_EDIT:
	              return WAN_TRANSFER_EDIT_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_DDNS:
	          switch ($thing) {
	            case DDNS_SERVER_ADD:
	              return DDNS_SERVER_ADD_CONTENT;
	              break;
	            case DDNS_SERVER_EDIT:
	              return DDNS_SERVER_EDIT_CONTENT;
	              break;
	            case DDNS_SERVER_DEL:
	              return DDNS_SERVER_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_WPROXY:
	          switch ($thing) {
	            case WEB_PRXOY_SETUP:
	              return WEB_PRXOY_SETUP_CONTENT;
	              break;
	            case WEB_EXCLUDE_ADD:
	              return WEB_EXCLUDE_ADD_CONTENT;
	              break;
	            case WEB_EXCLUDE_DELETE:
	            	return WEB_EXCLUDE_DELETE_CONTENT;
	              break;
	           case WEB_EXCLUDE_SEARCH:
	            	return WEB_EXCLUDE_SEARCH_CONTENT;
	              break;
	           case WEB_EXCLUDE_ADDIP:
	              return WEB_EXCLUDE_ADDIP_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_FTPPROXY:
	          switch ($thing) {
	            case FTP_PRXOY_SETUP:
	              return FTP_PRXOY_SETUP_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_MSNPROXY:
	          switch ($thing) {
	            case MSN_PRXOY_SETUP:
	              return MSN_PRXOY_SETUP_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_SKYPEPROXY:
	          switch ($thing) {
	            case SKYPE_PRXOY_SETUP:
	              return SKYPE_PRXOY_SETUP_CONTENT;
	              break;
	          } 
	          break;
	        case SERVICES_QQ:
	          switch ($thing) {
	            case QQ_SETUP:
	              return QQ_SETUP_CONTENT;
	              break;
	            case QQ_ID_ADD:
	              return QQ_ID_ADD_CONTENT;
	              break;
	            case QQ_ID_EDIT:
	              return QQ_ID_EDIT_CONTENT;
	              break;
	            case QQ_ID_DEL:
	              return QQ_ID_DEL_CONTENT;
	              break;
	          }
	          break;
	        case SERVICES_CLAMAV:
	          switch ($thing) {
	            case CLAMAV_ENGINE_CLEAN:
	              return CLAMAV_ENGINE_CLEAN_CONTENT;
	              break;
	            case CLAMAV_ENGINE_ALERT:
	              return CLAMAV_ENGINE_ALERT_CONTENT;
	              break;
	            case CLAMAV_ENGINE_UPDATE:
	              return CLAMAV_ENGINE_UPDATE_CONTENT;
	              break;
	          } 
	          break;
	       case SERVICES_SYSLOGD:
	          switch ($thing) {
	            case REMOTE_CONNECT_SETUP:
	              return REMOTE_CONNECT_SETUP_CONTENT;
	              break;
	          }
	          break;
	       case SERVICES_HA:
	          switch ($thing) {
	            case SERVICES_HA_SAVE:
	              return SERVICES_HA_SAVE_CONTENT;
	              break;
	          }
	          break;	      
	      } 
	      break;
	    case 6:
	      switch ($subtype) {
	        case MS_GATEWAY:
	          switch ($thing) {
	            case GATEWAY_SETUP:
	              return GATEWAY_SETUP_CONTENT;
	              break;
	            case GATEWAY_ADD:
	              return GATEWAY_ADD_CONTENT;
	              break;
	            case GATEWAY_EDIT:
	              return GATEWAY_EDIT_CONTENT;
	              break;
	            case GATEWAY_DEL:
	              return GATEWAY_DEL_CONTENT;
	              break;
	          } 
	          break;
	        case MS_PROXY:
	          switch ($thing) {
	            case PROXY_SERVICE_SETUP:
	              return PROXY_SERVICE_SETUP_CONTENT;
	              break;
	            case PROXY_SERVICE_SETUP_none_CLAMV_SPAM:
	              return PROXY_SERVICE_SETUP_none_CLAMV_SPAM_CONTENT;
	              break;
	            case PROXY_FLOW_BLOCK:
	              return PROXY_FLOW_BLOCK_CONTENT;
	              break;
	            case PROXY_IMPORT:
	              return PROXY_IMPORT_CONTENT;
	              break;
	            case PROXY_EXPORT:
	              return PROXY_EXPORT_CONTENT;
	              break;
	            case PROXY_GRAY_IP_REVERSE:
	              return PROXY_GRAY_IP_REVERSE_CONTENT;
	              break;
	            case PROXY_GRAY_IP_REVERSE_IMPORT:
	              return PROXY_GRAY_IP_REVERSE_IMPORT_CONTENT;
	              break;
	            case PROXY_GRAY_IP_REVERSE_EXPORT:
	              return PROXY_GRAY_IP_REVERSE_EXPORT_CONTENT;
	              break;
	          } 
	          break;
	        case MS_ANTIVIRUS:
	          switch ($thing) {
	            case ANTIVIRUS_SETUP:
	              return ANTIVIRUS_SETUP_CONTENT;
	              break;
	            case ANTIVIRUS_SEARCH:
	              return ANTIVIRUS_SEARCH_CONTENT;
	              break;
	          } 
	          break;
	        case MS_ANTISPAM:
	          switch ($thing) {
	            case ANTISPAM_SETUP:
	              return ANTISPAM_SETUP_CONTENT;
	              break;
	            case ANTISPAM_SPL_SEARCH:
	              return ANTISPAM_SPL_SEARCH_CONTENT;
	              break;
	            case ANTISPAM_DEL_SEARCH:
	              return ANTISPAM_DEL_SEARCH_CONTENT;
	              break;
	            case ANTISPAM_LISTSET:
	              return ANTISPAM_LISTSET_CONTENT;
	              break;
	            case ANTISPAM_LEARN:
	              return ANTISPAM_LEARN_CONTENT;
	              break;
	            case ANTISPAM_PSPAM_ADD:
	              return ANTISPAM_PSPAM_ADD_CONTENT;
	              break;
	            case ANTISPAM_PSPAM_EDIT:
	              return ANTISPAM_PSPAM_EDIT_CONTENT;
	              break;
	            case ANTISPAM_PSPAM_DEL:
	              return ANTISPAM_PSPAM_DEL_CONTENT;
	              break;
	            case ANTISPAM_SYSTEM_BLACKANDWHITE:
	              return ANTISPAM_SYSTEM_BLACKANDWHITE_CONTENT;
	              break;
	          } 
	          break;
	        case MS_MAILLOGS:
	          switch ($thing) {
	            case MAILLOGS_SETUP:
	              return MAILLOGS_SETUP_CONTENT;
	              break;
	             case MAILLOGS_SEARCH:
	              return MAILLOGS_SEARCH_CONTENT;
	              break;
	          } 
	          break;
	        case MS_FILTER:
	        	switch ($thing) {
	        		case MS_FILTER_ADD:
	        			return MS_FILTER_ADD_CONTENT;
	        			break;
	        		case MS_FILTER_EDIT:
	        			return MS_FILTER_EDIT_CONTENT;
	        			break;
	        		case MS_FILTER_DEL:
	        			return MS_FILTER_DEL_CONTENT;
	        			break;
	        	}
	        	break;
	      } 
	      break;
	    case 7:
	      switch ($subtype) {
	        case RECORDER_WEB:
	          switch ($thing) {
	            case WEB_RECORDER_TODAY:
	              return WEB_RECORDER_TODAY_CONTENT;
	              break;
	            case WEB_RECORDER_OLD:
	              return WEB_RECORDER_OLD_CONTENT;
	              break;
	          } 
	          break;
	        case RECORDER_FTP:
	          switch ($thing) {
	            case FTP_RECORDER_TODAY:
	              return FTP_RECORDER_TODAY_CONTENT;
	              break;
	            case FTP_RECORDER_OLD:
	              return FTP_RECORDER_OLD_CONTENT;
	              break;
	          } 
	          break;
	        case RECORDER_MSN:
	          switch ($thing) {
	          	case MSN_RECORDER_SETUP:
	            	return MSN_RECORDER_SETUP_CONTENT;
	            	break;
	            case MSN_RECORDER_TODAY:
	              return MSN_RECORDER_TODAY_CONTENT;
	              break;
	            case MSN_RECORDER_OLD:
	              return MSN_RECORDER_OLD_CONTENT;
	              break;
	            case MSN_RECORDER_CONTACT:
	              return MSN_RECORDER_CONTACT_CONTENT;
	              break;
	          } 
	          break;
	        case RECORDER_IM:
	          switch ($thing) {
	            case IM_RECORDER_TODAY:
	              return IM_RECORDER_TODAY_CONTENT;
	              break;
	            case IM_RECORDER_OLD:
	              return IM_RECORDER_OLD_CONTENT;
	              break;
	          } 
	          break;
	        case RECORDER_QQLOG:
	          switch ($thing) {
	            case QQLOG_BLOCK:
	              return QQLOG_BLOCK_CONTENT;
	              break;
	            case QQLOG_CHAT:
	              return QQLOG_CHAT_CONTENT;
	              break;
	          } 
	          break;
	        case RECORDER_MAILREC:
	          switch ($thing) {
	            case MAILREC_GATEWAY_LOG:
	              return MAILREC_GATEWAY_LOG_CONTENT;
	              break;
	            case MAILREC_PROXY_LOG:
	              return MAILREC_PROXY_LOG_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 8:
	      switch ($subtype) {
	        case VPN_IPSEC_TUNNEL:
	          switch ($thing) {
	            case IPSEC_TUNNEL_ADD:
	              return IPSEC_TUNNEL_ADD_CONTENT;
	              break;
	            case IPSEC_TUNNEL_EDIT:
	              return IPSEC_TUNNEL_EDIT_CONTENT;
	              break;
	            case IPSEC_TUNNEL_DEL:
	              return IPSEC_TUNNEL_DEL_CONTENT;
	              break;
							case IPSEC_TUNNEL_PAUSE:
								return IPSEC_TUNNEL_PAUSE_CONTENT;
								break;
							case IPSEC_TUNNEL_ENABLED:
								return IPSEC_TUNNEL_ENABLED_CONTENT;
								break;
	          } 
	          break;
	        case VPN_PPTP_SERVER:
	          switch ($thing) {
	            case PPTP_SERVER_SETUP:
	              return PPTP_SERVER_SETUP_CONTENT;
	              break;
	            case PPTP_SERVER_ADD:
	              return PPTP_SERVER_ADD_CONTENT;
	              break;
	            case PPTP_SERVER_EDIT:
	              return PPTP_SERVER_EDIT_CONTENT;
	              break;
	            case PPTP_SERVER_DEL:
	              return PPTP_SERVER_DEL_CONTENT;
	              break;
	            case PPTP_SERVER_ENABLED:
	              return PPTP_SERVER_ENABLED_CONTENT;
	              break;
	            case PPTP_SERVER_PAUSE:
	              return PPTP_SERVER_PAUSE_CONTENT;
	              break;
	          } 
	          break;
	        case VPN_PPTP_CLIENT:
	          switch ($thing) {
	            case PPTP_CLIENT_ADD:
	              return PPTP_CLIENT_ADD_CONTENT;
	              break;
	            case PPTP_CLIENT_EDIT:
	              return PPTP_CLIENT_EDIT_CONTENT;
	              break;
	            case PPTP_CLIENT_DEL:
	              return PPTP_CLIENT_DEL_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 9:
	      switch ($subtype) {
	        case TOOLS_CONNECT_TEST:
	          switch ($thing) {
	            case CONNECT_TEST_PING:
	              return CONNECT_TEST_PING_CONTENT;
	              break;
	            case CONNECT_TEST_TRACEROUTE:
	              return CONNECT_TEST_TRACEROUTE_CONTENT;
	              break;
	            case CONNECT_TEST_DNS:
	              return CONNECT_TEST_DNS_CONTENT;
	              break;
	            case CONNECT_TEST_SERVICE:
	              return CONNECT_TEST_SERVICE_CONTENT;
	              break;
	          } 
	          break;
	        case TOOLS_TCPDUMP:
	          switch ($thing) {
	            case TCPDUMP_SCHEDULE_ADD:
	              return TCPDUMP_SCHEDULE_ADD_CONTENT;
	              break;
	            case TCPDUMP_SCHEDULE_EDIT:
	              return TCPDUMP_SCHEDULE_EDIT_CONTENT;
	              break;
	            case TCPDUMP_SCHEDULE_DELETE:
	              return TCPDUMP_SCHEDULE_DELETE_CONTENT;
	              break;
	            case TCPDUMP_HISTORY_DELETE:
	              return TCPDUMP_HISTORY_DELETE_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	  	case 10:
	      switch ($subtype) {
	        case LOGS_SYSTEM_LOG:
	          switch ($thing) {
	            case SYSTEM_LOG:
	              return SYSTEM_LOG_CONTENT;
	              break;
	          } 
	          break;
	        case LOGS_LOGIN_EVENT:
	          switch ($thing) {
	            case LOGIN_EVENT:
	              return LOGIN_EVENT_CONTENT;
	              break;
	            case LOGIN_EVENT_SEARCH:
	              return LOGIN_EVENT_SEARCH_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 11:
	      switch ($subtype) {
	        case STATUS_PERFORMANCE:
	          switch ($thing) {
	            case PERFORMANCE_SYS_STATUS:
	              return PERFORMANCE_SYS_STATUS_CONTENT;
	              break;
	            case PERFORMANCE_INTERFACE_FLOW:
	              return PERFORMANCE_INTERFACE_FLOW_CONTENT;
	              break;
	            case PERFORMANCE_HISTORY:
	              return PERFORMANCE_HISTORY_CONTENT;
	              break;
	            case PERFORMANCE_CMP:
	              return PERFORMANCE_CMP_CONTENT;
	              break;
	          } 
	          break;
	        case STATUS_CONNECTION_TRACK:
	          switch ($thing) {
	            case CONNECTION_TRACK:
	              return CONNECTION_TRACK_CONTENT;
	              break;
	          } 
	          break;
	        case STATUS_USER_FLOW:
	          switch ($thing) {
	            case USER_FLOW_TOP10LIST:
	              return USER_FLOW_TOP10LIST_CONTENT;
	              break;
	            case USER_FLOW_TOP10GRAPH:
	              return USER_FLOW_TOP10GRAPH_CONTENT;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 12:
	    	switch ($subtype) {
	        case SYSTEM_LOGIN:
	          switch ($thing) {
	            case LOGIN_OK:
	              return LOGIN_OK_CONTENT;
	              break;
	          } 
	          break;
	        case SYSTEM_LOGOUT:
	          switch ($thing) {
	            case LOGOUT_OK:
	              return LOGOUT_OK_CONTENT;
	              break;
	          } 
	          break;
				}
				break;
			case 13:
	    	switch ($subtype) {
	        case SSL_VPN_SETU:
	          switch ($thing) {
	            case SSLVPN_SETUP:
	              return SSLVPN_SETUP_CONTENT;
	              break;
	            case CLIENT_CA_LIST:
	              return CLIENT_CA_LIST_CONTENT;
	              break;
	          } 
	          break;
				}
				break;
				case 14:
	    	switch ($subtype) {
	        case BOTNET_SETTING:
	          switch ($thing) {
	            case BOTNET_ADD:
	              return BOTNET_ADD_CONTENT;
	              break;
	            case BOTNET_EDIT:
	              return BOTNET_EDIT_CONTENT;
	              break;
	            case BOTNET_DELETE:
	              return BOTNET_DELETE_CONTENT;
	              break;
	            case BOTNET_OPERATION:
	              return BOTNET_OPERATION_CONTENT;
	              break;
	           case BOTNET_SNIFFER:
	              return BOTNET_SNIFFER_CONTENT;
	              break;
	          }
	          break;
	        case BOTNET_SEARCH_LOG:
	          switch ($thing) {
	            case BOTNET_SEARCH:
	              return BOTNET_SEARCH_CONTENT;
	              break;
	          }
	          break;
				}
				break;
				case 15:
				switch ($subtype) {
					case ANOMALY_IP_SETTING:
						switch($thing) {
							case ANOMALY_BLOCK_SAVE:
								return ANOMALY_BLOCK_SAVE_CONTENT;
								break;
							case ANOMALY_RECORD_SAVE:
								return ANOMALY_RECORD_SAVE_CONTENT;
								break;
							case ANOMALY_NOTIFY_EDIT:
								return ANOMALY_NOTIFY_EDIT_CONTENT;
								break;
							case ANOMALY_EXCEPT_ADD:
								return ANOMALY_EXCEPT_ADD_CONTENT;
								break;
							case ANOMALY_EXCEPT_EDIT:
								return ANOMALY_EXCEPT_EDIT_CONTENT;
								break;
							case ANOMALY_EXCEPT_DEL:
								return ANOMALY_EXCEPT_DEL_CONTENT;
								break;
						}
						break;
					case ADVANCED_SETTING:
						switch ($thing) {
							case ADVANCED_SNMP_ADD:
								return ADVANCED_ADD_CONTENT;
								break;
							case ADVANCED_DEFENSE_ADD:
								return ADVANCED_ADD_CONTENT;
								break;
							case ADVANCED_SNMP_EDIT:
								return ADVANCED_EDIT_CONTENT;
								break;
							case ADVANCED_DEFENSE_EDIT:
								return ADVANCED_EDIT_CONTENT;
								break;
							case ADVANCED_DELETE:
								return ADVANCED_DEL_CONTENT;
							break;
						}
						break;
					case ADVANCED_NETWORKSTATE:
						switch ($thing) {
							case ADVANCED_NETWORK_REFRESH:
								return ADVANCED_NETWORK_REFRESH_CONTENT;
								break;
							case ADVANCED_NETWORK_SEARCH:
								return ADVANCED_NETWORK_SEARCH_CONTENT;
								break;
							case ADVANCED_NETWORK_LOCK:
								return ADVANCED_NETWORK_LOCK_CONTENT;
								break;
							case ADVANCED_NETWORK_CHANGE:
								return ADVANCED_NETWORK_CHANGE_CONTENT;
								break;
							case ADVANCED_NETWORK_UPHOLE:
								return ADVANCED_NETWORK_UPHOLE_CONTENT;
								break;
							case ADVANCED_NETWORK_INTERLOCK:
								return ADVANCED_NETWORK_INTERLOCK_CONTENT;
								break;
						}
						break;
					case ADVANCED_BIND_LIST:
						switch ($thing) {
							case ADVANCED_BIND_LIST_ADD:
								return ADVANCED_BIND_LIST_ADD_CONTENT;
								break;
							case ADVANCED_BIND_LIST_EDIT:
								return ADVANCED_BIND_LIST_EDIT_CONTENT;
								break;
							case ADVANCED_BIND_LIST_DEL:
								return ADVANCED_BIND_LIST_DEL_CONTENT;
								break;
						}
						break;
					case ADVANCED_PROTECH:
						switch ($thing) {
							case ADVANCED_ARP_PROTECH_EDIT:
								return ADVANCED_ARP_PROTECH_EDIT_CONTENT;
								break;
							case ADVANCED_MAC_PROTECH_EDIT:
								return ADVANCED_MAC_PROTECH_EDIT_CONTENT;
								break;
							case ADVANCED_IP_PROTECH_EDIT:
								return ADVANCED_IP_PROTECH_EDIT_CONTENT;
								break;
						}
						break;
					case ADVANCED_PROTECT:
						switch ($thing) {
							case ADVANCED_PROTECT_ARP_SEARCH:
								return ADVANCED_PROTECT_ARP_SEARCH_CONTENT;
								break;
							case ADVANCED_PROTECT_MAC_REFRESH:
								return ADVANCED_PROTECT_MAC_REFRESH_CONTENT;
								break;
							case ADVANCED_PROTECT_MAC_DEL:
								return ADVANCED_PROTECT_MAC_DEL_CONTENT;
								break;
						}
					case ADVANCED_DEFENSE:
						switch ($thing) {
							case ADVANCED_DEFENSE_SAVE:
								return ADVANCED_DEFENSE_SAVE_CONTENT;
								break;
						}
						break;
					case INTRANET_PROTECT:
						switch ($thing) {
							case INTRANET_PROTECT_SAVE:
								return INTRANET_PROTECT_SAVE_CONTENT;
								break;
						}
						break;
				}
				break;
	  } 
	} 
	
	function get_title_parameters($id) {
	  global $db;
	  $res = $db->query("SELECT `order`, `value` FROM `log_title_parameter` WHERE `log_id` = $id");
	  while ($row = mysql_fetch_row($res)) {
	    $parameters[$row[0]] = $row[1];
	  } 
	  return $parameters;
	} 
	
	function get_title($type, $subtype, $thing, $parameters) {
		$Specific = new Specific();
		$SVar = $Specific->getAll();
		$Layout = new Layout("");
		if($SVar["HDD"]==0 && $SVar["CONNTRACK"]==0){
			$Layout->extraLangFile("Logs_Sys_AW5.lang");
		}else if($SVar["HDD"]==1 && $SVar["CONNTRACK"]==0){
			$Layout->extraLangFile("Logs_Sys_AW5R.lang"); 
		}else{
			$Layout->extraLangFile("Logs_Sys.lang");
		}
		$Var = $Layout->getAll();
		
	 	switch ($type) {
	    case 0:
	      switch ($subtype) {
	        case CONFIGURATION_DATEANDTIME:
	          switch ($thing) {
	            case DATEANDTIME_SETUP:
	              $title = DATEANDTIME_SETUP_TITLE;
	              break;
	            case DATEANDTIME_SETUP2:
	              $title = DATEANDTIME_SETUP2_TITLE;
	              break;
	          } 
	          break;
	        case CONFIGURATION_ADMINISTRATOR:
	        	switch ($thing) {
							case ADMINISTRATOR_ADD:
								$title = ADMINISTRATOR_ADD_TITLE;
								break;
							case ADMINISTRATOR_EDIT:
								$title = ADMINISTRATOR_EDIT_TITLE;
								break;
							case ADMINISTRATOR_DEL:
								$title = ADMINISTRATOR_DEL_TITLE;
								break;
							case SYSTEM_SETUP:
								$title = SYSTEM_SETUP_TITLE;
								break;
							case PERMITTED_IPS_ADD:
								$title = PERMITTED_IPS_ADD_TITLE;
								break;
							case PERMITTED_IPS_EDIT:
								$title = PERMITTED_IPS_EDIT_TITLE;
								break;
							case PERMITTED_IPS_DEL:
								$title = PERMITTED_IPS_DEL_TITLE;
								break;
							case RECORDER_CLEAR:
								$title = RECORDER_CLEAR_TITLE;
								break;
							case RECORDER_STORING:
								$title = RECORDER_STORING_TITLE;
								break;
							case PERMITTED_IPS_CHANGE:
								$title = PERMITTED_IPS_CHANGE_TITLE;
								break;
							case PERMITTED_IPS_SAVE:
								$title =  PERMITTED_IPS_SAVE_TITLE;
								break;
							case SYSTEM_SETUP_UNBLOCK:
								$title = SYSTEM_SETUP_UNBLOCK_TITLE;
								break;
							case FSCK_HDD_SAVE:
								$title = FSCK_HDD_SAVE_TITLE;
								break;
							case FSCK_HDD_NOW:
								$title = FSCK_HDD_NOW_TITLE;
								break;
						}
	          break;
	        case CONFIGURATION_BACKUPANDUPGRADE:
	          switch ($thing) {
	            case SYSTEM_BACKUP_BACK:
	              break;
	            case SYSTEM_BACKUP_REDUCE_UPGRADE:
	              break;
	            case SOFTWARE_UPGRADE_UPGRADE:
	              break;
							case AUTO_BACKUP_DOWNLOAD:
								$title = AUTO_BACKUP_DOWNLOAD_TITLE;
								break;
							case AUTO_BACKUP_RESTORE:
								$title = AUTO_BACKUP_RESTORE_TITLE;
								break;
							case AUTO_BACKUP_DELETE:
								$title = AUTO_BACKUP_DELETE_TITLE;
								break;
							case AUTO_BACKUP:
								$title = AUTO_BACKUP_TITLE;
								break;
							case FIRMWARE_UPDATE:
								break;
							case FIRMWARE_SETUP:
								$title = FIRMWARE_SETUP_TITLE;
								break;
							case FIRMWARE_DOWNLOAD:
								$title = FIRMWARE_DOWNLOAD_TITLE;
								break;
							case FIRMWARE_UPGRADE:
								$title = FIRMWARE_UPGRADE_TITLE;
								break;
							case FIRMWARE_LOG_DOWNLOAD:
								$title = FIRMWARE_LOG_DOWNLOAD_TITLE;
								break;
	          } 
	          break;
	        case CONFIGURATION_LANGUAGE:
	          switch ($thing) {
	            case LANGUAGE_SETUP:
	              $title = LANGUAGE_SETUP_TITLE;
	              break;
	          } 
	          break;
	        case CONFIGURATION_SIGNATUREUPDATE:
	          switch ($thing) {
	            case SIGNATURE_UPDATE_SETUP:
	              $title = SIGNATURE_UPDATE_SETUP_TITLE;
	              break;
	          } 
	          break;
	      	case CONFIGURATION_REPORT:
	          switch ($thing) {
	            case REPORT_BASIC_SETUP:
	              $title = REPORT_BASIC_SETUP_TITLE;
	              break;
	            case REPORT_RECIPIENT_ADD_DEF:
	              $title = REPORT_RECIPIENT_ADD_DEF_TITLE;
	              break;
	          	case REPORT_RECIPIENT_EDIT_DEF:
	              $title = REPORT_RECIPIENT_ADD_DEF_TITLE;
	              break;
	         	 	case REPORT_RECIPIENT_DELETE:
	              $title = REPORT_RECIPIENT_DELETE_TITLE;
	              break;
	           	case REPORT_QUERY:
	              $title = REPORT_QUERY_TITLE;
	              break;
	            case REPORT_BASIC_PREVIEW:
	            	$title = REPORT_BASIC_SETUP_TITLE;
	            	break;
	            case REPORT_RECIPIENT_ADD_USER:
	              $title = REPORT_RECIPIENT_ADD_USER_TITLE;
	              break;
	            case REPORT_RECIPIENT_EDIT_USER:
	              $title = REPORT_RECIPIENT_ADD_USER_TITLE;
	              break;
	          } 
	          break;
	          case CONFIGURATION_CMS:
	          switch ($thing) {
	            case CMS_BASIC_SETUP_CLIENT:
	              $title = CMS_BASIC_SETUP_CLIENT_TITLE;
	              break;
	            case CMS_BASIC_SETUP_SERVER:
	              $title = CMS_BASIC_SETUP_SERVER_TITLE;
	              break;
	            case CMS_MONITOR_ADD:
	          		$title = CMS_MONITOR_ADD_TITLE;
	              break;
	            case CMS_MONITOR_EDIT:
	          		$title = CMS_MONITOR_ADD_TITLE;
	              break;
	            case CMS_MONITOR_DELETE:
	          		$title = CMS_MONITOR_DELETE_TITLE;
	              break;
	          } 
	          break;
	          case CONFIGURATION_AP:
	          switch ($thing) {
	            case AP_SETTING_SAVE:
	              $title = AP_SETTING_SAVE_TITLE;
	              break;
	            case AP_MONITOR_ADD:
	              $title = AP_MONITOR_ADD_TITLE;
	              break;
	            case AP_MONITOR_EDIT:
	          		$title = AP_MONITOR_EDIT_TITLE;
	              break;
	            case AP_MONITOR_DELETE:
	          		$title = AP_MONITOR_DELETE_TITLE;
	              break;
	            case AP_MONITOR_RQT_ADD:
	            	$title = AP_MONITOR_RQT_ADD_TITLE;
	              break;
							case AP_MONITOR_RQT_DELETE:
	            	$title = AP_MONITOR_RQT_DELETE_TITLE;
	              break;
	            case AP_MONITOR_DELIVERY:
	            	$title = AP_MONITOR_DELIVERY_TITLE;
	              break;
	            case AP_MONITOR_LIST_DELIVERY:
	            	$title = AP_MONITOR_LIST_DELIVERY_TITLE;
	              break;
	          }
	          break;
	      } 
	      break;
	    case 1:
	      switch ($subtype) {
	        case NETWORKING_INTERFACES:
	          switch ($thing) {
	            case LAN_SETUP:
	             $title = LAN_SETUP_TITLE;
	              break;
	            case WAN1_SETUP:
	              $title = WAN1_SETUP_TITLE;
	              break;
	            case WAN2_SETUP:
	              $title = WAN2_SETUP_TITLE;
	              break;
	            case DMZ_SETUP:
	              $title = DMZ_SETUP_TITLE;
	              break;
	            case WAN3_SETUP:
	              $title = WAN3_SETUP_TITLE;
	              break;
	            case WAN4_SETUP:
	              $title = WAN4_SETUP_TITLE;
	              break;
	            case LAN_SETUP_v6:
	             $title = LAN_SETUP_v6_TITLE;
	              break;
	            case WAN1_SETUP_v6:
	              $title = WAN1_SETUP_v6_TITLE;
	              break;
	          } 
	          break;
	        case NETWORKING_ROUTING:
	          switch ($thing) {
	            case ROUTING_TABLE_ADD:
	              $title = ROUTING_TABLE_ADD_TITLE;
	              break;
	            case ROUTING_TABLE_EDIT:
	              $title = ROUTING_TABLE_EDIT_TITLE;
	              break;
	            case ROUTING_TABLE_DEL:
	              $title = ROUTING_TABLE_DEL_TITLE;
	              break;
	            case ROUTING_TABLE_v6_ADD:
	              $title = ROUTING_TABLE_v6_ADD_TITLE;
	              break;
	            case ROUTING_TABLE_v6_EDIT:
	              $title = ROUTING_TABLE_v6_EDIT_TITLE;
	              break;
	            case ROUTING_TABLE_v6_DEL:
	              $title = ROUTING_TABLE_v6_DEL_TITLE;
	              break;
	          } 
	          break;
	          case NETWORKING_8021Q:
	          switch ($thing) {
	            case Vlan_8021Q_ADD:
	              $title = Vlan_8021Q_ADD_TITLE;
	              break;
	            case Vlan_8021Q_EDIT:
	               $title = Vlan_8021Q_EDIT_TITLE;
	              break;
	            case Vlan_8021Q_DELETE:
	               $title = Vlan_8021Q_DELETE_TITLE;
	              break;
	          }
	          break;
	        case NETWORKING_INTERFACES_v6:
	          switch ($thing) {
	            case LAN_v6:
	              $title = LAN_v6_TITLE;
	              break;
	            case WAN1_v6:
	              $title = WAN1_v6_TITLE;
	              break;
	            case WAN2_v6:
	              $title = WAN2_v6_TITLE;
	              break;
	            case WAN3_v6:
	              $title = WAN3_v6_TITLE;
	              break;
	            case WAN4_v6:
	              $title = WAN4_v6_TITLE;
	              break;
	            case DMZ_v6:
	              $title = DMZ_v6_TITLE;
	              break;
	            case DNS_v6:
	              $title = DNS_v6_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 2:
	      switch ($subtype) {
	        case BRIDGE:
	          switch ($thing) {
	            case BRIDGE_OUT_ADD:
	              $title = BRIDGE_OUT_ADD_TITLE;
	              break;
	            case BRIDGE_OUT_EDIT:
	              $title = BRIDGE_OUT_EDIT_TITLE;
	              break;
	            case BRIDGE_OUT_DEL:
	              $title = BRIDGE_OUT_DEL_TITLE;
	              break;
	            case BRIDGE_IN_ADD:
	              $title = BRIDGE_IN_ADD_TITLE;
	              break;
	            case BRIDGE_IN_EDIT:
	              $title = BRIDGE_IN_EDIT_TITLE;
	              break;
	            case BRIDGE_IN_DEL:
	              $title = BRIDGE_IN_DEL_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 3:
	      switch ($subtype) {
	        case POLICY_LAN:
	          switch ($thing) {
	            case LAN_TO_WAN_ADD:
	              $title = LAN_TO_WAN_ADD_TITLE;
	              break;
	            case LAN_TO_WAN_EDIT:
	             $title = LAN_TO_WAN_EDIT_TITLE;
	              break;
	            case LAN_TO_WAN_DEL:
	              $title = LAN_TO_WAN_DEL_TITLE;
	              break;
	            case LAN_TO_DMZ_ADD:
	              $title = LAN_TO_DMZ_ADD_TITLE;
	              break;
	            case LAN_TO_DMZ_EDIT:
	              $title = LAN_TO_DMZ_EDIT_TITLE;
	              break;
	            case LAN_TO_DMZ_DEL:
	              $title = LAN_TO_DMZ_DEL_TITLE;
	              break;
	            case LAN_TO_LAN_ADD:
	              $title = LAN_TO_LAN_ADD_TITLE;
	              break;
	            case LAN_TO_LAN_EDIT:
	              $title = LAN_TO_LAN_EDIT_TITLE;
	              break;
	            case LAN_TO_LAN_DEL:
	              $title = LAN_TO_LAN_DEL_TITLE;
	              break;
	          } 
	          break;
	        case POLICY_DMZ:
	          switch ($thing) {
	            case DMZ_TO_WAN_ADD:
	              $title = DMZ_TO_WAN_ADD_TITLE;
	              break;
	            case DMZ_TO_WAN_EDIT:
	              $title = DMZ_TO_WAN_EDIT_TITLE;
	              break;
	            case DMZ_TO_WAN_DEL:
	              $title = DMZ_TO_WAN_DEL_TITLE;
	              break;
	            case DMZ_TO_LAN_ADD:
	              $title = DMZ_TO_LAN_ADD_TITLE;
	              break;
	            case DMZ_TO_LAN_EDIT:
	              $title = DMZ_TO_LAN_EDIT_TITLE;
	              break;
	            case DMZ_TO_LAN_DEL:
	              $title = DMZ_TO_LAN_DEL_TITLE;
	              break;
	            case BRIDGE_OUT_ADD:
	              $title = BRIDGE_OUT_ADD_TITLE;
	              break;
	            case BRIDGE_OUT_EDIT:
	              $title = BRIDGE_OUT_EDIT_TITLE;
	              break;
	            case BRIDGE_OUT_DEL:
	              $title = BRIDGE_OUT_DEL_TITLE;
	              break;
	            case DMZ_TO_DMZ_ADD:
	              $title = DMZ_TO_DMZ_ADD_TITLE;
	              break;
	            case DMZ_TO_DMZ_EDIT:
	              $title = DMZ_TO_DMZ_EDIT_TITLE;
	              break;
	            case DMZ_TO_DMZ_DEL:
	              $title = DMZ_TO_DMZ_DEL_TITLE;
	              break;
	          } 
	          break;
	        case POLICY_WAN:
	          switch ($thing) {
	            case WAN_TO_LAN_ADD:
	              $title = WAN_TO_LAN_ADD_TITLE;
	              break;
	            case WAN_TO_LAN_EDIT:
	              $title = WAN_TO_LAN_EDIT_TITLE;
	              break;
	            case WAN_TO_LAN_DEL:
	              $title = WAN_TO_LAN_DEL_TITLE;
	              break;
	            case WAN_TO_DMZ_ADD:
	              $title = WAN_TO_DMZ_ADD_TITLE;
	              break;
	            case WAN_TO_DMZ_EDIT:
	              $title = WAN_TO_DMZ_EDIT_TITLE;
	              break;
	            case WAN_TO_DMZ_DEL:
	              $title = WAN_TO_DMZ_DEL_TITLE;
	              break;
	            case BRIDGE_IN_ADD:
	              $title = BRIDGE_IN_ADD_TITLE;
	              break;
	            case BRIDGE_IN_EDIT:
	              $title = BRIDGE_IN_EDIT_TITLE;
	              break;
	            case BRIDGE_IN_DEL:
	              $title = BRIDGE_IN_DEL_TITLE;
	              break;
	          } 
	          break;
	        case POLICY_LAN_v6:
	          switch ($thing) {
	            case LAN_TO_WAN_ADD_v6:
	              $title = LAN_TO_WAN_ADD_v6_TITLE;
	              break;
	            case LAN_TO_WAN_EDIT_v6:
	             $title = LAN_TO_WAN_EDIT_v6_TITLE;
	              break;
	            case LAN_TO_WAN_DEL_v6:
	              $title = LAN_TO_WAN_DEL_v6_TITLE;
	              break;
	            case LAN_TO_DMZ_ADD_v6:
	              $title = LAN_TO_DMZ_ADD_v6_TITLE;
	              break;
	            case LAN_TO_DMZ_EDIT_v6:
	              $title = LAN_TO_DMZ_EDIT_v6_TITLE;
	              break;
	            case LAN_TO_DMZ_DEL_v6:
	              $title = LAN_TO_DMZ_DEL_v6_TITLE;
	              break;
	          } 
	          break;
	        case POLICY_DMZ_v6:
	          switch ($thing) {
	            case DMZ_TO_WAN_ADD_v6:
	              $title = DMZ_TO_WAN_ADD_v6_TITLE;
	              break;
	            case DMZ_TO_WAN_EDIT_v6:
	              $title = DMZ_TO_WAN_EDIT_v6_TITLE;
	              break;
	            case DMZ_TO_WAN_DEL_v6:
	              $title = DMZ_TO_WAN_DEL_v6_TITLE;
	              break;
	            case DMZ_TO_LAN_ADD_v6:
	              $title = DMZ_TO_LAN_ADD_v6_TITLE;
	              break;
	            case DMZ_TO_LAN_EDIT_v6:
	              $title = DMZ_TO_LAN_EDIT_v6_TITLE;
	              break;
	            case DMZ_TO_LAN_DEL_v6:
	              $title = DMZ_TO_LAN_DEL_v6_TITLE;
	              break;
	            case BRIDGE_OUT_ADD_v6:
	              $title = BRIDGE_OUT_ADD_v6_TITLE;
	              break;
	            case BRIDGE_OUT_EDIT_v6:
	              $title = BRIDGE_OUT_EDIT_v6_TITLE;
	              break;
	            case BRIDGE_OUT_DEL_v6:
	              $title = BRIDGE_OUT_DEL_v6_TITLE;
	              break;
	          } 
	          break;
	        case POLICY_WAN_v6:
	          switch ($thing) {
	            case WAN_TO_LAN_ADD_v6:
	              $title = WAN_TO_LAN_ADD_v6_TITLE;
	              break;
	            case WAN_TO_LAN_EDIT_v6:
	              $title = WAN_TO_LAN_EDIT_v6_TITLE;
	              break;
	            case WAN_TO_LAN_DEL_v6:
	              $title = WAN_TO_LAN_DEL_v6_TITLE;
	              break;
	            case WAN_TO_DMZ_ADD_v6:
	              $title = WAN_TO_DMZ_ADD_v6_TITLE;
	              break;
	            case WAN_TO_DMZ_EDIT_v6:
	              $title = WAN_TO_DMZ_EDIT_v6_TITLE;
	              break;
	            case WAN_TO_DMZ_DEL_v6:
	              $title = WAN_TO_DMZ_DEL_v6_TITLE;
	              break;
	            case BRIDGE_IN_ADD_v6:
	              $title = BRIDGE_IN_ADD_v6_TITLE;
	              break;
	            case BRIDGE_IN_EDIT_v6:
	              $title = BRIDGE_IN_EDIT_v6_TITLE;
	              break;
	            case BRIDGE_IN_DEL_v6:
	              $title = BRIDGE_IN_DEL_v6_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 4:
	      switch ($subtype) {
	        case OBJECTS_ADDRESS:
	          switch ($thing) {
	            case ADDRESS_LAN_IP_ADD:
	              $title = ADDRESS_LAN_IP_ADD_TITLE;
	              break;
	            case ADDRESS_LAN_IP_EDIT:
	              $title = ADDRESS_LAN_IP_EDIT_TITLE;
	              break;
	            case ADDRESS_LAN_IP_DEL:
	              $title = ADDRESS_LAN_IP_DEL_TITLE;
	              break;
	            case ADDRESS_DMZ_IP_ADD:
	              $title = ADDRESS_DMZ_IP_ADD_TITLE;
	              break;
	            case ADDRESS_DMZ_IP_EDIT:
	              $title = ADDRESS_DMZ_IP_EDIT_TITLE;
	              break;
	            case ADDRESS_DMZ_IP_DEL:
	              $title = ADDRESS_DMZ_IP_DEL_TITLE;
	              break;
	            case ADDRESS_LAN_GROUP_ADD:
	              $title = ADDRESS_LAN_GROUP_ADD_TITLE;
	              break;
	            case ADDRESS_LAN_GROUP_EDIT:
	              $title = ADDRESS_LAN_GROUP_EDIT_TITLE;
	              break;
	            case ADDRESS_LAN_GROUP_DEL:
	              $title = ADDRESS_LAN_GROUP_DEL_TITLE;
	              break;
	            case ADDRESS_DMZ_GROUP_ADD:
	              $title = ADDRESS_DMZ_GROUP_ADD_TITLE;
	              break;
	            case ADDRESS_DMZ_GROUP_EDIT:
	              $title = ADDRESS_DMZ_GROUP_EDIT_TITLE;
	              break;
	            case ADDRESS_DMZ_GROUP_DEL:
	              $title = ADDRESS_DMZ_GROUP_DEL_TITLE;
	              break;
	            case ADDRESS_WAN_IP_ADD:
	              $title = ADDRESS_WAN_IP_ADD_TITLE;
	              break;
	            case ADDRESS_WAN_IP_EDIT:
	              $title = ADDRESS_WAN_IP_EDIT_TITLE;
	              break;
	            case ADDRESS_WAN_IP_DEL:
	              $title = ADDRESS_WAN_IP_DEL_TITLE;
	              break;
	            case ADDRESS_WAN_GROUP_ADD:
	              $title = ADDRESS_WAN_GROUP_ADD_TITLE;
	              break;
	            case ADDRESS_WAN_GROUP_EDIT:
	              $title = ADDRESS_WAN_GROUP_EDIT_TITLE;
	              break;
	            case ADDRESS_WAN_GROUP_DEL:
	              $title = ADDRESS_WAN_GROUP_DEL_TITLE;
	              break;
	          } 
	          break;
	        case OBJECTS_ADDRESS_v6:
	          switch ($thing) {
	            case ADDRESS_LAN_IP_ADD_v6:
	              $title = ADDRESS_LAN_IP_ADD_TITLE;
	              break;
	            case ADDRESS_LAN_IP_EDIT_v6:
	              $title = ADDRESS_LAN_IP_EDIT_TITLE;
	              break;
	            case ADDRESS_LAN_IP_DEL_v6:
	              $title = ADDRESS_LAN_IP_DEL_TITLE;
	              break;
	            case ADDRESS_DMZ_IP_ADD_v6:
	              $title = ADDRESS_DMZ_IP_ADD_TITLE;
	              break;
	            case ADDRESS_DMZ_IP_EDIT_v6:
	              $title = ADDRESS_DMZ_IP_EDIT_TITLE;
	              break;
	            case ADDRESS_DMZ_IP_DEL_v6:
	              $title = ADDRESS_DMZ_IP_DEL_TITLE;
	              break;
	            case ADDRESS_LAN_GROUP_ADD_v6:
	              $title = ADDRESS_LAN_GROUP_ADD_TITLE;
	              break;
	            case ADDRESS_LAN_GROUP_EDIT_v6:
	              $title = ADDRESS_LAN_GROUP_EDIT_TITLE;
	              break;
	            case ADDRESS_LAN_GROUP_DEL_v6:
	              $title = ADDRESS_LAN_GROUP_DEL_TITLE;
	              break;
	            case ADDRESS_DMZ_GROUP_ADD_v6:
	              $title = ADDRESS_DMZ_GROUP_ADD_TITLE;
	              break;
	            case ADDRESS_DMZ_GROUP_EDIT_v6:
	              $title = ADDRESS_DMZ_GROUP_EDIT_TITLE;
	              break;
	            case ADDRESS_DMZ_GROUP_DEL_v6:
	              $title = ADDRESS_DMZ_GROUP_DEL_TITLE;
	              break;
	            case ADDRESS_WAN_IP_ADD_v6:
	              $title = ADDRESS_WAN_IP_ADD_TITLE;
	              break;
	            case ADDRESS_WAN_IP_EDIT_v6:
	              $title = ADDRESS_WAN_IP_EDIT_TITLE;
	              break;
	            case ADDRESS_WAN_IP_DEL_v6:
	              $title = ADDRESS_WAN_IP_DEL_TITLE;
	              break;
	            case ADDRESS_WAN_GROUP_ADD_v6:
	              $title = ADDRESS_WAN_GROUP_ADD_TITLE;
	              break;
	            case ADDRESS_WAN_GROUP_EDIT_v6:
	              $title = ADDRESS_WAN_GROUP_EDIT_TITLE;
	              break;
	            case ADDRESS_WAN_GROUP_DEL_v6:
	              $title = ADDRESS_WAN_GROUP_DEL_TITLE;
	              break;
	          } 
	          break;
	        case OBJECTS_SERVICES:
	          switch ($thing) {
	            case SERVICES_CUSTOM_DEFINE_ADD:
	              $title = SERVICES_CUSTOM_DEFINE_ADD_TITLE;
	              break;
	            case SERVICES_CUSTOM_DEFINE_EDIT:
	              $title = SERVICES_CUSTOM_DEFINE_EDIT_TITLE;
	              break;
	            case SERVICES_CUSTOM_DEFINE_DEL:
	              $title = SERVICES_CUSTOM_DEFINE_DEL_TITLE;
	              break;
	            case SERVICES_GROUP_ADD:
	              $title = SERVICES_GROUP_ADD_TITLE;
	              break;
	            case SERVICES_GROUP_EDIT:
	              $title = SERVICES_GROUP_EDIT_TITLE;
	              break;
	            case SERVICES_GROUP_DEL:
	              $title = SERVICES_GROUP_DEL_TITLE;
	              break;
	          } 
	          break;
	        case OBJECTS_QOS:
	          switch ($thing) {
	            case QOS_MODE:
	              break;
	            case QOS_SETUP_ADD:
	              $title = QOS_SETUP_ADD_TITLE;
	              break;
	            case QOS_SETUP_EDIT:
	              $title = QOS_SETUP_EDIT_TITLE;
	              break;
	            case QOS_SETUP_DEL:
	              $title = QOS_SETUP_DEL_TITLE;
	              break;
	          } 
	          break;
	      	case OBJECTS_SCHEDULE:
	          switch ($thing) {
	            case SCHEDULE_ADD:
	              $title = SCHEDULE_ADD_TITLE;
	              break;
	            case SCHEDULE_EDIT:
	              $title = SCHEDULE_EDIT_TITLE;
	              break;
	            case SCHEDULE_DEL:
	              $title = SCHEDULE_DEL_TITLE;
	              break;
	          } 
	          break;
	        case OBJECTS_APPLICATION:
	          switch ($thing) {
	            case APPLICATION_BLOCKING_ADD:
	              $title = APPLICATION_BLOCKING_ADD_TITLE;
	              break;
	            case APPLICATION_BLOCKING_EDIT:
	              $title = APPLICATION_BLOCKING_EDIT_TITLE;
	              break;
	            case APPLICATION_BLOCKING_DEL:
	              $title = APPLICATION_BLOCKING_DEL_TITLE;
	              break;
	          } 
	          break;
	        case OBJECTS_URL:
	          switch ($thing) {
	            case URL_BLOCKING_ADD:
	              $title = URL_BLOCKING_ADD_TITLE;
	              break;
	            case URL_BLOCKING_EDIT:
	              $title = URL_BLOCKING_EDIT_TITLE;
	              break;
	            case URL_BLOCKING_DEL:
	              $title = URL_BLOCKING_DEL_TITLE;
	              break;
	          } 
	          break;
	        case OBJECTS_VIRTUAL_SERVER:
	          switch ($thing) {
	            case VIRTUAL_IPMAP_ADD:
	              $title = VIRTUAL_IPMAP_ADD_TITLE;
	              break;
	            case VIRTUAL_IPMAP_EDIT:
	              $title = VIRTUAL_IPMAP_EDIT_TITLE;
	              break;
	            case VIRTUAL_IPMAP_DEL:
	              $title = VIRTUAL_IPMAP_DEL_TITLE;
	              break;
	            case VIRTUAL_PORTMAP_ADD:
	              $title = VIRTUAL_PORTMAP_ADD_TITLE;
	              break;
	            case VIRTUAL_PORTMAP_EDIT:
	              $title = VIRTUAL_PORTMAP_EDIT_TITLE;
	              break;
	            case VIRTUAL_PORTMAP_DEL:
	              $title = VIRTUAL_PORTMAP_DEL_TITLE;
	              break;
	            case VIRTUAL_MAPIP_ADD:
	              $title = VIRTUAL_MAPIP_ADD_TITLE;
	              break;
	            case VIRTUAL_MAPIP_EDIT:
	              $title = VIRTUAL_MAPIP_EDIT_TITLE;
	              break;
	            case VIRTUAL_MAPIP_DEL:
	              $title = VIRTUAL_MAPIP_DEL_TITLE;
	              break;
	            case VIRTUAL_SLB_ADD:
								$title = VIRTUAL_SLB_ADD_TITLE;
								break;
							case VIRTUAL_SLB_EDIT:
								$title = VIRTUAL_SLB_EDIT_TITLE;
								break;
							case VIRTUAL_SLB_DEL:
								$title = VIRTUAL_SLB_DEL_TITLE;
								break;
	          } 
	          break;
	        case OBJECTS_AUTHENTICATION:
	          switch ($thing) {
	            case AUTHENTICATION_SETUP_ALL:
	              $title = AUTHENTICATION_SETUP_ALL_TITLE;
	              break;
	            case AUTHENTICATION_USER_ADD:
	              $title = AUTHENTICATION_USER_ADD_TITLE;
	              break;
	            case AUTHENTICATION_USER_EDIT:
	              $title = AUTHENTICATION_USER_EDIT_TITLE;
	              break;
	            case AUTHENTICATION_USER_DEL:
	              $title = AUTHENTICATION_USER_DEL_TITLE;
	              break;
	            case AUTHENTICATION_GROUP_ADD:
	              $title = AUTHENTICATION_GROUP_ADD_TITLE;
	              break;
	            case AUTHENTICATION_GROUP_EDIT:
	              $title = AUTHENTICATION_GROUP_EDIT_TITLE;
	              break;
	            case AUTHENTICATION_GROUP_DEL:
	              $title = AUTHENTICATION_GROUP_DEL_TITLE;
	              break;
	            case AUTHENTICATION_SETUP_OTHER:
	              $title = AUTHENTICATION_SETUP_OTHER_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 5:
	      switch ($subtype) {
	        case SERVICES_DHCP:
	          switch ($thing) {
	            case DHCP_LAN:
	              $title = DHCP_LAN_TITLE;
	              break;
	            case DHCP_DMZ:
	              $title = DHCP_DMZ_TITLE;
	              break;
	            case DHCPHOST_ADD:
	              $title = DHCPHOST_ADD_TITLE;
	              break;
	            case DHCPHOST_EDIT:
	              $title = DHCPHOST_EDIT_TITLE;
	              break;
	            case DHCPHOST_DEL:
	              $title = DHCPHOST_DEL_TITLE;
	              break;
	            case DHCP_LAN01:
	              $title = DHCP_LAN01_TITLE;
	              break;
	            case DHCP_LAN02:
	              $title = DHCP_LAN02_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_DNSMASQ:
	          switch ($thing) {
	            case DNSMASQ_SETUP:
	              $title = DNSMASQ_SETUP_TITLE;
	              break;
	            case DNSMASQ_ROUTE_ADD:
	              $title = DNSMASQ_ROUTE_ADD_TITLE;
	              break;
	            case DNSMASQ_ROUTE_EDIT:
	              $title = DNSMASQ_ROUTE_EDIT_TITLE;
	              break;
	            case DNSMASQ_ROUTE_DEL:
	              $title = DNSMASQ_ROUTE_DEL_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_DNS:
	          switch ($thing) {
	            case DNS_SERVER_ADD:
	              $title = DNS_SERVER_ADD_TITLE;
	              break;
	             case DNS_SERVER_EDIT:
	              $title = DNS_SERVER_EDIT_TITLE;
	              break;
	             case DNS_SERVER_EDIT_2:
	              $title = DNS_SERVER_EDIT_2_TITLE;
	              break;
	             case DNS_SERVER_DEL:
	              $title = DNS_SERVER_DEL_TITLE;
	              break;
	             case DNS_SERVER_DNSREC_EDIT:
	              $title = DNS_SERVER_DNSREC_EDIT_TITLE;
	              break;
	             case SLAVE_ADD:
	              $title = SLAVE_ADD_TITLE;
	              break;
	             case SLAVE_EDIT:
	              $title = SLAVE_EDIT_TITLE;
	              break;
	             case SLAVE_DEL:
	              $title = SLAVE_DEL_TITLE;
	              break;
					case VIEW_ADD:
	              $title = VIEW_ADD_TITLE;
	              break;
	             case VIEW_EDIT:
	              $title = VIEW_EDIT_TITLE;
	              break;
	             case VIEW_DEL:
	              $title = VIEW_DEL_TITLE;
	              break;
					case WAN_RECURSION_EDIT:
	              $title = WAN_RECURSION_EDIT_TITLE;
	              break;
					case WAN_TRANSFER_EDIT:
	              $title = WAN_TRANSFER_EDIT_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_DDNS:
	          switch ($thing) {
	            case DDNS_SERVER_ADD:
	              $title = DDNS_SERVER_ADD_TITLE;
	              break;
	            case DDNS_SERVER_EDIT:
	              $title = DDNS_SERVER_EDIT_TITLE;
	              break;
	            case DDNS_SERVER_DEL:
	              $title = DDNS_SERVER_DEL_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_WPROXY:
	          switch ($thing) {
	            case WEB_PRXOY_SETUP:
	              $title = WEB_PRXOY_SETUP_TITLE;
	              break;
	            case WEB_EXCLUDE_ADD:
	              $title = WEB_EXCLUDE_ADD_TITLE;
	              break;
	           case WEB_EXCLUDE_DELETE:
	              $title = WEB_EXCLUDE_DELETE_TITLE;
	              break;
	           case WEB_EXCLUDE_SEARCH:
	            	$title = WEB_EXCLUDE_SEARCH_TITLE;
	              break;
	           case WEB_EXCLUDE_ADDIP:
	              $title = WEB_EXCLUDE_ADD_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_FTPPROXY:
	          switch ($thing) {
	            case FTP_PRXOY_SETUP:
	              $title = MSN_PRXOY_SETUP_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_MSNPROXY:
	          switch ($thing) {
	            case MSN_PRXOY_SETUP:
	              $title = MSN_PRXOY_SETUP_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_SKYPEPROXY:
	          switch ($thing) {
	            case SKYPE_PRXOY_SETUP:
	              $title = SKYPE_PRXOY_SETUP_TITLE;
	              break;
	          } 
	          break;
	        case SERVICES_QQ:
	          switch ($thing) {
	            case QQ_SETUP:
	              $title = QQ_SETUP_TITLE;
	              break;
	            case QQ_ID_ADD:
	              $title = QQ_ID_ADD_TITLE;
	              break;
	            case QQ_ID_EDIT:
	              $title = QQ_ID_EDIT_TITLE;
	              break;
	            case QQ_ID_DEL:
	              $title = QQ_ID_DEL_TITLE;
	              break;
	          }
	          break;
	        case SERVICES_CLAMAV:
	          switch ($thing) {
	            case CLAMAV_ENGINE_CLEAN:
	              break;
	            case CLAMAV_ENGINE_ALERT:
	              $title = CLAMAV_ENGINE_ALERT_TITLE;
	              break;
	            case CLAMAV_ENGINE_UPDATE:
	              break;
	          } 
	          break;
	       case SERVICES_SYSLOGD:
	          switch ($thing) {
	            case REMOTE_CONNECT_SETUP:
	              $title = REMOTE_CONNECT_SETUP_TITLE;
	              break;
	          }
	          break;
	       case SERVICES_HA:
	          switch ($thing) {
	            case SERVICES_HA_SAVE:
	              $title = SERVICES_HA_SAVE_TITLE;
	              break;
	          }
	          break;
	      } 
	      break;
	    case 6:
	      switch ($subtype) {
	        case MS_GATEWAY:
	          switch ($thing) {
	            case GATEWAY_SETUP:
	              $title = GATEWAY_SETUP_TITLE;
	              break;
	            case GATEWAY_ADD:
	              $title = GATEWAY_ADD_TITLE;
	              break;
	            case GATEWAY_EDIT:
	              $title = GATEWAY_EDIT_TITLE;
	              break;
	            case GATEWAY_DEL:
	              $title = GATEWAY_DEL_TITLE;
	              break;
	          } 
	          break;
	        case MS_PROXY:
	          switch ($thing) {
	            case PROXY_SERVICE_SETUP:
	              $title = PROXY_SERVICE_SETUP_TITLE;
	              break;
	            case PROXY_SERVICE_SETUP_none_CLAMV_SPAM:
	              $title = PROXY_SERVICE_SETUP_none_CLAMV_SPAM_TITLE;
	              break;
	            case PROXY_FLOW_BLOCK:
	              $title = PROXY_FLOW_BLOCK_TITLE;
	              break;
	            case PROXY_GRAY_IP_REVERSE:
	              $title = PROXY_GRAY_IP_REVERSE_TITLE;
	              break;
	          } 
	          break;
	        case MS_ANTIVIRUS:
	          switch ($thing) {
	            case ANTIVIRUS_SETUP:
	              $title = ANTIVIRUS_SETUP_TITLE;
	              break;
	            case ANTIVIRUS_SEARCH:
	              $title = ANTIVIRUS_SEARCH_TITLE;
	              break;
	          } 
	          break;
	        case MS_ANTISPAM:
	          switch ($thing) {
	            case ANTISPAM_SETUP:
	              $title = ANTISPAM_SETUP_TITLE;
	              break;
	            case ANTISPAM_SPL_SEARCH:
	              $title = ANTISPAM_SPL_SEARCH_TITLE;
	              break;
	            case ANTISPAM_DEL_SEARCH:
	              $title = ANTISPAM_DEL_SEARCH_TITLE;
	              break;
	            case ANTISPAM_LISTSET:
	              $title = ANTISPAM_LISTSET_TITLE;
	              break;
	            case ANTISPAM_LEARN:
	              $title = ANTISPAM_LEARN_TITLE;
	              break;
	            case ANTISPAM_PSPAM_ADD:
	              $title = ANTISPAM_PSPAM_ADD_TITLE;
	              break;
	            case ANTISPAM_PSPAM_EDIT:
	              $title = ANTISPAM_PSPAM_EDIT_TITLE;
	              break;
	            case ANTISPAM_PSPAM_DEL:
	              $title = ANTISPAM_PSPAM_DEL_TITLE;
	              break;
	            case ANTISPAM_SYSTEM_BLACKANDWHITE:
	              $title = ANTISPAM_SYSTEM_BLACKANDWHITE_TITLE;
	              break;
	          } 
	          break;
	        case MS_MAILLOGS:
	          switch ($thing) {
	            case MAILLOGS_SETUP:
	              $title = MAILLOGS_SETUP_TITLE;
	              break;
	             case MAILLOGS_SEARCH:
	              $title = MAILLOGS_SEARCH_TITLE;
	              break;
	          } 
	          break;
	        case MS_FILTER:
	        	switch ($thing) {
	        		case MS_FILTER_ADD:
	        			$title = MS_FILTER_ADD_TITLE;
	        			break;
	        		case MS_FILTER_EDIT:
	        			$title = MS_FILTER_EDIT_TITLE;
	        			break;
	        		case MS_FILTER_DEL:
	        			$title = MS_FILTER_DEL_TITLE;
	        			break;
	        	}
	        	break;
	      } 
	      break;
	    case 7:
	      switch ($subtype) {
	        case RECORDER_WEB:
	          switch ($thing) {
	            case WEB_RECORDER_TODAY:
	              $title = WEB_RECORDER_TODAY_TITLE;
	              break;
	            case WEB_RECORDER_OLD:
	              $title = WEB_RECORDER_OLD_TITLE;
	              break;
	          } 
	          break;
	        case RECORDER_FTP:
	          switch ($thing) {
	            case FTP_RECORDER_TODAY:
	              $title = FTP_RECORDER_TODAY_TITLE;
	              break;
	            case FTP_RECORDER_OLD:
	              $title = FTP_RECORDER_OLD_TITLE;
	              break;
	          } 
	          break;
	        case RECORDER_MSN:
	          switch ($thing) {
	          	case MSN_RECORDER_SETUP:
	            	$title = MSN_RECORDER_SETUP_TITLE;
	            	break;
	            case MSN_RECORDER_TODAY:
	              $title = MSN_RECORDER_TODAY_TITLE;
	              break;
	            case MSN_RECORDER_OLD:
	              $title = MSN_RECORDER_OLD_TITLE;
	              break;
	            case MSN_RECORDER_CONTACT:
	              $title = MSN_RECORDER_CONTACT_TITLE;
	              break;
	          } 
	          break;
	        case RECORDER_IM:
	          switch ($thing) {
	            case IM_RECORDER_TODAY:
	              $title = IM_RECORDER_TODAY_TITLE;
	              break;
	            case IM_RECORDER_OLD:
	              $title = IM_RECORDER_OLD_TITLE;
	              break;
	          } 
	          break;
	        case RECORDER_QQLOG:
	          switch ($thing) {
	            case QQLOG_BLOCK:
	              $title = QQLOG_BLOCK_TITLE;
	              break;
	            case QQLOG_CHAT:
	              $title = QQLOG_CHAT_TITLE;
	              break;
	          } 
	          break;
	        case RECORDER_MAILREC:
	          switch ($thing) {
	            case MAILREC_GATEWAY_LOG:
	              $title = MAILREC_GATEWAY_LOG_TITLE;
	              break;
	            case MAILREC_PROXY_LOG:
	              $title = MAILREC_PROXY_LOG_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 8:
	      switch ($subtype) {
	        case VPN_IPSEC_TUNNEL:
	          switch ($thing) {
	            case IPSEC_TUNNEL_ADD:
	              $title = IPSEC_TUNNEL_ADD_TITLE;
	              break;
	            case IPSEC_TUNNEL_EDIT:
	              $title = IPSEC_TUNNEL_EDIT_TITLE;
	              break;
	            case IPSEC_TUNNEL_DEL:
	              $title = IPSEC_TUNNEL_DEL_TITLE;
	              break;
							case IPSEC_TUNNEL_PAUSE:
								$title = IPSEC_TUNNEL_PAUSE_TITLE;
								break;
							case IPSEC_TUNNEL_ENABLED:
							$title = IPSEC_TUNNEL_ENABLED_TITLE;
							break;
	          } 
	          break;
	        case VPN_PPTP_SERVER:
	          switch ($thing) {
	            case PPTP_SERVER_SETUP:
	              $title = PPTP_SERVER_SETUP_TITLE;
	              break;
	            case PPTP_SERVER_ADD:
	              $title = PPTP_SERVER_ADD_TITLE;
	              break;
	            case PPTP_SERVER_EDIT:
	              $title = PPTP_SERVER_EDIT_TITLE;
	              break;
	            case PPTP_SERVER_DEL:
	              $title = PPTP_SERVER_DEL_TITLE;
	              break;
	            case PPTP_SERVER_ENABLED:
	              $title = PPTP_SERVER_DEL_TITLE;
	              break;
	            case PPTP_SERVER_PAUSE:
	              $title = PPTP_SERVER_DEL_TITLE;
	              break;
	          } 
	          break;
	        case VPN_PPTP_CLIENT:
	          switch ($thing) {
	            case PPTP_CLIENT_ADD:
	              $title = PPTP_CLIENT_ADD_TITLE;
	              break;
	            case PPTP_CLIENT_EDIT:
	              $title = PPTP_CLIENT_EDIT_TITLE;
	              break;
	            case PPTP_CLIENT_DEL:
	              $title = PPTP_CLIENT_DEL_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 9:
	      switch ($subtype) {
	        case TOOLS_CONNECT_TEST:
	          switch ($thing) {
	            case CONNECT_TEST_PING:
	              $title = CONNECT_TEST_PING_TITLE;
	              break;
	            case CONNECT_TEST_TRACEROUTE:
	              $title = CONNECT_TEST_TRACEROUTE_TITLE;
	              break;
	            case CONNECT_TEST_DNS:
	              $title = CONNECT_TEST_DNS_TITLE;
	              break;
	            case CONNECT_TEST_SERVICE:
	              $title = CONNECT_TEST_SERVICE_TITLE;
	              break;
	          } 
	          break;
	        case TOOLS_TCPDUMP:
	          switch ($thing) {
	            case TCPDUMP_SCHEDULE_ADD:
	              $title = TCPDUMP_SCHEDULE_ADD_TITLE;
	              break;
	            case TCPDUMP_SCHEDULE_EDIT:
	              $title = TCPDUMP_SCHEDULE_EDIT_TITLE;
	              break;
	            case TCPDUMP_SCHEDULE_DELETE:
	              $title = TCPDUMP_SCHEDULE_DELETE_TITLE;
	              break;
	            case TCPDUMP_HISTORY_DELETE:
	              $title = TCPDUMP_HISTORY_DELETE_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	  	case 10:
	      switch ($subtype) {
	        case LOGS_SYSTEM_LOG:
	          switch ($thing) {
	            case SYSTEM_LOG:
	              $title = SYSTEM_LOG_TITLE;
	              break;
	          } 
	          break;
	        case LOGS_LOGIN_EVENT:
	          switch ($thing) {
	            case LOGIN_EVENT:
	              $title = LOGIN_EVENT_TITLE;
	              break;
	            case LOGIN_EVENT_SEARCH:
	              $title = LOGIN_EVENT_SEARCH_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 11:
	      switch ($subtype) {
	        case STATUS_PERFORMANCE:
	          switch ($thing) {
	            case PERFORMANCE_SYS_STATUS:
	              $title = PERFORMANCE_SYS_STATUS_TITLE;
	              break;
	            case PERFORMANCE_INTERFACE_FLOW:
	              $title = PERFORMANCE_INTERFACE_FLOW_TITLE;
	              break;
	            case PERFORMANCE_HISTORY:
	              $title = PERFORMANCE_HISTORY_TITLE;
	              break;
	            case PERFORMANCE_CMP:
	              $title = PERFORMANCE_CMP_TITLE;
	              break;
	          } 
	          break;
	        case STATUS_CONNECTION_TRACK:
	          switch ($thing) {
	            case CONNECTION_TRACK:
	              $title = CONNECTION_TRACK_TITLE;
	              break;
	          } 
	          break;
	        case STATUS_USER_FLOW:
	          switch ($thing) {
	            case USER_FLOW_TOP10LIST:
	              $title = USER_FLOW_TOP10LIST_TITLE;
	              break;
	            case USER_FLOW_TOP10GRAPH:
	              $title = USER_FLOW_TOP10GRAPH_TITLE;
	              break;
	          } 
	          break;
	      } 
	      break;
	    case 12:
	    	switch ($subtype) {
	        case SYSTEM_LOGIN:
	          switch ($thing) {
	            case LOGIN_OK:
	              $title = LOGIN_OK_TITLE;
	              break;
	          } 
	          break;
	         case SYSTEM_LOGOUT:
	          switch ($thing) {
	            case LOGOUT_OK:
	              $title = LOGOUT_OK_TITLE;
	              break;
	          } 
	          break;
				}
				break;
			case 13:
	    	switch ($subtype) {
	        case SSL_VPN_SETU:
	          switch ($thing) {
	            case SSLVPN_SETUP:
	              $title = SSLVPN_SETUP_TITLE;
	              break;
	            case CLIENT_CA_LIST:
	              $title = CLIENT_CA_LIST_TITLE;
	              break;
	          } 
	          break;
				}
				break;
				case 14:
	    	switch ($subtype) {
	        case BOTNET_SETTING:
	          switch ($thing) {
	            case BOTNET_ADD:
	              $title = BOTNET_ADD_TITLE;
	              break;
	            case BOTNET_EDIT:
	              $title = BOTNET_EDIT_TITLE;
	              break;
	            case BOTNET_DELETE:
	              $title = BOTNET_DELETE_TITLE;
	              break;
	            case BOTNET_OPERATION:
	             	$title = BOTNET_OPERATION_TITLE;
	              break;
	            case BOTNET_SNIFFER:
	             	$title = BOTNET_SNIFFER_TITLE;
	              break;
	          }
	          break;
	        case BOTNET_SEARCH_LOG:
	          switch ($thing) {
	            case BOTNET_SEARCH:
	              $title = BOTNET_SEARCH_TITLE;
	              break;
	          } 
	          break;
				}
				break;
				case 15:
				switch ($subtype) {
					case ANOMALY_IP_SETTING:
						switch($thing) {
							case ANOMALY_BLOCK_SAVE:
								$title = ANOMALY_BLOCK_SAVE_TITLE;
								break;
							case ANOMALY_RECORD_SAVE:
								$title = ANOMALY_RECORD_SAVE_TITLE;
								break;
							case ANOMALY_NOTIFY_EDIT:
								$title = ANOMALY_NOTIFY_EDIT_TITLE;
								break;
							case ANOMALY_EXCEPT_ADD:
								$title = ANOMALY_EXCEPT_ADD_TITLE;
								break;
							case ANOMALY_EXCEPT_EDIT:
								$title = ANOMALY_EXCEPT_EDIT_TITLE;
								break;
							case ANOMALY_EXCEPT_DEL:
								$title = ANOMALY_EXCEPT_DEL_TITLE;
								break;
						}
						break;
					case ADVANCED_SETTING:
						switch ($thing) {
							case ADVANCED_SNMP_ADD:
								$title = ADVANCED_SNMP_ADD_TITLE;
								break;
							case ADVANCED_DEFENSE_ADD:
								$title = ADVANCED_DEFENSE_ADD_TITLE;
								break;
							case ADVANCED_SNMP_EDIT:
								$title = ADVANCED_SNMP_EDIT_TITLE;
								break;
							case ADVANCED_DEFENSE_EDIT:
								$title = ADVANCED_DEFENSE_EDIT_TITLE;
								break;
							case ADVANCED_DELETE:
								$title = ADVANCED_DEL_TITLE;
								break;
						}
						break;
					case ADVANCED_NETWORKSTATE:
						switch ($thing) {
							case ADVANCED_NETWORK_REFRESH:
								$title = ADVANCED_NETWORK_REFRESH_TITLE;
								break;
							case ADVANCED_NETWORK_SEARCH:
								$title = ADVANCED_NETWORK_SEARCH_TITLE;
								break;
							case ADVANCED_NETWORK_LOCK:
								$title = ADVANCED_NETWORK_LOCK_TITLE;
								break;
							case ADVANCED_NETWORK_CHANGE:
								$title = ADVANCED_NETWORK_CHANGE_TITLE;
								break;
							case ADVANCED_NETWORK_UPHOLE:
								$title = ADVANCED_NETWORK_UPHOLE_TITLE;
								break;
							case ADVANCED_NETWORK_INTERLOCK:
								$title = ADVANCED_NETWORK_INTERLOCK_TITLE;
								break;
						}
					case ADVANCED_BIND_LIST:
						switch ($thing) {
							case ADVANCED_BIND_LIST_ADD:
								$title = ADVANCED_BIND_LIST_ADD_TITLE;
								break;
							case ADVANCED_BIND_LIST_EDIT:
								$title = ADVANCED_BIND_LIST_EDIT_TITLE;
								break;
							case ADVANCED_BIND_LIST_DEL:
								$title = ADVANCED_BIND_LIST_DEL_TITLE;
								break;
						}
						break;
					case ADVANCED_PROTECH:
						switch ($thing) {
							case ADVANCED_ARP_PROTECH_EDIT:
								$title =  ADVANCED_ARP_PROTECH_EDIT_TITLE;
								break;
							case ADVANCED_MAC_PROTECH_EDIT:
								$title =  ADVANCED_MAC_PROTECH_EDIT_TITLE;
								break;
							case ADVANCED_IP_PROTECH_EDIT:
								$title =  ADVANCED_IP_PROTECH_EDIT_TITLE;
								break;
						}
						break;
					case ADVANCED_PROTECT:
						switch ($thing) {
							case ADVANCED_PROTECT_ARP_SEARCH:
								$title = ADVANCED_PROTECT_ARP_SEARCH_TITLE;
								break;
							case ADVANCED_PROTECT_MAC_REFRESH:
								$title = ADVANCED_PROTECT_MAC_REFRESH_TITLE;
								break;
							case ADVANCED_PROTECT_MAC_DEL:
								$title = ADVANCED_PROTECT_MAC_DEL_TITLE;
								break;
						}
					case ADVANCED_DEFENSE:
						switch($thing) {
							case ADVANCED_DEFENSE_SAVE:
								$title = ADVANCED_DEFENSE_SAVE_TITLE;
								break;
						}
						break;
					case INTRANET_PROTECT:
						switch($thing) {
							case INTRANET_PROTECT_SAVE:
								$title = INTRANET_PROTECT_SAVE_TITLE;
								break;
						}
						break;					
				}
				break;
	  } 
	  $title = $Var[$title];
	  if (!$title) {
	    return $parameters[0];
	  } else {
	    for ($i = 0; $i < count($parameters); $i++) {
	      $title = str_replace("{" . $i . "}", $parameters[$i], $title);
	    } 
	    return $title;
	  } 
	}
  
}
?>
