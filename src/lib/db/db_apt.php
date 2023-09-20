<?php

class db_apt
{
    static $error_call='';
    static $allow_regx = '#^([a-z0-9\(\)\._=\-\+\*\`\s\'\",]+)$#i';
    public $table='';
    public $primary='id';
    public $select='*';
    public $sql='';
    public $limit='';
    public $where='';
    public $order='';
    public $group='';
    public $join='';
    public $union='';

    //Union
    private $if_union = false;
    private $union_select = '';

    //Join
    private $if_join = false;
    private $if_add_tablename = false;

    //Count
    private $count_fields = '*';

    public $page_size = 10;
    public $num = 0;
    public $pages = 0;
    public $page = 0;
    public $pager = null;

    public $auto_cache = false;
    public $cache_life = 600;
    public $cache_prefix = 'db_apt';

    public $RecordSet;

    public $is_execute = 0;

    public $result_filter = array();

    public $call_by = 'func';
    public $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    
    function init($what='')
    {
        if($what=='')
        {
            $this->table='';
            $this->primary='id';
            $this->select='*';
            $this->sql='';
            $this->limit='';
            $this->where='';
            $this->order='';
            $this->group='';
            $this->join='';
            $this->union='';
        }
        else
        $this->$what = '';
    }
    
    function equal($field,$_where)
    {
        if($_where instanceof db_apt)
        {
            $where = $field.'=('.$_where->getsql().')';
        }
        else
        {
            $where = "`$field`='$_where'";
        }
        $this->where($where);
    }
    
    function from($table)
    {
        $this->table = $table;
    }
    
    function addtable($table_name,$on)
    {
    	$this->if_union = true;
    	$this->union_pk = $this->table.'.'.$this->pk;
    	$this->table .=','.$table_name;
    	$this->where("{$on}");
    }
    
    function select($select,$force=false)
    {
        if($this->select == "*" or $force) $this->select = $select;
        else $this->select = $this->select.','.$select;
    }
    
    function where($where)
    {
        if($this->where=="")
        {
            $this->where="where ".$where;
        }
        else
        {
            $this->where=$this->where." and ".$where;
        }
    }
    
    function like($field,$like)
    {
    	$field = addslashes_deep($field);
        self::sql_safe($field);
        $this->where("`{$field}` like '{$like}'");
    }
    
    function orwhere($where)
    {
        if($this->where=="")
        {
            $this->where="where ".$where;
        }
        else
        {
            $this->where=$this->where." or ".$where;
        }
    }
    
    function limit($limit)
    {
        if(!empty($limit))
        {
            $_limit = explode(',',$limit,2);
            if(count($_limit)==2) $this->limit='limit '.(int)$_limit[0].','.(int)$_limit[1];
            else $this->limit="limit ".(int)$limit;
        }
        else $this->limit = '';
    }
    
    function order($order)
    {
        if(!empty($order))
        {
            self::sql_safe($order);
            $this->order="order by $order";
        }
        else $this->order = '';
    }

    function group($group)
    {
        if(!empty($group))
        {
            self::sql_safe($group);
            $this->group = "group by $group";
        }
        else $this->group = '';
    }

    function in($field,$ins)
    {
        $this->where("`$field` in ({$ins})");
    }

    function notin($field,$ins)
    {
        $this->where("`$field` not in ({$ins})");
    }

    function join($table_name,$on)
    {
        $this->union_pk = $this->table.'.'.$this->pk;
        $this->join.="INNER JOIN `{$table_name}` ON ({$on})";
    }

    function leftjoin($table_name,$on)
    {
        $this->union_pk = $this->table.'.'.$this->pk;
        $this->join.="LEFT JOIN `{$table_name}` ON ({$on})";
    }

    function rightjoin($table_name,$on)
    {
        $this->union_pk = $this->table.'.'.$this->pk;
        $this->join.="RIGHT JOIN `{$table_name}` ON ({$on})";
    }

    function pagesize($pagesize)
    {
        $this->page_size = (int)$pagesize;
    }

    function page($page)
    {
        $this->page = (int)$page;
    }

    function id($id)
    {
        $this->where("`{$this->primary}` = '$id'");
    }
    
    function fastpaging()
    {
        $this->num = $this->count();
        $this->union_pk = !empty($this->union_pk)?$this->union_pk:$this->pk;
        $offset=($this->page-1)*$this->page_size;
        if($offset<0) $offset=0;
        if($this->num%$this->page_size>0){
        	$this->pages=intval($this->num/$this->page_size)+1;
        }else{
        	$this->pages=$this->num/$this->page_size;
        }
        $this->limit($offset.','.$this->page_size);
        $fastsql="select {$this->union_pk} from {$this->table} {$this->where} {$this->order} {$this->limit}";
        $rs = $this->db->query($fastsql);
        foreach ($rs->fetchall() as $ids){
			$strid .= $ids[$this->pk].',';
        }
        $strid .= '0';
        $this->limit = '';
        $this->where("$this->union_pk IN ($strid)");
        $this->pager = load::classes('lib.util.pager',TEA_PATH,array('total'=>$this->num,'pagesize'=>$this->page_size));
    }

    function paging()
    {
        $this->num = $this->count();
        $offset=($this->page-1)*$this->page_size;
        if($offset<0) $offset=0;
        if($this->num%$this->page_size>0)
        $this->pages=intval($this->num/$this->page_size)+1;
        else
        $this->pages=$this->num/$this->page_size;
        $this->limit($offset.','.$this->page_size);
        
        $this->pager = load::classes('lib.util.pager',TEA_PATH,array('total'=>$this->num,'pagesize'=>$this->page_size));
    }

    function filter($filter_func)
    {
        $filter_list = explode(',',$filter_func);
        $this->result_filter = array_merge($$this->result_filter,$filter_list);
    }

    static function sql_safe($sql_sub)
    {
        if(!preg_match(self::$allow_regx,$sql_sub))
        {
            debug::error('Database APT Error','SQL is NOT Safe :'.$sql_sub);
        }
    }

    function getsql($ifreturn=true)
    {
        $this->sql="select {$this->select} from {$this->table} {$this->join} {$this->where} {$this->union} {$this->group} {$this->order} {$this->limit}";
        if($this->if_union) $this->sql = str_replace('{#union_select#}',$this->union_select,$this->sql);
        if($ifreturn) return $this->sql;
    }

    function raw_put($params)
    {
        foreach($params as $array)
        {
            if(isset($array[0]) and isset($array[1]) and count($array)==2)
            {
                $this->_call($array[0],$array[1]);
            }
            else
            {
                $this->raw_put($array);
            }
        }
    }

    function exeucte($sql='')
    {
        if($sql=='') $this->getsql(false);
        else $this->sql = $sql;
        $this->res = $this->db->query($this->sql);
        $this->is_execute++;
    }

    function union($sql)
    {
        $this->if_union = true;
        if($sql instanceof SelectDB)
        {
            $this->union_select = $sql->select;
            $sql->select = '{#union_select#}';
            $this->union = 'UNION ('.$sql->getsql(true).')';
        }
        else $this->union = 'UNION ('.$sql.')';
    }

    function put($params)
    {
        if(isset($params['put']))
        {
        	debug::error('Database APT Error','Params put() cannot call put().');
        }
        //where
        if(isset($params['where']))
        {
            $wheres = $params['where'];
            if(is_array($wheres)){
            	foreach($wheres as $field=>$where){
            		if(is_array($where)){
            			foreach ($where as $k=>$v){
            				if(in_array($k,array("=",">",">=","<","<=","!="))){
            					$wherestr = "`".$field."`".$k."'".addslashes_deep($v)."'";
            					if(!empty($wherestr)) $this->where($wherestr);
            				}
            			}
            		}else{
            		    if($where == null){
                            $wherestr = "`".$field."` is NULL";
                        }else{
                            $wherestr = "`".$field."`='".addslashes_deep($where)."'";
                        }
            			if(!empty($wherestr)) $this->where($wherestr);
            		}
            	}
            }else{
            	debug::error('Database APT Error',"Params of where must be an array! Example: \$condition['where']['uid']['<='] = 20;");
            }
            unset($params['where']);
        }
        //orwhere
        if(isset($params['orwhere']))
        {
            $orwheres = $params['orwhere'];
            if(is_array($orwheres)){
            	foreach($orwheres as $field=>$where){
            		if(is_array($where)){
            			foreach ($where as $k=>$v){
            				if(in_array($k,array("=",">",">=","<","<=","!="))){
            					$wherestr = "`".$field."`".$k."'".addslashes_deep($v)."'";
            					if(!empty($wherestr)) $this->orwhere($wherestr);
            				}
            			}
            		}else{
                        if($where == null){
                            $wherestr = "`".$field."` is NULL";
                        }else {
                            $wherestr = "`" . $field . "`='" . addslashes_deep($where) . "'";
                        }
            			if(!empty($wherestr)) $this->orwhere($wherestr);
            		}
            	}
            }else{
            	debug::error('Database APT Error',"Params of orwhere must be an array! Example: \$condition['orwhere']['uid']['<='] = 20;");
            }
            unset($params['orwhere']);
        }
        //fastpaging
        if(isset($params['fastpaging']))
        {
            unset($params['fastpaging']);
        }

        foreach($params as $key=>$value)
        {
            $this->_call($key,$value);
        }
    }

    private function _call($method,$param)
    {
        if($method=='update' or $method=='delete' or $method=='insert') return false;
        if(strpos($method,'_')!==0)
        {
            if(method_exists($this,$method))
            {
                if(is_array($param)) call_user_func_array(array($this,$method),$param);
                else call_user_func(array($this,$method),$param);
            }
            else
            {
            	$param = addslashes_deep($param);
                if($this->call_by=='func'){
                	$this->put(array('where'=>array($method=>$param)));
                }elseif($this->call_by=='smarty'){
                    if(strpos($param,'$')===false){
                		$this->put(array('where'=>array($method=>$param)));
                    }else{
                    	$this->put(array('where'=>array($method=>"'{".$param."}'")));
                    }
                }else{
        			debug::error('Database APT Error',"Params Error :$method=$param.");
                }
            }
        }
    }

    function getone($field='',$cache_id='')
    {
        $this->limit('1');
        if($this->is_execute==0) $this->exeucte();
        $record = $this->res->fetch();
        if($field==='') return $record;
        return $record[$field];
    }

    function getall($cache_id='')
    {
        if($this->is_execute==0) $this->exeucte();
        return $this->res->fetchall();
    }

    public function count()
    {
        $sql="select count({$this->count_fields}) as c from {$this->table} {$this->join} {$this->where} {$this->union} {$this->group}";
        if($this->if_union)
        {
            $sql = str_replace('{#union_select#}',"count({$this->count_fields}) as c",$sql);
            $c = $this->db->query($sql)->fetchall();
            $cc = 0;
            foreach($c as $_c)
            {
                $cc+=$_c['c'];
            }
            return $cc;
        }
        else
        {
            $c = $this->db->query($sql)->fetch();
            return $c['c'];
        }
    }
    
    function insert($data)
    {
        $field="";
        $values="";
        foreach($data as $key => $value)
        {
            $value = addslashes_deep($value);
            $field = $field."`$key`,";
            $values = $values."'$value',";
        }
        $field=substr($field,0,-1);
        $values=substr($values,0,-1);
        return $this->db->query("insert into {$this->table} ($field) values($values)");
    }

    function insertOnDuplicateKey($data, $update)
    {
        $field="";
        $values="";
        foreach($data as $key => $value)
        {
            $value = addslashes_deep($value);
            $field = $field."`$key`,";
            $values = $values."'$value',";
        }
        $field=substr($field,0,-1);
        $values=substr($values,0,-1);
        return $this->db->query("insert into {$this->table} ($field) values($values)  ON DUPLICATE KEY UPDATE ".$update);
    }

    function update($data)
    {
        $update="";
        foreach($data as $key=>$value)
        {
            $value = addslashes_deep($value);
            if($value!='' and $value[0]=='`') $update=$update."`$key`=$value,";
            else $update = $update."`$key`='$value',";
        }
        $update = substr($update,0,-1);
        return $this->db->query("update {$this->table} set $update {$this->where} {$this->limit}");
    }
    
    function delete()
    {
        return $this->db->query("delete from {$this->table} {$this->where} {$this->limit}");
    }
    
}