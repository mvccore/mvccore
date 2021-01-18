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

trait RouteMethods {

	/**
	 * @inheritDocs
	 * @param \MvcCore\Route[]|array $routes
	 *				Keyed array with routes, keys are route names or route
	 *				`Controller::Action` definitions.
	 * @param string|NULL $groupName
	 *				Group name is first matched/parsed word in requested path to
	 *				group routes by to try to match only routes you really need,
	 *				not all of them. If `NULL` by default, routes are inserted
	 *				into default group.
	 * @param bool $autoInitialize
	 *				If `TRUE`, locale routes array is cleaned and then all
	 *				routes (or configuration arrays) are sent into method
	 *				`$router->AddRoutes();`, where are routes auto initialized
	 *				for missing route names or route controller or route action
	 *				record, completed always from array keys. You can you `FALSE`
	 *				to set routes without any change or auto-initialization, it
	 *				could be useful to restore cached routes etc.
	 * @return \MvcCore\Router
	 */
	public function SetRoutes ($routes = [], $groupName = NULL, $autoInitialize = TRUE) {
		/** @var $this \MvcCore\Router */
		if ($autoInitialize) {
			$this->routes = [];
			$this->AddRoutes($routes, $groupName);
		} else {
			$routesAreEmpty = count($routes) === 0;
			$noGroupNameDefined = $groupName === NULL;
			// complete URL routes and routes with name keys
			$newRoutes = [];
			$this->urlRoutes = [];
			foreach ($routes as $route) {
				$routeName = $route->GetName();
				$newRoutes[$routeName] = $route;
				$this->urlRoutes[$routeName] = $route;
				$controllerAction = $route->GetControllerAction();
				if ($controllerAction !== ':')
					$this->urlRoutes[$controllerAction] = $route;
				if ($noGroupNameDefined) {
					$routeGroupName = $route->GetGroupName();
					if ($routeGroupName === NULL) $routeGroupName = '';
					if (!array_key_exists($routeGroupName, $this->routesGroups))
						$this->routesGroups[$routeGroupName] = [];
					$this->routesGroups[$routeGroupName][] = $route;
				}
			}
			$this->routes = $newRoutes;
			if ($noGroupNameDefined) {
				if ($routesAreEmpty) {
					$this->routesGroups = [];
					$this->noUrlRoutes = [];
				}
				$this->routesGroups[''] = $newRoutes;
			} else {
				$this->routesGroups[$groupName] = $newRoutes;
			}
			$this->anyRoutesConfigured = (!$routesAreEmpty) || $this->preRouteMatchingHandler !== NULL;
		}
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Route[]|array $routes
	 *			   Keyed array with routes, keys are route names or route
	 *			   `Controller::Action` definitions.
	 * @param string|NULL $groupName
	 *			   Group name is first matched/parsed word in requested path to
	 *			   group routes by to try to match only routes you really need,
	 *			   not all of them. If `NULL` by default, routes are inserted
	 *			   into default group.
	 * @param bool $prepend
	 *			   Optional, if `TRUE`, all given routes will be prepended from
	 *			   the last to the first in given list, not appended.
	 * @param bool $throwExceptionForDuplication
	 *			   `TRUE` by default. Throw an exception, if route `name` or
	 *			   route `Controller:Action` has been defined already. If
	 *			   `FALSE` old route is over-written by new one.
	 * @return \MvcCore\Router
	 */
	public function AddRoutes (array $routes = [], $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		/** @var $this \MvcCore\Router */
		if ($prepend) $routes = array_reverse($routes);
		$routeClass = self::$routeClass;
		foreach ($routes as $routeName => & $route) {
			$numericKey = is_numeric($routeName);
			$ctrlActionName = !$numericKey && mb_strpos($routeName, ':') !== FALSE;
			if ($route instanceof \MvcCore\IRoute) {
				if ($numericKey) {
					if (!$route->GetName()) {
						$routeAutoName = $route->GetControllerAction();
						if ($routeAutoName === ':') $routeAutoName = 'Route_' . $routeName;
						$route->SetName($routeAutoName);
					}
				} else {
					if ($ctrlActionName) {
						$route->SetControllerAction($routeName);
					} else if ($route->GetName() !== $routeName && $route->GetName() === $route->GetControllerAction()) {
						$route->SetName($routeName);
					}
					if ($route->GetName() === NULL)
						$route->SetName($routeName);
				}
				$this->AddRoute(
					$route, $groupName, $prepend, $throwExceptionForDuplication
				);
			} else if (is_array($route)) {
				if (!$numericKey)
					$route[$ctrlActionName ? 'controllerAction'  : 'name'] = $routeName;
				$this->AddRoute(
					$this->getRouteInstance($route),
					$groupName, $prepend, $throwExceptionForDuplication
				);
			} else if (is_string($route)) {
				// route name is always Controller:Action
				$routeCfgData = ['pattern' => $route];
				$routeCfgData[$ctrlActionName ? 'controllerAction'  : 'name'] = $routeName;
				$this->AddRoute(
					$routeClass::CreateInstance($routeCfgData),
					$groupName, $prepend, $throwExceptionForDuplication
				);
			} else {
				throw new \InvalidArgumentException (
					"[".get_class()."] Route is not possible to assign"
					." (key: `{$routeName}`, value: `" . serialize($route) . "`)."
				);
			}
		}
		$this->anyRoutesConfigured = count($routes) > 0 || $this->preRouteMatchingHandler !== NULL;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Route|array $routeCfgOrRoute
	 *			   Route instance or route config array.
	 * @param string|NULL $groupName
	 *			   Group name is first matched/parsed word in requested path to
	 *			   group routes by to try to match only routes you really need,
	 *			   not all of them. If `NULL` by default, routes are inserted
	 *			   into default group.
	 * @param bool $prepend
	 *			   Optional, if `TRUE`, given route will be prepended,
	 *			   not appended.
	 * @param bool $throwExceptionForDuplication
	 *			   `TRUE` by default. Throw an exception, if route `name` or
	 *			   route `Controller:Action` has been defined already. If
	 *			   `FALSE` old route is over-written by new one.
	 * @return \MvcCore\Router
	 */
	public function AddRoute ($routeCfgOrRoute, $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		/** @var $this \MvcCore\Router */
		$instance = $this->getRouteInstance($routeCfgOrRoute);
		$routeName = $instance->GetName();
		$controllerAction = $instance->GetControllerAction();
		if ($throwExceptionForDuplication) {
			$errorMsgs = [];
			if (isset($this->routes[$routeName]))
				$errorMsgs[] = 'Route with name `'.$routeName.'` has already been defined between router routes.';
			if (isset($this->urlRoutes[$controllerAction]))
				$errorMsgs[] = 'Route with `Controller:Action` combination: `'.$controllerAction.'` has already been defined between router routes.';
			if ($errorMsgs) {
				//var_dump($this->routes);
				$debBack = debug_backtrace();
				$debBackLength = count($debBack);
				if ($debBackLength > 1) {
					$debBackSemiFinalRec = $debBack[$debBackLength - 2];
					$file = str_replace('\\', '/', $debBackSemiFinalRec['file']);
					$bootstrapFilePath = '/App/Bootstrap.php';
					if (mb_strpos($file, $bootstrapFilePath) === mb_strlen($file) - mb_strlen($bootstrapFilePath)) 
						die('['.get_class().'] '.implode(' ',$errorMsgs));
				}
				throw new \InvalidArgumentException('['.get_class().'] '.implode(' ',$errorMsgs));
			}
		}
		$this->urlRoutes[$routeName] = $instance;
		if ($controllerAction !== ':') $this->urlRoutes[$controllerAction] = $instance;
		$this->addRouteToGroup ($instance, $routeName, $groupName, $prepend);
		if ($prepend) {
			$newItem = [$routeName => $instance];
			$this->routes = $newItem + $this->routes;
		} else {
			$this->routes[$routeName] = $instance;
		}
		$this->anyRoutesConfigured = TRUE;
		return $this;
	}

	/**
	 * Add route instance into named routes group. Every routes group is chosen
	 * in routing moment by first parsed word from requested URL.
	 * @param \MvcCore\Route	$route		A route instance reference.
	 * @param string			$routeName	Route name.
	 * @param string|NULL		$groupName	Group name, first parsed word from requested URL.
	 * @param bool				$prepend	IF `TRUE`, prepend route instance, `FALSE` otherwise.
	 * @return void
	 */
	protected function addRouteToGroup (\MvcCore\IRoute $route, $routeName, $groupName, $prepend) {
		/** @var $this \MvcCore\Router */
		if ($groupName === NULL) {
			$routesGroupsKey = '';
		} else {
			$routesGroupsKey = $groupName;
			$route->SetGroupName($groupName);
		}
		if (isset($this->routesGroups[$routesGroupsKey])) {
			$groupRoutes = & $this->routesGroups[$routesGroupsKey];
		} else {
			$groupRoutes = [];
			$this->routesGroups[$routesGroupsKey] = & $groupRoutes;
		}
		if ($prepend) {
			$newItem = [$routeName => $route];
			$groupRoutes = $newItem + $groupRoutes;
		} else {
			$groupRoutes[$routeName] = $route;
		}
	}

	/**
	 * @inheritDocs
	 * @param string|\MvcCore\Route $routeOrRouteName
	 * @return bool
	 */
	public function HasRoute ($routeOrRouteName) {
		/** @var $this \MvcCore\Router */
		if (is_string($routeOrRouteName)) {
			return isset($this->routes[$routeOrRouteName]);
		} else /*if ($routeOrRouteName instance of \MvcCore\IRoute)*/ {
			return (
				isset($this->routes[$routeOrRouteName->GetName()]) ||
				isset($this->routes[$routeOrRouteName->GetControllerAction()])
			);
		}
		//return FALSE;
	}

	/**
	 * @inheritDocs
	 * @param string $routeName
	 * @return \MvcCore\Route|NULL
	 */
	public function RemoveRoute ($routeName) {
		/** @var $this \MvcCore\Router */
		$result = NULL;
		if (isset($this->routes[$routeName])) {
			$result = $this->routes[$routeName];
			unset($this->routes[$routeName]);
			$this->removeRouteFromGroup($result, $routeName);
			$controllerAction = $result->GetControllerAction();
			if (isset($this->urlRoutes[$routeName]))
				unset($this->urlRoutes[$routeName]);
			if (isset($this->urlRoutes[$controllerAction]))
				unset($this->urlRoutes[$controllerAction]);
			/** @var $currentRoute \MvcCore\Route */
			$currentRoute = $this->currentRoute;
			if ($currentRoute->GetName() === $result->GetName())
				$this->currentRoute = NULL;
		}
		if (!$this->routes && $this->preRouteMatchingHandler === NULL)
			$this->anyRoutesConfigured = FALSE;
		return $result;
	}

	/**
	 * Unset route from defined group. This method doesn't unset the route
	 * from router object to not be possible to create URL by given route anymore.
	 * This does route method: `\MvcCore\Route::RemoveRoute($routeName);`.
	 * @param \MvcCore\IRoute $route
	 * @param string $routeName
	 * @return void
	 */
	protected function removeRouteFromGroup (\MvcCore\IRoute $route, $routeName) {
		/** @var $this \MvcCore\Router */
		$routeGroup = $route->GetGroupName();
		$groupRoutesKey = $routeGroup ?: '';
		if (isset($this->routesGroups[$groupRoutesKey]))
			unset($this->routesGroups[$groupRoutesKey][$routeName]);
	}

	/**
	 * @inheritDocs
	 * @param string|NULL $groupName
	 *				Group name is first matched/parsed word in requested path to
	 *				group routes by to try to match only routes you really need,
	 *				not all of them. If `NULL` by default, there are returned
	 *				all routes from all groups.
	 * @return \MvcCore\Route[]
	 */
	public function GetRoutes ($groupName = NULL) {
		/** @var $this \MvcCore\Router */
		if ($groupName !== NULL)
			return $this->routesGroups[$groupName];
		return $this->routes;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Route|NULL
	 */
	public function GetRoute ($routeName) {
		/** @var $this \MvcCore\Router */
		if (isset($this->routes[$routeName]))
			return $this->routes[$routeName];
		return NULL;
	}

	/**
	 * @inheritDocs
	 * @param \MvcCore\Route $currentRoute
	 * @return \MvcCore\Router
	 */
	public function SetCurrentRoute (\MvcCore\IRoute $currentRoute) {
		/** @var $this \MvcCore\Router */
		/** @var $currentRoute \MvcCore\Route */
		$this->currentRoute = $currentRoute;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \MvcCore\Route
	 */
	public function GetCurrentRoute () {
		/** @var $this \MvcCore\Router */
		return $this->currentRoute;
	}

	/**
	 * Get always route instance from given route configuration data or return
	 * already created given instance.
	 * @param \MvcCore\Route|array $routeCfgOrRoute Route instance or route config array.
	 * @return \MvcCore\Route
	 */
	protected function getRouteInstance (& $routeCfgOrRoute) {
		/** @var $this \MvcCore\Router */
		if ($routeCfgOrRoute instanceof \MvcCore\IRoute)
			return $routeCfgOrRoute->SetRouter($this);
		$routeClass = self::$routeClass;
		return $routeClass::CreateInstance($routeCfgOrRoute)->SetRouter($this);
	}
}
