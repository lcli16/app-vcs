<?php

namespace Lcli\AppVcs\Kernel;

use Lcli\AppVcs\Helpers;
use Lcli\AppVcs\Http\Request as HttpRequest;

class Request {
	public static function instance()
	{
		$options = [
			'client_id' => Helpers::getClientId(),
			'url'       => Helpers::getServerUrl(),
		];
		return new HttpRequest($options);
	}
}