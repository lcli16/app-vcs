<?php

namespace Lcli\AppVcs;

class Helpers {
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
	
	public static function getRootPath()
	{
		$rootPath = Helpers::config('root_path');
		is_dir($rootPath) or mkdir($rootPath, 0755, true);
		return $rootPath;
	}
	
	public static function getBackupPath()
	{
		$backupPath = Helpers::config('backup_path');
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		return $backupPath;
	}
	
	public static function getDbConfig()
	{
		return Helpers::config('database');
	}
	
	public static function getServerUrl()
	{
		return Helpers::config('server_url');
	}
	
	public static function getClientId()
	{
		return Helpers::config('client_id');
	}
}