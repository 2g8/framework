<?php
class api
{
    protected $_api_config;

    //如果要启动API服务，先去config里面加载 api，再配置apipath即可
    function __construct($apiconfig){
        $this->_api_config['uri'] = $apiconfig['uri'] ? $apiconfig['uri'] : '/api/';
        $this->_api_config['path'] = $apiconfig['path'] ? $apiconfig['path'] : '/api';
        $this->startapi();
    }

    //初始化API函数，指定API的controller路径和访问路径，默认为/api
    function startapi(){
        global $tea;
        if (strpos($_SERVER["REQUEST_URI"], $this->_api_config['uri']) === 0) {
            defined('BASE_PATH') or define('BASE_PATH', $this->_api_config['uri']);
            $tea->conf->app['controller_path'] = APP_PATH . $this->_api_config['path'];
        }
    }


    //API专用的函数
    function get_post_json_data(){
        $data = json_decode(file_get_contents("php://input"), true);
    }


    function requestMethod($origin = false){
        if ($origin) {
            // 获取原始请求类型
            return $_SERVER['REQUEST_METHOD'] ?: 'GET';
        } else {
            if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } else {
                return $_SERVER['REQUEST_METHOD'] ?: 'GET';
            }
        }
    }
}