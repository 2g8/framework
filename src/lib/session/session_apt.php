<?php

class session_apt
{
	protected $options;
	
	function __construct($options = array())
	{
		$this->options = $options;
	}

	static function &get_instance($name = 'tea_file', $options = array())
	{
		static $instances = array();
		if (!isset($instances[$name]))
		{
			$class = $name;
			if(!class_exists($class))
			{
				$path = dirname(__FILE__).DIRECTORY_SEPARATOR.$name.'.php';
				if (!file_exists($path)) debug::error('Session Driver Error',"Session Driver <b>$name</b> could not loaded.");
				require_once($path);
			}
			$instances[$name] = new $class($options);
		}
		return $instances[$name];
	}

	function register()
	{
		session_set_save_handler(array($this, 'open'), array($this, 'close'), array($this, 'read'), array($this, 'write'), array($this, 'destroy'), array($this, 'gc'));
	}
}