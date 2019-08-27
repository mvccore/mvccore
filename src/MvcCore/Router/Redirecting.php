<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license  https://mvccore.github.io/docs/mvccore/5.0.0/LICENCE.md
 */

namespace MvcCore\Router;

trait Redirecting
{
	/**
	 * Redirect to proper trailing slash URL version only
	 * if there is necessary by configurable property
	 * `\MvcCore\Router::$trailingSlashBehaviour` and if 
	 * there is necessary by last character in request path.
	 * @return bool
	 */
	protected function redirectToProperTrailingSlashIfNecessary () {
		if (!$this->trailingSlashBehaviour) return TRUE;
		// path is still not modified by media or localization router in this moment
		$path = $this->request->GetPath();
		if ($path == '/')
			return TRUE; // do not redirect for homepage with trailing slash
		if ($path == '') {
			// add homepage trailing slash and redirect
			$this->redirect(
				$this->request->GetBaseUrl()
				. '/'
				. $this->request->GetQuery(TRUE)
				. $this->request->GetFragment(TRUE),
				\MvcCore\IResponse::MOVED_PERMANENTLY,
				'Home trailing slash added'
			);
		}
		$lastPathChar = mb_substr($path, mb_strlen($path) - 1);
		if ($lastPathChar == '/' && $this->trailingSlashBehaviour == \MvcCore\IRouter::TRAILING_SLASH_REMOVE) {
			// remove trailing slash and redirect
			$this->redirect(
				$this->request->GetBaseUrl()
				. rtrim($path, '/')
				. $this->request->GetQuery(TRUE)
				. $this->request->GetFragment(TRUE),
				\MvcCore\IResponse::MOVED_PERMANENTLY,
				'Removed trailing slash'
			);
			return FALSE;
		} else if ($lastPathChar != '/' && $this->trailingSlashBehaviour == \MvcCore\IRouter::TRAILING_SLASH_ALWAYS) {
			// add trailing slash and redirect
			$this->redirect(
				$this->request->GetBaseUrl()
				. $path . '/'
				. $this->request->GetQuery(TRUE)
				. $this->request->GetFragment(TRUE),
				\MvcCore\IResponse::MOVED_PERMANENTLY,
				'Trailing slash added'
			);
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Redirect request to given URL with optional code and terminate application.
	 * @param string		$url	New location url.
	 * @param int			$code	Http status code, 301 by default.
	 * @param string|NULL	$reason	Any optional text header for reason why.
	 * @return void
	 */
	protected function redirect ($url, $code = 301, $reason = NULL) {
		$app = \MvcCore\Application::GetInstance();
		$response = $app->GetResponse();
		$response
			->SetCode($code)
			->SetHeader('Location', $url);
		if ($reason !== NULL)
			$response->SetHeader('X-Reason', $reason);
		$app->Terminate();
	}
}
