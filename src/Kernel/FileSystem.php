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
}