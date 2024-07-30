<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;

class Backup {
	private static $backupFile = [];
	private static $backupDb   = '';
	
	/**
	 * 备份文件
	 * @param array $upgradeFiles 需要备份的文件
	 * @return bool
	 */
	public static function file($upgradeFiles)
	{
		$rootPath    = Helpers::getRootPath();
		$backupPath  = Helpers::getBackupPath();
		$backupFiles = [];
		foreach ($upgradeFiles as $file) {
			$path = $file['path'];
			if (!$path) {
				continue;
			}
			$filePath      = $backupPath . '/' . $path;
			$localFilePath = $rootPath . '/' . $path;
			FileSystem::writeByPath($filePath, $localFilePath);
			$backupFiles[] = [
				'path' => $filePath,
			];
			
		}
		
		static::$backupFile = $backupFiles;
		
		
		return true;
	}
	
	/**
	 * 备份数据库
	 * @param array $tables 需要备份的表名
	 * @return bool
	 */
	public static function database($tables)
	{
		$backupPath = Helpers::getBackupPath();
		
		$database = Helpers::config('database');
		$host     = $database['host'];
		$port     = $database['port'];
		$db       = $database['database'];
		$username = $database['username'];
		$password = $database['password'];
		
		$backupFileName = date('Ymd') . '_backup.sql';
		$backupFile     = $backupPath . '/' . $backupFileName;
		
		// 构建mysqldump命令
		$command = "mysqldump -u {$username} -p'{$password}' {$db} " . implode(' ', $tables) . " > {$backupFile}";
		
		// 执行命令
		exec($command, $output, $return_var);
		
		// 检查命令执行状态
		if (intval($return_var) === 0 && file_exists($backupFile)) {
			return true;
		}
		static::$backupDb = $backupFile;
		
		return false;
	}
	
	/**
	 * 回滚代码
	 * @return void
	 */
	public static function rollback()
	{
		// 获取备份文件
		static::rollbackFile();
		static::rollbackDb();
	}
	
	/**
	 * 备份文件
	 * @param array $upgradeFiles 需要备份的文件
	 * @return bool
	 */
	protected static function rollbackFile()
	{
		$upgradeFiles = self::$backupFile;
		$rootPath     = Helpers::getRootPath();
		$backupPath   = Helpers::getBackupPath();
		foreach ($upgradeFiles as $file) {
			$path = $file['path'];
			if (!$path) {
				continue;
			}
			$filePath      = $backupPath . '/' . $path;
			$localFilePath = $rootPath . '/' . $path;
			FileSystem::write($localFilePath, file_get_contents($filePath));
		}
		return true;
	}
	
	/**
	 * 备份数据库
	 * @param array $tables 需要备份的表名
	 * @return bool
	 */
	protected static function rollbackDb()
	{
		$backupPath     = Helpers::getBackupPath();
		$database       = Db::instance();
		$backupFileName = date('Ymd') . '_backup.sql';
		$sqlFile        = $backupPath . '/' . $backupFileName;
		$sqlScript      = file_get_contents($sqlFile);
		
		// 使用正则表达式分割SQL脚本成单个语句
		$delimiter     = ';';          // SQL语句结束符
		$pattern       = "/;(\r?\n)/"; // 正则表达式匹配语句结束符后跟换行符
		$sqlStatements = preg_split($pattern, $sqlScript);
		
		// 执行每个SQL语句
		foreach ($sqlStatements as $stmt) {
			$stmt = trim($stmt); // 去除首尾空白字符
			if (!empty($stmt)) { // 检查SQL语句是否为空
				if ($database->query($stmt) === FALSE) {
					echo 'Error executing query: ' . $database->error();
					break; // 如果有错误，停止执行
				}
			}
		}
		
		return false;
	}
	
}