<?php

class model
{
	public $db;
	public $tea;

	public $table="";
	public $pk="id";
	public $foreignkey='catid';
	
	public $verifier = null;
	public $addrules = array();
	
	public $tablesize = 1000000;
	public $fields;
	public $select='*';

	public $create_sql='';	
	public $_data=array();

	function __construct($teaframework = null)
	{
	    if($teaframework){
	        $tea = $teaframework;
        }else{
            global $tea;
        }
		$this->db = $tea->db;
		load::file('lib.db.db_apt',TEA_PATH);
		$this->db_apt = new db_apt($tea->db);
        $this->db_apt->from($this->table);
		$this->tea = $tea;
	}
	
    function shard_table($id)
    {
        $table_id = intval($id/$this->tablesize);
        $this->table = $this->table.'_'.$table_id;
    }
    
	public final function get($object_id='',$where='')
	{
		return new record($object_id,$this->db,$this->table,$this->pk,$where,$this->select);
	}
	
	public final function gets($params,&$pager=null)
	{
	    if(empty($params)) return false;
		$this->db_apt->from($this->table);
		$this->db_apt->pk = $this->pk;
		$this->db_apt->select($this->select);
		if(!isset($params['order'])) $params['order'] = $this->pk.' desc';
		$this->db_apt->put($params);
		if(isset($params['page']))
		{
			if(isset($params['fastpaging'])){ //启用快速分页
				$this->db_apt->fastpaging();
			}else{
				$this->db_apt->paging();
			}
			$pager = $this->db_apt->pager;
		}
		return $this->db_apt->getall();
	}
	
	public final function put($data)
	{
		if(empty($data) or !is_array($data)) return false;
		$this->db->insert($data,$this->table);
		return $this->db->insert_id();
	}
	
	public final function set($id,$data,$where='')
	{
		if(empty($where)) $where=$this->pk;
		return $this->db->update($id,$data,$this->table,$where);
	}
	
	public final function sets($data,$params)
	{
		if(empty($params))
		{
			return false;
		}
		$this->db_apt->from($this->table);
		$this->db_apt->put($params);
		$this->db_apt->update($data);
		return true;
	}
	
	public final function replace($data)
	{
		if(empty($data) or !is_array($data)) return false;
		return $this->db->replace($data,$this->table);
	}
	
	public final function del($id,$where=null)
	{
		if($where==null) $where = $this->pk;
		return $this->db->delete($id,$this->table,$where);
	}
	
    public final function dels($params)
    {
        if(empty($params))
        {
            return false;
        }
        $this->db_apt->from($this->table);
		$this->db_apt->put($params);
        $this->db_apt->delete();
        return true;
    }
    
    public final function count($params)
    {
		$this->db_apt->from($this->table);
		$this->db_apt->put($params);
		return $this->db_apt->count();
    }
    
	public final function all()
	{
		return new recordset($this->db,$this->table,$this->pk,$this->select);
	}
	
	function createTable()
	{
		if($this->create_sql) return $this->db->query($this->create_sql);
		else return false;
	}
	
	public final function getStatus()
	{
		return $this->db->query("show table status from ".$this->tea->conf->db['dbname']." where name='{$this->table}'")->fetch();
	}
	
	function getList(&$params,$get='data')
	{
		$this->db_apt->from($this->table);
		$this->db_apt->select($this->select);
		$this->db_apt->limit(isset($params['row'])?$params['row']:10);
		unset($params['row']);
		$this->db_apt->order(isset($params['order'])?$params['order']:$this->pk.' desc');
		unset($params['order']);

		if(isset($params['typeid']))
		{
			$this->db_apt->where($this->foreignkey.'='.$params['typeid']);
			unset($params['typeid']);
		}
		$this->db_apt->put($params);
		if(array_key_exists('page',$params))
		{
			$this->db_apt->paging();
			$this->tea->conf->add('page',$params['page']);
			$this->tea->conf->add('start',10 * intval($params['page']/10));
			if($this->db_apt->pages>10 and $params['page']<$start)
				$this->tea->conf->add('more',1);			
			$this->tea->conf->add('end',$this->db_apt->pages - $this->tea->conf->start);
			$this->tea->conf->add('pages',$this->db_apt->pages);
			$this->tea->conf->add('pagesize',$this->db_apt->page_size);
			$this->tea->conf->add('num',$this->db_apt->num);
		}
		if($get==='data') return $this->db_apt->getall();
		elseif($get==='sql') return $this->db_apt->getsql();
	}
	
	function getMap($gets,$field=null)
	{
	    $list = $this->gets($gets);
	    $new = array();
	    foreach($list as $li)
	    {
	        if(empty($field)) $new[$li[$this->pk]] = $li;
	        else $new[$li[$this->pk]] = $li[$field];
	    }
	    unset($list);
	    return $new;
	}
	
	function getTree($gets,$category='fid',$order='id desc')
	{
	    $gets['order'] = $category.','.$order;
	    $list = $this->gets($gets);
	    foreach($list as $li)
	    {
	        if($li[$category]==0) $new[$li[$this->pk]] = $li;
	        else $new[$li[$category]]['child'][$li[$this->pk]] = $li;
	    }
	    unset($list);
	    return $new;
	}
	
	function exists($gets)
	{
	    $c = $this->count($gets);
	    if($c>0) return true;
	    else return false;
	}
	
	function desc()
	{
		return $this->db->query('describe '.$this->table)->fetchall();
	}
	
	public function __call($name, $args)
	{
		return load::classes($name)->__input($this, $args);
	}
}

class record implements ArrayAccess
{
	public $_data = array();
	public $_change;
	public $db;

	public $pk="id";
	public $table="";

	public $change=0;
	public $_current_id=0;
	public $_currend_key;

	function __construct($id,$db,$table,$pk,$where='',$select='*')
	{
		$this->db = $db;
		$this->_current_id = is_numeric($id) ? $id : addslashes_deep($id);	//处理id防止注入
		$this->table = $table;
		$this->pk = $pk;
		if(empty($where)) $where = $pk;
		if(!empty($this->_current_id))
		{
			$sql = "select $select from ".$this->table." where ".$where."='$this->_current_id' limit 1";
			$res=$this->db->query($sql);
			$this->_data=$res->fetch();
			if(!empty($this->_data)) $this->change = 1;
		}
	}
	
	function put($data)
	{
		if($this->change == 1)
		{
			$this->change = 2;
			$this->_change = $data;
		}
		elseif($this->change==0)
		{
			$this->change = 1;
			$this->_data=$data;
		}
	}
	
	function get()
	{
		return $this->_data;
	}

	function __get($property)
	{
		if(array_key_exists($property,$this->_data)) return $this->_data[$property];
		else debug::error('Model Record Error',"Record object no property: $property.");
	}

	function __set($property,$value)
	{
		if(is_string($value))
			$value = addslashes($value);
		if($this->change==1 or $this->change==2)
		{
			$this->change=2;
			$this->_change[$property]=$value;
			$this->_data[$property]=$value;
		}
		else
		{
			$this->_data[$property]=$value;
		}
		return true;
	}
	
	function save()
	{
		if($this->change==0 or $this->change==1)
		{
			$this->db->insert($this->_data,$this->table);
			$this->_current_id=$this->db->insert_id();
		}
		elseif($this->change==2)
		{
			$update = $this->_data;
			unset($update[$this->pk]);
			$this->db->update($this->_current_id,$this->_change,$this->table,$this->pk);
		}
		return $this->_current_id;
	}
	
	function update()
	{
		$update = $this->_data;
		unset($update[$this->pk]);
		$this->db->update($this->_current_id,$this->_change,$this->table,$this->pk);
	}
	
	function delete()
	{
		$this->db->delete($this->_current_id,$this->table,$this->pk);
	}

    #[\ReturnTypeWillChange]
    function offsetExists($keyname)
	{
		return array_key_exists($keyname,$this->_data);
	}

    #[\ReturnTypeWillChange]
    function offsetGet($keyname)
	{
		return $this->_data[$keyname];
	}

    #[\ReturnTypeWillChange]
    function offsetSet($keyname,$value)
	{
		$this->_data[$keyname] = $value;
	}

    #[\ReturnTypeWillChange]
    function offsetUnset($keyname)
	{
		unset($this->_data[$keyname]);
	}
}




class recordset implements Iterator
{
	public $_list=array();

	public $table='';
	public $db;
	public $db_select;

	public $pk="";

	public $_current_id=0;

	function __construct($db,$table,$pk,$select)
	{
		$this->table = $table;
		$this->pk = $pk;
		$this->db = $db;
		$this->db_apt = load::classes('lib.db.db_apt',TEA_PATH,$this->db);
		$this->db_apt->from($table);
		$this->db_apt->pk = $pk;
		$this->db_apt->select($select);
		$this->db_apt->order($this->pk." desc");
	}
	
	function get()
	{
		return $this->_list;
	}
	
	function params($params)
	{
		$this->db_apt->put($params);
	}
	
	function filter($where)
	{
		$this->db_apt->where($where);
	}
	
	function eq($field,$value)
	{
		$this->db_apt->equal($field,$value);
	}
	
	function orfilter($where)
	{
		$this->db_apt->orwhere($where);
	}
	
	function fetch($field='')
	{
		return $this->db_apt->getone($field);
	}
	
	function fetchall()
	{
		return $this->db_apt->getall();
	}

	function __call($method,$argv)
	{
		return call_user_func_array(array($this->db_apt,$method),$argv);
	}

    #[\ReturnTypeWillChange]
    public function rewind()
	{
		if(empty($this->_list)) $this->_list = $this->db_apt->getall();
		$this->_current_id=0;
	}

    #[\ReturnTypeWillChange]
    public function key()
	{
		return $this->_current_id;
	}

    #[\ReturnTypeWillChange]
    public function current()
	{
		$record = new record(0,$this->db,$this->table,$this->pk);
		$record->put($this->_list[$this->_current_id]);
		$record->_current_id = $this->_list[$this->_current_id][$this->pk];
		return $record;
	}

    #[\ReturnTypeWillChange]
    public function next()
	{
		$this->_current_id++;
	}

    #[\ReturnTypeWillChange]
    public function valid()
	{
		if(isset($this->_list[$this->_current_id])) return true;
		else return false;
	}
	
}