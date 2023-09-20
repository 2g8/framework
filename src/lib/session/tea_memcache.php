<?php

class tea_memcache extends session_apt
{
	function __construct($options = array())
	{
		if(!$this->test())
		{
            exit("The memcache extension isn't available");
        }
		ini_set('session.save_handler', 'memcache');
		ini_set('session.save_path', $options['memcache_servers']);
	}
	
	function test()
	{
		return extension_loaded('memcache');
	}
}
