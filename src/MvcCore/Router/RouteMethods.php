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

trait RouteMethods
{
	/**
	 * Clear all possible previously configured routes
	 * and set new given request routes again.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes(array(
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		'products_list'	=> [
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		]
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
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
	 * @return \MvcCore\Router|\MvcCore\IRouter
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
	 * Append or prepend new request routes.
	 * If there is no name configured in route array configuration,
	 * set route name by given `$routes` array key, if key is not numeric.
	 *
	 * Routes could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		"Products:List"	=> "/products-list/<name>/<color>",
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		'products_list'	=> [
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		]
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",		"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoutes([
	 *		new Route(
	 *			"name"			=> "products_list",
	 *			"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"reverse"		=> "/products-list/<name>/<color>",
	 *			"controller"	=> "Products",
	 *			"action"		=> "List",
	 *			"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *		)
	 *	]);`
	 * @param \MvcCore\Route[]|\MvcCore\IRoute[]|array $routes
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
	 * @return \MvcCore\Router|\MvcCore\IRouter
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
	 * Append or prepend new request route.
	 * Set up route by route name into `\MvcCore\Router::$routes` array
	 * to route incoming request and also set up route by route name and
	 * by `Controller:Action` combination into `\MvcCore\Router::$urlRoutes`
	 * array to build URL addresses.
	 *
	 * Route could be defined in various forms:
	 * Example:
	 *	`\MvcCore\Router::GetInstance()->AddRoute([
	 *		"name"		=> "Products:List",
	 *		"pattern"	=> "/products-list/<name>/<color>",
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute([
	 *		"name"				=> "products_list",
	 *		"pattern"			=> "/products-list/<name>/<color>",
	 *		"controllerAction"	=> "Products:List",
	 *		"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *		"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *		"/products-list/<name>/<color>",
	 *		"Products:List",
	 *		["name" => "default-name",	"color" => "red"],
	 *		["name" => "[^/]*",		"color" => "[a-z]*"]
	 *	));`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->AddRoute(new Route(
	 *		"name"			=> "products_list",
	 *		"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 *	));`
	 * @param \MvcCore\Route|\MvcCore\IRoute|array $routeCfgOrRoute
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
	 * @return \MvcCore\Router|\MvcCore\IRouter
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
	 * Get `TRUE` if router has any route by given route name or `FALSE` if not.
	 * @param string|\MvcCore\IRoute $routeOrRouteName
	 * @return bool
	 */
	public function HasRoute ($routeOrRouteName) {
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
	 * Remove route from router by given name and return removed route instance.
	 * If router has no route by given name, `NULL` is returned.
	 * @param string $routeName
	 * @return \MvcCore\Route|\MvcCore\IRoute|NULL
	 */
	public function RemoveRoute ($routeName) {
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
		$routeGroup = $route->GetGroupName();
		$groupRoutesKey = $routeGroup ?: '';
		if (isset($this->routesGroups[$groupRoutesKey]))
			unset($this->routesGroups[$groupRoutesKey][$routeName]);
	}

	/**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @param string|NULL $groupName
	 *				Group name is first matched/parsed word in requested path to
	 *				group routes by to try to match only routes you really need,
	 *				not all of them. If `NULL` by default, there are returned
	 *				all routes from all groups.
	 * @return \MvcCore\Route[]|\MvcCore\IRoute[]
	 */
	public function GetRoutes ($groupName = NULL) {
		if ($groupName !== NULL)
			return $this->routesGroups[$groupName];
		return $this->routes;
	}

	/**
	 * Get configured `\MvcCore\Route` route instances by route name,
	 * `NULL` if no route presented.
	 * @return \MvcCore\Route|\MvcCore\IRoute|NULL
	 */
	public function GetRoute ($routeName) {
		if (isset($this->routes[$routeName]))
			return $this->routes[$routeName];
		return NULL;
	}

	/**
	 * Set matched route instance for given request object
	 * into `\MvcCore\Route::Route();` method. Currently matched
	 * route is always assigned internally in that method.
	 * @param \MvcCore\Route|\MvcCore\IRoute $currentRoute
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function SetCurrentRoute (\MvcCore\IRoute $currentRoute) {
		/** @var $this \MvcCore\Router */
		$this->currentRoute = $currentRoute;
		return $this;
	}

	/**
	 * Get matched route instance reference for given request object
	 * into `\MvcCore\Route::Route($request);` method. Currently
	 * matched route is always assigned internally in that method.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function GetCurrentRoute () {
		return $this->currentRoute;
	}

	/**
	 * Get always route instance from given route configuration data or return
	 * already created given instance.
	 * @param \MvcCore\Route|\MvcCore\IRoute|array $routeCfgOrRoute Route instance or
	 *																		   route config array.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	protected function getRouteInstance (& $routeCfgOrRoute) {
		/** @var $this \MvcCore\Router */
		if ($routeCfgOrRoute instanceof \MvcCore\IRoute)
			return $routeCfgOrRoute->SetRouter($this);
		$routeClass = self::$routeClass;
		return $routeClass::CreateInstance($routeCfgOrRoute)->SetRouter($this);
	}
}
