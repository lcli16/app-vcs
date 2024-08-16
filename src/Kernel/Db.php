<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Db\Mysql;
use Lcli\AppVcs\Helpers;

class Db {
	private static $instance = null;
	private static $driver   = 'mysql';
	
	public static function instance()
	{
		$config   = Helpers::getDbConfig();
		$driver   = isset($config['driver']) ? $config['driver'] : 'mysql';
		$instance = new Mysql();
		if (self::$instance === null) {
			self::$instance = $instance;
		}
		return self::$instance;
	}
	
	
	private function __clone() {}
	
	 
	
	/***
	 * 获取SQL文件里的表名
	 * @param string $sqlFilePath
	 * @return array|mixed 操作表列表
	 */
	static function getOperatorTableRecords($sqlFilePath)
	{
		if (!file_exists($sqlFilePath)){
			return [];
		}
		// 读取 SQL 文件内容
		$sqlContent = file_get_contents($sqlFilePath);
		
		// 使用正则表达式匹配表名
		// 匹配 CREATE TABLE, INSERT INTO, UPDATE, DELETE FROM, SELECT FROM 语句中的表名
		preg_match_all('/(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM|SELECT\s+FROM)\s+`?([a-zA-Z0-9_]+)`?\s*/i', $sqlContent, $matches);
		
		// 获取匹配到的表名
		$tableNames = $matches[1];
		// 去重
		$uniqueTableNames = array_unique($tableNames);
		
		return $uniqueTableNames;
	}
	
	/***
	 * 获取SQL文件里的表名
	 * @param string $sqlFilePath
	 * @return array|mixed 操作表列表
	 */
	static function getCreateTableRecords($sqlFilePath)
	{
		if (!file_exists($sqlFilePath)){
			return [];
		}
		// 读取 SQL 文件内容
		$sqlContent = file_get_contents($sqlFilePath);
		
		// 使用正则表达式匹配表名
		// 匹配 CREATE TABLE, INSERT INTO, UPDATE, DELETE FROM, SELECT FROM 语句中的表名
		preg_match_all('/(?:CREATE\s+TABLE)\s+`?([a-zA-Z0-9_]+)`?\s*/i', $sqlContent, $matches);
		
		// 获取匹配到的表名
		$tableNames = $matches[1];
		
		// 去重
		$uniqueTableNames = array_unique($tableNames);
		
		return $uniqueTableNames;
	}
	
	
}