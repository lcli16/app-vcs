<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\AppVcsException;

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
	
	static function getAllFiles($path)
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
						$files = array_merge($files, $this->getAllFiles($fullPath));
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