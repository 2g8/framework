<?php

class saemplate
{
	public 	$template_dir,
			$compile_dir,
			$compile_file,
			$compile_time,
	       	$compile_force = false, 
	       	$compile_check = true;

	protected 
			$_vars = array(),
	       	$rules = array(
						'/([\n\r]+)\t+/s' => '$1',
						'/\<\!\-\-#.+?#\-\-\>/s' => '',
						'/\<\!\-\-\{(.+?)\}\-\-\>/s' => '{$1}',
						'/\{template\s+(.+)\}/' => '<?php $this->display($1); ?>',
						'/\{if\s+(.+?)\}/' => '<?php if($1) { ?>',
						'/\{else\}/' => '<?php } else { ?>',
						'/\{elseif\s+(.+?)\}/' => '<?php } elseif ($1) { ?>',
						'/\{\/if\}/' => '<?php } ?>',
						'/\{loop\s+(\S+)\s+(\S+)\}/' => '<?php if(is_array($1)) foreach($1 as $2) { ?>',
						'/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/' => '<?php if(is_array($1)) foreach($1 as $2 => $3) { ?>',
						'/\{\/loop\}/' => '<?php } ?>',
						'/\{(\((.*?)\))\}/' => '<?php echo $1;?>',
						'/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(.*?\))\}/' => '<?php echo $1;?>',
						'/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s' => '<?php echo $1;?>',
						);
	       
    function __construct()
    {
    	if(empty($this->compile_dir)) $this->compile_dir = 'saemc://saemplate_c';
    }

	public function set_rule($pattern, $replacement)
	{
		$this->rules[$pattern] = $replacement;
	}
    
	public function set_view($view)
    {
        $this->dir = $this->template_dir.DIRECTORY_SEPARATOR;
    	$this->tplext = $this->tplext ? $this->tplext : '.html';
    	$this->file = strpos($view,'.') > 0 ? $this->dir.$view : $this->dir.$view.$this->tplext;
    	$this->compile_file = $this->compile_dir.DIRECTORY_SEPARATOR.str_replace(array('/', '\\'), ',', $view).'.php';
    	$this->compile_time = $this->compile_dir.DIRECTORY_SEPARATOR.str_replace(array('/', '\\'), ',', $view).'.time';
        return $this;
    }
    
    function assign($key, $data = null)
    {
        if (is_array($key))
        {
            $this->_vars = array_merge($this->_vars, $key);
        }
        elseif (is_object($key))
        {
        	$this->_vars = array_merge($this->_vars, (array)$key);
        }
        else
        {
            $this->_vars[$key] = $data;
        }
        return $this;
    }
    
	function clean_vars()
    {
        $this->_vars = array();
        return $this;
    }
    
    function display($tplname)
    {
    	$this->set_view($tplname);
        $this->_before_render($tplname);
        if ($this->_vars) extract($this->_vars);
        ob_start();
        include $this->_file();
        $output = ob_get_contents();
		ob_end_clean();
        $this->_after_render($output);
        echo $output;
    }
    
    protected function _before_render($tplname) {}
    
    protected function _after_render(&$output) {}
    
	public function dir_compile($dir = null)
	{
		if (is_null($dir)) $dir = $this->dir;
		$files = glob($dir.'*');
		foreach ($files as $file)
		{
			if (is_dir($file))
			{
				$this->dir_compile($file);
			}
			else
			{
		        $this->_compile(substr($file, strlen($this->dir)));
			}
		}
	}
	
	public function clear_compile()
	{
		$files = glob($this->compile_dir.'*');
		foreach ($files as $file)
		{
			if (is_file($file)) @unlink($file);
		}
	}
	
    protected function _file()
    {
    	$compile_time = file_get_contents($this->compile_time);
		if ($this->compile_force || ($this->compile_check && (!file_exists($this->compile_file) || @filemtime($this->file) > $compile_time)))
		{
			$this->_compile();
		}
		return $this->compile_file;
    }
    
    protected function _compile($view = null)
    {
    	if ($view) $this->set_view($view);
    	$data = file_get_contents($this->file);
    	$data = $this->_parse($data);
    	
    	if (false === @file_put_contents($this->compile_file, $data)) debug::error('<font color=red>Sae</font>mplate Error',"$this->compile_file file is not writable.Check your memcache setting!");
    	if (false === @file_put_contents($this->compile_time, time())) debug::error('<font color=red>Sae</font>mplate Error',"$this->compile_time file is not writable.");
    	return true;
    }
    
	private function _parse($string)
	{
		return preg_replace(array_keys($this->rules), $this->rules, $string);
	}
	
	private static function _addquote($var)
	{
		return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
	}
}