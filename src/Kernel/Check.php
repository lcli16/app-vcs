<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use splitbrain\phpcli\Colors;
use splitbrain\phpcli\TableFormatter;

class Check {
	protected static  $env = [
		'php' => ['5.6.00','8.2.00'],
		'functions' => ['exec', 'shell_exec'],
		'php_ini' => [
			'max_execution_time' => 1200
		],
		'config' => [
			// 项目标识(🔅必填)
			'project_id'         => ['require' => true, 'tips' => '示例：demo_project', 'name' => '项目标识'],
			// 通讯域名(🔅必填)： 注册时会生成一串独一且固定的通迅文件，用于和平台交互
			'client_connect_url' => ['require' => true, 'tips' => '示例：http://dev.a1.tzkj.com', 'name' => '通讯域名'],
			// 服务地址(🔅必填)
			'server_url'         => ['require' => true, 'tips' => '示例：https://www.baidu.com', 'name' => '服务地址'],
			// 应用ID (🔅必填)
			'app_id'             =>  ['require' => true, 'tips' => '示例：demo_app', 'name' => '应用ID'],
			// 工作目录 (🔅必填)
			'work_path'       =>  ['require' => true, 'tips' => '默认：data， 最终生成路径:根目录+工作目录/xxxx', 'name' => '工作目录'],
			// 客户端ID(🉑非必填)
			'client_id'          => ['require' => false, 'tips' => '示例：client_app_vcs', 'name' => '客户端ID'],
			// 执行时生成的临时文件进行存储的目录(非必填)
			'temp_file_path'     =>  ['require' => true, 'tips' => '示例：temp, 根目录+临时目录， 默认为：temp', 'name' => '临时目录'],
			// 备份目录 (🉑非必填)
			'backup_path'        =>  ['require' => true, 'tips' => '示例：backup, 根目录+备份目录， 默认为：backup', 'name' => '备份目录'],
			// 安装sdk的服务端目录 (非必填)
			'root_path'          =>   ['require' => true, 'tips' => '示例：/www/wwwroot/you_plugin_dir， 默认为:注册命令时的项目根目录', 'name' => '插件服务目录'],
			// 项目目录 需要更新的代码目录 (🔅必填)
			'project_path'       =>  ['require' => false, 'tips' => '示例：/www/wwwroot/you_project_dir， 默认为:注册命令时的项目根目录', 'name' => '项目目录'],
			// 数据库配置(🉑非必填)
			'database'           => [
				'require' => false, 'tips' => '数据库配置','name' => '数据库配置',
				'config' => [
					'driver'   => ['require' => false, 'tips' => ' 默认:mysql','name' => '数据库驱动'],
					'host'     =>  ['require' => false, 'tips' => '  默认:127.0.0.1','name' => '数据库地址'],
					'port'     => ['require' => false, 'tips' => ' 默认:3306','name' => '数据库端口'],
					'database' => ['require' => false, 'tips' => '','name' => '数据库名'],
					'username' => ['require' => false, 'tips' => '','name' => '数据库账户名'],
					'password' => ['require' => false, 'tips' => '','name' => '数据库密码'],
				]
			],
		]
	];
	public static function run()
	{
		echo "\r\n";
		
		// colored columns
		$info = [
			'php_version' => ['status' => 1, 'name' => "PHP版本             ", 'help' => ''],
			'exec' => ['status' => 1, 'name' => 'exec(命令函数)      ', 'help' => ''],
			'shell_exec' => ['status' => 1, 'name' => 'shell_exec(命令函数)', 'help' => ''],
			'config' => ['status' => 1, 'name' => 'config(执行配置)    ', 'help' => ''],
		];
		$isPass = true;
		$pluginEnv = self::$env;
		// 检查环境
		// 1. PHP 版本
		$phpVersion = phpversion();
		
		$pluginPhp  = $pluginEnv['php'];
		$phpVersionInt = (int)str_replace('.', '', $phpVersion);
		$minVersionInt = (int)str_replace('.', '', $pluginPhp[0]);
		$maxVersionInt = (int)str_replace('.', '', $pluginPhp[1]);
		if ($phpVersionInt<$minVersionInt || $phpVersionInt > $maxVersionInt){
			$info['php_version']['help'] = "PHP 版本必须 >={$pluginPhp[0]} 且 <= {$pluginPhp[1]}, 你的版本：v{$phpVersion}";
			$info['php_version']['status'] = 0;
		} 
		
		// 2. PHP 配置
		$php_ini  = $pluginEnv['php_ini'];
		$mxt = ini_get('max_execution_time');
		if ($mxt < $php_ini['max_execution_time']){
			Helpers::output("PHP 配置 max_execution_time 建议 >= {$php_ini['max_execution_time']}s ",'warning');
		}
		
		// 3. 禁用函数
		$pluginFunctions = $pluginEnv['functions'];
		$funcStr = implode(',', $pluginFunctions);
		foreach ($pluginFunctions as $func){
			if (!function_exists($func)){
				$info[$func]['help'] ="请解除 PHP 安全函数： {$funcStr} ";
				$isPass = false;
			    $info[$func]['status'] = 0;
			}
		}
		
		// 4. 配置检查
		$pluginConfig = $pluginEnv['config'];
		$isPass = static::configCheck($pluginConfig);
		if (!$isPass){
			$info['config']['status'] = 0;
		}
		// 5.检查 cli 是否正常
		exec('php --version', $out,$code);
		if ($code !== 0){
			Helpers::output("命令行运行失败， 请检查是否开放 PHP CLI 函数 exec ",'error');
		}
		
		$out = shell_exec('php --version');
		if (!$out) {
			Helpers::output('命令行运行失败， 请检查是否开放 PHP CLI 函数 shell_exec ', 'error');
		}
		Helpers::output($out, 'info');
		foreach ($info as $key => $item) {
			$val = $item['name'];
			$help = $item['help'];
			$statusStr = '-';
			$color = Colors::C_BLACK;
			if ($item['status'] === 1){
				$statusStr = '通过';
				$color = 'success';
			}else if ($item['status'] === 0){
				$isPass = false;
				$statusStr = '不通过';
				$color = 'error';
			}  else if ($item['status'] === 2){
				$statusStr = '警告';
				$color = 'warning';
			}
			Helpers::output($val.' '.$item['value'].' '.$statusStr, $color);
			
			if ($help) Helpers::output($help, 'info');
		}
		
		Helpers::output('环境检查完成:'.($isPass?'通过':'不通过'), $isPass?'success':'error');
		echo "\r\n";
		return $isPass;
		
		
	}
	
	
	
	public static function configCheck($pluginConfig)
	{
		$isPass = true;
		$config = Helpers::config();
		foreach ($config as $configName => $value){
			$pluginConfigItem = $pluginConfig[$configName]??[];
			if ( isset($pluginConfigItem['require']) && $pluginConfigItem['require'] && !$value){
				Helpers::output("配置 【{$pluginConfigItem['name']}】 错误：配置不能为空或不合法",'error');
				Helpers::output("{$pluginConfigItem['tips']}",'error');
				$isPass = false;
				$children = $value['config']??null;
				if ($children){
					$isPass = static::configCheck($children);
				}
				break;
			}else{
				$name = $pluginConfigItem['name']??$configName;
			}
		}
		return $isPass;
	}
}