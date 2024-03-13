<?php

/**
* MvcCore
*
* This source file is subject to the BSD 3 License
* For the full copyright and license information, please view
* the LICENSE.md file that are distributed with this source code.
*
* @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
* @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
*/

namespace MvcCore {
	/**
	 * @inheritDoc
	 */
	class Debug implements IDebug {
		use \MvcCore\Debug\Props;
		use \MvcCore\Debug\Initializations;
		use \MvcCore\Debug\Handlers;
	}
}

namespace {
	/**
	 * @param  bool $development
	 * @return void
	 */
	\MvcCore\Debug::$InitGlobalShortHands = function ($development) {
		/**
		 * Dump any variable with output buffering in browser debug bar,
		 * store result for printing later. Return printed variable as string.
		 * @param  mixed               $value   Variable to dump.
		 * @param  string              $title   Optional title.
		 * @param  array<string,mixed> $options Dumper options.
		 * @return mixed Variable itself.
		 */
		function x ($value, $title = NULL, $options = []) { // @phpstan-ignore-line
			$options['backtraceIndex'] = 2;
			return \MvcCore\Debug::BarDump($value, $title, $options);
		}
		/**
		 * Dumps multiple variables with output buffering in browser debug bar.
		 * store result for printing later.
		 * @param  mixed $args,... Variables to dump.
		 * @return void
		 */
		function xx () { // @phpstan-ignore-line
			$args = func_get_args();
			foreach ($args as $arg) \MvcCore\Debug::BarDump($arg, NULL, ['backtraceIndex' => 2]);
		}
		if ($development) {
			/**
			 * Dump variables and die. If no variable, throw stop exception.
			 * @param  mixed $args,... Variables to dump.
			 * @throws \Exception
			 * @return void
			 */
			function xxx ($args = NULL) { // @phpstan-ignore-line
				$args = func_get_args();
				if (count($args) === 0) {
					throw new \ErrorException('Stopped.', 500);
				} else {
					\MvcCore\Application::GetInstance()->GetResponse()->SetHeader('Content-Type', 'text/html');
					@header('Content-Type: text/html');
					echo '<pre><code>';
					foreach ($args as $arg) {
						$dumpedArg = \MvcCore\Debug::Dump($arg, TRUE, TRUE);
						echo $dumpedArg;
						echo '</code></pre>';
					}
				}
				exit;
			}
		} else {
			/**
			 * Log variables and die. If no variable, throw stop exception.
			 * @param mixed $args,... Variables to dump.
			 * @throws \Exception
			 * @return void
			 */
			function xxx ($args = NULL) { // @phpstan-ignore-line
				$args = func_get_args();
				if (count($args) > 0)
					foreach ($args as $arg)
						\MvcCore\Debug::Log($arg, \MvcCore\IDebug::DEBUG);
				echo 'Error 500 - Stopped.';
				exit;
			}
		}
	};
}
