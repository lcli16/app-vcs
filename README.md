
<p align="center">
  <a href="https://modstart.com">
    <img src="http://web.apilist.site/doc/logo.png" alt="ModStart" width="360" />
  </a>
</p>
<p align="center">
  php 应用版本管理插件包
</p>


<p align="center">  
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="License Apache2.0" src="https://img.shields.io/badge/License-Apache2.0-blue">
  </a>
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="GitHub last release" src="https://img.shields.io/github/v/release/modstart/ModStartCMS">
  </a>
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="GitHub last commit" src="https://img.shields.io/github/last-commit/modstart/ModStartCMS">
  </a>
  <br />
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="Laravel" src="https://img.shields.io/badge/Framework-ModStart-blue">
  </a>
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="Laravel" src="https://img.shields.io/badge/PHP-5.6/7.0/8.x-red">
  </a>
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="Laravel" src="https://img.shields.io/badge/Laravel-5.1/9.0-red">
  </a>
  <a href="https://github.com/modstart/ModStartCMS" target="_blank">
    <img alt="Laravel" src="https://img.shields.io/badge/JS-Vue/ElementUI-green">
  </a>
</p>

## 快速入门

### 1. 安装下载版本管理PHP依赖包:
🔆 1.1 在 wwwroot 下创建 一个目录，用于下载扩展包， 运行命令：
```bash
composer require lcli/app-vcs
```
⚠️ 要注意的是, 找不到版本包,请切换成官方镜像源即可, 命令如下:
```bash
composer config -g repo.packagist composer https://repo.packagist.org
```
🔆 1.2 安装完成后，运行初始化命令，系统会自动生成配置文件：
```bash
php vendor/bin/appvcs init
```
### 2. 进入配置文件config/appvcs.php:
配置相关信息后保存
```php
<?php
return [
	// 项目标识(🉑必填) 同一台服务器上唯一，自行生成，不能含有特殊字符或中文，可由数字、字母、下划线、.点 -杠、组成的唯一标识
	'project_id'     => '',
	// 服务地址(🔅必填)
	'server_url'     => 'https://www.baidu.com',
	// 客户端ID(🉑非必填)
	'client_id'      => '',
	// 应用ID (🔅必填)
	'app_id'         => '',
	// 执行时生成的临时文件进行存储的目录(非必填)
	'temp_file_path' => '',
	// 备份目录 (🉑非必填)
	'backup_path'    => '',
	// 安装sdk的服务端目录 (🔅必填)
	'root_path'      => dirname(__DIR__),
	// 项目目录 需要更新的代码目录 (🔅必填)
	'project_path'   => dirname(__DIR__),
	// 数据库配置(🉑非必填)
	'database'       => [
		'driver'   => 'mysql',
		'host'     => '127.0.0.1',
		'port'     => 3306,
		'database' => 'lhr_app',
		'username' => 'root',
		'password' => '',
	],
];
```
项目 ID 、项目目录、根目录、应用 ID 必须填写
### 3. 注册客户端:

🔆3.1 配置完成后运行命令：
```bash
php vendor/bin/appvcs -u {server_url} -n {project_id} -P {project_path}  register {app_id}
# server_url ：  服务端地址， 例如：https://www.baidu.com/
# project_id:    项目标识符, 例如： gentou-test
# project_path:  项目路径，绝对路径，是更新项目的地址
# app_id:        应用 ID，服务端获取
```
⚠️ {appId}: 从版本管理系统中创建获取
运行显示：注册完成即可和后台通讯

```bash
[2024-08-06 18:25:08] 正在注册客户端...
[2024-08-06 18:25:09] 客户端注册成功, 正在配置客户端...
[2024-08-06 18:25:09] 
[2024-08-06 18:25:09] 正在下载通许脚本...
  % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
                                 Dload  Upload   Total   Spent    Left  Speed
100  1420    0  1420    0     0   4174      0 --:--:-- --:--:-- --:--:--  4164
[2024-08-06 18:25:09] 生成运行脚本...
[2024-08-06 18:25:09] 配置脚本权限
[2024-08-06 18:25:09] 开启脚本
[2024-08-06 18:25:09] 生成守护进程脚本
[2024-08-06 18:25:09] 
[2024-08-06 18:25:09] 运行守护进程
[2024-08-06 18:25:09] 注册成功!
```
## 命令行助手
```bash
php vendor/bin/appvcs help
```
```html
app-vcs@ubuntu-linux-22-04-02-desktop:/www/wwwroot/tzkj/gentou$ php vendor/bin/appvcs help

USAGE:
   appvcs <OPTIONS> <COMMAND> ... <appId>

                                                                                                                                        
       ___    ____  ____      _    _____________                                                                                        
      /   |  / __ \/ __ \    | |  / / ____/ ___/                                                                                        
     / /| | / /_/ / /_/ /____| | / / /    \__ \                                                                                         
    / ___ |/ ____/ ____/_____/ |/ / /___ ___/ /                                                                                         
   /_/  |_/_/   /_/          |___/\____//____/                                                                                          
                                                                                                                                        
   -by 1cli                                                                                                                             
                                                                                                                                        

OPTIONS:
   -v, --version                           版本信息                                                                                         

   -u <1>, --url <1>                       设置服务端APi 地址                                                                                  

   -P <1>, --project_path <1>              项目目录                                                                                         

   -c <1>, --client_id <1>                 客户端 ID                                                                                       

   -V <1>, --project_version <1>           指定版本号                                                                                        

   -p <1>, --path <1>                      安装库根目录                                                                                       

   -d <1>, --database <1>                  数据库配置，格式：mysql://username:password@host:port/dbname                                          
                                           例如：mysql://root:root@127.0.0.1:port/app-vcs                                                  

   -h, --help                              Display this help screen and exit immediately.                                               

   --no-colors                             Do not use any colors in output. Useful when piping output to other tools or files.          

   --loglevel <level>                      Minimum level of messages to display. Default is info. Valid levels are: debug, info, notice,
                                           success, warning, error, critical, alert, emergency.                                         


ARGUMENTS:
   <appId>                                 APP-VCS 管理平台应用 ID                                                                            

COMMANDS:
   This tool accepts a command as first parameter as outlined below:                                                                    


   register

     注册客户端                                                                                                                              
                                                                                                                                        

   rollback

     回滚项目版本                                                                                                                             
                                                                                                                                        

   deploy

     部署项目                                                                                                                               
                     
```
##  🔥 功能一览

 
- 版本检查
- 版本更新
- 数据库更新迁移
- 数据库回滚
- 升级备份文件
- 升级备份数据库
- ....

##  💡 系统简介

`APP-VCS` 是一个应用版本升级依赖包, 必须依赖版本升级系统使用

系统完全开源，基于 **Apache 2.0** 开源协议，**免费且不限制商业使用**。



**技术栈**

- [PHP](https://php.net/)
- [Mysql](https://vuejs.org/)





## 🌐 使用说明

⚠️只支持php的系统接入, 其他语言,需要自行编写逻辑.

###  配置
安装完成后, 需要进行配置, 自行生成一个名为:**appvcs.php**的文件, 并配置好对应的参数, 配置说明:
```php

// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------
return [
	/*
	|--------------------------------------------------------------------------
	| 服务地址
	|--------------------------------------------------------------------------
	|
	| 版本管理平台API地址, 填写规范: http://host:port/,
	| 结尾必须带上 "/"
	|
	*/
	'server_url'     => 'http://dev.app-vcs.com/',
	/*
	|--------------------------------------------------------------------------
	| 客户端ID
	|--------------------------------------------------------------------------
	|
	| 从版本管理平台中创建获取
	|
	*/
	'client_id'      => 'client-test-1',
	/*
	|--------------------------------------------------------------------------
	| 应用ID
	|--------------------------------------------------------------------------
	|
	| 从版本管理系统中创建获取
	|
	*/
	'app_id'         => 'gentou',
	/*
	|--------------------------------------------------------------------------
	| 网站本地存储目录
	|--------------------------------------------------------------------------
	|
	| 本地根目录地址, 例如thinkphp的根目录在: www/wwwroot/xxx/public, 
	| 那么必须填写绝对路径地址: /www/wwwroot/xxx/public
	|
	*/
	'root_path' => app()->getRootPath(),
	/*
	|--------------------------------------------------------------------------
	| 数据库地址
	|--------------------------------------------------------------------------
	|
	| 安装升级时，需要备份数据库，这里填写备份地址
	|
	*/
	'database'       => [
		// 数据库类型
		'driver'   => 'mysql',
		// 服务器地址
		'host'     => '127.0.0.1',
		// 数据库端口
		'port'     => 3306,
		// 数据库名
		'database' => 'tzkj_gentou',
		// 用户名
		'username' => 'root',
		// 密码
		'password' => 'root',
	],
];
```
### 3.3 使用示例
配置完成后, 就可以使用AppVcs了, 示例如下:
#### 3.3.1 版本更新检查
```php
$appVcs = new \Lcli\AppVcs\AppVcs();
$check  = $appvcs->check();

```
#### 3.3.2 获取更新补丁包
```php
$appvcs = new AppVcs();
$upgradeResult  = $appvcs->upgrade();
```
#### 3.3.3 获取当前客户端版本信息
```php
$appvcs = new AppVcs();
$upgradeResult  = $appvcs->getVersion();

```
## 4. 仓库提交约束
使用版本管理系统,必须遵守平台代码提交约束, 否则无法进行版本发布或引发错误,

约束有以下几点:
1. 每提交一次代码必须给需要发布的代码添加版本tag, 并提交到应用对应绑定的仓库地址中

```php
tag命名规范为: 1.0.0, 1.0.1, 1.0.2, 1.0.3, ...(不包含v字符)
```
2. 代码提交(`git commit`)内容描述规范: 首行必须为标题, 隔2行后填写更新内容, 例如:
```php
新增文件回滚测试:v1.1.11

[测试]新增文件回滚测试
[修复]修复v1.1.10数据库迁移失败
```
3. 数据库迁移文件数据库必须放在根目录下的:`database/upgrade` 目录下,
   命名需要发布的版本号一致`v{版本号}.sql`, 例如:`v1.0.0.sql` ,否则无法找到迁移文件
```php
迁移文件内容是sql文件, 内容规范为:

-- {表名:用于备份对应客户端的表}
执行语句....

例如:
-- lmq_demo
CREATE TABLE lmq_demo (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          name VARCHAR(255) NOT NULL
);
-- lmq_demo
ALTER TABLE lmq_demo ADD COLUMN `describe` VARCHAR(255);
```
4. 数据库回滚文件
   数据库回滚必须放在: `database/upgrade/rollback` 目录下, 命名要和版本号一致:`v{版本号}.sql`, 例如:`v1.0.0.sql` ,否则无法找到回滚文件
   示例:
```sql
-- v1.0.0.sql 文件:
ALTER TABLE lmq_demo
    DROP COLUMN `icon`;
```

##  🔧 系统安装

### 环境要求
    - `PHP 5.6` `PHP 7.0`
    - `MySQL` `>=5.0`
    - `PHP Extension`：`Fileinfo`
    - `Apache/Nginx`




> 我们的测试基于 PHP 的 5.6 / 7.0 / 8.0 / 8.1 版本，系统稳定性最好

 
 
    