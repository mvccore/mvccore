<?php

include_once(__DIR__ . '/../vendor/autoload.php');

if (php_sapi_name() !== 'cli') {
	\Tracy\Debugger::enable(\Tracy\Debugger::DEVELOPMENT);
	\Tracy\Debugger::$productionMode = false;
	/** @var int  how long strings display by dump() */
	\Tracy\Debugger::$maxLength = 1024*1024;
	\Tracy\Debugger::$showLocation = true;
	
	header("Content-Type: text/html; charset=utf-8");
}

function run (callable $testFn, ...$args) {
	try {
		return call_user_func_array($testFn, $args);
	} catch (\Throwable $e) {
		if (php_sapi_name() !== 'cli') {
			header_remove();
			\Tracy\Debugger::exceptionHandler($e);
		} else {
			throw $e;
		}
	}
}

\Tester\Environment::setup();