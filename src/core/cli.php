<?php

class cli
{
    public $db;
    public $tea;

	public function __construct($tea){
        $this->db = $tea->db;
        load::file('lib.db.db_apt',TEA_PATH);
        $this->db_apt = new db_apt($tea->db);
        $this->tea = $tea;
	}

	public function create_model($tablename){
        $db_name = $this->tea->conf->db['dbname'];
        $res = $this->db->query('select column_name,column_key from information_schema.columns where TABLE_SCHEMA = \''.$db_name.'\' and TABLE_NAME = \''.$tablename.'\'');
        $columns = $res->fetchall();
        if(!empty($columns) && is_array($columns)){
            foreach ($columns as $column){
                if(strtolower($column['column_key']) == 'pri'){
                    $pri_key = $column['column_name'];
                }
                $str_cols .= '\''.$column['column_name'].'\',';
            }
        }
        $str_cols = substr($str_cols,0,-1);
        $datenow = date('Y-m-d H:i');
        $content = base64_decode('PD9waHAKLy9hdXRob3IgICAgOiBUZWFwaHAgTW9kZWwgR2VuZXJhdG9yIDxkaW5nQGdvbmcuc2k+Ci8vY3JlYXRlZCAgIDogeyRkYXRlbm93fQpjbGFzcyBtb2RlbF97JHRhYmxlbmFtZX0gZXh0ZW5kcyBtb2RlbCB7CiAgICBwdWJsaWMgJHRhYmxlID0gJ3skdGFibGVuYW1lfSc7CS8v5pWw5o2u6KGo5ZCNCiAgICBwdWJsaWMgJHBrID0gJ3skcHJpX2tleX0nOwkJLy/mlbDmja7ooajnmoTkuLvplK4KICAgIHB1YmxpYyAkY2xvdW1ucyA9IFt7JHN0cl9jb2xzfV07IC8v5a2X5q61CgogICAgZnVuY3Rpb24gaW4oJGRhdGEpewogICAgICAgIC8vJGRhdGFbJ2NyZWF0ZV90aW1lJ10gPSBkYXRlKCdZLW0tZCBIOmk6cycpOwogICAgICAgIC8vJGRhdGFbJ3VwZGF0ZV90aW1lJ10gPSBkYXRlKCdZLW0tZCBIOmk6cycpOwogICAgICAgIC8vJGRhdGFbJ2NsaWVudF9pcCddID0gZ2V0aXAoKTsKICAgICAgICBmb3JlYWNoKCRkYXRhIGFzICRrPT4kdil7CiAgICAgICAgICAgIGlmKGluX2FycmF5KCRrLCR0aGlzLT5jbG91bW5zKSl7CiAgICAgICAgICAgICAgICAka2V5IC49ICRrLicsJzsKICAgICAgICAgICAgICAgICR2YWwgLj0gIiciLiR2LiInLCI7CiAgICAgICAgICAgIH0KICAgICAgICB9CiAgICAgICAgJGtleSA9IHN1YnN0cigka2V5LDAsLTEpOwogICAgICAgICR2YWwgPSBzdWJzdHIoJHZhbCwwLC0xKTsKICAgICAgICByZXR1cm4gJHRoaXMtPmRiLT5xdWVyeSgnSU5TRVJUIElOVE8gJy4kdGhpcy0+dGFibGUuIigka2V5KSBWQUxVRVMgKCR2YWwpIik7CiAgICB9CgogICAgZnVuY3Rpb24gdXAoJGRhdGEsJGlkKXsKICAgICAgICAvLyRkYXRhWyd1cGRhdGVfdGltZSddID0gZGF0ZSgnWS1tLWQgSDppOnMnKTsKICAgICAgICBmb3JlYWNoKCRkYXRhIGFzICRrPT4kdil7CiAgICAgICAgICAgIGlmKGluX2FycmF5KCRrLCR0aGlzLT5jbG91bW5zKSl7CiAgICAgICAgICAgICAgICAkdXBzdHIgLj0gJGsuIj0nIi4kdi4iJywiOwogICAgICAgICAgICB9CiAgICAgICAgfQogICAgICAgICR1cHN0ciA9IHN1YnN0cigkdXBzdHIsMCwtMSk7CiAgICAgICAgcmV0dXJuICR0aGlzLT5kYi0+cXVlcnkoJ1VQREFURSAnLiR0aGlzLT50YWJsZS4iIFNFVCAiLiR1cHN0ci4iIFdIRVJFICIuJHRoaXMtPnBrLiI9Ii4kaWQpOwogICAgfQoKfQ==');
        $content = str_replace('{$datenow}',$datenow, $content);
        $content = str_replace('{$tablename}',$tablename, $content);
        $content = str_replace('{$pri_key}',$pri_key, $content);
        $content = str_replace('{$str_cols}',$str_cols, $content);
	    file_put_contents(APP_PATH.'/model/model_'.$tablename.'.php',$content);
    }

    public function update_model($tablename){
	    $model_file = APP_PATH.'/model/model_'.$tablename.'.php';
	    if(file_exists($model_file)){
            $content = file_get_contents($model_file);
            $db_name = $this->tea->conf->db['dbname'];
            $res = $this->db->query('select column_name,column_key from information_schema.columns where TABLE_SCHEMA = \''.$db_name.'\' and TABLE_NAME = \''.$tablename.'\'');
            $columns = $res->fetchall();
            if(!empty($columns) && is_array($columns)){
                foreach ($columns as $column){
                    if(strtolower($column['column_key']) == 'pri'){
                        $pri_key = $column['column_name'];
                    }
                    $str_cols .= '\''.$column['column_name'].'\',';
                }
            }
            $str_cols = substr($str_cols,0,-1);
            $content = preg_replace('/\$cloumns = \[(.*?)\];/','$cloumns = ['.$str_cols.'];',$content);
            file_put_contents($model_file,$content);
        }else{
	        echo 'Model File not exist, Please run create_model first.';
        }
    }

}

if(PHP_SAPI === 'cli'){
    global $argv,$tea;
    $cmdwhitelist = ['create_model','update_model'];
    if(!empty($argv) && in_array($argv[1],$cmdwhitelist)){
        $tea->init(array('db','model'));
        $cli = new cli($tea);
        if($argv[1] == 'create_model'){
            $cli->create_model($argv[2]);
        }
        if($argv[1] == 'update_model'){
            $cli->update_model($argv[2]);
        }
        die;
    }
}