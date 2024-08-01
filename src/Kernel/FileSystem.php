<?php

namespace Lcli\AppVcs\Kernel;

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
}