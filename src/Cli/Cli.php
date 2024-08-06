<?php

namespace Lcli\AppVcs\Cli;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\Kernel\Backup;
use Lcli\AppVcs\Kernel\FileSystem;
use Lcli\AppVcs\Kernel\Kernel;
use Lcli\AppVcs\Kernel\Request;
use Lcli\AppVcs\Kernel\Transaction;

class Cli {
	
	private $arguments;
	
	private $rootPath    = '';
	private $projectPath = '';
	private $config      =  [];
	
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
			                      'description' => '设置服务端APi 地址',
			                      'default'     => ''
		                      ]);
		$arguments->addOption([
			                      'client_id',
			                      'c'
		                      ], [
			                      'description' => '客户端ID',
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
		$this->config = Helpers::config();
		
		$arguments = self::instance();
		$this->config['client_id'] = Helpers::getServerIp();
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
		
		// 客户端 ID
		$clientId = isset($arguments['client_id']) ? $arguments['client_id'] : false;
		if ($clientId) {
			$this->config['client_id'] = $clientId;
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
			$rootPath = dirname(__DIR__, 5) ;
		}
		
		$this->config['root_path'] = $rootPath;
		$this->config['app_id'] = $appId;
		Kernel::upgrade($appId, $version);
		\Cli\line("部署成功! 版本:v{$version} ");
	}
	
	public function rollback($appId, $version)
	{
		if ($this->rootPath) {
			$rootPath = $this->rootPath;
		} else {
			$rootPath = __DIR__ . '/../../../../../';
		}
		
		
		$workPath          = Helpers::getWorkPath();
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
		
		$files    = FileSystem::getAllFiles($backupPath);
		$fileList = [];
		foreach ($files as $file) {
			
			$fileList[] = [
				'path'      => str_replace($backupPath, '', $file),
				'state'     => 'M',
				'full_path' => $file,
			];
		}
		
		Backup::rollback($data);
		\Cli\line("%2 执行回滚完成! 回滚版本:v{$version} %n");
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
		
	 
		$serverIp =  Helpers::getServerIp();
		$config   =  $this->config;
		is_dir($config['root_path'] . "/" . Helpers::$workPath) or mkdir($config['root_path'] . Helpers::$workPath, 0775, true);
		$data = [
			'client_id' => $serverIp,
			'app_id'    => $appId,
			'title'     => $serverIp,
			'config'    => $config
		];
		 
		// 注册客户端
		$result  = \Lcli\AppVcs\Kernel\Request::instance()->register($data);
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