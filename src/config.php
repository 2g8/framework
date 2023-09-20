<?php

return array(
	'mode' => 'run', 									// 应用程序模式:run,debug，默认为运行模式，
    'reloadr' => false,                                 // 代码修改自动刷新页面,mode=debug时才生效
	
	'app' => array(										// 应用配置
		'controller_path' => APP_PATH.'/controller', 	// 用户控制器程序的路径定义
		'model_path' => APP_PATH.'/model', 				// 用户模型程序的路径定义
		'cache_path' => APP_PATH.'/data/cache', 		// 框架临时文件夹目录
	),
	
	//路由
	'uri' => array(  									// 路由配置
		'type' => 'default', 							// 路由方式. 默认为default方式，可选default,pathinfo,rewrite,tea
		'default_controller' => 'main', 				// 默认的控制器名称
		'default_action' => 'index',  					// 默认的动作名称
		'para_controller' => 'c',  						// 请求时使用的控制器变量标识
		'para_action' => 'a',  							// 请求时使用的动作变量标识
		'suffix' => '',									// 末尾添加的标记，一般为文件类型,如".html"，有助SEO
	),
	
	//数据库
	'db' => array(  									// 数据库连接配置
		'driver' => 'tea_mysql',   						// 驱动类型 tea_pdo,tea_mysql,tea_mysqli,tea_adodb
	),
	
	//模板视图
	'view' => array( 									// 视图配置
		'engine' => 'teamplate', 						// 视图驱动名称teamplate,smarty
		'config' =>array(
			'tplext' => '.htm', 						// 模板文件后缀
			'htmlcompress' => false, 					// 模板文件输出是否压缩html, 只支持teamplate
			'reloadr' => false, 					    // 模板文件输出是否压缩html, 只支持teamplate
			'template_dir' => APP_PATH.'/tpl', 			// 模板目录
			'compile_dir' => APP_PATH.'/data/cache', 	// 编译目录
			'cache_dir' => APP_PATH.'/data/cache', 		// smarty缓存目录
			'left_delimiter' => '{',  					// smarty左限定符
			'right_delimiter' => '}', 					// smarty右限定符
		)
	)
	
);
