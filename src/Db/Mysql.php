<?php

namespace Lcli\AppVcs\Db;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\Services\DbService;
use Lcli\AppVcs\AppVcsException;

class Mysql implements DbService {
	private $conn = null;
	public function __construct() {
		// 数据库配置
		$database = Helpers::getDbConfig();
		$host = $database[ 'host' ];
		$port = $database[ 'port' ];
		$db = $database[ 'database' ];
		$user = $database[ 'username' ];
		$pass = $database[ 'password' ];
		
		// 创建连接
		$conn = new mysqli($host, $user, $pass, $db, $port);
		// 检查连接
		if ($conn->connect_error) {
			throw new  AppVcsException('数据库连接失败');
		}
		$this->conn = $conn;
	}
	
	public function query($sql)
	{
		return $this->conn->query($sql);
	}
	
	public function error()
	{
		return $this->conn->error;
	}
	
	public function __destruct()
	{
		$this->conn->close();
	}
}