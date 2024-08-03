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
 
	protected function runWithCli(  array $parameters): bool
	{
		
		
		return shell_exec(...$parameters);
	}
	
	public function __invoke(...$parameters): bool
	{
		return $this->runWithCli(  $parameters);
	}
}
