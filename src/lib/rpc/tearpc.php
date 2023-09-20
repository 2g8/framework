<?php

class tearpc {
	public	$ip_list = array(),			//IP白名单
			$auth_list = array(),		//授权白名单
			$auth_function = array(),	//用户鉴权函数
			$out_buffer = "",	//输出
			$charset = "UTF-8",	//输出
			$function_list = array();	//开放函数列表
	
	function set_auth($auth_str){
        $this->auth_list[] = $auth_str;
    }
    
    function errormsg($code,$msg){    	
        //发header
        $this->out_header();
        //发送错误信息
    	$this->out_buffer['code'] = $code;
    	$this->out_buffer['errormsg'] = $msg;
        //发送footer
        $this->out_footer();
    }
    
	function add($function, $class = '', $name = '') {
		//如果函数为空
		if(empty($function)) $this->errormsg(301,"TEA RPC Error - Functions can not be empty!");
		//如果函数为一个类
        if(is_object($function)){
            $obj = $function;
            $function = get_class_methods(get_class($obj));
            $name = $function;
        }
        //如果名称为空，则和函数对应起来
        if (empty($name)) {
            $name = $function;
        }
        //如果函数为字符串
        if(is_string($function)){	
            if(empty($class)){
                $func = $function;
            	if(!is_callable($func)) $this->errormsg(302,"TEA RPC Error - Function $function does not exists!");
            }elseif(is_object($class)) {
                $func = array(&$class, $function);
            	if(!is_callable($func,true,$call_func)) $this->errormsg(303,"TEA RPC Error - Function $call_func does not exists!");
            }elseif(is_string($class)) {
                $func = array($class, $function);
            	if(!is_callable($func,true,$call_func)) $this->errormsg(304,"TEA RPC Error - Function $call_func does not exists!");
            }            
            $this->function_list[strtolower($name)] = $func;
        }else{
            if(count($function) != count($name)){
                $this->errormsg(305,"TEA RPC Error - Functions number and Name number is not equal!");
            }
            foreach($function as $key=>$func){
                $this->add($func, $class, $name[$key]);
            }
        }
        return true;
    }
    
    function set_charset($charset){
        $this->charset = $charset;
    }
    
    function set_gzip($enablegzip){
        $this->gzip = $enablegzip;
    }    
    
    function out_header(){
        header("HTTP/1.1 200 OK");
        header("Content-Type: application/json; charset={$this->charset}");
        header("X-Powered-By: Teaphp RPC Server/1.0");
        //header('P3P: CP="CAO DSP COR CUR ADM DEV TAI PSA PSD IVAi IVDi CONi TELo OTPi OUR DELi SAMi OTRi UNRi PUBi IND PHY ONL UNI PUR FIN COM NAV INT DEM CNT STA POL HEA PRE GOV"');
        header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');        
    	//清输出
        while(ob_get_length() !== false){
        	@ob_end_clean();
        }
        @ob_start();
        ob_implicit_flush(0);
        $this->out_buffer = "";        
    }

    function out_footer(){
       	//输出结果
        echo $this->out_gzip(json_encode($this->out_buffer));
        ob_end_flush();
        exit;    
    }
    
    function out_gzip($buffer) {
        $len = strlen($buffer);
        if ($this->gzip && strstr($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')){
            $gzbuffer = gzencode($buffer);
            $gzlen = strlen($gzbuffer);
            if ($len > $gzlen) {
                header("Content-Length: $gzlen");
                header("Content-Encoding: gzip");
                return $gzbuffer;
            }
        }
        header("Content-Length: $len");
        return $buffer;
    }
    
    function call_function(){
    	load::file('core.filter');
    	filter::input();
    	$function_name = strtolower($_REQUEST['func']);
    	if(array_key_exists($function_name, $this->function_list)){
    		$function = $this->function_list[$function_name];
    		$args = $_REQUEST['args'];
    		$this->out_buffer['code'] = 1;
    		$this->out_buffer['result'] = call_user_func_array($function, $args);
    	}else{
    		$this->errormsg(211,"API Error - Function $function_name does not exists!");
    	}
    }
    
    function start(){
        //判断权限
    	$ip = getip();
        if(count($this->ip_list)>0 and !in_array($ip,$this->ip_list)){
        	$this->errormsg(201,"Access Deny! IP Denied!");
        }
        if(count($this->auth_list)>0 and !in_array(trim($_GET['auth']),$this->auth_list)){
        	$this->errormsg(202,"Access Deny! Auth Failed!");
        }
        //发header
        $this->out_header();
        //调用程序
        $this->call_function();
        //发送footer
        $this->out_footer();
        
    }
    
    

}