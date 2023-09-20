<?php

class tea_mysql implements idb
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
		if(isset($dbconfig['persistent']) and $dbconfig['persistent'])
            $this->conn = mysql_pconnect($dbconfig['host'],$dbconfig['dbuser'],$dbconfig['dbpass']) or debug::error('Mysql Error',mysql_error());
        else
            $this->conn = mysql_connect($dbconfig['host'],$dbconfig['dbuser'],$dbconfig['dbpass']) or debug::error('Mysql Error',mysql_error());

        mysql_select_db($dbconfig['dbname'],$this->conn) or debug::error('Mysql Error',mysql_error($this->conn));
        if($dbconfig['charset']) mysql_query('set names '.$dbconfig['charset'],$this->conn) or debug::error('Mysql Error',mysql_error($this->conn));
	}
	
	function query($sql)
	{
		$res = mysql_query($sql,$this->conn) or debug::error('SQL Line Error',mysql_error($this->conn)."<hr/>$sql");
		if ($res) {
			$cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
			if ($cmd === 'SELECT') {
				return new mysqlrecord($res);
			} elseif ($cmd === 'UPDATE' || $cmd === 'DELETE') {
				return $this->affected_rows();
			} elseif ($cmd === 'INSERT') {
				return $this->insert_id();
			}
		}
	}
	
	function affected_rows()
	{
		return mysql_affected_rows($this->conn);
	}
	
	function insert_id()
	{
		return mysql_insert_id($this->conn);
	}
	
	function ping()
	{
	    if(!mysql_ping($this->conn)) return false;
	    else return true;
	}
	
	function close()
	{
		mysql_close($this->conn);
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
