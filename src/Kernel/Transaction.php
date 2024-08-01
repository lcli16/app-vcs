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
		Helpers::setVersion($data['version'], $this->config);
		$this->status = 2;
	}
	
	public function rollback($data=null, $config = null, $exception = null)
	{
		// 失败还原代码
		Backup::rollback($data, $config);
		 
		$this->status = 3;
	}
}