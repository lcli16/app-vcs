<?php

namespace Lcli\AppVcs;

use Lcli\AppVcs\Cli\Cli;

class Helpers {
	public static $workPath = 'storage/appvcs';
	
	public static $config = [];
	
	public static function config($name = null)
	{
		if (static::$config) {
			$configs = static::$config;
		} else {
			$userConfig = dirname(__DIR__, 4) . '/config/appvcs.php';
			
			if (!file_exists($userConfig)) {
				Helpers::generatedConfig([]);
			}
			$configs = include $userConfig;
		}
		
		if ($name) {
			if (is_array($configs)) {
				$config = isset($configs[$name]) ? $configs[$name] : '';
			} else {
				$config = '';
			}
		} else {
			$config = $configs;
		}
		return $config;
	}
	
	
	public static function getWorkPath()
	{
		$rootPath = static::getRootPath();
		$workPath = $rootPath . '/' . static::$workPath . '/' . self::getAppId();
		return $workPath;
	}
	
	public static function getTempFilePath($upgradeVersion)
	{
		$rootPath     = self::getWorkPath();
		$tempFilePath = $rootPath . '/temp/' . $upgradeVersion;
		is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
		return $tempFilePath;
	}
	
	public static function getProjectPath()
	{
		$projectPath = self::config('project_path');
		if ($projectPath) {
			is_dir($projectPath) or mkdir($projectPath, 0755, true);
		} else {
			$projectPath = self::getRootPath();
		}
		
		return $projectPath;
	}
	
	public static function getRootPath()
	{
		$rootPath = self::config('root_path');
		if (!$rootPath) {
			// 查找配置文件
			$findRootPath = static::findRootPath();
			
			static::$config['root_path'] = $findRootPath;
			return $findRootPath;
		}
		is_dir($rootPath) or mkdir($rootPath, 0755, true);
		return $rootPath;
	}
	
	public static function findRootPath($levels = 1)
	{
		
		$configDir = dirname(__DIR__, $levels);
		$dirs      = explode('/', $configDir);
		if (count($dirs) <= 1) {
			return dirname(__DIR__);
		}
		if (end($dirs) == 'app-vcs' || !file_exists($configDir . '/config/appvcs.php')) {
			
			return self::findRootPath(++$levels);
		}
		
		return $configDir;
	}
	
	public static function getRollbackSqlPath($version)
	{
		$rootPath   = self::getDatabaseSqlPath($version);
		$backupPath = $rootPath . '/rollback';
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function getDatabaseSqlPath($upgradeVersion)
	{
		$rootPath   = self::getTempFilePath($upgradeVersion);
		$backupPath = $rootPath . "/deploy/{$upgradeVersion}/database";
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function generatedDatabaseSqlFilename($version)
	{
		$databaseSqlDir = self::getDatabaseSqlPath($version);
		return $databaseSqlDir . '/mysql.sql';
		
	}
	
	public static function getUpgradeDataFileName()
	{
		return self::getBackupPath() . '/upgrade_config.json';
	}
	
	public static function getUpgradeData()
	{
		$filename = self::getUpgradeDataFileName();
		$content  = @file_get_contents($filename);
		if ($content) {
			$result = json_decode($content, true);
		} else {
			$result = [];
		}
		return $result;
	}
	
	public static function setUpgradeData($data)
	{
		
		$upgradeData = self::getUpgradeData();
		
		self::updateUpgradeData($upgradeData, $data);
		
		return file_put_contents(self::getUpgradeDataFileName(), json_encode($upgradeData, JSON_UNESCAPED_UNICODE));
		
	}
	
	public static function updateUpgradeData(&$localData, $setData)
	{
		
		foreach ($setData as $field => $value) {
			if (is_array($value)) {
				$localData[$field] = self::updateUpgradeData($upgradeData[$field], $value);
			} else {
				$localData[$field] = $value;
			}
		}
		return $localData;
	}
	
	public static function getBackupDbName()
	{
		$backupPath     = self::getBackupPath();
		$version        = self::getVersion();
		$backupFileName = $version . '-backup.sql';
		return $backupPath . '/' . $backupFileName;
	}
	
	public static function getBackupPath()
	{
		$rootPath         = self::getWorkPath();
		$configBackupPath = self::config('backup_path');
		if ($configBackupPath) {
			$backupPath = $configBackupPath;
		} else {
			// 在服务端目录上一级保存, 以免出现全量发布, 未指定项目目录或默认目录导致备份文件一同被删除情况
			$backupPath = $rootPath . '/backup/' . self::getVersion();
		}
		// var_dump($backupPath);die;
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function getVersionPath()
	{
		$rootPath = self::getWorkPath();
		$appId    = Helpers::getAppId();
		return $rootPath . "/{$appId}-version.txt";
	}
	
	public static function getVersion()
	{
		if (static::$config['version']) {
			return static::$config['version'];
		}
		$versionFile = self::getVersionPath();
		if (!file_exists($versionFile)) {
			return '1.0.0';
		}
		return file_get_contents($versionFile);
	}
	
	public static function setVersion($value)
	{
		$versionFile = self::getVersionPath();
		return file_put_contents($versionFile, $value);
	}
	
	public static function getDbConfig()
	{
		return self::config('database');
	}
	
	public static function getServerUrl()
	{
		return self::config('server_url');
	}
	
	public static function getClientId()
	{
		$clientId = self::config('client_id');
		if (!$clientId) {
			$clientId = Helpers::getServerIp();
		}
		return $clientId;
	}
	
	public static function getAppId()
	{
		return self::config('app_id');
	}
	
	public static function generatedConfig(array $config = [])
	{
		// 生成配置文件
		$configDir = dirname(__DIR__, 4) . '/config';
		is_dir($configDir) or mkdir($configDir, 0775, true);
		$source = __DIR__ . '/Config/config.php';
		copy($source, $configDir . '/appvcs.php');
	}
	
	static function getServerIp()
	{
		$hostName = gethostname();
		$host     = gethostbyname($hostName);
		$ip       = $hostName . '@' . $host;
		return $ip;
		$response      = file_get_contents('https://api.ipify.org?format=json');
		$json_response = json_decode($response, true);
		return $json_response['ip'];
	}
	
	public static function output($msg, $type = 'info')
	{
		if (php_sapi_name() === 'cli') {
			$cli = new Cli();
			switch ($type) {
				case 'error':
					$cli->error($msg);
					break;
				case 'success':
					$cli->success($msg);
					break;
				case 'warning':
					$cli->warning($msg);
					break;
				default:
					$cli->info($msg);
					break;
			}
		}
		
	}
	
	
	public static function checkPath($dir)
	{
		
		// 检查是否有配置文件， 如果没有，那么不是项目目录
		$configFile = $dir . '/config/appvcs.php';
		static::output('正在读取配置文件：' . $configFile, 'debug');
		if (!file_exists($configFile)) {
			
			static::output('该工作目录下没有配置文件,无法运行，请手动创建配置文件:' . $configFile, 'error');
			return false;
		} else {
			$config = include $configFile;
			$appId  = isset($config['app_id']) ? $config['app_id'] : '';
			if (!$appId) {
				static::output('配置文件错误，缺少应用 ID（app_id), 请配置文件后重新执行:' . $configFile, 'error');
				return false;
			}
		}
		static::output('配置文件解析成功：' . $configFile, 'success');
		return true;
	}
}