<?
class mysql   {
	
	var $host; //Host
	var $uname; //username
	var $pwd; //password
	var $dbname; //Database name
	var $mysql_link; //Connection link
	var $nrquery; //Number of performed queris
	var $total_time; //Total time for the exeqution of queries
	var $last_query_time; //Time for the exequtioin of the last query
	var $qlist; //an array containg all the queries executed by the object
	var $affected; //number of affected rows
	var $last_id; //THe last ID generated from an auto_increment INSERT QUERY
	
/* Constructor : create connection with DB */
	function mysql($dbname="") {
		$this->host = ":/tmp/mysql.sock";
		$this->uname = "root";
		$this->pwd = "l7fwmysql";
		$this->dbname = "imspector";
		if (!$this->mysql_link = @mysql_connect($this->host,$this->uname,$this->pwd)) die("Error : Could not connect to database!");
		mysql_select_db($this->dbname,$this->mysql_link);
		$this->total_time = 0;
		$this->last_query_time = 0;
	}
/* This function returns an array with all the results of a SELECT query */	
	function results($sql,$type=MYSQL_ASSOC) {
		$start = $this->getmicrotime();
		$r = mysql_query($sql,$this->mysql_link);
		$end = $this->getmicrotime();
		$query['sql'] = $sql;
		if ($r) {
			$this->last_query_time = $end - $start;
			$this->total_time += $this->last_query_time;
			$this->nrquery += 1;
			$query['time'] = $this->last_query_time;
			$this->qlist[] = $query;
			$nr = mysql_num_rows($r);
			if ($nr == 0) return 0; //return 0 if no results
			else {
				$nr = 0;
				while ($row = mysql_fetch_array($r,$type)) {
					reset($row);
					while (list($key, $val) = each($row)) {
						$ret[$nr][$key] = $val; 
					}
					$nr++;
				}
				return $ret;
			}
		} else return false;
	}
  
/* executes a query and return false on failure */	
	function query($sql) {
		$start = $this->getmicrotime();
		$q = @mysql_query($sql,$this->mysql_link);
		$end = $this->getmicrotime();
		$query['sql'] = $sql;
		if ($q) {
			$this->nrquery += 1;
			$this->last_query_time = $end - $start;
			$this->total_time += $this->last_query_time;
			$this->affected = mysql_affected_rows($this->mysql_link);
			$this->last_id = mysql_insert_id($this->mysql_link);
			$query['time'] = $this->last_query_time;
			$this->qlist[] = $query;
			return true;
		}
		else return  false;
	}
	
		function insert($table,$field,$value) {
               $pgc=get_magic_quotes_gpc();
                if(!is_array($field)) return 0;
                if(!is_array($value)) return 0;
	
                count($field)==count($value) or die(count($field) .":". count($value) );
               
                $sql="INSERT INTO $table ( ";
                for($i=1;$i<=count($field);$i++) {                      
                        $sql.=$field[$i-1];
                        if($i!=count($field)) $sql.=",";        
                } 
                $sql.=") values(";
                
                for($i=1;$i<=count($value);$i++) {
                		if(!$pgc) $value[$i-1]=mysql_escape_string($value[$i-1]);
                        $sql.="'".$value[$i-1]."'";
                        if($i!=count($value)) $sql.=",";        
                } 
                $sql.=")";				

                $this->query($sql);
        }
        
    function IsExistTable($table){		
    	
		$result = mysql_list_tables($this->dbname);
		
		while ($row = mysql_fetch_row($result)) {		
        		if($row[0]==$table) 	return true;
        	
   		}
   		return false;
	}
	
/* Close connection with database */
	function close() {
		mysql_close($this->mysql_link);
	}
	
/* return microtime */
	function getmicrotime(){ 
		list($usec, $sec) = explode(" ",microtime()); 
		return ((float)$usec + (float)$sec); 
	} 

}
?>