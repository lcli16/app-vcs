<?php

namespace Lcli\AppVcs\Http;

class Http {
	/**
	 * 发送POST请求
	 *
	 * @param string $url     请求的URL
	 * @param array  $data    POST数据
	 * @param array  $headers 请求头
	 * @param int    $timeout 超时时间（秒）
	 * @return array 包含响应状态码和响应体的数组
	 */
	public function post($url, $data = [], $headers = [], $timeout = 5)
	{
		$ch = curl_init();
		
		// 设置cURL选项
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::prepareHeaders($headers));
		
		// 执行请求
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		return $response;
	}
	
	/**
	 * 准备请求头
	 *
	 * @param array $headers 请求头数组
	 * @return array 准备好的请求头数组
	 */
	private static function prepareHeaders($headers)
	{
		$preparedHeaders = [];
		foreach ( $headers as $key => $value ) {
			$preparedHeaders[] = "$key: $value";
		}
		return $preparedHeaders;
	}
}