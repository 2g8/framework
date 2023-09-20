<?php

class session
{
	
	protected $_session = null;
	protected $_session_apt = null;
	protected $_session_driver = array('tea_file','tea_memcache','tea_apc','tea_db');//,'tea_eaccelerator','tea_xcache','tea_db');//Ôʱֻ֧³Öile
	protected $_session_config,$started = false;
	
	function __construct($sessionconfig)
	{
        $this->_session_config['driver'] = $sessionconfig['driver'] ? $sessionconfig['driver'] : 'tea_file';
        $this->_session_config['maxlifetime'] = isset($sessionconfig['maxlifetime']) ? $sessionconfig['maxlifetime'] : 1440;
        $this->_session_config['cache_expire'] = isset($sessionconfig['cache_expire']) ? $sessionconfig['cache_expire'] : 180;
        $this->_session_config['cookie_lifetime'] = isset($sessionconfig['cookie_lifetime']) ? $sessionconfig['cookie_lifetime'] : 0;
        $this->_session_config['cookie_path'] = isset($sessionconfig['cookie_path']) ? $sessionconfig['cookie_path'] : '/';
        $this->_session_config['cookie_domain'] = isset($sessionconfig['cookie_domain']) ? $sessionconfig['cookie_domain'] : '';
        
        $this->_session_config['options'] = $sessionconfig['options'];
        
        if(!in_array($this->_session_config['driver'],$this->_session_driver))
		{
			debug::error('Session Driver Error',"Session Driver <b>$driver</b> not no support.");
		}
	}
	
	function start()
	{
		if (!$this->started)
		{
			ini_set('session.gc_maxlifetime', $this->_session_config['maxlifetime']);
			session_cache_expire($this->_session_config['cache_expire']);
			session_set_cookie_params($this->_session_config['cookie_lifetime'], $this->_session_config['cookie_path'], $this->_session_config['cookie_domain']);
			load::file('lib.session.session_apt',TEA_PATH);
			$apt = session_apt::get_instance($this->_session_config['driver'], $this->_session_config['options']);
			session_start();
			$this->started = true;
		}
	}
}
