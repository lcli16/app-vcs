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
}