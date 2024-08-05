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
		return \Lcli\AppVcs\Kernel\Request::instance($config)->check([ 'appId' => $appId, 'version' => $version ], $config);
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
		
		$result = \Lcli\AppVcs\Kernel\Request::instance($config)->upgrade([ 'appId' => $appId, 'version' => $version ], $config);
		if (!$result){
			throw new  AppVcsException('获取版本信息失败');
		}
		
		$versionInfo = isset($result['versionInfo'])?$result['versionInfo']:[];
		try {
			// 操作更新
			$files = isset($result['files']) ?$result[ 'files' ]: [];
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
			$filePutRsult = @file_put_contents($zipFile, file_get_contents($url));
			
			$destinationDir = $tempFilePath;
			if ($filePutRsult){
				$zip = new ZipArchive();
				if ($zip->open($zipFile) === TRUE) {
					$zip->extractTo($destinationDir);
				}
				$zip->close();
			}
			
			// 1.备份网站文件+数据库
			$config['result'] = $result;
		 
			$backupStatus = Backup::file($files, $config);
		
			if (!$backupStatus){
				throw new  AppVcsException('升级失败:备份文件失败');
			}
			
			// 2.备份数据库
			$issetTables = isset($versionInfo['backup_tables']);
			$backup = $issetTables?$versionInfo['backup_tables']:[];
			if ($backup){
				$backupStatus = Backup::database($backup, $config, $version);
				if (!$backupStatus){
					throw new  AppVcsException('升级失败:备份sql失败');
				}
			}
			
			$projectPath  = Helpers::getProjectPath($config);
			if (!$projectPath){
				$projectPath = $rootPath;
			}
			
			// 获取发布操作类型:0=按需发布,1=全量发布
			$publishAction = isset($versionInfo['publish_action'])?$versionInfo['publish_action']:0;
			
			if (intval($publishAction) === 1){
				// 全量发布需要删除原先代码, 然后将新的文件下载到目录下
				FileSystem::clearDir($projectPath);
			}
			
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
				$localFilePath = $projectPath . '/' . $path;
				
				
				
				// 安全文件只运行不下载
				$safeFileOrDirs = [Helpers::getDatabaseSqlPath($config)];
				if (isset($versionInfo['safe_files']) && is_array($versionInfo['safe_files']) && $versionInfo['safe_files']){
					$safeFileOrDirs = array_merge($safeFileOrDirs, $versionInfo['safe_files']);
				}
				// 过滤文件
				$filterFiles = isset($versionInfo['filter_files'])?$versionInfo['filter_files']:[];
				if (!$filterFiles){
					$filterFiles = [];
				}
				if (in_array($path, $filterFiles)){
					continue;
				}
				
				
				if (!in_array($path, $safeFileOrDirs)){
					// var_dump($localFilePath, $upgradeFilePath, $path);
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
			
			// 如果有脚本指令, 运行脚本
			$scripts = isset($versionInfo['script'])?$versionInfo['script']:[];
			if ($scripts && is_array($scripts)){
				foreach ($scripts as $cmd){
					shell_exec($cmd);
				}
			}
			 
			// 提交事务
			$transction->success($result);
			return true;
		} catch (\Error $e){
			self::throwError($result, $transction, $config, $e);
		} catch (\Exception $e){
			
			
			// 回滚程序
			self::throwError($result, $transction, $config, $e);
		}
	}
	
	public static function throwError($result, $transction, $config, $e)
	{
		$errorData = [
			'upgrade' => $result,
		];
		$transction->rollback($errorData, $config, ['message' => $e->getMessage(), 'trace' => $e->getTrace(),'file' => $e->getFile(), 'line' => $e->getLine() ]);
		throw new AppVcsException($e->getMessage());
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