<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Router;

trait GettersSetters {

	/**
	 * @inheritDocs
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		/** @var $this \MvcCore\Router */
		return $this->request;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Request $request
	 * @return \MvcCore\Router
	 */
	public function SetRequest (\MvcCore\IRequest $request) {
		/** @var $this \MvcCore\Router */
		/** @var $request \MvcCore\Request */
		$this->request = $request;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param bool|NULL $routeByQueryString 
	 * @return \MvcCore\Router
	 */
	public function SetRouteByQueryString ($routeByQueryString = TRUE) {
		/** @var $this \MvcCore\Router */
		$this->routeByQueryString = $routeByQueryString;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return bool|NULL
	 */
	public function GetRouteByQueryString () {
		/** @var $this \MvcCore\Router */
		return $this->routeByQueryString;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetRouteToDefaultIfNotMatch () {
		/** @var $this \MvcCore\Router */
		return $this->routeToDefaultIfNotMatch;
	}

	/**
	 * @inheritDocs
	 * @param bool $enable
	 * @return \MvcCore\Router
	 */
	public function SetRouteToDefaultIfNotMatch ($enable = TRUE) {
		/** @var $this \MvcCore\Router */
		$this->routeToDefaultIfNotMatch = $enable;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function & GetDefaultParams () {
		/** @var $this \MvcCore\Router */
		return $this->defaultParams;
	}

	/**
	 * @inheritDocs
	 * @return array
	 */
	public function & GetRequestedParams () {
		/** @var $this \MvcCore\Router */
		return $this->requestedParams;
	}

	/**
	 * @inheritDocs
	 * @return int
	 */
	public function GetTrailingSlashBehaviour () {
		/** @var $this \MvcCore\Router */
		return $this->trailingSlashBehaviour;
	}

	/**
	 * @inheritDocs
	 * @param int $trailingSlashBehaviour
	 * @return \MvcCore\Router
	 */
	public function SetTrailingSlashBehaviour ($trailingSlashBehaviour = -1) {
		/** @var $this \MvcCore\Router */
		$this->trailingSlashBehaviour = $trailingSlashBehaviour;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetAutoCanonizeRequests () {
		/** @var $this \MvcCore\Router */
		return $this->autoCanonizeRequests;
	}

	/**
	 * @inheritDocs
	 * @param bool $autoCanonizeRequests 
	 * @return \MvcCore\Router
	 */
	public function SetAutoCanonizeRequests ($autoCanonizeRequests = TRUE) {
		/** @var $this \MvcCore\Router */
		$this->autoCanonizeRequests = $autoCanonizeRequests;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router
	 */
	public function SetPreRouteMatchingHandler (callable $preRouteMatchingHandler = NULL) {
		/** @var $this \MvcCore\Router */
		$this->preRouteMatchingHandler = $preRouteMatchingHandler;
		if ($preRouteMatchingHandler !== NULL) {
			$this->anyRoutesConfigured = TRUE;
		} else {
			$this->anyRoutesConfigured = count($this->routes) > 0;
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return callable|NULL
	 */
	public function GetPreRouteMatchingHandler () {
		/** @var $this \MvcCore\Router */
		return $this->preRouteMatchingHandler;
	}

	/**
	 * @inheritDocs
	 * @param callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router
	 */
	public function SetPreRouteUrlBuildingHandler (callable $preRouteUrlBuildingHandler = NULL) {
		/** @var $this \MvcCore\Router */
		$this->preRouteUrlBuildingHandler = $preRouteUrlBuildingHandler;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return callable|NULL
	 */
	public function GetPreRouteUrlBuildingHandler () {
		/** @var $this \MvcCore\Router */
		return $this->preRouteUrlBuildingHandler;
	}
}
