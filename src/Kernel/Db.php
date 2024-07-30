<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Db\Mysql;
use Lcli\AppVcs\Helpers;

class Db {
	private static $instance = null;
	private static $driver = 'mysql';
	public static function instance()
	{
		$driver = self::$driver;
		$instance = new Mysql();
		if (self::$instance === null) {
			self::$instance = $instance;
		}
		return self::$instance;
	}
	
	private function __construct() {
		$db = Helpers::config('database');
		$driver = $db['driver'];
		if (!isset($driver)){
			$driver = 'mysql';
		}
		self::$driver = $driver;
	}
	private function __clone() {}
	
	private function __wakeup() {}
	
}