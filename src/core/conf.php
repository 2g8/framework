<?php

class conf
{
	protected static $autofind,$autofindpathes;
	public function __construct($config){
		//设置默认framework config
		$configfile = require(TEA_PATH.'/config.php');
		self::set($configfile);
		//用户config覆盖
		if(!empty($config)){
			self::set($config);
		}
		//controller配置覆盖 《--放在run里
		
	}
	
	public function set($confArr){
	    foreach($confArr as $k=>$v){
	    	if(is_array($v)){
	    		$nowval = !empty($this->{$k}) ? $this->{$k} : array();
	        	$this->{$k} = array_merge($nowval,$v);
	    	}else{
	    		$this->{$k} = $v;
	    	}
	    }
	}
    
    public function add($key, $value){
        $this->{$key} = $value;
    }
    
    public function get($key){
        return $this->{$key};
    }
    
    function __call($method,$args=array())
	{
		return call_user_func_array(array($this,$method),$args);
	}
	
}