<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\AppVcsException;
use Lcli\AppVcs\Cli\Cli;
use Lcli\AppVcs\Helpers;

class FileSystem {
	
	public static function writeByPath($filePath, $localFilePath)
	{
		return file_put_contents($filePath, file_get_contents($localFilePath));
	}
	
	public static function write($filePath, $contents)
	{
		return file_put_contents($filePath, $contents);
	}
	
	public static function delete($filePath)
	{
		if (file_exists($filePath) && is_file($filePath) && !is_dir($filePath)){
			return unlink($filePath);
		}
		return false;
	}

	public static function  clearDir($dir, $isChildrenDir=false) {
		if (!is_dir($dir)) {
			throw new AppVcsException("$dir must be a directory");
		}
		$files = array_diff(scandir($dir), array('.', '..'));
		
		if (file_exists($dir.'/appvcs.php')) {
			return;
		}
		if (!$isChildrenDir){
			$checkDir = Helpers::checkPath($dir);
			if (!$checkDir){
				return false;
			}
		}
		
		foreach ($files as $file) {
			$path = $dir . '/' . $file;
			
			if (is_dir($path)) {
				self::clearDir($path, true); // 递归删除子目录中的文件
			} else {
				@unlink($path); // 删除文件
			}
		}
		if ($isChildrenDir){
			rmdir($dir); // 删除目录
		}
	}
	
	public static function getFiles($dir)
	{
		$files = static::getAllFiles($dir);
		$_files = [];
		foreach ($files as $fileFullPath) {
			$path = str_replace($dir , '', $fileFullPath);
			$_files[] = [
				'path' => $path,
				'fullPath' => $fileFullPath
			];
		}
		return $_files;
	}
	
	static function getAllFiles($path, $absolutePath=true)
	{
		$files = [];
		
		// 检查路径是否存在并且是目录
		if (is_dir($path)) {
			$dir = opendir($path);
			while (false !== ($file = readdir($dir))) {
				if ($file != '.' && $file != '..') {
					$fullPath = $path . DIRECTORY_SEPARATOR . $file;
					
					// 如果是目录，则递归调用自身
					if (is_dir($fullPath)) {
						$files = array_merge($files, static::getAllFiles($fullPath, $absolutePath));
					} else {
						// 如果是文件，则添加到文件列表中
						
						$files[] = $fullPath;
						
					}
				}
			}
			closedir($dir);
		}
		
		return $files;
	}
	
}