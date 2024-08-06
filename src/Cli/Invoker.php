<?php

/**
 * This file is part of the Carbon package.
 *
 * (c) Lcli <9125517778@qq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Lcli\AppVcs\Cli;

class Invoker
{
	public function __invoke(...$parameters)
	{
		$cli = new Cli();
		$cli->run();
	}
}
