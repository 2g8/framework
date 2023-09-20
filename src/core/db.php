<?php

interface idb
{
	function connect();
	function query($sql);
	function insert_id();
	function close();
}

interface idbrecord
{
	function fetch();
	function fetchall();
}

class db
{
	protected $_db = null;
	protected $_db_apt = null;
	protected $_db_driver = array('tea_pdo','tea_mysql','tea_saemysql','tea_mysqli');

    protected $read_times = 0;
    protected $write_times = 0;

	
	function __construct($dbconfig)
	{
		$driver = $dbconfig['driver'] ? $dbconfig['driver'] : 'tea_pdo';
		if(!in_array($driver,$this->_db_driver))
		{
			debug::error('Database Driver Error',"Database Driver <b>$driver</b> not no support.");
		}
		$this->_db = load::classes('lib.db.'.$driver,TEA_PATH,$dbconfig);
		$this->_db->connect();
		$this->_db_apt = load::classes('lib.db.db_apt',TEA_PATH,$this);
	}
	
	function __init()
	{
	    $this->check_status();
	    $this->_db_apt->init();
	    $this->read_times = 0;
	    $this->write_times = 0;
	}
	
	function check_status()
	{
    	if(!$this->_db->ping())
        {
            $this->_db->close();
            $this->_db->connect();
        }
	}
	
	public final function query($sql)
	{
		$this->read_times +=1;
		//安全过滤
		sqlsafe::check($sql);
		return $this->_db->query($sql);
	}
	
	public function insert($data,$table)
	{
		$this->_db_apt->init();
		$this->_db_apt->from($table);
		$this->write_times +=1;
		return $this->_db_apt->insert($data);
	}
	
	public function replace($data,$table)
	{
		$field="";
        $values="";
        foreach($data as $key => $value)
        {
        	$value = str_replace("'","&#039;",$value);
            $field = $field."`$key`,";
            $values = $values."'$value',";
        }
        $field = substr($field,0,-1);
        $values = substr($values,0,-1);
		$this->write_times +=1;
		return $this->query("replace into $table ($field) values($values)");
	}
	
	public function delete($id,$table,$where='id')
	{
		if(func_num_args()<2) debug::error('db_apt param error','Delete must have 2 paramers ($id,$table) !');
		$this->_db_apt->init();
		$this->_db_apt->from($table);
		$this->write_times +=1;
		return $this->query("delete from $table where $where='$id'");
	}
	
	public function update($id,$data,$table,$where='id')
	{
		if(func_num_args()<3) debug::error('db_apt param error','Update must have 3 paramers ($id,$data,$table) !');
		$this->_db_apt->init();
		$this->_db_apt->from($table);
		$this->_db_apt->where("$where='$id'");
		$this->write_times +=1;
		return $this->_db_apt->update($data);
	}
	
	public function get($id,$table,$primary='id')
	{
		$this->_db_apt->init();
		$this->_db_apt->from($table);
		$this->_db_apt->where("$primary='$id'");
		return $this->_db_apt->getone();
	}
	
	function __call($method,$args=array())
	{
		return call_user_func_array(array($this->_db,$method),$args);
	}
}


class sqlsafe {
	protected static $checkcmd = array('SELECT', 'UPDATE', 'INSERT', 'REPLACE', 'DELETE');
	protected static $disallow = array('load_file(','hex(','substring(','if(','ord(','char(','intooutfile','intodumpfile','unionselect','unionall', 'uniondistinct','/*','*/','#','--','"');
	public static function check($sql) {
		$cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
		if (in_array($cmd, self::$checkcmd)) {
			$test = self::_do_query_safe($sql);
			if ($test != 1) {
			    $pos = $test['pos'];
			    $cleansql = $test['cleansql'];
				debug::error("SQL SafeCheck Error","SQL doesn't safe,【".$test['fun']."】 Found!  【CleanSql】: ".substr($cleansql,0,$pos).' ========>>>'.substr($cleansql,$pos)." 【Sql】: ".$sql." 【Suggest】: Use addslash_deep trim your data value.");
			}
		}
		return true;
	}

	private static function _do_query_safe($sql) {
		$sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
		$mark = $clean = '';
		if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false) {
			$clean = preg_replace("/'(.+?)'/s", '', $sql);
		} else {
			$len = strlen($sql);
			$mark = $clean = '';
			for ($i = 0; $i < $len; $i++) {
				$str = $sql[$i];
				switch ($str) {
					case '\'':
						if (!$mark) {
							$mark = '\'';
							$clean .= $str;
						} elseif ($mark == '\'') {
							$mark = '';
						}
						break;
					case '/':
						if (empty($mark) && $sql[$i + 1] == '*') {
							$mark = '/*';
							$clean .= $mark;
							$i++;
						} elseif ($mark == '/*' && $sql[$i - 1] == '*') {
							$mark = '';
							$clean .= '*';
						}
						break;
					case '#':
						if (empty($mark)) {
							$mark = $str;
							$clean .= $str;
						}
						break;
					case "\n":
						if ($mark == '#' || $mark == '--') {
							$mark = '';
						}
						break;
					case '-':
						if (empty($mark) && substr($sql, $i, 3) == '-- ') {
							$mark = '-- ';
							$clean .= $mark;
						}
						break;

					default:

						break;
				}
				$clean .= $mark ? '' : $str;
			}
		}
        //echo $clean.'<hr>';
		$clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));
		$clean = str_replace('/**/', '', $clean);
		
		if (is_array(self::$disallow)) {
			foreach (self::$disallow as $fun) {
				if (strpos($clean, $fun) !== false){
				    $ret['pos'] = strpos($clean, $fun);
				    $ret['fun'] = $fun;
				    $ret['cleansql'] = $clean;
					return $ret;
				}
			}
		}
		return 1;
	}

}