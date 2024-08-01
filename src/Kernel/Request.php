<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\Http\Request as HttpRequest;

class Request {
	public static function instance($config=[])
	{
		$options = [
			'client_id' => Helpers::getClientId($config),
			'url'       => Helpers::getServerUrl($config),
		];
		return new HttpRequest($options);
	}
}