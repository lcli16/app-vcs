<?php
namespace Lcli\AppVcs;


use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\http\Request;
use Lcli\AppVcs\Kernel\Kernel;

class AppVcs {
	private $config = [];
	public function __construct($options=[]) {
		$this->config = $options;
	}
	
	/**
	 * 检查更新
	 * @param $version
	 * @return array|mixed
	 */
	public   function check($version=null)
	{
		$appId = Helpers::getAppId();
		return Kernel::check($appId, $version);
	}
	
	/**
	 * 更新程序
	 * @param $version
	 * @return array|mixed
	 */
	public   function upgrade($version=null)
	{
		$appId = Helpers::getAppId();
		return Kernel::upgrade($appId, $version);
	}
	
	public function getVersion()
	{
		return Kernel::version();
	}
}