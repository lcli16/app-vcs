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
		$sqlScript = file_get_contents($sqlFile);
		Helpers::output( $sqlScript);
		// 分割SQL脚本成单个语句
		$statements = explode(";\n", $sqlScript);
		
		// 执行每个SQL语句
		foreach ($statements as $statement) {
			Helpers::output( '正在执行数据库迁移语句'.$statement);
			if (trim($statement) != '') { // 忽略空语句
				$conn = Db::instance();
				if (!$conn->query($statement)) {
					throw new  AppVcsException('数据库更新错误:' . $conn->error());
				}else{
					Helpers::output( '数据库迁移语句执行成功'.$statement, 'success');
				}
			}
		}
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
				file_put_contents($localFilePath, file_get_contents($upgradeFilePath));
				break;
		}
		Helpers::output( '执行完成， 操作：'.$state.', 文件：'.$upgradeFilePath,'success');
	}
}