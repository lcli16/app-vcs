<?php
namespace Lcli\AppVcs;


use Lcli\AppVcs\http\Request;
use Lcli\AppVcs\Kernel\Kernel;
use Lcli\AppVcs\Helpers;

class AppVcs {
	/**
	 * 检查更新
	 * @param $version
	 * @return array|mixed
	 */
	public static function check($version=null)
	{
		$appId = Helpers::config('app_id');
		return Kernel::check($appId, $version);
	}
	
	/**
	 * 更新程序
	 * @param $version
	 * @return array|mixed
	 */
	public static function upgrade($version=null)
	{
		$appId = Helpers::config('app_id');
		return Kernel::upgrade($appId, $version);
	}
}