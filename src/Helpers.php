<?php

namespace Lcli\AppVcs;

class Helpers {
	public static $workPath = 'public/vendor/AppVcs';
	
	
	public static function config($name = null)
	{
		
		$userConfig = dirname(__DIR__, 4) . '/config/appvcs.php';
		
		if (!file_exists($userConfig)) {
			throw new AppVcsException('没有配置文件');
		}
		$configs = include $userConfig;
		if ($name) {
			if (is_array($configs)) {
				$config = isset($configs[$name])?$configs[$name]:'';
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
		$workPath = $rootPath . '/' . static::$workPath . "/" . self::getAppId();
		return $workPath;
	}
	
	public static function getTempFilePath()
	{
		$rootPath     = self::getWorkPath();
		$tempFilePath = $rootPath . '/temp';
		is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
		return $tempFilePath;
	}
	
	public static function getProjectPath()
	{
		$rootPath = self::config('root_path');
		is_dir($rootPath) or mkdir($rootPath, 0755, true);
		return $rootPath;
	}
	
	public static function getRootPath()
	{
		$rootPath = self::config('root_path');
		if (!$rootPath) {
			return dirname(__DIR__, 4);
		}
		is_dir($rootPath) or mkdir($rootPath, 0755, true);
		return $rootPath;
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
		$rootPath   = self::getTempFilePath();
		$backupPath = $rootPath . "/deploy/{$upgradeVersion}/database";
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function generatedDatabaseSqlFilename($version)
	{
		$databaseSqlDir = self::getDatabaseSqlPath($version);
		return $databaseSqlDir . '/v' . $version . '.sql';
		
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
	 
		 self::updateUpgradeData($upgradeData, $data );
	 
		return file_put_contents(self::getUpgradeDataFileName(), json_encode($upgradeData, JSON_UNESCAPED_UNICODE));
		
	}
	
	public static function updateUpgradeData(&$localData, $setData )
	{
		
		foreach ($setData as $field => $value) {
			if (is_array($value)){
				$localData[$field] = self::updateUpgradeData($upgradeData[$field], $value);
			}else{
				$localData[$field] = $value;
			}
		}
		return $localData;
	}
	
	public static function getBackupDbName()
	{
		$backupPath     = self::getBackupPath();
		$version        = self::getVersion();
		$backupFileName = $version . '_backup.sql';
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
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function getVersionPath()
	{
		$rootPath = self::getWorkPath();
		return $rootPath . '/app-vcs-version.txt';
	}
	
	public static function getVersion()
	{
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
		if (!$clientId){
			$clientId =  Helpers::getServerIp();
		}
		return $clientId;
	}
	
	public static function getAppId()
	{
		return self::config('app_id');
	}
	
	public static function generatedConfig(Event $event)
	{
		// 生成配置文件
		$configDir = __DIR__ . '/../../../../config';
		is_dir($configDir) or mkdir($configDir, 0775, true);
		$source = __DIR__ . '/Config/config.php';
		copy($source, $configDir . '/appvcs.php');
	}
	
	static function getServerIp()
	{
		$hostName = gethostname();
		$host = gethostbyname($hostName);
		$ip = $hostName.'@'.$host;
		return $ip;
		$response      = file_get_contents('https://api.ipify.org?format=json');
		$json_response = json_decode($response, true);
		return $json_response['ip'];
	}
}