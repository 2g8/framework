<?php
class load
{
	private static $filepathes = array(),$classinstances = array();
	
    public static function classes($files,$path='',$arg=''){    	
    	if(is_string($files)){
    		return self::_class($files,$path,$arg);
    	}else{
    		foreach ($files as $file){
    			$obj[] = self::_class($file,$path,$arg);
    		}
    		return $obj;
    	}
    }
    
	public static function file($files,$path=''){    	
    	if(is_string($files)){
    		self::_file($files,$path);
    	}else{
    		foreach ($files as $file){
    			self::_file($files,$path);
    		}
    	}
    }
    
    public static function _class($file,$path='',$arg=''){
    	if(is_object($arg)){
    		$checksum = md5($file.$path.spl_object_hash($arg));
    	}else{    		
    		if(is_array($arg)){
	    		$checksum = md5($file.$path.serialize($arg));
	    	}else{ 		
	    		$checksum = md5($file.$path);
	    	}
    	}
    	if(!self::_file($file,$path)){
    		return false;
    	}else{
    		if(isset(self::$classinstances[$checksum])){
				return self::$classinstances[$checksum];
			}else{
				$parts = explode('.', $file);
				$classname = array_pop($parts);
				self::$classinstances[$checksum] = new $classname($arg);
				return self::$classinstances[$checksum];
			}
		}
    }
    
	protected static function _file($file,$path=''){		
		$filename = str_replace('.', DIRECTORY_SEPARATOR, $file);
		if(empty($path)){
			$f = self::_autofinduri($filename);
			if(!empty($f)) require_once($f);
		}else{
			$f = self::_fileuri($filename,$path);
    		if(!empty($f)) require_once($f);
		}
		self::$filepathes[$path.DIRECTORY_SEPARATOR.$file] = $f;
		if(empty(self::$filepathes[$path.DIRECTORY_SEPARATOR.$file])){
			return false;
		}
		return true;
    }
    
    protected static function _fileuri($name, $path){
    	$postfix = array('.php','.func.php','.class.php');
		foreach ($postfix as $pfix){
			if (file_exists($path.DIRECTORY_SEPARATOR.$name.$pfix)) return $path.DIRECTORY_SEPARATOR.$name.$pfix;
		}
    }
    
    protected static function _autofinduri($name){
		foreach (array(TEA_PATH,TEA_PATH.'/core',TEA_PATH.'/lib',APP_PATH,APP_PATH.'/controller',APP_PATH.'/model',APP_PATH.'/lib',) as $path){
			$postfix = array('.php','.func.php','.class.php');
			foreach ($postfix as $pfix){
				if (file_exists($path.DIRECTORY_SEPARATOR.$name.$pfix)) return $path.DIRECTORY_SEPARATOR.$name.$pfix;
			}
		}
    }
    
    function __call($method,$args=array())
	{
		return call_user_func_array(array($this,$method),$args);
	}
    
    
}

//处理spl_object_hash兼容
if (!function_exists('spl_object_hash')) {
    function spl_object_hash($object)
    {
        if (is_object($object)) {
            ob_start(); var_dump($object); $dumptmp = ob_get_contents(); ob_end_clean();
            return md5($dumptmp);
        }
        return null;
    }
}

//自动加载类
spl_autoload_register(array('load', 'classes'));