<?php
return [
	// 服务地址
	'server_url'     => 'https://www.baidu.com',
	// 客户端ID
	'client_id'      => 'client-test-1',
	// 应用ID
	'app_id'         => 'test',
	// 执行时生成的临时文件进行存储的目录
	'temp_file_path' => '',
	// 备份目录
	'backup_path'    => '',
	// 安装sdk的服务端目录
	'root_path'      => __DIR__ . '../../../../../',
	// 项目目录 需要更新的代码目录
	'project_path'    => __DIR__ . '../../../../../',
	// 数据库配置
	'database'       => [
		'driver'   => 'mysql',
		'host'     => '127.0.0.1',
		'port'     => 3306,
		'database' => 'lhr_app',
		'username' => 'root',
		'password' => '',
	],
];