<?php

/**
 * Get the application start timestamp.
 */
defined('TEA_START_TIME') or define('TEA_START_TIME',microtime(true));
defined('TEA_START_MEM') or define('TEA_START_MEM',memory_get_usage());

/**
 * Get the framework path.
 */
defined('TEA_PATH') or define('TEA_PATH', dirname(__FILE__));
defined('APP_PATH') or die('MUST define APP_PATH to your app root.');

//设置时区
date_default_timezone_set('PRC');

class tea
{
	public $load,$conf,$model,$db,$view,$debug,$session;
    
    public function __construct($config = []){
    	//初始化loader
    	require(TEA_PATH."/core/load.php");
        $this->load = new load;
        //初始化conf
        $this->conf = $this->load->classes('core.conf',TEA_PATH,$config);
        //初始化debug
        $this->debug = $this->load->classes('core.debug',TEA_PATH);
		if($this->conf->mode == "debug"){
        	define('DEBUG', 'on');error_reporting(E_ALL&~E_NOTICE);
        	if($this->conf->reloadr == true){
                $this->conf->view["config"]["reloadr"] = true;
                if(strpos($_SERVER["DOCUMENT_URI"],"/__reloadr.js") !== false){
                    echo file_get_contents(__DIR__.'./lib/reloadr/reloadr.js');exit;
                }
                if(strpos($_SERVER["DOCUMENT_URI"],"/__reloadr.c") !== false){
                    include_once(__DIR__.'./lib/reloadr/reloadr.php');
                    exit;
                }
            }
        }else{
        	define('DEBUG', 'off');error_reporting(0);
        }
        //初始化框架环境
        $this->conf->app['controller_path'] = !empty($this->conf->app['controller_path']) ? $this->conf->app['controller_path'] : APP_PATH.'/controller';
        $this->_is_dir($this->conf->app['controller_path']) or $this->debug->error("Tea Framework Error","Controller Path doesn't exist : ".$this->conf->app['controller_path']);
        //进行路由
        $this->uri = $this->load->classes('core.uri',TEA_PATH,$this->conf->uri);
	}
	
	//初始化类
	public function init($cls = array()){
		if(!empty($cls) && is_array($cls)){
			foreach ($cls as $name){
				$this->import($name);
			}
		}
	}
	
	//框架主要类引入函数
	public function import($name){
		switch ($name){
		    case 'db':
		        if($this->db === NULL){
		            $this->db = $this->load->classes('core.db',TEA_PATH,$this->conf->db);
		        }
		        break;
		    case 'model':
		        if($this->model === NULL){
       				$this->conf->app['model_path'] = !empty($this->conf->app['model_path']) ? $this->conf->app['model_path'] : APP_PATH.'/model';
        			$this->_is_dir($this->conf->app['model_path']) or $this->debug->error("Tea Framework Error","Model Path doesn't exist : ".$this->conf->app['model_path']);
		            $this->model = $this->load->classes('core.model',TEA_PATH);
		        }
		        break;
		    case 'cache':
		        if($this->cache === NULL){
			        $this->conf->app['cache_path'] = !empty($this->conf->app['cache_path']) ? $this->conf->app['cache_path'] : APP_PATH.'/data';
			        $this->_is_dir($this->conf->app['cache_path']) or $this->debug->error("Tea Framework Error","Cache Path doesn't exist : ".$this->conf->app['cache_path']);
		            $this->cache = $this->load->classes('core.cache',TEA_PATH,$this->conf->cache);
		        }
		        break;
		    case 'session':
		        if($this->session === NULL){
		            $this->session = $this->load->classes('core.session',TEA_PATH, $this->conf->session);
		            $this->session->start();
		        }
		        break;
		    case 'view':
		        if($this->view === NULL){
		            $this->view = $this->load->classes('core.view',TEA_PATH, $this->conf->view);
		        }
		        break;
		    case 'filter':
		        if($this->filter === NULL){
					$this->load->file('core.filter',TEA_PATH);
					filter::input();
		        }
		        break;
		    case 'acl':
		        if($this->acl === NULL){
		            $this->acl = $this->load->classes('core.acl',TEA_PATH, $this);
		        }
		        break;
		    default:
		        $this->load->file($name);
		}
	}
	
	//框架执行函数
	public function run($teaphp_autoroute = ''){
		//初始化框架autoload
        $this->init($this->conf->autoload);
        //获取CAP
		$route = $this->uri->cap;
		//如果autostart为真，加载并执行请求的CA
		if(empty($teaphp_autoroute)){
			$this->load->file('core.controller',TEA_PATH);
			$this->load->file($route['controller'],$this->conf->app['controller_path']);
			if(class_exists($route['controller'],false)){
				$controller = new $route['controller']($this);
				if(method_exists($controller,'init')){
					$controller->init();
				}
                if(method_exists($controller,$route['action'])) {
                    $controller->{$route['action']}();
                }else{
                    //action不存在，返回404页面
                    $this->load->file('main',$this->conf->app['controller_path']);
                    $controller = new main($this);
                    $controller->notfound();
                }
			}else{
			    //controller不存在，返回404页面
				//$this->debug->error("Tea Framework Error","Controller Class called doesn't exist : ".$route['controller']);
                $this->load->file('main',$this->conf->app['controller_path']);
                $controller = new main($this);
                $controller->notfound();
			}
		}
	}
	
	private function _is_dir($dirname){
		if(!is_dir($dirname)) makedir($dirname);
		if(is_dir($dirname)) return true;
		return false;
	}
	
	//getPowerby
    public static function getPowerby(){
        return 'powered by <a href="http://www.teaphp.com/" target="_blank">teaphp framework</a>';
    }
	//getVersion
    public static function getVersion(){
        return '8.0.0';
    }
	//getTeapath
    public static function getTeapath(){
        return TEA_PATH;
    }
    //框架性能测试benchmark
    public static function benchmark(){
    	//print_r(get_declared_classes());
    	//print_r(get_defined_functions());
    	//print_r(get_included_files());
        return round(microtime(true)-TEA_START_TIME,10);
    }
	
}
//引入相关函数
require(TEA_PATH."/core/function.php");

//实例化框架并运行
$tea = new tea($config);
if(isset($teaphp_autoroute)){
    $tea->run($teaphp_autoroute);
}else{
    $tea->run();
}