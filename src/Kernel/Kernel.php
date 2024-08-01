<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\AppVcsException;
use ZipArchive;

class Kernel {
	
	
	/**
	 * 检查更新
	 * @param $clientId
	 * @param $appId
	 * @param $version
	 * @return array|mixed
	 */
	public static function check($appId, $version = null, $config=[])
	{
		return \Lcli\AppVcs\Kernel\Request::instance()->check([ 'appId' => $appId, 'version' => $version ], $config);
	}
	
	/**
	 * 下载更新包
	 * @param $clientId
	 * @param $appId
	 * @param $version
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public static function upgrade($appId, $version = null, $config=[])
	{
		// 开始事务
		$transction = new Transaction($config);
		$transction->start(['appId' => $appId, 'version' => $version]);
		$result = \Lcli\AppVcs\Kernel\Request::instance()->upgrade([ 'appId' => $appId, 'version' => $version ], $config);
		if (!$result){
			throw new  AppVcsException('获取版本信息失败');
		}
		try {
			// 操作更新
			$files = $result[ 'files' ] ?? [];
			$url = $result[ 'url' ];
			$fileName = isset($result[ 'fileName' ])?$result[ 'fileName' ]:'app-vcs-upgrade.zip';
			$tempFilePath = Helpers::getTempFilePath($config);
			if (!$tempFilePath) {
				throw new  AppVcsException('请配置根目录');
			}
			is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
			
			$rootPath = Helpers::getRootPath($config);
			if (!$rootPath) {
				throw new  AppVcsException('请配置根目录');
			}
			is_dir($rootPath) or mkdir($rootPath, 0755, true);
			
			$zipFile = $tempFilePath . '/' . $fileName;
			
			// 下载远程文件并保存到本地
			file_put_contents($zipFile, file_get_contents($url));
			
			$destinationDir = $tempFilePath;
			
			$zip = new ZipArchive();
			if ($zip->open($zipFile) === TRUE) {
				$zip->extractTo($destinationDir);
				
				// 1.备份网站文件+数据库
				$backupStatus = Backup::file($files);
			
				if (!$backupStatus){
					throw new  AppVcsException('升级失败:备份文件失败');
				}
				
				// 2.备份数据库
				$backup = $result['config']['backup']['database']['tables']??[];
				$backupStatus = Backup::database($backup);
				if (!$backupStatus){
					throw new  AppVcsException('升级失败:备份sql失败');
				}
			 
				$zip->close();
				// 3.开始执行升级
				foreach ( $files as $file ) {
					$state = $file[ 'state' ];
					$path = $file[ 'path' ];
					$type = $file[ 'type' ];
					if (!$path) {
						continue;
					}
					
					// 获取更新文件
					$upgradeFilePath = $destinationDir . '/' . $path;
					// 替换更新
					$localFilePath = $rootPath . '/' . $path;
					// 安全文件只运行不下载
					$safeFileOrDirs = isset($result['config']['migrate']['safe_files'])?$result['config']['migrate']['safe_files']:[Helpers::getDatabaseSqlPath()];
					if (!in_array($path, $safeFileOrDirs)){
						switch ($type) {
							case 'file':
								Migrate::file($state, $localFilePath, $upgradeFilePath);
								break;
							case 'sql': // 数据库迁移
								
								Migrate::database($upgradeFilePath);
								break;
							case 'config': // 配置更新,
							default: // 业务数据更新
								Migrate::file($state, $localFilePath, $upgradeFilePath);
								break;
						}
					}
					
				}
			} 
			// 提交事务
			$transction->success($result);
			return true;
		} catch (\Error|\Exception|AppVcsException $e){
			// dump($e);die;
			// 回滚程序
			$errorData = [
				'upgrade' => $result
			];
			$transction->rollback($errorData, $config, $e);
			throw new AppVcsException($e->getMessage());
		}
	}
	
	
	/**
	 * 获取系统版本
	 * @param $config
	 * @return false|string
	 */
	public static function version($config=[])
	{
		return Helpers::getVersion($config);
	}
	
	
	
	
}