<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;

class Config {
	
	public static function setConfigs($config)
	{
		$default = include  '../Config/config.php';
		foreach ($config as $name => &$value){
			 if (!$value){
				 $value = $default[$name]??'';
			 }
		}
		Helpers::$config = $config;
		return true;
	}
	
	public static function setConfig($name, $value)
	{
		Helpers::$config[$name] = $value;
		return true;
	}
	
}