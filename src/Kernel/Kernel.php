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
		return \Lcli\AppVcs\Kernel\Request::instance()
		                                  ->check([
			                                          'appId'   => $appId,
			                                          'version' => $version
		                                          ]);
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
		ignore_user_abort(true);
		set_time_limit(0);
		
		ini_set('memory_limit', '-1');
		
		
		// 开始事务
		$transction = new Transaction();
		
		$transction->start([
			                   'appId'   => $appId,
			                   'version' => $version
		                   ]);
		
		$result = \Lcli\AppVcs\Kernel\Request::instance()
		                                     ->upgrade([
			                                               'appId'   => $appId,
			                                               'version' => $version
		                                               ]);
		if (!$result) {
			throw new  AppVcsException('获取版本信息失败');
		}
		
		// 获取客户端配置
		$clientConfig = $result['config']['client']??[];
		
		if ($clientConfig){
			$configApp = $result['config']['app']??[];
			if ($configApp){
				$clientConfig = array_merge($clientConfig, $configApp);
			}
			$configVersion = $result['vetsion']??[];
			
			if (!$configVersion){
				$clientConfig = array_merge($clientConfig, $configVersion);
			}
			
			if (Helpers::$config){
				$clientConfig = array_merge($clientConfig, Helpers::$config);
			}
			Helpers::$config = $clientConfig;
		}
		// 获取过滤的目录
		$ignoreBackupFiles = Helpers::getIgnoreBackupFiles();
		Helpers::output('开始执行升级程序:应用ID:' . $appId . ', 版本号:' . $version, 'info', 10);
		 
		Helpers::output('正在获取更新应用信息' . $appId . ', 版本号:' . $version, 'info', 20);
		Helpers::output(json_encode($result, JSON_UNESCAPED_UNICODE));
		$versionInfo = isset($result['versionInfo']) ? $result['versionInfo'] : [];
		// 获取发布操作类型:0=按需发布,1=全量发布
		$publishAction  = isset($versionInfo['publish_action']) ? $versionInfo['publish_action'] : 0;
		$upgradeVersion = $versionInfo['version'];
		
		// 保存更新数据
		Helpers::setUpgradeData($result);
		
		try {
			// 操作更新
			$files        = isset($result['files']) ? $result['files'] : [];
			$upgradeFiles = [];
			foreach ($files as $upgradeFileFullPath) {
				$path     = str_replace($projectPath, '', $upgradeFileFullPath);
				if (in_array($path, $ignoreBackupFiles)){
					continue;
				}
				$upgradeFiles[] = [
					'path'     => $path,
					'fullPath' => $fileFullPath
				];
			}
			$files = $upgradeFiles;
			
			$url          = $result['url'];
			$fileName     = isset($result['fileName']) ? $result['fileName'] : 'app-vcs-upgrade.zip';
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
			
			if (!$checkDir) {
				return false;
			}
			is_dir($rootPath) or mkdir($rootPath, 0755, true);
			// 项目目录
			$projectPath = Helpers::getProjectPath();
			if (!$projectPath) {
				$projectPath = $rootPath;
			}
			
			$zipFile = Helpers::getZipPath() . '/' . $fileName;
			$destinationDir = Helpers::getProjectPath();
			
			// 1.备份文件
			// 如果是全量发布， 那么备份全部文件
			
			$backupStatus = Backup::fileByTar();
			if (!$backupStatus) {
				Helpers::output('升级失败:备份文件失败', 'error', 30);
				return;
			}
			// 2.备份数据库
			$issetTables = isset($versionInfo['tables_files']);
			$backup      = $issetTables ? $versionInfo['tables_files'] : [];
			
			$backupStatus = Backup::database($backup, $upgradeVersion);
			if (!$backupStatus) {
				Helpers::output('升级失败:备份sql失败', 'error', 40);
				return;
			}
			
			if ($url) {
				Helpers::output('正在下载更新包补丁' . $url, 'info', 50);
				if (!file_exists($zipFile)) {
					// 下载远程文件并保存到本地
					exec("curl -o $zipFile {$url}", $downRes, $downResVar);
					if ($downResVar === 0) {
						Helpers::output('下载补丁包成功', 'success', 55);
					} else {
						Helpers::output('下载补丁包失败', 'error', 55);
						Helpers::output($downRes, 'error', 55);
						exit();
					}
					// 清除文件系统的缓存
					clearstatcache();
				}
				if (!is_dir($destinationDir)) {
					$mkDirStatus = mkdir($destinationDir, 0775, true);
					if (!$mkDirStatus) {
						Helpers::output('解压目录不存在[' . $destinationDir . '],自动创建也失败,请手动创建', 'error', 55);
						exit();
					}
				}
				
				// 过滤文件
				$filterFiles = $clientConfig['filter_files']??'';
				$filterFiles = explode("\r\n", $filterFiles);
				
				$filterFiles[] = 'config/appvcs.php';
				$filterFiles = array_filter(array_unique($filterFiles));
				$unzipX = implode(' ', $filterFiles);
			
				Helpers::output('补丁包下载完成：' . $zipFile . ',正在解压文件至：' . $destinationDir, 'info', 60);
				$command = "unzip -o {$zipFile} -d {$destinationDir} -x $unzipX ";
				exec($command, $unzipOutput, $returnVar);
				Helpers::output($command, 'debug');
				
				if ($returnVar === 0) {
					Helpers::output("[成功]解压补丁包【{$zipFile}】完成!!!，解压至:{$destinationDir}", 'success', 30);
				} else {
					$execCodeMsg = self::execCode($returnVar);
					Helpers::output("[失败]解压补丁包【{$zipFile}】状态码:{$returnVar}, $execCodeMsg", 'error', 25);
					Helpers::output(is_array($unzipOutput) ? json_encode($unzipOutput, JSON_UNESCAPED_UNICODE) : $unzipOutput, 'error', 25);
					exit();
				}
				
				if (intval($publishAction) === 1) {
					Helpers::output('补丁包类型：全量发布' . $url, 'info', 70);
					// 全量发布需要删除原先代码, 然后将新的文件下载到目录下
					FileSystem::clearDir($projectPath);
				}
			}
			// 3.开始执行升级
			Helpers::output('正在获取更新文件', 'debug', 75);
			if ($files) {
				foreach ($files as $file) {
					$state = $file['state'];
					$path  = $file['path'];
					$type  = $file['type'];
					$path = $path['path']??$path;
					Helpers::output("正在迁移文件：{$path}-{$state}-{$type}", 'debug');
					if (!$path) {
						continue;
					}
					
					// 获取更新文件
					$upgradeFilePath = $destinationDir . '/' . $path;
					$localFilePath   = $projectPath . '/' . $path;
					
					// 安全文件只运行不下载
					$safeFileOrDirs = [Helpers::getDatabaseSqlPath($upgradeVersion)];
					$safeFiles = $clientConfig['safe_files']??'';
					if (  is_array($safeFiles) && $safeFiles) {
						$safeFileOrDirs = array_merge($safeFileOrDirs, $safeFiles);
					}
					Helpers::output('安全文件', 'debug');
					Helpers::output(json_encode($safeFileOrDirs, JSON_UNESCAPED_UNICODE), 'debug');
					
					if ($filterFiles){
						
						Helpers::output('过滤文件', 'debug');
						
						Helpers::output(json_encode($filterFiles, JSON_UNESCAPED_UNICODE), 'debug');
						
						// 存在指定过滤文件， 那么直接过滤
						if (in_array($path, $filterFiles)) {
							Helpers::output('已过滤：' . $path . '，文件', 'warning');
							continue;
						}
						
						// 如果包含， 也过滤
						foreach ($filterFiles as $filterFileItem) {
							$isContains = strpos($path, $filterFileItem) !== false;
							if (!$isContains) {
								Helpers::output('已过滤：' . $path . '，文件(包含)', 'warning');
								continue 2;
							}
						}
					}
					
					
					
					
					if (!in_array($path, $safeFileOrDirs)) {
						
						Helpers::output('正在安装升级文件,类型：' . $type . '，安装至：' . $localFilePath . ', 升级文件：' . $upgradeFilePath);
						switch ($type) {
							// case 'file':
							// 	Migrate::file($state, $localFilePath, $upgradeFilePath);
							//
							// 	break;
							case 'sql': // 数据库迁移
								Migrate::database($versionInfo['version']);
								break;
							case 'config': // 配置更新,
							default: // 业务数据更新
								// Migrate::file($state, $localFilePath, $upgradeFilePath);
								break;
						}
					}
					
				}
				Helpers::output('文件升级完成', 'success', 80);
			} else {
				Helpers::output('没有需要升级的文件', 'success', 80);
			}
			
			// 如果有脚本指令, 运行脚本
			$scripts = isset($versionInfo['script']) ? $versionInfo['script'] : [];
			if ($scripts && is_array($scripts)) {
				foreach ($scripts as $cmd) {
					if (!$cmd) continue;
					Helpers::output('正在执行脚本:' . $cmd);
					$output = shell_exec($cmd);
					Helpers::output($output);
				}
			}
			// 提交事务
			$transction->success($result);
			return true;
		} catch (\Error $e) {
			
			self::throwError($result, $transction, $e);
			
		} catch (\Exception $e) {
			
			
			// 回滚程序
			self::throwError($result, $transction, $e);
			
		}
	}
	
	public static function throwError($result, $transction, $e)
	{
		$error = $e->getMessage() . ' in ' . $e->getFile() . '-' . $e->getLine();
		Helpers::output('运行错误：' . $error, 'error');
		Helpers::output($e->getTraceAsString(), 'error');
		$errorData = [
			'upgrade' => $result,
		];
		$transction->rollback($errorData, [
			'message' => $e->getMessage(),
			'trace'   => $e->getTrace(),
			'file'    => $e->getFile(),
			'line'    => $e->getLine()
		]);
		
		
		
		return;
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
	
	
	public static function execCode($code)
	{
		$msg = '未知错误';
		switch ($code) {
			case '1':
				$msg = '权限不足!' . shell_exec('whoami');
				break;
			case '50':
				$msg = '这可能与权限有关,请检查解压目录是否是www用户组,如不是请更新为www用户组';
				break;
		}
		return $msg;
	}
	
}