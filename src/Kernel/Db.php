<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Db\Mysql;
use Lcli\AppVcs\Helpers;

class Db {
	private static $instance = null;
	private static $driver = 'mysql';
	public static function instance($config = [])
	{
		$driver = isset($config['driver'])?$config['driver']:'mysql';
		$instance = new Mysql($config);
		if (self::$instance === null) {
			self::$instance = $instance;
		}
		return self::$instance;
	}
	
 
	private function __clone() {}
	
	private function __wakeup() {}
	
}