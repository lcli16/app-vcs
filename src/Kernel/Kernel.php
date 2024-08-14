<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\AppVcsException;
use ZipArchive;
use function AlibabaCloud\Client\value;

class Kernel {
	
	
	/**
	 * 检查更新
	 * @param $clientId
	 * @param $appId
	 * @param $version
	 * @return array|mixed
	 */
	public static function check($appId, $version = null)
	{
		return \Lcli\AppVcs\Kernel\Request::instance()->check([ 'appId' => $appId, 'version' => $version ]);
	}
	
	/**
	 * 下载更新包
	 * @param $clientId
	 * @param $appId
	 * @param $version
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public static function upgrade($appId, $version = null)
	{
	    ini_set('memory_limit', '-1');
		Helpers::output('开始执行升级程序:应用ID:'.$appId.', 版本号:'.$version);
		// 开始事务
		$transction = new Transaction();
		
		$transction->start(['appId' => $appId, 'version' => $version]);
		
		$result = \Lcli\AppVcs\Kernel\Request::instance()->upgrade([ 'appId' => $appId, 'version' => $version ]);
		if (!$result){
			throw new  AppVcsException('获取版本信息失败');
		}
		Helpers::output('正在获取更新应用信息'.$appId.', 版本号:'.$version);
		Helpers::output(json_encode($result,JSON_UNESCAPED_UNICODE));
		$versionInfo = isset($result['versionInfo'])?$result['versionInfo']:[];
		// 获取发布操作类型:0=按需发布,1=全量发布
		$publishAction = isset($versionInfo['publish_action'])?$versionInfo['publish_action']:0;
		$upgradeVersion =  $versionInfo['version'];
		// 保存更新数据
		Helpers::setUpgradeData($result);
		
		try {
			// 操作更新
			$files = isset($result['files']) ?$result[ 'files' ]: [];
			$url = $result[ 'url' ];
			$fileName = isset($result[ 'fileName' ])?$result[ 'fileName' ]:'app-vcs-upgrade.zip';
			$tempFilePath = Helpers::getTempFilePath($upgradeVersion);
			if (!$tempFilePath) {
				throw new  AppVcsException('请配置根目录');
			}
			is_dir($tempFilePath) or mkdir($tempFilePath, 0755, true);
			
			$rootPath = Helpers::getRootPath();
			if (!$rootPath) {
				throw new  AppVcsException('请配置根目录');
			}
		
			$checkDir = Helpers::checkPath($rootPath);
			
			if (!$checkDir){
				return false;
			}
			is_dir($rootPath) or mkdir($rootPath, 0755, true);
			// 项目目录
			$projectPath  = Helpers::getProjectPath();
			if (!$projectPath){
				$projectPath = $rootPath;
			}
			
			$zipFile = Helpers::getZipPath() . '/' . $fileName;
			
			$destinationDir = Helpers::getProjectPath();
			
			
			// 1.备份文件
			// 如果是全量发布， 那么备份全部文件
			if (intval($publishAction) === 1){
				// 全量发布需要删除原先代码, 然后将新的文件下载到目录下
				$projectFiles = FileSystem::getAllFiles($projectPath, false);
				$_files = [];
				
				foreach ($projectFiles as $fileFullPath) {
					$path = str_replace($projectPath , '', $fileFullPath);
					$_files[] = [
						'path' => $path,
						'fullPath' => $fileFullPath
					];
				}
				$backupStatus = Backup::file($_files, $result);
				
			}else{
				$backupStatus = Backup::file($files, $result);
			}
			
			if (!$backupStatus){
				Helpers::output('升级失败:备份文件失败','error');
				return ;
			}
			// 2.备份数据库
			$issetTables = isset($versionInfo['tables_files']);
			$backup = $issetTables?$versionInfo['tables_files']:[];
			
			$backupStatus = Backup::database($backup, $upgradeVersion);
			if (!$backupStatus){
				Helpers::output('升级失败:备份sql失败', 'error');
				return ;
			}
			
			Helpers::output('正在下载更新包补丁'.$url);
			if(file_exists($zipFile)){
			    $filePutRsult = true;
			}else{
			    	// 下载远程文件并保存到本地
			    $filePutRsult = shell_exec("curl -o $zipFile {$url}");
			}
		
// 			@file_put_contents($zipFile, file_get_contents($url));
			if ($filePutRsult){
				Helpers::output('补丁包下载完成：'.$zipFile.',正在解压文件至：'.$destinationDir);
				$zip = new ZipArchive();
				if ($zip->open($zipFile) === TRUE) {
					$zip->extractTo($destinationDir);
				}
				$zip->close();
			}
			
			if (intval($publishAction) === 1){
				Helpers::output('补丁包类型：全量发布'.$url);
				// 全量发布需要删除原先代码, 然后将新的文件下载到目录下
				FileSystem::clearDir($projectPath);
			}
			
			// 3.开始执行升级
			Helpers::output("正在获取更新文件", "debug");
			
			foreach ( $files as $file ) {
				$state = $file[ 'state' ];
				$path = $file[ 'path' ];
				$type = $file[ 'type' ];
				Helpers::output("正在迁移文件：{$path}-{$state}-{$type}", 'debug');
				if (!$path) {
					continue;
				}
				
				// 获取更新文件
				$upgradeFilePath = $destinationDir . '/' . $path;
				$localFilePath = $projectPath . '/' . $path;
				
				// 安全文件只运行不下载
				$safeFileOrDirs = [Helpers::getDatabaseSqlPath($upgradeVersion)];
				if (isset($versionInfo['safe_files']) && is_array($versionInfo['safe_files']) && $versionInfo['safe_files']){
					$safeFileOrDirs = array_merge($safeFileOrDirs, $versionInfo['safe_files']);
				}
				Helpers::output("安全文件", 'debug');
				Helpers::output(json_encode($safeFileOrDirs, JSON_UNESCAPED_UNICODE), 'debug');
				// 过滤文件
				$filterFiles = isset($versionInfo['filter_files'])?$versionInfo['filter_files']:[];
				if (!$filterFiles){
					$filterFiles = [];
				}
				Helpers::output('过滤文件', 'debug');
				Helpers::output(json_encode($filterFiles, JSON_UNESCAPED_UNICODE), 'debug');
				// 存在指定过滤文件， 那么直接过滤
				if (in_array($path, $filterFiles)   ){
					Helpers::output('已过滤：'.$path.'，文件', 'warning');
					continue;
				}
			 
				// 如果包含， 也过滤
				foreach ($filterFiles as $filterFileItem){
					$isContains =  strpos($path, $filterFileItem)!==false;
					if (!$isContains){
						Helpers::output('已过滤：'.$path.'，文件(包含)', 'warning');
						continue 2;
					}
				}
				 
				if (!in_array($path, $safeFileOrDirs)){
					
					Helpers::output('正在安装升级文件,类型：'.$type.'，安装至：'.$localFilePath.', 升级文件：'.$upgradeFilePath);
					switch ($type) {
						case 'file':
							Migrate::file($state, $localFilePath, $upgradeFilePath);
							
							break;
						case 'sql': // 数据库迁移
							Migrate::database($versionInfo['version']);
							break;
						case 'config': // 配置更新,
						default: // 业务数据更新
							
							Migrate::file($state, $localFilePath, $upgradeFilePath);
							break;
					}
				}
				
			}
			Helpers::output('文件升级完成', 'success');
			// 测试升级失败
			// $d = 1/0;
			
			// 如果有脚本指令, 运行脚本
			$scripts = isset($versionInfo['script'])?$versionInfo['script']:[];
			if ($scripts && is_array($scripts)){
				foreach ($scripts as $cmd){
					if (!$cmd) continue;
					Helpers::output('正在执行脚本:'.$cmd);
					$output = shell_exec($cmd);
					Helpers::output($output);
				}
			}
			// $d = 1/0;
			// 提交事务
			$transction->success($result);
			return true;
		} catch (\Error $e){
			
			self::throwError($result, $transction, $e);
		} catch (\Exception $e){
			
			
			// 回滚程序
			self::throwError($result, $transction, $e);
		}
	}
	
	public static function throwError($result, $transction, $e)
	{
		Helpers::output('更新失败:'.$e->getMessage(),'error');
		$errorData = [
			'upgrade' => $result,
		];
		$transction->rollback($errorData, ['message' => $e->getMessage(), 'trace' => $e->getTrace(),'file' => $e->getFile(), 'line' => $e->getLine() ]);
		throw new AppVcsException($e);
	}
	
	public static function rollback()
	{
		  Backup::rollback();
	}
	
	
	
	/**
	 * 获取系统版本
	 * @return false|string
	 */
	public static function version()
	{
		return Helpers::getVersion();
	}
	
	
	
	
}