<?php

class tea_mysqli extends mysqli implements idb
{
    public $conn = null;
    public $config;

    function __construct($dbconfig)
    {
        $this->config = $dbconfig;
        $this->conn = new mysqli($dbconfig['host'],$dbconfig['dbuser'],$dbconfig['dbpass'],$dbconfig['dbname']);
        if(mysqli_connect_errno())  debug::error('Mysqli Error',"Connect failed: %s\n".mysqli_connect_error());
        if($dbconfig['charset']) $this->conn->set_charset($dbconfig['charset']);
    }

    #[\ReturnTypeWillChange]
    function connect($host = NULL, $user = NULL, $password = NULL, $database = NULL, $port = NULL, $socket = NULL)
    {
        $dbconfig = &$this->config;
        $this->conn = new mysqli($dbconfig['host'],$dbconfig['dbuser'],$dbconfig['dbpass'],$dbconfig['dbname']);
        if(mysqli_connect_errno())  debug::error('Mysqli Error',"Connect failed: %s\n".mysqli_connect_error());
        if($dbconfig['charset']) $this->conn->set_charset($dbconfig['charset']);
    }

    #[\ReturnTypeWillChange]
    function query($sql, $resultmode = NULL)
    {
        $this->conn->real_escape_string($sql);
        $res = $this->conn->query($sql) or debug::error('SQL Line Error',$this->conn->error."<hr/>$sql");
        if(!$res) debug::error('SQL Line Error',$this->error."<hr/>$sql");
        if(strpos($sql,'session') === false){
            $GLOBALS['TEA_QUERY_NUMS'] += 1; //排除掉session操作数据库
        }
        if ($res) {
			$cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
			if ($cmd === 'SELECT') {
				return new mysqlirecord($res);
			} elseif ($cmd === 'UPDATE' || $cmd === 'DELETE') {
				return $this->conn->affected_rows;
			} elseif ($cmd === 'INSERT') {
				return $this->conn->insert_id;
			}else{
                return new mysqlirecord($res);
            }
		}
    }
    
    function insert_id()
    {
        return $this->conn->insert_id;
    }
    
}


class mysqlirecord implements idbrecord 
{
    public $result;
    
    function __construct($result)
    {
        $this->result = $result;
    }

    function fetch()
    {
        if(empty($this->result))
        {
            return false;
        }
        return $this->result->fetch_assoc();
    }

    function fetchall()
    {
        if(empty($this->result))
        {
            return false;
        }
        $data = array();
        while($record = $this->result->fetch_assoc())
        {
            $data[] = $record;
        }
        return $data;
    }
    
    function free()
    {
        $this->result->free_result();
    }
    
}