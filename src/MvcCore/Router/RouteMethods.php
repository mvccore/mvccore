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
	 *		'products_list'	=> array(
	 *			"pattern"			=> "/products-list/<name>/<color>",
	 *			"controllerAction"	=> "Products:List",
	 *			"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *			"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 *		)
	 *	]);`
	 * or:
	 *	`\MvcCore\Router::GetInstance()->SetRoutes([
	 *		new Route(
	 *			"/products-list/<name>/<color>",
	 *			"Products:List",
	 *			["name" => "default-name",	"color" => "red"],
	 *			["name" => "[^/]*",		"color" => "[a-z]*"]
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
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										`Controller::Action` definitions.
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, routes are 
	 *								 inserted into default group.
	 * @param bool $autoInitialize If `TRUE`, locale routes array is cleaned and 
	 *							   then all routes (or configuration arrays) are 
	 *							   sended into method `$router->AddRoutes();`, 
	 *							   where are routes auto initialized for missing 
	 *							   route names or route controller or route action
	 *							   record, completed always from array keys.
	 *							   You can you `FALSE` to set routes without any 
	 *							   change or autoinitialization, it could be usefull 
	 *							   to restore cached routes etc.
	 * @return \MvcCore\Router
	 */
	public function & SetRoutes ($routes = [], $groupName = NULL, $autoInitialize = TRUE) {
		if ($autoInitialize) {
			$this->routes = [];
			$this->AddRoutes($routes, $groupName);
		} else {
			$this->routes = $routes;
			$routesAreEmpty = count($routes) === 0;
			$noGroupNameDefined = $groupName === NULL;
			if ($noGroupNameDefined) {
				if ($routesAreEmpty) {
					$this->routesGroups = [];
					$this->noUrlRoutes = [];
				}
				$this->routesGroups[''] = $routes;
			} else {
				$this->routesGroups[$groupName] = $routes;
			}
			$this->urlRoutes = [];
			foreach ($routes as $route) {
				$this->urlRoutes[$route->GetName()] = $route;
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
			$this->anyRoutesConfigured = !$routesAreEmpty;
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
	 * @param \MvcCore\Route[]|array $routes Keyed array with routes,
	 *										 keys are route names or route
	 *										 `Controller::Action` definitions.
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, routes are 
	 *								 inserted into default group.
	 * @param bool $prepend	Optional, if `TRUE`, all given routes will
	 *						be prepended from the last to the first in
	 *						given list, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Router
	 */
	public function & AddRoutes (array $routes = [], $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
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
					if ($ctrlActionName)
						$route->SetControllerAction($routeName);
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
					"[".__CLASS__."] Route is not possible to assign (key: \"$routeName\", value: " . serialize($route) . ")."
				);
			}
		}
		$this->anyRoutesConfigured = count($routes) > 0;
		return $this;
	}

	/**
	 * Append or prepend new request route.
	 * Set up route by route name into `\MvcCore\Router::$routes` array
	 * to route incoming request and also set up route by route name and
	 * by `Controller:Action` combination into `\MvcCore\Router::$urlRoutes`
	 * array to build url addresses.
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
	 * @param \MvcCore\Route|\MvcCore\IRoute|array $routeCfgOrRoute Route instance or
	 *																route config array.
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, routes are 
	 *								 inserted into default group.
	 * @param bool $prepend	Optional, if `TRUE`, given route will
	 *						be prepended, not appended.
	 * @param bool $throwExceptionForDuplication `TRUE` by default. Throw an exception,
	 *											 if route `name` or route `Controller:Action`
	 *											 has been defined already. If `FALSE` old route
	 *											 is overwriten by new one.
	 * @return \MvcCore\Router
	 */
	public function & AddRoute ($routeCfgOrRoute, $groupName = NULL, $prepend = FALSE, $throwExceptionForDuplication = TRUE) {
		$instance = & $this->getRouteInstance($routeCfgOrRoute);
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
				throw new \InvalidArgumentException('['.__CLASS__.'] '.implode(' ',$errorMsgs));
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
	 * TODO: dopsat
	 * @param \MvcCore\Route $route 
	 * @param string $routeName 
	 * @param string|NULL $groupName 
	 * @param bool $prepend 
	 */
	protected function addRouteToGroup (\MvcCore\IRoute & $route, $routeName, $groupName, $prepend) {
		if ($groupName === NULL) {
			$routesGroupsKey = '';
		} else {
			$routesGroupsKey = $groupName;
			$route->SetGroupName($groupName);
		}
		if (array_key_exists($routesGroupsKey, $this->routesGroups)) {
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
	 * Return `TRUE` if router has any route by given route name, `FALSE` otherwise.
	 * @param string|\MvcCore\IRoute $routeOrRouteName
	 * @return bool
	 */
	public function HasRoute ($routeOrRouteName) {
		if (is_string($routeOrRouteName)) {
			return isset($this->routes[$routeOrRouteName]);
		} else /*if ($routeOrRouteName instanceof \MvcCore\IRoute)*/ {
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
			if ($this->currentRoute->GetName() === $result->GetName())
				$this->currentRoute = NULL;
		}
		if (!$this->routes && $this->preRouteMatchingHandler === NULL) 
			$this->anyRoutesConfigured = FALSE;
		return $result;
	}

	protected function removeRouteFromGroup (\MvcCore\IRoute & $route, $routeName) {
		$routeGroup = $route->GetGroupName();
		$groupRoutesKey = $routeGroup ?: '';
		if (isset($this->routesGroups[$groupRoutesKey])) 
			unset($this->routesGroups[$groupRoutesKey][$routeName]);
	}

	/**
	 * Get all configured route(s) as `\MvcCore\Route` instances.
	 * Keys in returned array are route names, values are route objects.
	 * @param string|NULL $groupName Group name is first matched/parsed word in 
	 *								 requested path to group routes by to try to
	 *								 match only routes you realy need, not all of
	 *								 them. If `NULL` by default, there are 
	 *								 returned all routes from all groups.
	 * @return \MvcCore\Route[]
	 */
	public function & GetRoutes ($groupName = NULL) {
		if ($groupName !== NULL) 
			return $this->routesGroups[$groupName];
		return $this->routes;
	}

	/**
	 * Get configured `\MvcCore\Route` route instances by route name, `NULL` if no route presented.
	 * @return \MvcCore\Route|NULL
	 */
	public function & GetRoute ($routeName) {
		if (isset($this->routes[$routeName]))
			return $this->routes[$routeName];
		return NULL;
	}

	/**
	 * Set matched route instance for given request object
	 * into `\MvcCore\Route::Route();` method. Currently matched
	 * route is always assigned internally in that method.
	 * @param \MvcCore\Route $currentRoute
	 * @return \MvcCore\Router
	 */
	public function & SetCurrentRoute (\MvcCore\IRoute $currentRoute) {
		$this->currentRoute = $currentRoute;
		return $this;
	}

	/**
	 * Get matched route instance reference for given request object
	 * into `\MvcCore\Route::Route($request);` method. Currently
	 * matched route is always assigned internally in that method.
	 * @return \MvcCore\Route
	 */
	public function & GetCurrentRoute () {
		return $this->currentRoute;
	}

	/**
	 * Get always route instance from given route configuration data or return
	 * already created given instance.
	 * @param \MvcCore\Route|\MvcCore\IRoute|array $routeCfgOrRoute Route instance or
	 *																		   route config array.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	protected function & getRouteInstance (& $routeCfgOrRoute) {
		if ($routeCfgOrRoute instanceof \MvcCore\IRoute) 
			return $routeCfgOrRoute->SetRouter($this);
		$routeClass = self::$routeClass;
		return $routeClass::CreateInstance($routeCfgOrRoute)->SetRouter($this);
	}
}
