<?php

class tea_saemysql implements idb
{
	public $conn = null;
	public $config;

	function __construct($dbconfig)
	{
        $this->config = $dbconfig;
	}
	
	function connect()
	{
		$dbconfig = &$this->config;
		$this->conn  = new SaeMysql();
		if($dbconfig['dbname']){
			$this->conn->setAppname($dbconfig['dbname']);
			$this->conn->setAuth($dbconfig['dbuser'],$dbconfig['dbpass']);
		}
        if($dbconfig['charset']){
        	if($dbconfig['charset'] == 'utf-8') $dbconfig['charset'] = 'UTF8';
        	$this->conn->setCharset(strtoupper($dbconfig['charset']));
        }
	}
	
	function query($sql)
	{
		$cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
		if ($cmd === 'SELECT') {
			$res = $this->conn->getData($sql);
			return new mysqlrecord($res);
		}else{			
			$res = $this->conn->runSql($sql);
			if ($cmd === 'UPDATE' || $cmd === 'DELETE') {
				return $this->affected_rows();
			}
			if ($cmd === 'INSERT') {
				return $this->insert_id();
			}
		}
	}
	
	function affected_rows()
	{
		return $this->conn->affectedRows();
	}
	
	function insert_id()
	{
		return $this->conn->lastId();
	}
	
	function ping()
	{
	    if(!$this->conn) return false;
	    else return true;
	}
		
	function close()
	{
		$this->conn->closeDb();
	}
	
}


class mysqlrecord implements idbrecord 
{
	public $result;
	
	function __construct($result)
	{
		$this->result = $result;
	}

    function fetch()
    {
    	return mysql_fetch_assoc($this->result);
    }

    function fetchall()
    {
    	$data = array();
    	while($record = mysql_fetch_assoc($this->result))
    	{
    		$data[] = $record;
    	}
    	return $data;
    }
    
    function free()
    {
    	mysql_free_result($this->result);
    }
}
