<?php

class tea_pdo extends PDO
{
	
	function __construct($dbconfig)
	{
		$dsn=$dbconfig['dbtype'].":host=".$dbconfig['host'].";dbname=".$dbconfig['dbname'];
		try
		{
			if(isset($dbconfig['persistent']) and $dbconfig['persistent']){
				parent::__construct($dsn, $dbconfig['dbuser'],$dbconfig['dbpass'],array(ATTR_PERSISTENT=>true));
			}else{
			    parent::__construct($dsn, $dbconfig['dbuser'],$dbconfig['dbpass']);
			}
			if($dbconfig['charset']) parent::query('set names '.$dbconfig['charset']);
			$this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
		}
		catch (PDOException $e)
		{
			debug::error('PDO Exception',$e->__toString());
		}
	}
	
	
	function connect()
	{
	}
	
	
	public function query($sql)
	{
		parent::quote($sql);
		$res = parent::query($sql) or debug::error("SQL Error",implode(", ",$this->errorInfo())."<hr />$sql");
		return $res;
	}
	
	
	function insert_id()
	{
		return $this->lastInsertId();
	}
	
	
	function close()
	{
		unset($this);
	}
}