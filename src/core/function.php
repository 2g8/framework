<?php

//================================================
//框架相关函数
//================================================
//model加载函数
function model($modelname){
	global $tea;
	$tea->init(array('db','model'));
	$tea->load->file($modelname,$tea->conf->app['model_path']);
	$inst = new $modelname($tea);
	return $inst;
}

function url($cap  = array(),$new_params = array()){
	global $tea;
	return $tea->uri->makeurl($cap,$new_params);
}

function makeurl($controller='',$action='',$params = array()){
	return url(array('controller'=>$controller,'action'=>$action,'params'=>$params));
}

//根据控制器和动作为菜单添加样式的函数
//getcss
function getcss($classname='selected',$c='',$a='',$withclass = 1){
	global $tea;
	$cap = $tea->uri->router();
	$c = !empty($c) ? $c : 'main';
	if(!empty($a)){
		if(is_array($a)){
			foreach ($a as $aa){
				if($cap['controller'] == $c && $cap['action'] == $aa) $cname = $classname;
			}
		}else{
			if($cap['controller'] == $c && $cap['action'] == $a) $cname = $classname;
		}
	}else{
		if($cap['controller'] == $c){
			$cname = $classname;
		}else{
			$cname = '';
		}
	}
	if($withclass == 1){
		return empty($cname) ? '' : ' class="'.$cname.'"';
	}else{
		return $cname;
	}
}
		
//跳转页面
function redirect($url)
{
	header("Location: $url\n");
	exit;
}

//打印数据
function dump($data)
{
	var_dump($data);
	exit;
}
//================================================
//系统环境相关函数
//================================================
//获取客户IP地址
function getip() {
	$ip = $_SERVER['REMOTE_ADDR'];
	if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
		foreach ($matches[0] AS $xip) {
			if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
				$ip = $xip;
				break;
			}
		}
	}
	if(isset($_SERVER["HTTP_X_REAL_IP"])){
		$ip = $_SERVER["HTTP_X_REAL_IP"];
	}
	if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
	return $ip;
}
//是否为IE浏览器
function is_ie()
{
	return strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') ? true : false;
}

//================================================
//调试相关函数
//================================================
//调试记录入文件
function filelog($msg, $filename = "")
{
	$dt = date('Y-m-d H:i:s');
	$filename = str_replace( '/','', str_replace("\\", '/', str_replace( '.','', $filename)));//处理文件名中的点和/
	if(empty($filename)){
		$logfile = date('Y-m-d').'.log';
	}else{
		$logfile = $filename;
	}
	$path = APP_PATH.'/data/runlogs/';
	if (!is_dir($path)) mkdir($path);
	$msg = $dt."	".$msg.chr(13).chr(10);
	@error_log($msg, 3, $path.$logfile);
	@chmod($logfile, 0777);
}

//================================================
//系统相关函数
//================================================
/* 创建目录 */
function makedir($dir) {
	$dirlist = explode('/', $dir); 
	$depth = count($dirlist); 
	$dir = $dirlist[0]; 
	for($i = 0; $i<$depth; $i++) {
		if(empty($dir)) break;
		if(!is_dir($dir)) { 
			if($dir!='.')
				mkdir($dir, 0777);
		} 
		$dir.= '/'.$dirlist[$i+1];
	}
}


//================================================
//字符类处理函数
//================================================
//HTML编码
function htmlspecialchars_deep($string)
{
	return is_array($string) ? array_map('htmlspecialchars_deep', $string) : htmlspecialchars($string, ENT_QUOTES);
}
//转义入口
function quote($str, $noarray = false) {
	if (is_numeric($str))
		return $str + 0;
	if (is_string($str))
		return addslashes_deep($str);
	if (is_array($str)) {
		if($noarray === false) {
			foreach ($str as &$v) {
				$v = quote($v, true);
			}
			return $str;
		} else {
			return '';
		}
	}
	if (is_bool($str))
		return $str ? '1' : '0';
	return '';
}
//转义字符串,数组,及object
function addslashes_deep($string)
{
	if(is_array($string)){
		return array_map('addslashes_deep', $string);
	}
	if (is_object($string)){
		foreach ($string as $key => $val)
        {
            $string->$key = addslashes_deep($val);
        }
        return $string;
	}
	return tea_addslashes($string);
}
//脱转义字符串,数组,及object
function stripslashes_deep($string)
{
	if(is_array($string)){
		return array_map('stripslashes_deep', $string);
	}
	if (is_object($string)){
		foreach ($string as $key => $val)
        {
            $string->$key = stripslashes_deep($val);
        }
        return $string;
	}
	return tea_stripslashes($string);
}
//转义函数
function tea_addslashes($string)
{
	return str_replace(array("\\","\0","\n","\r","\x1a","'",'"'), array("\\\\","\\0","\\n","\\r","\Z","\'",'\"'), $string);
}
//脱转义函数
function tea_stripslashes($string)
{
	return str_replace(array("\\\\","\\0","\\n","\\r","\Z","\'",'\"'), array("\\","\0","\n","\r","\x1a","'",'"'), $string);
}