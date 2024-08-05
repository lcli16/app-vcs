<?php

namespace Lcli\AppVcs\Cli;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\Kernel\Backup;
use Lcli\AppVcs\Kernel\Kernel;
use Lcli\AppVcs\Kernel\Request;
use Lcli\AppVcs\Kernel\Transaction;

class Cli {
	
	private $arguments;
	
	private $rootPath    = '';
	private $projectPath = '';
	private $config      = [
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
		'project_path'   => __DIR__ . '../../../../../',
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
	
	public static function instance()
	{
		error_reporting(E_ALL & ~E_WARNING);
		$strict    = in_array('--strict', $_SERVER['argv']);
		$arguments = new \cli\Arguments(compact('strict'));
		
		$arguments->addFlag([
			                    'help',
			                    'h'
		                    ], '查看帮助');
 
		$arguments->addOption([
			                      'register',
			                      'r'
		                      ], [
			                      'description' => '注册客户端服务',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'rollback',
			                      'rb'
		                      ], [
			                      'description' => '回滚项目',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'url',
			                      'u'
		                      ], [
			                      'description' => 'Set the cache directory',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'rollback_version',
			                      'rbv'
		                      ], [
			                      'description' => '回滚版本号',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'path',
			                      'p'
		                      ], [
			                      'description' => '服务端目录',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'project_path',
			                      'pp'
		                      ], [
			                      'description' => '项目目录',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'deploy',
			                      'd'
		                      ], [
			                      'description' => '部署项目',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'deploy_version',
			                      'dv'
		                      ], [
			                      'description' => '部署项目版本',
			                      'default'     => ''
		                      ]);
		$arguments->parse();
		
		return $arguments;
	}
	
	public function boot()
	{
		
		$arguments = self::instance();
		
		$args            = $arguments->getArguments();
		$this->arguments = $arguments;
		
		$isNull = true;
		foreach ($args as $arg => $isOpen) {
			if ($isOpen) {
				$isNull = false;
			}
		}
		
		$invalidArguments = $arguments->getInvalidArguments();
		
		if ($invalidArguments) {
			\cli\err('  %1 无效参数:{:a}  %n', ['a' => $invalidArguments[0]]);
		}
		// 服务端目录
		$rootPath = isset($arguments['path']) ? $arguments['path'] : false;
		if ($rootPath) {
			$this->rootPath = $rootPath;
			$this->config['root_path'] = $rootPath;
		}
		
		// 项目目录
		$projectPath = isset($arguments['project_path']) ? $arguments['project_path'] : false;
		if ($projectPath) {
			$this->projectPath = $projectPath;
			$this->config['project_path'] = $projectPath;
		}
		
		$url = isset($arguments['url']) ? $arguments['url'] : false;
		if ($url) {
			$this->config['server_url'] = $url;
		}
		
		$register = isset($arguments['register']) ? $arguments['register'] : false;
		if ($register !== false) {
			
			if (!$url) {
				\cli\err('  %1 请输入服务器地址, 例如: php appvcs  -r test -u http://www.baidu.com %n');
				exit();
			}
			$this->config['server_url'] = $url;
			$this->register($register, $url);
			exit();
		}
		// 版本回滚
		$rollback = isset($arguments['rollback']) ? $arguments['rollback'] : false;
		if ($rollback !== false) {
			$rollbackVersion = isset($arguments['rollback_version']) ? $arguments['rollback_version'] : false;
			if (!$rollbackVersion) {
				\cli\err('  %1 请输入回滚版本号, 例如: php appvcs  rollback web-test --rbv 1.0.0  %n');
				exit();
			}
			
			$this->rollback($rollback, $rollbackVersion);
			exit();
		}
		
		// 部署
		$deploy = isset($arguments['deploy']) ? $arguments['deploy'] : false;
		if ($deploy !== false) {
			$deployVersion = isset($arguments['deploy_version']) ? $arguments['deploy_version'] : false;
			if (!$deployVersion) {
				\cli\err('  %1 请输入部署应用 ID/版本号, 例如: php appvcs  deploy {appId} --dv 1.1.1  %n');
				exit();
			}
			$this->config['app_id'] = $deploy;
			$this->deploy($deploy, $deployVersion);
			exit();
		}
		
		if ($arguments['help'] || $isNull) {
			$this->help();
		}
		exit(" \n\n");
	}
	
	public function deploy($appId, $version)
	{
		if ($this->rootPath) {
			$rootPath = $this->rootPath;
		} else {
			$rootPath = __DIR__ . '/../../../../../';
		}
		
		$this->config['root_path'] = $rootPath;
		$this->config['app_id'] = $appId;
		Kernel::upgrade($appId, $version, $this->config);
		\Cli\line("部署成功! 版本:v{$version} ");
	}
	
	public function rollback($appId, $version)
	{
		if ($this->rootPath) {
			$rootPath = $this->rootPath;
		} else {
			$rootPath = __DIR__ . '/../../../../../';
		}
		
		
		$workPath          = Helpers::getWorkPath($this->config);
		$workDir           = $rootPath . $workPath;
		$backupVersionName = str_replace('.', "_", $version);
		$backupPath        = $workDir . "/backup/v{$backupVersionName}";
		$data              = [
			'upgrade' => [
				'version' => $version
			],
		];
		
		$this->config['root_path'] = $rootPath;
		$this->config['app_id'] = $appId;
		
		$files    = $this->getAllFiles($backupPath);
		$fileList = [];
		foreach ($files as $file) {
			
			$fileList[] = [
				'path'      => str_replace($backupPath, '', $file),
				'state'     => 'M',
				'full_path' => $file,
			];
		}
		
		Backup::$backupFile = $fileList;
		Backup::rollback($data, $this->config);
		\Cli\line("%2 执行回滚完成! 回滚版本:v{$version} %n");
	}
	
	function getAllFiles($path)
	{
		$files = [];
		// 检查路径是否存在并且是目录
		if (is_dir($path)) {
			$dir = opendir($path);
			while (false !== ($file = readdir($dir))) {
				if ($file != '.' && $file != '..') {
					$fullPath = $path . DIRECTORY_SEPARATOR . $file;
					// 如果是目录，则递归调用自身
					if (is_dir($fullPath)) {
						$files = array_merge($files, $this->getAllFiles($fullPath));
					} else {
						// 如果是文件，则添加到文件列表中
						$files[] = $fullPath;
					}
				}
			}
			closedir($dir);
		}
		
		return $files;
	}
	
	
	public function register($appId, $url)
	{
		$date = date('Y-m-d H:i:s');
		\cli\out("[$date] 正在注册客户端...\n");
		
		if ($this->rootPath) {
			$rootPath = $this->rootPath;
		} else {
			$rootPath = __DIR__ . '/../../../../../';
		}
		$projectPath = $rootPath;
		if ($this->config['project_path']) {
			$projectPath = $this->config['project_path'];
		}
		
		$hostname = gethostname();
		$serverIp = gethostbyname($hostname);
		$config   = [
			'client_id'    => $serverIp,
			'app_id'       => $appId,
			'server_url'   => $url,
			'root_path'    => $rootPath,
			'project_path' => $projectPath
		];
		is_dir($config['root_path'] . "/" . Helpers::$workPath) or mkdir($config['root_path'] . Helpers::$workPath, 0775, true);
		$data = [
			'client_id' => $serverIp,
			'app_id'    => $appId,
			'title'     => $serverIp,
			'config'    => $config
		];
		 
		// 注册客户端
		$result  = \Lcli\AppVcs\Kernel\Request::instance($config)->register($data, $config);
		$command = $result['command'];
		if ($command) {
			$date = date('Y-m-d H:i:s');
			\cli\out("[$date] 客户端注册成功, 正在配置客户端...\n");
			$this->killScript($appId);
			
			foreach ($command as $console) {
				$date = date('Y-m-d H:i:s');
				echo "[$date] " . $console['describe'] . "\n";
				$output = shell_exec($console['command']);
				$date   = date('Y-m-d H:i:s');
				echo "[$date] " . trim($output) ? $output : '执行成功' . "\n";
			}
		}
		$date = date('Y-m-d H:i:s');
		exit("[$date] 注册成功!\n");
	}
	
	
	function killScript($appId)
	{
		$scriptName = "/AppVcs/{$appId}/";
		$result     = shell_exec("pgrep -f '$scriptName'");
		if ($result) {
			$pids = array_filter(explode("\n", $result));
			foreach ($pids as $pid) {
				if ($pid && is_numeric($pid)) {
					shell_exec("kill -9 $pid");
				}
			}
		}
	}
	
	
	public function help()
	{
		exit($this->arguments->getHelpScreen());
	}
}