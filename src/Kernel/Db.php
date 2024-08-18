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
		if (!file_exists($sqlFilePath)) {
			return [];
		}
		
		$tableNames = [];
		
		// 打开文件
		$file = fopen($sqlFilePath, 'r');
		
		while (!feof($file)) {
			$line = fgets($file);
			
			// 使用正则表达式匹配表名
			// 匹配 INSERT INTO, UPDATE, DELETE FROM, SELECT FROM 语句中的表名
			preg_match_all('/(?:INSERT\s+INTO\s+|UPDATE\s+|DELETE\s+FROM\s+|SELECT\s+FROM\s+)`?([a-zA-Z0-9_]+)`?\s*/i', $line, $matches);
			
			// 添加匹配到的表名到数组
			if (!empty($matches[1])) {
				foreach ($matches[1] as $tableName) {
					$tableNames[$tableName] = true; // 使用键值为表名的数组来去重
				}
			}
		}
		
		fclose($file);
		
		// 提取表名数组的键作为唯一表名
		$uniqueTableNames = array_keys($tableNames);
		
		return $uniqueTableNames;
	}
	
	/***
	 * 获取SQL文件里的表名
	 * @param string $sqlFilePath
	 * @return array|mixed 操作表列表
	 */
	static function getCreateTableRecords($sqlFilePath)
	{
		if (!file_exists($sqlFilePath)) {
			return [];
		}
		
		$tableNames = [];
		
		// 打开文件
		$file = fopen($sqlFilePath, 'r');
		
		while (!feof($file)) {
			$line = fgets($file);
			
			// 使用正则表达式匹配表名
			// 匹配 CREATE TABLE, INSERT INTO, UPDATE, DELETE FROM, SELECT FROM 语句中的表名
			preg_match_all('/(?:CREATE\s+TABLE)\s+`?([a-zA-Z0-9_]+)`?\s*/i', $line, $matches);
			
			// 添加匹配到的表名到数组
			if (!empty($matches[1])) {
				foreach ($matches[1] as $tableName) {
					$tableNames[] = $tableName;
				}
			}
		}
		
		fclose($file);
		// 去重
		$uniqueTableNames = array_unique($tableNames);
		
		return $uniqueTableNames;
	}
	
	
}