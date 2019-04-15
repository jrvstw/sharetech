<?
class Backup
{
	function get_query_setting($type)
	{
		switch($type)
		{
			case "mail":
				if(file_exists("/tmp/mount_postfix"))
				{//把σ本更砞﹚
					$cf = parse_ini_file("/tmp/mount_postfix");
				}
				else
				{//箇砞
					$cf = array(
						"YYYY_MM"				=> "",
						"db" 						=> "postfix",
						"MRC_DATA" 			=> "/HDD/MRC_DATA",
						"SEPARATE_DATA" => "/HDD/SEPARATE_DATA"
					);				
				}			
			break;

			case "web":
				if(file_exists("/tmp/mount_tinyproxy"))
				{//把σ本更砞﹚
					$cf = parse_ini_file("/tmp/mount_tinyproxy");
				}
				else
				{//箇砞
					$cf = array(
						"YYYY_MM"				=> "",
						"db" 						=> "tinyproxy",
						"WRC_DATA" 			=> "/HDD/WRC_DATA"
					);				
				}			
			break;					

			case "ftp":
				if(file_exists("/tmp/mount_ftpproxy"))
				{//把σ本更砞﹚
					$cf = parse_ini_file("/tmp/mount_ftpproxy");
				}
				else
				{//箇砞
					$cf = array(
						"YYYY_MM"				=> "",
						"db" 						=> "tinyproxy",
						"ftp_file_temp"	=> "/HDD/ftp_file_temp"
					);				
				}			
			break;					

			case "conntrack":
				if(file_exists("/tmp/mount_temporary"))
				{//把σ本更砞﹚
					$cf = parse_ini_file("/tmp/mount_temporary");
				}
				else
				{//箇砞
					$cf = array(
						"YYYY_MM"				=> "",
						"db" 						=> "temporary"
					);				
				}			
			break;					

			case "msn":
				if(file_exists("/tmp/mount_msnproxy"))
				{//把σ本更砞﹚
					$cf = parse_ini_file("/tmp/mount_msnproxy");
				}
				else
				{//箇砞
					$cf = array(
						"YYYY_MM"				=> "",
						"db" 						=> "msnproxy",
						"CFH3_msnproxy" => "/CFH3/msnproxy"
					);				
				}			
			break;
		}
		
		return $cf;	
	}
	
	function set_query_setting($type, $YYYYMM, $MOUNTPOINT, $tempDirectory)
	{
		switch($type)
		{
			case "mail":
				//exec("/bin/cp -R {$MOUNTPOINT}/postfix_{$YYYYMM} $tempDirectory/");
				exec("/PGRAM/rsync/bin/rsync -av {$MOUNTPOINT}/postfix_{$YYYYMM} $tempDirectory");
				exec("/bin/chown -R mysqld.mysqld $tempDirectory/postfix_{$YYYYMM}");
				exec("/bin/chmod 700 $tempDirectory/postfix_{$YYYYMM}");
				exec("/bin/chmod 660 $tempDirectory/postfix_{$YYYYMM}/*");
				exec("rm -rf /CFH3/mysqlDB/postfix_{$YYYYMM}");
				exec("ln -s {$tempDirectory}/postfix_{$YYYYMM} /CFH3/mysqlDB/postfix_{$YYYYMM}");
				
				$txt = "YYYY_MM = ".str_replace("_", "-", $YYYYMM)."\n";
				$txt.= "db = postfix_{$YYYYMM}\n";
				$txt.= "MRC_DATA = $MOUNTPOINT/MRC_DATA_{$YYYYMM}\n";
				$txt.= "SEPARATE_DATA = $MOUNTPOINT/SEPARATE_DATA_{$YYYYMM}\n";
			
				$fp = fopen("/tmp/mount_postfix", "w");
				fwrite($fp, $txt);
				fclose($fp);
			break;

			case "web":
				//exec("/bin/cp -R {$MOUNTPOINT}/tinyproxy_{$YYYYMM} $tempDirectory/");
				exec("/PGRAM/rsync/bin/rsync -av {$MOUNTPOINT}/tinyproxy_{$YYYYMM} $tempDirectory");
				exec("/bin/chown -R mysqld.mysqld $tempDirectory/tinyproxy_{$YYYYMM}");
				exec("/bin/chmod 700 $tempDirectory/tinyproxy_{$YYYYMM}");
				exec("/bin/chmod 660 $tempDirectory/tinyproxy_{$YYYYMM}/*");
				exec("rm -rf /CFH3/mysqlDB/tinyproxy_{$YYYYMM}");
				exec("ln -s {$tempDirectory}/tinyproxy_{$YYYYMM} /CFH3/mysqlDB/tinyproxy_{$YYYYMM}");
				
				$txt = "YYYY_MM = ".str_replace("_", "-", $YYYYMM)."\n";
				$txt.= "db = tinyproxy_{$YYYYMM}\n";
				$txt.= "WRC_DATA = $MOUNTPOINT/WRC_DATA_{$YYYYMM}\n";
			
				$fp = fopen("/tmp/mount_tinyproxy", "w");
				fwrite($fp, $txt);
				fclose($fp);
			break;					

			case "ftp":
				//exec("/bin/cp -R {$MOUNTPOINT}/ftpproxy_{$YYYYMM} $tempDirectory/");
				exec("/PGRAM/rsync/bin/rsync -av {$MOUNTPOINT}/ftpproxy_{$YYYYMM} $tempDirectory");
				exec("/bin/chown -R mysqld.mysqld $tempDirectory/ftpproxy_{$YYYYMM}");
				exec("/bin/chmod 700 $tempDirectory/ftpproxy_{$YYYYMM}");
				exec("/bin/chmod 660 $tempDirectory/ftpproxy_{$YYYYMM}/*");
				exec("rm -rf /CFH3/mysqlDB/ftpproxy_{$YYYYMM}");
				exec("ln -s {$tempDirectory}/ftpproxy_{$YYYYMM} /CFH3/mysqlDB/ftpproxy_{$YYYYMM}");
				
				$txt = "YYYY_MM = ".str_replace("_", "-", $YYYYMM)."\n";
				$txt.= "db = ftpproxy_{$YYYYMM}\n";
				$txt.= "ftp_file_temp = $MOUNTPOINT/ftp_file_temp_{$YYYYMM}\n";
			
				$fp = fopen("/tmp/mount_ftpproxy", "w");
				fwrite($fp, $txt);
				fclose($fp);
			break;					

			case "conntrack":
				//exec("/bin/cp -R {$MOUNTPOINT}/temporary_{$YYYYMM} $tempDirectory/");
				exec("/PGRAM/rsync/bin/rsync -av {$MOUNTPOINT}/temporary_{$YYYYMM} $tempDirectory");
				exec("/bin/chown -R mysqld.mysqld $tempDirectory/temporary_{$YYYYMM}");
				exec("/bin/chmod 700 $tempDirectory/temporary_{$YYYYMM}");
				exec("/bin/chmod 660 $tempDirectory/temporary_{$YYYYMM}/*");
				exec("rm -rf /CFH3/mysqlDB/temporary_{$YYYYMM}");
				exec("ln -s {$tempDirectory}/temporary_{$YYYYMM} /CFH3/mysqlDB/temporary_{$YYYYMM}");
				
				$txt = "YYYY_MM = ".str_replace("_", "-", $YYYYMM)."\n";
				$txt.= "db = temporary_{$YYYYMM}\n";
			
				$fp = fopen("/tmp/mount_temporary", "w");
				fwrite($fp, $txt);
				fclose($fp);
			break;					

			case "msn":
				exec("/PGRAM/rsync/bin/rsync -av {$MOUNTPOINT}/msnproxy_{$YYYYMM} $tempDirectory");
				exec("/bin/chown -R mysqld.mysqld $tempDirectory/msnproxy_{$YYYYMM}");
				exec("/bin/chmod 700 $tempDirectory/msnproxy_{$YYYYMM}");
				exec("/bin/chmod 660 $tempDirectory/msnproxy_{$YYYYMM}/*");
				exec("rm -rf /CFH3/mysqlDB/msnproxy_{$YYYYMM}");
				exec("ln -s {$tempDirectory}/msnproxy_{$YYYYMM} /CFH3/mysqlDB/msnproxy_{$YYYYMM}");
				
				$txt = "YYYY_MM = ".str_replace("_", "-", $YYYYMM)."\n";
				$txt.= "db = msnproxy_{$YYYYMM}\n";
				$txt.= "CFH3_msnproxy = $MOUNTPOINT/msnproxy_DATA_{$YYYYMM}\n";

				$fp = fopen("/tmp/mount_msnproxy", "w");
				fwrite($fp, $txt);
				fclose($fp);
			break;
		}
	}

	function testConnect($host, $folder, $username, $password)
	{
		$MOUNTPOINT = "/tmp/smbBK".time();
		mkdir($MOUNTPOINT, 0755);
		
		if($folder[0] == '/') {//Automatically remove root
			$folder = substr($folder, 1);
		}
		
		$cmd1 = "mount -t cifs -o username='{$username}',password='{$password}' '//{$host}/{$folder}' {$MOUNTPOINT}";
		$cmd2 = "mount -t smbfs -o username='{$username}',password='{$password}' '//{$host}/{$folder}' {$MOUNTPOINT}";	

		if($this->mount($MOUNTPOINT, $cmd1) || $this->mount($MOUNTPOINT, $cmd2)) {
			$result = true;
		} else {
			$result = false;
		}
				
		$this->umount($MOUNTPOINT);

		return $result;
	}

	function getImportList($host, $folder, $username, $password)
	{
		$MOUNTPOINT = "/tmp/smbBK".time();
		mkdir($MOUNTPOINT, 0755);
		
		if($folder[0] == '/') {//Automatically remove root
			$folder = substr($folder, 1);
		}

		$cmd1 = "mount -t cifs -o username='{$username}',password='{$password}' '//{$host}/{$folder}' {$MOUNTPOINT}";
		$cmd2 = "mount -t smbfs -o username='{$username}',password='{$password}' '//{$host}/{$folder}' {$MOUNTPOINT}";	

		if($this->mount($MOUNTPOINT, $cmd1) || $this->mount($MOUNTPOINT, $cmd2)) {
			$result = true;
		} else {
			$result = false;
		}
		
		$list = array();
		if($result == true)
		{
			exec("/bin/ls -l $MOUNTPOINT", $retA, $retC);
			if($retC == 0)
			{
				foreach($retA as $line)
				{
					unset($match);
					if(preg_match('/\s(tinyproxy|postfix|ftpproxy|temporary|msnproxy)_([0-9]{4}_[0-9]{2})$/', $line, $match)) {
						$list[$match[1]][] = $match[2];
					}
				}
			}
		}
		
		$this->umount($MOUNTPOINT);

		return $list;
	}
	
	function mount($MOUNTPOINT, $CMD)
	{	
		unset($ret);
		exec($CMD, $ret);
		if(count($ret) > 0) //no message is good
		{
			$this->sMountResult = "Unable to connect to remote host: Connection refused.(1)";
			$this->debugMsg("mount error (1)");
			$this->umount($MOUNTPOINT);
			return false;
		}

		unset($ret);
		exec("df $MOUNTPOINT --sync | tail -n 1", $ret);
		if(!preg_match('/\s+([0-9]{1,2})%.+'.str_replace('/', '\/', substr($MOUNTPOINT, 1)).'/', $ret[0]))
		{
			$this->sMountResult = "Unable to connect to remote host: Connection refused.(2)";
			$this->debugMsg("mount error (2)");
			$this->umount($MOUNTPOINT);
			return false;
		}
		
		unset($ret);
		exec("ls -a $MOUNTPOINT", $ret);
		if(count($ret) == 0)	//df check ok, but not real SUCCESS! (ls: /tmp/smbmount: Permission denied)
		{
			$this->sMountResult = "Permission denied.(3)";
			$this->debugMsg("mount error (3)");
			$this->umount($MOUNTPOINT);
			return false;
		}
		
		$testFile = "$MOUNTPOINT/testFile";
		if( !($file = @fopen($testFile, "w")) )	// test write
		{//糶舦ぃì
			$this->sMountResult = "Permission denied.(4)";
			$this->debugMsg("mount error (4)");
			$this->umount($MOUNTPOINT);
			return false;
		}
		fclose($file);
		@unlink($testFile);

		$this->debugMsg("mount {$MOUNTPOINT} OK!");

		return true;
	}

	function umount($MOUNTPOINT)
	{
		exec("umount $MOUNTPOINT"); 
		$this->debugMsg("umount $MOUNTPOINT");
		
		//浪琩既ヘ魁琌痷砆umount奔, Τ暗簿埃既ヘ魁笆
		exec("ls -l $MOUNTPOINT | wc -l", $ret);
		if($ret[0] == "1")
		{
			exec("rm -rf $MOUNTPOINT"); 
			$this->debugMsg("rmdir -rf $MOUNTPOINT");
		}
		else
		{
			$this->debugMsg("$MOUNTPOINT have not been removed. ($ret[0])");		
		}
	}

	function debugMsg($message)
	{
		$fp = fopen("/tmp/smbconnect", "a");
		fwrite($fp, date("Y-m-d H:i:s")." $message\n");
		fclose($fp);
	}
}
?>