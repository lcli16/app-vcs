<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;

class Transaction {
	public  $config = [];
	public  $data   = [];
	private $status = 0;
	
	public function __construct()
	{
		$this->config = Helpers::config();
	}
	
	public function start($data = null)
	{
		$this->status = 1;
	}
	
	public function success($data = null)
	{
		Helpers::output('更新程序成功！正在同步信息中','success');
		$preVersion = Helpers::getVersion();
		$appId = Helpers::getAppId();
		Helpers::setVersion($data['version']);
		$version = isset($data['version'])?$data['version']:'';
		$state = 'success';
		$result = Request::instance()->callback([ 'state' => $state, 'appId' => $appId, 'pre_version'=>$preVersion, 'version' => $version, 'content' => $data ]);
		Helpers::output('信息同步完成！安装结束！', 'success');
		$this->status = 2;
	}
	
	public function rollback($data=null,   $exception = null)
	{
		
		// 失败还原代码
		Backup::rollback($data );
		
		$state = 'error';
		$appId = Helpers::getAppId();
		$version = isset($data['upgrade']['version'])?$data['upgrade']['version']:'';
		Request::instance()->callback([ 'state' => $state, 'appId' => $appId, 'version' => $version, 'content' => $exception ]);
		$this->status = 3;
	}
}