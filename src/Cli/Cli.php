<?php

namespace Lcli\AppVcs\Cli;

use Lcli\AppVcs\AppVcsException;
use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\Kernel\Backup;
use Lcli\AppVcs\Kernel\FileSystem;
use Lcli\AppVcs\Kernel\Kernel;
use Lcli\AppVcs\Kernel\Request;
use Lcli\AppVcs\Kernel\Transaction;
use splitbrain\phpcli\Options;

class Cli extends \splitbrain\phpcli\CLI {
	public   function output($msg, $type='info')
	{
		if (php_sapi_name() === 'cli'){
			switch ($type){
				case 'error':
					$this->error($msg);
					break;
				case 'success':
					$this->success($msg);
					break;
				case 'warning':
					$this->warning($msg);
					break;
				default:
					$this->info($msg);
					break;
			}
		}else{
			throw new AppVcsException($msg);
		}
		
	}
	private $config = [];
	
	// register options and arguments
	protected function setup(Options $options)
	{
		$banner = <<<BANNER

    ___    ____  ____      _    _____________
   /   |  / __ \/ __ \    | |  / / ____/ ___/
  / /| | / /_/ / /_/ /____| | / / /    \__ \
 / ___ |/ ____/ ____/_____/ |/ / /___ ___/ /
/_/  |_/_/   /_/          |___/\____//____/

-by 1cli
BANNER;
		
		$options->setHelp($banner);
		$options->registerOption('version', '版本信息', 'v');
		// $options->registerOption('register', '注册客户端', 'r');
		$options->registerCommand("register", "注册客户端");
		$options->registerArgument('appId', 'APP-VCS 管理平台应用 ID', true);
		$options->registerCommand("rollback", "回滚项目版本");
		$options->registerCommand("deploy", "部署项目");
		
		
		$options->registerOption('url', '设置服务端APi 地址', 'u', true);
		$options->registerOption('project_path', '项目目录', 'P', true);
		$options->registerOption('client_id', '客户端 ID', 'c', true);
		$options->registerOption('project_version', '指定版本号', 'V', true);
		$options->registerOption('path', '安装库根目录', 'p', true);
		$options->registerOption('database', "数据库配置，格式：mysql://username:password@host:port/dbname\n例如：mysql://root:root@127.0.0.1:port/app-vcs", 'd', true);
	}
	
	// implement your code
	protected function main(Options $options)
	{
		
		$this->config = Helpers::config();
		$this->setConfig('client_id', Helpers::getServerIp());
		
		$cmd  = $options->getCmd();
		$args = $options->getArgs();
		// 客户端 ID
		if ($clientId = $options->getOpt("client_id")) {
			$this->setConfig('client_id', $clientId);
		}
		// 服务端目录
		if ($path = $options->getOpt('path')) {
			$this->setConfig('root_path', $path);
		}
		// 项目目录
		if ($projectPath = $options->getOpt('project_path')) {
			$this->setConfig('project_path', $projectPath);
		}
		
		// 服务端 API
		if ($url = $options->getOpt('url')) {
			$this->setConfig('server_url', $url);
		}
		// 数据库配置
		if ($database = $options->getOpt('database')) {
			$parseDb  = parse_url($database);
			$dbConfig = [
				'driver'   => $parseDb['scheme'],
				'host'     => $parseDb['host'],
				'port'     => $parseDb['port'],
				'database' => str_replace('/', '', $parseDb['path']),
				'username' => $parseDb['user'],
				'password' => $parseDb['pass'],
			];
			$this->setConfig('database', $dbConfig);
		}
		
		// 设置版本号
		$projectVersion = $options->getOpt('project_version');
		//注册客户端
		if ($cmd == 'register') {
			if (!$url) {
				$this->error('请输入服务器地址, 例如: php appvcs  -r test -u http://www.baidu.com');
				exit();
			}
			$appId = $args[0];
			$this->setConfig('app_id', $appId);
			$this->register($appId, $url);
			exit();
		}
		
		//注册客户端
		if ($cmd == 'rollback') {
			if (!$projectVersion) {
				$this->error('请输入回滚版本号, 例如: php  appvcs -V 1.0.0 -P /www/wwwroot/tzkj/gentou rollback gentou  ');
				exit();
			}
			$appId = $args[0];
			$this->setConfig('app_id', $appId);
			$this->rollback($appId, $projectVersion);
			exit();
		}
		
		//部署客户端
		if ($cmd == 'deploy') {
			if (!$projectVersion) {
				$this->error('请输入部署应用 ID/版本号, 例如: php appvcs  deploy {appId} --dv 1.1.1  ');
				exit();
			}
			$appId = $args[0];
			$this->setConfig('app_id', $appId);
			$this->deploy($appId, $projectVersion);
			exit();
		}
		if (!$cmd) {
			exit($options->help());
		}
		exit(" \n\n");
	}
	
	
	public static function instance()
	{
		error_reporting(E_ALL & ~E_WARNING);
	}
	
	public function setConfig($name, $value)
	{
		$this->config[$name] = $value;
		Helpers::$config     = $this->config;
	}
	
	
	public function deploy($appId, $version)
	{
		$rootPath = $this->config['root_path'];
		if (!$rootPath) {
			$rootPath = dirname(__DIR__, 5);
		}
		
		$this->config['root_path'] = $rootPath;
		$this->config['app_id']    = $appId;
		Kernel::upgrade($appId, $version);
		$this->success("部署成功! 版本:v{$version} ");
	}
	
	public function rollback($appId, $version)
	{
		$rootPath = $this->config['root_path'];
		if (!$rootPath) {
			$rootPath = dirname(__DIR__, 5);
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
		$this->config['app_id']    = $appId;
		
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
		$this->success("执行回滚完成! 回滚版本:v{$version}");
	}
	
	
	public function register($appId, $url)
	{
		$date = date('Y-m-d H:i:s');
		$this->info("[$date] 正在注册客户端...\n");
		
		$rootPath = $this->config['root_path'];
		if (!$rootPath) {
			$rootPath = dirname(__DIR__, 5);
		}
		$projectPath = $rootPath;
		if ($this->config['project_path']) {
			$projectPath = $this->config['project_path'];
		}
		
		
		$serverIp = Helpers::getServerIp();
		$config   = $this->config;
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
			$this->success("[$date] 客户端注册成功, 正在配置客户端...\n");
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
	
	
}