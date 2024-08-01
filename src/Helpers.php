<?php

namespace Lcli\AppVcs;

class Helpers {
	private static $workPath = 'public/version/AppVcs';
	public static function config($name = null)
	{
		
		if (function_exists('config')) {
			if (!$name) {
				$config = config("appvcs");
			} else {
				$config = config("appvcs.{$name}");
			}
			
		} else {
			$default = include_once __DIR__ . '/Config/config.php';
			if (!$name) {
				$config = $default;
			} else {
				$config = $default[ $name ];
			}
		}
		return $config;
	}
	
	
	
	/**
	 * 设置配置
	 * @param $options
	 * @return mixed
	 */
	public static function setConfig($options)
	{
		if ($config) {
			if (is_array($config)){
				$config = $options;
				
			}else{
				// 配置文件
				$configFile = include_once $options;
				$config = $configFile;
			}
		}else{
			$config = [];
		}
		return $config;
	}
	
	public static function getWorkPath()
	{
		$rootPath = self::getRootPath($config);
		$workPath = $rootPath.'/'.static::$workPath;
		return $workPath;
	}
	
	public static function getTempFilePath($config=[] )
	{
		$rootPath = self::getWorkPath($config);
		$tempFilePath = $rootPath.'/temp';
		is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
		return $tempFilePath;
	}
	
	public static function getRootPath($config=[])
	{
		if (isset($config['root_path'])){
			return $config['root_path'];
		}
		$rootPath = Helpers::config('root_path');
		is_dir($rootPath) or mkdir($rootPath, 0755, true);
		return $rootPath;
	}
	public static function getRollbackSqlPath($config=[])
	{
		$rootPath = self::getDatabaseSqlPath($config);
		$backupPath = $rootPath.'/rollback';
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function getDatabaseSqlPath($config=[])
	{
		$rootPath = self::getTempFilePath($config);
		$backupPath = $rootPath.'/databases/upgrade';
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	public static function getBackupPath($config=[])
	{
		$rootPath = self::getWorkPath($config);
		$backupPath = $rootPath.'/backup';
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function getVersionPath($config=[])
	{
		$rootPath = Helpers::getWorkPath($config);
		return $rootPath . '/app-vcs-version.txt';
	}
	
	public static function getVersion($config=[])
	{
		$versionFile = self::getVersionPath($config);
		if (!file_exists($versionFile)){
			return '';
		}
		return file_get_contents($versionFile);
	}
	
	public static function setVersion($value, $config=[])
	{
		$versionFile = self::getVersionPath($config);
		return file_put_contents($versionFile, $value);
	}
	
	public static function getDbConfig($config=[])
	{
		if (isset($config['database'])){
			return $config['database'];
		}
		return Helpers::config('database');
	}
	
	public static function getServerUrl($config=[])
	{
		if (isset($config['server_url'])){
			return $config['server_url'];
		}
		return Helpers::config('server_url');
	}
	
	public static function getClientId($config=[])
	{
		if (isset($config['client_id'])){
			return $config['client_id'];
		}
		return Helpers::config('client_id');
	}
	public static function getAppId($config=[])
	{
		if (isset($config['app_id'])){
			return $config['app_id'];
		}
		return Helpers::config('app_id');
	}
}