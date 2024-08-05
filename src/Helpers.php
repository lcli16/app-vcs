<?php

namespace Lcli\AppVcs;

class Helpers {
	public static $workPath = 'public/vendor/AppVcs';
	
	public static $config = [];
	public static function config($name = null)
	{
		
		if (function_exists('config')) {
			if (!$name) {
				$config = config("appvcs");
			} else {
				$config = config("appvcs.{$name}");
			}
			
		} else {
			$userConfig = '../../../../config/appvcs.php';
			
			if (file_exists($userConfig)){
			
				$configs = include_once $userConfig;
			}else{
				$configs = include_once __DIR__ . '/Config/config.php';
				
			}
			
			if (!$name) {
				
				$config = $configs;
			} else {
				 if (is_array($configs)){
					 $config = $configs[ $name ];
				 }else{
					 $config = '';
				 }
				
			}
		}
		return $config;
	}
	
 
	public static function getWorkPath($config=[])
	{
		$rootPath = static::getRootPath($config);
		$workPath = $rootPath.'/'.static::$workPath."/".$config['app_id'];
		return $workPath;
	}
	
	public static function getTempFilePath($config=[] )
	{
		$rootPath = self::getWorkPath($config);
		$tempFilePath = $rootPath.'/temp';
		is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
		return $tempFilePath;
	}
	public static function getProjectPath($config=[])
	{
		if (isset($config['project_path'])){
			return $config['project_path'];
		}
		$rootPath = Helpers::config('root_path');
		is_dir($rootPath) or mkdir($rootPath, 0755, true);
		return $rootPath;
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
		$configBackupPath = self::config('backup_path');
		if ($configBackupPath){
			$backupPath = $configBackupPath;
		}else{
			// 在服务端目录上一级保存, 以免出现全量发布, 未指定项目目录或默认目录导致备份文件一同被删除情况
			$backupPath = $rootPath.'/backup';
		}
		
		
		
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