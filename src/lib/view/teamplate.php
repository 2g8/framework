<?php
//last modify ： 2017年3月12日14:42:35
//适配 php7

class teamplate
{
	public 	$template_dir,
			$compile_dir,
			$compile_file,
	       	$compile_force = false, 
	       	$compile_check = true;

	protected 
			$_vars = [],
            $htmlcompress = false,
            $reloadr = false;

    public $tplext,$cache_dir,$left_delimiter,$right_delimiter,$dir,$file;
	       
    function __construct()
    {
    	
    }
    
	public function set_view($view)
    {
        $this->dir = $this->template_dir.DIRECTORY_SEPARATOR;
    	$this->tplext = $this->tplext ? $this->tplext : '.html';
    	$this->file = strpos($view,'.') > 0 ? $this->dir.$view : $this->dir.$view.$this->tplext;
    	$this->compile_file = $this->compile_dir.DIRECTORY_SEPARATOR.str_replace(array('/', '\\'), ',', $view).'.php';
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
    
    protected function _after_render(&$output) {
        if($this->htmlcompress){
            $output = $this->compress_html($output);
        }
        if($this->reloadr){
            $output .= "
<!-- teaphp: 代码更新自动刷新页面 -->
<script src='/__reloadr.js'></script>
<script>
    Reloadr.go({
        client: [
        ],
        server: [
            '/controller/*.php',
            '/model/*.php',
            '/tpl/*.htm'
        ],
        path: '/__reloadr.c',
        frequency: 2000
    });
</script>
            ";
        }
    }

    public function compress_html($string)
    {
        $chunks = preg_split( '/(<pre.*?\/pre>)/ms', $string, -1, PREG_SPLIT_DELIM_CAPTURE );
        $string = '';//清除换行符,清除制表符,去掉注释标记
        foreach ($chunks as $c)
        {
            if (strpos( $c, '<pre' ) !== 0)
            {
                //remove new lines & tabs
                $c = preg_replace( '/[\\n\\r\\t]+/', ' ', $c );
                //remove extra whitespace
                $c = preg_replace( '/\\s{2,}/', ' ', $c );
                //remove inter-tag whitespace
                $c = preg_replace( '/>\\s</', '><', $c );
                //remove CSS & JS comments
                $c = preg_replace( '/\\/\\*.*?\\*\\//i', '', $c );
                //remove html comments
                $c = preg_replace("/<!--[^!]*-->/", '', $c);
            }
            $string .= $c;
        }
        return $string;
    }
    
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
		if ($this->compile_force || ($this->compile_check && (!file_exists($this->compile_file) || @filemtime($this->file) > @filemtime($this->compile_file))))
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
    	
    	if (false === @file_put_contents($this->compile_file, $data)) debug::error('<font color=red>Tea</font>mplate Error',"$this->compile_file file is not writable.");
    	return true;
    }
    
	private function _parse($str)
	{
		//var_dump($str);
		$str = preg_replace_callback("/\{template\s+(.+)\}/", function($r){return '<?php $this->display('.$r[1].')?>';}, $str );
		$str = preg_replace ( "/\{include\s+(.+)\}/", "<?php include \\1; ?>", $str );
		$str = preg_replace ( "/\{php\s+(.+)\}/", "<?php \\1?>", $str );
		$str = preg_replace ( "/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str );
		$str = preg_replace ( "/\{else\}/", "<?php } else { ?>", $str );
		$str = preg_replace ( "/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $str );
		$str = preg_replace ( "/\{\/if\}/", "<?php } ?>", $str );
		//for 循环
		$str = preg_replace("/\{for\s+(.+?)\}/","<?php for(\\1) { ?>",$str);
		$str = preg_replace("/\{\/for\}/","<?php } ?>",$str);
		//++ --
		$str = preg_replace("/\{\+\+(.+?)\}/","<?php ++\\1; ?>",$str);
		$str = preg_replace("/\{\-\-(.+?)\}/","<?php ++\\1; ?>",$str);
		$str = preg_replace("/\{(.+?)\+\+\}/","<?php \\1++; ?>",$str);
		$str = preg_replace("/\{(.+?)\-\-\}/","<?php \\1--; ?>",$str);
		$str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\}/", "<?php \$teamplate_n=1;if(is_array(\\1)) foreach(\\1 AS \\2) { ?>", $str );
		$str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php \$teamplate_n=1; if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $str );
		$str = preg_replace ( "/\{\/loop\}/", "<?php \$teamplate_n++;}//unset(\$teamplate_n); ?>", $str );
		$str = preg_replace ( "/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str );
		$str = preg_replace ( "/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str );
		$str = preg_replace ( "/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str );
		$str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s",array($this, '_addquote'),$str);
		$str = preg_replace ( "/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str );
		$str = "<?php defined('APP_PATH') or exit('No permission!'); ?>\r\n" . $str;
		return $str;
	}
	
	public function _addquote($matches) {
		$var = '<?php echo '.$matches[1].';?>';
		return str_replace ( "\\\"", "\"", preg_replace ( "/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var ) );
	}
}