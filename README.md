
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

### 3.1 下载版本管理PHP依赖包:
```bash
composer require lcli/app-vcs
```
⚠️要注意的是, 找不到版本包,请切换成官方镜像源即可, 命令如下:
```bash
composer config -g repo.packagist composer https://repo.packagist.org
```
 

##  🔥 功能一览

最新版本  1.1.1

 

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
```mind
要求:
php:>=5.6
建议:
建议使用composer管理依赖包
```
### 3.1 下载版本管理PHP依赖包:
```bash
composer require lcli/app-vcs
```
⚠️要注意的是, 找不到版本包,请切换成官方镜像源即可, 命令如下:
```bash
composer config -g repo.packagist composer https://repo.packagist.org
```
### 3.2 配置
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


- **Laravel 5.1 版本**
    - `PHP 5.6` `PHP 7.0`
    - `MySQL` `>=5.0`
    - `PHP Extension`：`Fileinfo`
    - `Apache/Nginx`


 

> 我们的测试基于 PHP 的 5.6 / 7.0 / 8.0 / 8.1 版本，系统稳定性最好

 
 
    