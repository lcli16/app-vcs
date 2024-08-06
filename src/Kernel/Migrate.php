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
		$conn = Db::instance();
		// 读取SQL文件
		$sqlFile = Helpers::generatedDatabaseSqlFilename($upgradeVersion);
		$sqlScript = file_get_contents($sqlFile);
		
		// 分割SQL脚本成单个语句
		$statements = explode(";\n", $sqlScript);
	 
		// 执行每个SQL语句
		foreach ( $statements as $statement ) {
			if (trim($statement) != '') { // 忽略空语句
				if (!$conn->query($statement)) {
					throw new  AppVcsException('数据库更新错误:' . $conn->error);
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
		switch ($state) {
			case 'A': // 新增
			case 'M': // 修改
				file_put_contents($localFilePath, file_get_contents($upgradeFilePath));
				break;
			case 'D': // 删除
				@unlink($localFilePath);
				break;
			default:
				break;
		}
	}
}