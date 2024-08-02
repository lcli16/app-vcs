<?php

namespace Lcli\AppVcs\Http;

use Lcli\AppVcs\AppVcsException;
use Lcli\AppVcs\Helpers;
use function Couchbase\defaultDecoder;

class Request {
	private $url      = 'http://dev.app-vcs.com/';
	private $clientId = '';
	private $http     = null;
	
	public function __construct($options)
	{
		$clientId = $options['client_id'];
		if (!$clientId) {
			throw new AppVcsException('客户id不能为空');
		}
		$url = isset($options['url']) ?$options['url']: '';
		if (!$url) {
			throw new AppVcsException("服务地址为空");
		}
		$this->url      = $url;
		$this->clientId = $clientId;
		$this->http     = new Http();
	}
	
	public function config($options)
	{
		
		$this->url      = Helpers::getServerUrl($options);
		$this->clientId = Helpers::getClientId($options);
		if (!$this->url) {
			throw new AppVcsException('服务地址为空');
		}
		if (!$this->clientId) {
			throw new AppVcsException('客户id不能为空');
		}
	}
	
	/**
	 * 执行回调
	 * @param $data
	 * @param $config
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public function callback($data, $config = [])
	{
		$this->config($config);
		$url = $this->url . "api/appvcs/callback/{$this->clientId}";
		return $this->post($url, $data);
	}
	/**
	 * 检查更新
	 * @param $data
	 * @param $config
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public function check($data, $config = [])
	{
		$this->config($config);
		$url = $this->url . "api/appvcs/system/check/{$this->clientId}";
		return $this->post($url, $data);
	}
	
	/**
	 * 下载更新包
	 * @param $data
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public function upgrade($data, $config = [])
	{
		$this->config($config );
		$url = $this->url . "api/appvcs/system/upgrade/{$this->clientId}";
		return $this->post($url, $data);
	}
	
	/**
	 * 发送请求-post
	 * @param $url
	 * @param $data
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	private function post($url, $data)
	{
		$response = $this->http->post($url, $data);
		return $this->output($response);
	}
	
	/**
	 * 响应输出
	 * @param $response
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	private function output($response)
	{
		$resp = json_decode($response, true);
		if (!$resp) {
			throw new AppVcsException("数据解析失败");
		}
		$code = isset($resp['code']) ?$resp['code']: -1;
		if ($code < 0) {
			throw new AppVcsException(isset($resp['msg']) ?$resp['msg']: '未知错误');
		}
		return isset($resp['data']) ?$resp['data']: [];
	}
	
}