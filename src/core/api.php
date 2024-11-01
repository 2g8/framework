<?php
class api
{
    protected $_api_config;
    public $appid, $apikey, $auth, $request_method, $data;

    //如果要启动API服务，先去config里面加载 api，再配置apipath即可
    function __construct($apiconfig){
        $this->_api_config['uri'] = isset($apiconfig['uri']) ? $apiconfig['uri'] : '/api/';
        $this->_api_config['path'] = isset($apiconfig['path']) ? $apiconfig['path'] : '/api';
        $this->_api_config['access-control-allow-origin'] = isset($apiconfig['access-control-allow-origin']) ? $apiconfig['access-control-allow-origin'] : '*';
        $this->_api_config['access-control-allow-credentials'] = isset($apiconfig['access-control-allow-credentials']) ? $apiconfig['access-control-allow-credentials'] : 'true';
        $this->_api_config['access-control-allow-methods'] = isset($apiconfig['access-control-allow-methods']) ? $apiconfig['access-control-allow-methods'] : 'PUT, GET, POST, PATCH, DELETE, OPTIONS';
        $this->_api_config['access-control-allow-headers'] = isset($apiconfig['access-control-allow-headers']) ? $apiconfig['access-control-allow-headers'] : 'X-Requested-With, Authorization, Content-Type, X-Api-Key, X-App-Id';
        $this->_api_config['access-control-max-age'] = isset($apiconfig['access-control-max-age']) ? $apiconfig['access-control-max-age'] : '86400';
        $this->_api_config['origin_method'] = isset($apiconfig['origin_method']) ? $apiconfig['origin_method'] : false;
        $this->startapi();
    }

    //初始化API函数，指定API的controller路径和访问路径，默认为/api
    function startapi(){
        global $tea;
        if (strpos($_SERVER["REQUEST_URI"], $this->_api_config['uri']) === 0) {
            defined('BASE_PATH') or define('BASE_PATH', $this->_api_config['uri']);
            $tea->conf->app['controller_path'] = APP_PATH . $this->_api_config['path'];
        }
        $this->request_method = $this->get_request_method($this->_api_config['origin_method']);
        /* Handle CORS */
        // Specify domains from which requests are allowed
        header('Access-Control-Allow-Origin: '.$this->_api_config['access-control-allow-origin']);
        // Specify which request methods are allowed
        header('Access-Control-Allow-Methods: '.$this->_api_config['access-control-allow-methods']);
        // Specify is Credentials are allowed
        header('Access-Control-Allow-Credentials: '.$this->_api_config['access-control-allow-credentials']);
        // Additional headers which may be sent along with the CORS request
        header('Access-Control-Allow-Headers: '.$this->_api_config['access-control-allow-headers']);
        // Set the age to improve speed/caching.
        header('Access-Control-Max-Age: '.$this->_api_config['access-control-max-age']);
        $this->data = $this->get_data();
    }

    //API专用的函数
    //get auth token
    function get_auth(){
        //auth in headers
        $headers = getallheaders();
        if (array_key_exists('Authorization', $headers)) {
            if (substr($headers['Authorization'], 0, 6) == 'Basic ') {
                $this->auth['type'] = 'basic';
                $this->auth['token'] = trim(substr($headers['Authorization'], 6));
            }
            if (substr($headers['Authorization'], 0, 6) == 'Token ') {
                $this->auth['type'] = 'token';
                $this->auth['token'] = trim(substr($headers['Authorization'], 6));
            }
            if (substr($headers['Authorization'], 0, 7) == 'Bearer ') {
                $this->auth['type'] = 'bearer';
                $this->auth['token'] = trim(substr($headers['Authorization'], 7));
            }
        }
        return $this->auth ?? false;
    }

    //get app id
    function get_app_id(){
        $headers = getallheaders();
        if (array_key_exists('X-App-Id', $headers)) {
            $this->appid['type'] = 'X-App-Id_in_header';
            $this->appid['appid'] = $headers['X-App-Id'];
        }
        $cookie = array_change_key_case($_COOKIE,CASE_LOWER);
        if (array_key_exists('x-app-id', $cookie)) {
            $this->appid['type'] = 'x-app-id_in_cookie';
            $this->appid['appid'] = $cookie['x-app-id'];
        }
        $query = array_change_key_case($_REQUEST,CASE_LOWER);
        if (array_key_exists('x-app-id', $query)) {
            $this->appid['type'] = 'x-app-id_in_query';
            $this->appid['appid'] = $query['x-app-id'];
        }
        return $this->appid ?? false;
    }

    //get api key
    function get_api_key(){
        $headers = getallheaders();
        if (array_key_exists('X-Api-Key', $headers)) {
            $this->apikey['type'] = 'x-api-key_in_header';
            $this->apikey['apikey'] = $headers['X-Api-Key'];
        }
        //apikey in cookie
        $cookie = array_change_key_case($_COOKIE,CASE_LOWER);
        if (array_key_exists('x-api-key', $cookie)) {
            $this->apikey['type'] = 'x-api-key_in_cookie';
            $this->apikey['apikey'] = $cookie['x-api-key'];
        }
        //apikey in query
        $query = array_change_key_case($_REQUEST,CASE_LOWER);
        if (array_key_exists('x-api-key', $query)) {
            $this->apikey['type'] = 'x-api-key_in_query';
            $this->apikey['apikey'] = $query['x-api-key'];
        }
        return $this->apikey ?? false;
    }

    function get_data(){
        switch ($this->request_method) {
            case 'POST':
                if(isArray($_POST)){
                    $requestData = $_POST;
                }else{
                    $requestData = $this->get_json();
                    if(!$requestData){
                        $requestData = $this->get_input();
                    }
                }
                break;
            case 'GET':
                $requestData = $_GET;
                break;
            case 'DELETE':
                $requestData = $_DELETE;
                break;
            case 'PUT':
            case 'PATCH':
                parse_str(file_get_contents('php://input'), $requestData);
                //if the information received cannot be interpreted as an arrangement it is ignored.
                if (!isArray($requestData)) {
                    $requestData = [];
                }
                break;
            case 'OPTIONS': //根据header直接返回
                echo 'replied to options with Access-Control-Allow headers';
                http_response_code(204);exit;
                break;
            default:
                $requestData = [];
                break;
        }
        return $requestData;
    }

    function get_json(){
        $raw = $this->get_input();
        $data = json_decode($raw, true);
        if(json_last_error() == JSON_ERROR_NONE){
            return $data;
        }
        return false;
    }

    function get_input(){
        return file_get_contents("php://input");
    }


    function get_request_method($origin = false){
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