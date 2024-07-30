<?php

namespace Lcli\AppVcs\Kernel;

class Transaction {
	private static $status = 0;
	public static function start()
	{
		static::$status = 1;
	}
	
	public static function success()
	{
		static::$status = 2;
	}

	public static function rollback()
	{
		// 失败还原代码
		Backup::rollback();
		static::$status = 3;
	}
}