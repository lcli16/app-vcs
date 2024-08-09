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
	
	public static function  clearDir($projectDir ) {
		$upgradeData = Helpers::getUpgradeData();
		$files = $upgradeData['files'];
		foreach ($files as $file){
			$filePath = $projectDir.'/'.$file['path'];
			if ($file['state'] === 'D' && is_file($filePath)){
				Helpers::output('正在清除删除文件命令：'.$filePath,'debug');
				@unlink($filePath);
				Helpers::output('删除文件完成：'.$filePath, 'success');
			}
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