<?php

class tea_file extends session_apt
{
	function __construct($options = array())
	{
		ini_set('session.save_handler', 'files');
    	session_save_path($path);
	}
}
