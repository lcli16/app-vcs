<?php

namespace Lcli\AppVcs\Http;

use Lcli\AppVcs\AppVcsException;

class Request {
	private  $url = 'http://dev.app-vcs.com/';
	private  $clientId = '';
	private $http = null;
	public function __construct($options) {
		$clientId = $options['client_id'];
		if (!$clientId) {
			throw new AppVcsException('客户id不能为空');
		}
		$url = $options['url']??'';
		if (!$url){
			throw new AppVcsException("服务地址为空");
		}
		$this->url = $url;
		$this->clientId = $clientId;
		$this->http = new Http();
	}
	
	public function check($data)
	{
		$url = $this->url."api/appvcs/system/check/{$this->clientId}";
		return $this->post($url, $data);
	}
	
	/**
	 * 下载更新包
	 * @param $data
	 * @return array|mixed
	 * @throws \Lcli\AppVcs\AppVcsException
	 */
	public function upgrade($data)
	{
		$url = $this->url."api/appvcs/system/upgrade/{$this->clientId}";
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
	   $code = $resp['code']??-1;
	   if (!$code<0){
		   throw new AppVcsException($resp['msg']??'未知错误');
	   }
	   return $resp['data']??[];
    }
	
}