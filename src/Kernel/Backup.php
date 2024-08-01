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
	public static function file($upgradeFiles, $config=[])
	{
		$rootPath    = Helpers::getRootPath($config);
		$backupPath  = Helpers::getBackupPath($config);
		$backupFiles = [];
		
		foreach ($upgradeFiles as $file) {
			$path = $file['path'];
			if (!$path) {
				continue;
			}
			$filePath      = $backupPath . '/' . $path;
			$tempFilePath = explode('/', $filePath);
			unset($tempFilePath[count($tempFilePath)-1]);
			$fileDir = implode('/', $tempFilePath);
			is_dir($fileDir) or mkdir($fileDir, 0775,  true);
			$localFilePath = $rootPath . '/' . $path;
			if (file_exists($localFilePath)){
				
				FileSystem::writeByPath($filePath, $localFilePath);
				
				$backupFiles[] = $file;
			}
		}
		static::$backupFile = $backupFiles;
		return true;
	}
	
	/**
	 * 备份数据库
	 * @param array $tables 需要备份的表名
	 * @return bool
	 */
	public static function database($tables, $config=[])
	{
		$tables = array_unique($tables);
		$backupPath = Helpers::getBackupPath($config);
		
		$database = Helpers::getDbConfig($config);
		$host     = $database['host'];
		$port     = $database['port'];
		$db       = $database['database'];
		$username = $database['username'];
		$password = $database['password'];
		
		$backupFileName = date('Ymd') . '_backup.sql';
		$backupFile     = $backupPath . '/' . $backupFileName;
		
		// 构建mysqldump命令
		$command = "mysqldump  --socket=/tmp/mysql.sock  -u'{$username}' -p'{$password}' {$db} " . implode(' ', $tables) . ">'{$backupFile}'";
		
		// 执行命令
		exec($command, $output, $return_var);
		
		// 检查命令执行状态
		if (in_array(intval($return_var), [0, 6])  && file_exists($backupFile)) {
			return true;
		}
		static::$backupDb = $backupFile;
		
		return false;
	}
	
	/**
	 * 回滚代码
	 * @return void
	 */
	public static function rollback($data=null, $config=null)
	{
		// 获取备份文件
		static::rollbackFile($data, $config);
		static::rollbackDb($data, $config);
	}
	
	/**
	 * 备份文件
	 * @param array $upgradeFiles 需要备份的文件
	 * @return bool
	 */
	protected static function rollbackFile($data=[], $config=[])
	{
		$upgradeFiles = self::$backupFile;
		$rootPath     = Helpers::getRootPath($config);
		$backupPath   = Helpers::getBackupPath($config);
		
		foreach ($upgradeFiles as $file) {
			$path = $file['path'];
			if (!$path) {
				continue;
			}
			
			
			$backupFilePath      = $backupPath . '/' . $path;
			$localFilePath = $rootPath . '/' . $path;
			// 新增的文件则删除
			if ($file['state'] === 'A') { // A=新增
				FileSystem::delete($localFilePath);
			}else{ // 其他还原
				FileSystem::write($localFilePath, file_get_contents($backupFilePath));
			}
			
		}
		return true;
	}
	
	/**
	 * 回滚数据库
	 * @param array $tables 需要备份的表名
	 * @return bool
	 */
	protected static function rollbackDb($data=[], $config=[])
	{
		$upgradeData = isset($data['upgrade'])?$data['upgrade']:[];
		$backupPath     = Helpers::getRollbackSqlPath($config);
		
		$database       = Db::instance();
		$backupFileName = "v{$upgradeData['version']}.sql";
		$sqlFile        = $backupPath . '/' . $backupFileName;
		// 如果回滚文件不存在的话, 走备份文件
		if (file_exists($sqlFile)){
			$backupFileName = "v{$upgradeData['version']}.sql";
			$sqlFile        = $backupPath . '/' . $backupFileName;
		}
		
		if (file_exists($sqlFile)){
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
			
			return true;
		}
		return false;
	}
	
}