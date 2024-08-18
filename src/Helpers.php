<?php

namespace Lcli\AppVcs;

use Lcli\AppVcs\Cli\Cli;
use Module\AppVcs\Util\ToolsUtil;
use function AlibabaCloud\Client\value;

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
	
	public static function getZipPath()
	{
		$rootPath     = self::getWorkPath();
		$tempFilePath = $rootPath . '/upgrade/';
		is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
		return $tempFilePath;
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
		
		return $backupPath;
	}
	
	public static function getDatabaseSqlPath($upgradeVersion)
	{
		$rootPath   = self::getTempFilePath($upgradeVersion);
		$backupPath = $rootPath . "/deploy/{$upgradeVersion}/database";
		// is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function generatedDatabaseSqlFilename($version)
	{
		$databaseSqlDir = self::getDatabaseSqlPath($version);
		$file = $databaseSqlDir . '/mysql.sql';
		if (!file_exists($file)){
			$file = Helpers::getProjectPath()."/deploy/{$version}/database/mysql.sql";
		}
		return $file;
	}
	
	public static function getUpgradeDataFileName()
	{
		return self::getUpgradeConfigPath() . '/upgrade_config.json';
	}
	
	public static function getUpgradeData()
	{
		$filename = self::getUpgradeDataFileName();
		if (!is_file($filename)) {
			return [];
		}
		
		$fileHandle = fopen($filename, 'r');
		if ($fileHandle === false) {
			throw new Exception("Failed to open file: {$filename}");
		}
		
		$content = '';
		while (!feof($fileHandle)) {
			$chunk = fread($fileHandle, 8192); // 读取 8KB 的数据块
			if ($chunk === false) {
				fclose($fileHandle);
				throw new Exception("Failed to read chunk from file: {$filename}");
			}
			$content .= $chunk;
		}
		fclose($fileHandle);
		$result = json_decode($content, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return [];
			// throw new Exception('Failed to decode JSON content: ' . json_last_error_msg());
		}
		return $result ? : [];
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
		$backupPath     = self::getUpgradeConfigPath();
		$version        = self::getVersion();
		$backupFileName = $version . '-backup.sql';
		return $backupPath . '/' . $backupFileName;
	}
	
	public static function getUpgradeConfigPath()
	{
		$rootPath   = self::getWorkPath();
		$backupPath = $rootPath . '/config/' . self::getVersion();
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
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
		if (isset(static::$config['version']) && static::$config['version']) {
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
	
	static function findSqlComments($sqlText) {
		preg_match_all('/--.*$|#.*$|^\/\*.*?\*\//s', $sqlText, $matches);
		
		return( $matches[0])?true:false;
	}
	public static function output($msg, $type = 'info', $progress=null)
	{
		if (!is_null($progress)){
			Helpers::setProgress($progress);
		}
		
		if (php_sapi_name() === 'cli') {
			$cli = new Cli();
			
			switch ($type) {
				case 'error':
					$cli->success($msg);
					break;
				case 'success':
					$cli->success($msg);
					break;
				case 'warning':
					$cli->success($msg);
					break;
				case 'debug':
					$cli->success($msg);
					break;
				default:
					$cli->success($msg);
					break;
			}
		}
		self::logWrite($msg, $type);
	}
	
	
	public static function checkPath($dir)
	{
		
		static::output('读取配置:');
		foreach (Helpers::config() as $configkey => $configValue) {
			static::output($configkey . ":" . (is_array($configValue)?json_encode($configValue,JSON_UNESCAPED_UNICODE):$configValue));
		}
		return true;
	}
	
	public static function getProjectId()
	{
		$projectId = self::config('project_id');
		if (!$projectId) {
			$projectDir = self::getRootPath();
			$d = explode('/', dirname($projectDir));
			$projectId  = end($d);
		}
		return $projectId;
	}
	
	/**
	 * @Util 删除目录
	 *
	 * @param $dir        string 目录
	 * @param $removeSelf bool 是否删除本身
	 * @return bool
	 */
	public static function rm($dir, $removeSelf = true)
	{
		if (is_dir($dir)) {
			$dh = opendir($dir);
			while (($file = readdir($dh)) !== false) {
				if ($file != '.' && $file != '..') {
					$fullPath = rtrim($dir, '/\\') . '/' . $file;
					if (is_dir($fullPath)) {
						self::rm($fullPath, true);
					} else {
						@unlink($fullPath);
					}
				}
			}
			closedir($dh);
			if ($removeSelf) {
				@rmdir($dir);
			}
		} else {
			@unlink($dir);
		}
		return true;
	}
	/**
	 * 删除空文件夹
	 * @param      $dir
	 * @param bool $recursive
	 * @return bool
	 */
	static function rmDir($dir, $recursive = true)
	{
		// 使用 scandir() 获取目录中的所有条目
		$entries = scandir($dir);
		
		// 如果 scandir() 返回 false，则目录不存在或无法访问
		if ($entries === false) {
			return false;
		}
		
		// 目录中除了 '.' 和 '..' 之外没有其他任何条目，则认为目录为空
		$isEmpty = count($entries) === 2 && $entries[0] === '.' && $entries[1] === '..';
		if ($isEmpty) {
			if (is_dir($dir)) {
				rmdir($dir);
				if ($recursive) {
					return Helpers::rmDir(dirname($dir), $recursive);
				}
				return true;
			}
			return true;
		}
		return false;
	}
	
	public static function getProgressFile($upgradeVersion)
	{
		$tempFilePath = self::getTempFilePath($upgradeVersion);
		return $tempFilePath . '/progress.txt';
	}
	
	public static function getProgress($upgradeVersion=null)
	{
		$progressFile = self::getProgressFile($upgradeVersion);
		return file_get_contents($progressFile);
	}
	public static function setProgress($value)
	{
		$upgradeVersion = self::getVersion();
		$progressFile = self::getProgressFile($upgradeVersion);
		file_put_contents($progressFile, $value);
	}
	
	
	
	public static function logWrite($content, $type='info', $custom=false)
	{
		$fileName = '';
		$rootPath = self::getRootPath();
		$appId = self::getAppId();
		$id = isset(self::$config['id'])?self::$config['id']:'';
		
		if ($rootPath && $appId && $id){
			$name = md5($appId.'-'.$id);
			$logName = "{$name}.log";
			$storagePath = $rootPath . "/storage/appvcs/{$appId}/log";
			is_dir($storagePath) or mkdir($storagePath, 0775, true);
			$fileName = $storagePath.'/'.$logName;
		}
		if (!$fileName){
			return ;
		}
		$date = date('Y-m-d H:i:s');
		$ip = self::get_client_ip();
		if ($custom){
			$msg = $content."\n";
			file_put_contents($fileName,  $content."\n", FILE_APPEND);
		}else{
			$msg = "[ $type ][{$date}] {$ip} ".$content."\n";
			file_put_contents($fileName, "[ $type ][{$date}] {$ip} ".$content."\n", FILE_APPEND);
		}
		return $msg;
	}

	static function get_client_ip()
	{
		$ipaddress = '';
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ipaddress = $_SERVER['HTTP_CLIENT_IP'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_X_FORWARDED'];
		} else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
		} else if (isset($_SERVER['HTTP_FORWARDED'])) {
			$ipaddress = $_SERVER['HTTP_FORWARDED'];
		} else if (isset($_SERVER['REMOTE_ADDR'])) {
			$ipaddress = $_SERVER['REMOTE_ADDR'];
		} else {
			$ipaddress = 'UNKNOWN';
		}
		return $ipaddress;
	}
}