<?php

class view
{
	public $_view = null;
	public $displayed = false;
	public $_extfuncs;
	public $_view_engine = array('teamplate','saemplate','smarty');
	
	public function __construct($config)
	{
		$engine = $config['engine']?$config['engine']:'teamplate';
		if(!in_array($engine,$this->_view_engine))
		{
			debug::error('View Engine Error',"View Engine <b>$engine</b> not no support.");
		}
		if($engine == 'smarty') $engine = "smarty.smarty";
		$this->_view = load::classes('lib.view.'.$engine,TEA_PATH);
		if($config['config'] && is_array($config['config'])){
			foreach( $config['config'] as $key => $value ){
				$this->_view->{$key} = $value;
			}
		}
	}
	
	public function regfuncs()
	{
		if( is_array($this->_extfuncs) ){
			foreach( $this->_extfuncs as $alias => $func )
			{
				$this->_view->register_function($alias, $func);
			}
		}
	}
	
	function __call($method,$args=array())
	{
		return call_user_func_array(array($this->_view,$method),$args);
	}
	
}