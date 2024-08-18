<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\AppVcsException;

class Migrate {
	
	/**
	 * 数据库迁移
	 * @param $version
	 * @return void
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public static function database($upgradeVersion)
	{
		
		// 读取SQL文件
		$sqlFile = Helpers::generatedDatabaseSqlFilename($upgradeVersion);
		Helpers::output('读取迁移数据库文件:'.$sqlFile);
		if (!file_exists($sqlFile)) {
			Helpers::output('数据库迁移文件不存在:'.$sqlFile,'warning');
			return;
		}
		
		$backupPath = Helpers::getDatabaseSqlPath($upgradeVersion);
		is_dir($backupPath) or mkdir($backupPath, 0755, true);
		$conn = Db::instance();
		// 打开文件
		$file = fopen($sqlFile, 'r');
		// 用于存储 SQL 语句
		$sql = '';
		while (!feof($file)) {
			$line = fgets($file);
			// 忽略注释行
			if (preg_match('/^\s*(--|\/\*)/', $line)) {
				continue;
			}
			// 添加当前行到 SQL 语句
			$sql .= $line;
			// 如果遇到分号，则执行 SQL 语句
			Helpers::output("执行SQL语句:".$sql);
			if (substr(trim($line), -1, 1) == ';') {
				if (!$conn->query($sql)) {
					Helpers::output( 'Error executing SQL statement: ' . $sql, 'error');
					Helpers::output( 'MySQL Error: ' . $conn->error, 'error');
					
				}else{
					Helpers::output( '数据库迁移语句执行成功'.$sql, 'success');
				}
				$sql = ''; // 清空 SQL 语句
			}
		}
		fclose($file); 
	}
	
	/**
	 * 迁移文件
	 * @param $state
	 * @param $localFilePath
	 * @param $upgradeFilePath
	 * @return void
	 */
	public static function file($state, $localFilePath, $upgradeFilePath)
	{
		$dir = dirname($localFilePath);
		is_dir($dir) or mkdir($dir, 0755, true);
		
		switch ($state) {
			case 'D': // 删除
				Helpers::output( '正在迁文件， 操作：'.$state.', 文件：'.$upgradeFilePath,'warning');
				if (file_exists($localFilePath)) {
					@unlink($localFilePath);
				}
				// 同时删除旧版本空目录
				Helpers::rmDir(dirname($localFilePath));
				break;
			case 'A': // 新增
			case 'M': // 修改
			default:
			Helpers::output( '正在迁文件， 操作：'.$state.', 文件：'.$upgradeFilePath,'debug');
				if (!file_exists($upgradeFilePath)) {
					return;
				}
				FileSystem::writeByPath($localFilePath, $upgradeFilePath);
				break;
		}
		Helpers::output( '执行完成， 操作：'.$state.', 文件：'.$upgradeFilePath,'success');
	}
}