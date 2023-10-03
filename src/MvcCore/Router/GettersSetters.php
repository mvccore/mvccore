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

/**
 * @mixin \MvcCore\Router
 */
trait GettersSetters {

	/**
	 * @inheritDoc
	 * @return \MvcCore\Request
	 */
	public function GetRequest () {
		return $this->request;
	}

	/**
	 * @inheritDoc
	 * @param  \MvcCore\Request $request
	 * @return \MvcCore\Router
	 */
	public function SetRequest (\MvcCore\IRequest $request) {
		/** @var \MvcCore\Request $request */
		$this->request = $request;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  bool|NULL $routeByQueryString 
	 * @return \MvcCore\Router
	 */
	public function SetRouteByQueryString ($routeByQueryString = TRUE) {
		$this->routeByQueryString = $routeByQueryString;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return bool|NULL
	 */
	public function GetRouteByQueryString () {
		return $this->routeByQueryString;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetRouteToDefaultIfNotMatch () {
		return $this->routeToDefaultIfNotMatch;
	}

	/**
	 * @inheritDoc
	 * @param  bool $enable
	 * @return \MvcCore\Router
	 */
	public function SetRouteToDefaultIfNotMatch ($enable = TRUE) {
		$this->routeToDefaultIfNotMatch = $enable;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array
	 */
	public function & GetDefaultParams () {
		return $this->defaultParams;
	}

	/**
	 * @inheritDoc
	 * @return array
	 */
	public function & GetRequestedParams () {
		return $this->requestedParams;
	}

	/**
	 * @inheritDoc
	 * @return int
	 */
	public function GetTrailingSlashBehaviour () {
		return $this->trailingSlashBehaviour;
	}

	/**
	 * @inheritDoc
	 * @param  int $trailingSlashBehaviour
	 * @return \MvcCore\Router
	 */
	public function SetTrailingSlashBehaviour ($trailingSlashBehaviour = -1) {
		$this->trailingSlashBehaviour = $trailingSlashBehaviour;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetAutoCanonizeRequests () {
		return $this->autoCanonizeRequests;
	}

	/**
	 * @inheritDoc
	 * @param  bool $autoCanonizeRequests 
	 * @return \MvcCore\Router
	 */
	public function SetAutoCanonizeRequests ($autoCanonizeRequests = TRUE) {
		$this->autoCanonizeRequests = $autoCanonizeRequests;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  callable $preRouteMatchingHandler 
	 * @return \MvcCore\Router
	 */
	public function SetPreRouteMatchingHandler (callable $preRouteMatchingHandler = NULL) {
		$this->preRouteMatchingHandler = $preRouteMatchingHandler;
		if ($preRouteMatchingHandler !== NULL) {
			$this->anyRoutesConfigured = TRUE;
		} else {
			$this->anyRoutesConfigured = count($this->routes) > 0;
		}
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return callable|NULL
	 */
	public function GetPreRouteMatchingHandler () {
		return $this->preRouteMatchingHandler;
	}

	/**
	 * @inheritDoc
	 * @param  callable $preRouteUrlBuildingHandler 
	 * @return \MvcCore\Router
	 */
	public function SetPreRouteUrlBuildingHandler (callable $preRouteUrlBuildingHandler) {
		$this->preRouteUrlBuildingHandler = $preRouteUrlBuildingHandler;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return callable|NULL
	 */
	public function GetPreRouteUrlBuildingHandler () {
		return $this->preRouteUrlBuildingHandler;
	}
}
