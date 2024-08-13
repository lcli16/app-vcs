<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use splitbrain\phpcli\CLI;

class Backup {
	
	
	public static function fixPaht($path)
	{
		$path = str_replace('"', '', $path);
		$path = str_replace('\\', '/', $path);
		$list = explode('/', $path);
		return $path;
	}
	
	/**
	 * 备份文件
	 * @param array $upgradeFiles 需要备份的文件
	 * @return bool
	 */
	public static function file($upgradeFiles, $upgradeData)
	{
	 
		$rootPath   = Helpers::getProjectPath();
		$backupPath = Helpers::getBackupPath();
		$version    = Helpers::getVersion();
		foreach ($upgradeFiles as $file) {
			$path = self::fixPaht($file['path']);
			Helpers::output('正在备份文件:'.$path);
			if (!$path) {
				Helpers::output('文件路径为空，自动过滤:'.$path,'warning');
				continue;
			}
			$filePath     = $backupPath . '/' . $path;
			$tempFilePath = explode('/', $filePath);
			unset($tempFilePath[count($tempFilePath) - 1]);
			$fileDir = implode('/', $tempFilePath);
			is_dir($fileDir) or mkdir($fileDir, 0775, true);
			$localFilePath = $rootPath . '/' . $path;
			if (file_exists($localFilePath)) {
				FileSystem::writeByPath($filePath, $localFilePath);
				Helpers::output('文件备份完成:'.$path,'success');
			}else{
				Helpers::output('文件不存在:'.$path,'error');
			}
		}
		
		return true;
	}
	
	/**
	 * 备份数据库
	 * @param array $tables 需要备份的表名
	 * @return bool
	 */
	public static function database($tables, $upgradeVersion)
	{
		Helpers::output('正在备份数据库中...:'.$path);
		Helpers::output(json_decode($tables?(is_array($tables)?json_encode($tables,JSON_UNESCAPED_UNICODE):$tables):[]));
		if (!$tables) {
			return true;
		}
		$tables      = array_unique($tables);
		$sqlFilePath = Helpers::generatedDatabaseSqlFilename($upgradeVersion);
		Helpers::output('读取数据库备份文件:'.$sqlFilePath);
		$sqlTables   = Db::getOperatorTableRecords($sqlFilePath);
		if ($sqlTables) {
			$tables = array_merge($tables, $sqlTables);
			// 设置操作表
			Helpers::setUpgradeData(['versionInfo' => ['tables_files' => $tables]]);
		}
		
		
		$database   = Helpers::getDbConfig();
		$host       = $database['host'];
		$port       = $database['port'];
		$db         = $database['database'];
		$username   = $database['username'];
		$password   = $database['password'];
		$version    = Helpers::getVersion();
		$backupFile = Helpers::getBackupDbName();
		$dumpTables = [];
		foreach ($tables as $table) {
			Helpers::output('正在备份数据库表:'.$table);
			if (!$table) {
				continue;
			}
			// SQL 查询语句
			$sql = "SHOW TABLES LIKE '$table'";
			Helpers::output('查询表是否存在:'.$sql);
			// 执行查询
			$isPassTable = Db::instance()->query($sql);
			Helpers::output('查询表是否存在:');
			Helpers::output(json_encode($isPassTable));
			// 判断表是否存在
			if ($isPassTable->num_rows > 0) {
				$dumpTables[] = $table;
				
			}else{
				Helpers::output('数据库表不存在:'.$table, 'warning');
			}
		}
		if (!$dumpTables) {
			return true;
		}
		
		$command =
			"mysqldump  --socket=/tmp/mysql.sock  -u'{$username}' -p'{$password}' {$db} " . implode(' ', $dumpTables) . ">'{$backupFile}'";
		Helpers::output('执行备份数据库:'.$command);
		// 执行命令
		exec($command, $output, $return_var);
		// 检查命令执行状态
		if (in_array(intval($return_var), [
				0,
				6
			]) && file_exists($backupFile)) {
			Helpers::output('数据库备份完成，保存至:'.$backupFile, 'success');
			return true;
		}else{
			Helpers::output('数据库备份失败:'.$output,'error');
		}
		
		return false;
	}
	
	/**
	 * 回滚代码
	 * @return void
	 */
	public static function rollback($data = null)
	{
		Helpers::output('正在回滚中');
		Helpers::output(is_array($output)?json_encode($output,JSON_UNESCAPED_UNICODE):$output);
		// 获取备份文件
		static::rollbackFile($data);
		static::rollbackDb($data);
	}
	
	/**
	 * 备份文件
	 * @param array $upgradeFiles 需要备份的文件
	 * @return bool
	 */
	protected static function rollbackFile($data = [])
	{
		$upgradeData = Helpers::getUpgradeData();
		
		$rootPath    = Helpers::getProjectPath();
		$backupPath  = Helpers::getBackupPath();
		$backupFiles = FileSystem::getFiles($backupPath);
		
		// 找到新增的文件， 删除了
		$upgradeFiles = isset($upgradeData['files']) ? $upgradeData['files'] : [];
		foreach ($upgradeFiles as $file) {
			$path = $file['path'];
			Helpers::output('获取更新包文件:'.$path);
			if (!$path) {
				Helpers::output('更新包文件为空:'.$path,'warning');
				continue;
			}
			$backupFilePath = $backupPath . '/' . $path;
			$localFilePath  = $rootPath . '/' . $path;
			Helpers::output('读取备份文件:'.$backupFilePath,'debug');
			Helpers::output('读取本地文件:'.$localFilePath,'debug');
			// 新增的文件则删除
			if (!file_exists($backupFilePath) && $file['state'] === 'A') {
				FileSystem::delete($localFilePath);
				Helpers::output('移除更新包新增文件完成:'.$localFilePath,'success');
			}else{
				Helpers::output('文件不存在或不是新增文件:'.$backupFilePath,'warning');
			}
			Helpers::output( json_encode($file, JSON_UNESCAPED_UNICODE),'debug');
		}
		
		// 恢复备份文件
		foreach ($backupFiles as $file) {
			$path = $file['path'];
			Helpers::output('正在恢复备份文件:'.$path,'debug');
			if (!$path) continue;
			$backupFilePath = $backupPath . '/' . $path;
			$localFilePath  = $rootPath . '/' . $path;
			Helpers::output('本地文件:'.$localFilePath.'，备份文件:'.$backupFilePath,'debug');
			if (file_exists($backupFilePath)) {
				Helpers::output('备份文件恢复完成, 还原至:'.$localFilePath,'success');
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
	protected static function rollbackDb($data = [])
	{
		Helpers::output('正在回滚数据库','debug');
		$upgradeData    = Helpers::getUpgradeData();
		Helpers::output(is_array($upgradeData)?json_encode($upgradeData,JSON_UNESCAPED_UNICODE):$upgradeData,'debug');
		$upgradeVersion = $upgradeData['version'];
		$rollbackDbPath = Helpers::getRollbackSqlPath($upgradeVersion);
		$rollbackFile   = $rollbackDbPath . '/v' . $upgradeVersion . '.sql';
		Helpers::output('获取回滚数据库文件'.$rollbackFile,'debug');
		$backupDbFile = Helpers::getBackupDbName();
		Helpers::output('获取备份数据库文件'.$backupDbFile,'debug');
		
		// 组合回滚文件和备份文件，执行顺序：创建表>备份表>回滚文件
		$backupDbList = [
			$backupDbFile,
			$rollbackFile
		];
		// var_dump($backupDbList);die;
		$database = Db::instance();
		foreach ($backupDbList as $sqlFile) {
			Helpers::output('读取数据库文件：'.$sqlFile,'debug');
			if (file_exists($sqlFile)) {
				
				$sqlScript = file_get_contents($sqlFile);
				Helpers::output($sqlScript,'debug');
				// 使用正则表达式分割SQL脚本成单个语句
				$delimiter     = ';';          // SQL语句结束符
				$pattern       = "/;(\r?\n)/"; // 正则表达式匹配语句结束符后跟换行符
				$sqlStatements = preg_split($pattern, $sqlScript);
				
				// 执行每个SQL语句
				foreach ($sqlStatements as $stmt) {
					$stmt = trim($stmt); // 去除首尾空白字符
					Helpers::output('执行回滚数据库:'.$stmt,'debug');
					if (!empty($stmt)) { // 检查SQL语句是否为空
						if ($database->query($stmt) === FALSE) {
							Helpers::output('执行回滚失败: ' . $database->error() . ', sql:' . $stmt, 'error');
							continue;
						}else{
							Helpers::output('执行回滚数据库成功:'.$stmt,'success');
						}
					}
				}
			} else{
				Helpers::output('数据库文件不存在，自动过滤:'.$sqlFile,'warning');
			}
		}
		
	
		// 创建了什么表， 创建了就删除
		$sqlFilePath = Helpers::generatedDatabaseSqlFilename($upgradeVersion);
		$sqlTables   = Db::getCreateTableRecords($sqlFilePath);
		$sqlTables   = array_unique(array_filter($sqlTables));
		Helpers::output('读取迁移数据库文件:'.$sqlFilePath,'debug');
		Helpers::output(is_array($sqlTables)?json_encode($sqlTables, JSON_UNESCAPED_UNICODE):$sqlTables,'debug');
		if ($sqlTables) {
			foreach ($sqlTables as $tableName) {
				$sql = "DROP TABLE {$tableName};";
				Helpers::output('移除新增表完成:'.$sql,'success');
				$database->query($sql);
			}
		}
		return false;
	}
	
}