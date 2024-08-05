<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;

class Transaction {
	public  $config = [];
	public  $data   = [];
	private $status = 0;
	
	public function __construct($options)
	{
		$this->config = $options;
	}
	
	public function start($data = null)
	{
		$this->status = 1;
	}
	
	public function success($data = null)
	{
		$preVersion = Helpers::getVersion($this->config);
		$appId = Helpers::getAppId($this->config);
		Helpers::setVersion($data['version'], $this->config);
		$version = isset($data['version'])?$data['version']:'';
		$state = 'success';
		
		$result = Request::instance($this->config)->callback([ 'state' => $state, 'appId' => $appId, 'pre_version'=>$preVersion, 'version' => $version, 'content' => $data ], $this->config);
		
		$this->status = 2;
	}
	
	public function rollback($data=null, $config = null, $exception = null)
	{
		// 失败还原代码
		Backup::rollback($data, $config);
		$state = 'error';
		$appId = Helpers::getAppId($this->config);
		$version = isset($data['upgrade']['version'])?$data['upgrade']['version']:'';
		Request::instance($this->config)->callback([ 'state' => $state, 'appId' => $appId, 'version' => $version, 'content' => $exception ], $this->config);
		$this->status = 3;
	}
}