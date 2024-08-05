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
 
	protected function runWithCli(  array $parameters)
	{
		array_shift($parameters);
		$cmd = implode(" ", $parameters);
		return shell_exec($cmd);
	}
	
	public function __invoke(...$parameters)
	{
		
		$cli = new Cli();
	 
		$cli->boot();
		
		die;
		$result =  $this->runWithCli(  $parameters);
	 
		
	
		exit($result);
	}
	
	public function help()
	{
		exit("help");
	}
}
