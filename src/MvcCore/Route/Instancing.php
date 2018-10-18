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

namespace MvcCore\Route;

trait Instancing
{
	/**
	 * TODO: neaktuální
	 * Create every time new route instance, no singleton managing!
	 * Called usually from core methods:
	 * - `\MvcCore\Router::AddRoutes();`
	 * - `\MvcCore\Router::AddRoute();`
	 * - `\MvcCore\Router::routeByControllerAndActionQueryString();`
	 * This method is the best place where to implement custom
	 * route initialization for core.
	 * First argument should be configuration array or
	 * route pattern value to parse into match and reverse patterns.
	 * Example:
	 * `new Route(array(
	 *		"pattern"			=> "/products-list/<name>/<color>",
	 *		"controllerAction"	=> "Products:List",
	 *		"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *		"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 * ));`
	 * or:
	 * `new Route(
	 *		"/products-list/<name>/<color>",
	 *		"Products:List",
	 *		array("name" => "default-name",	"color" => "red"),
	 *		array("name" => "[^/]*",		"color" => "[a-z]*")
	 * );`
	 * or:
	 * `new Route(array(
	 *		"name"			=> "products_list",
	 *		"match"			=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 * ));`
	 * @param string|array	$patternOrConfig	Required, configuration array or route pattern value to parse into match and reverse patterns.
	 * @param string		$controllerAction	Optional, controller and action name in pascale case like: `"Photogallery:List"`.
	 * @param string		$defaults			Optional, default param values like: `array("name" => "default-name", "page" => 1)`.
	 * @param array			$constraints		Optional, params regex constraints for regular expression match fn no `"match"` record in configuration array as first argument defined.
	 * @param array			$filters			Optional, callable function(s) under keys `"in" | "out"` to filter in and out params accepting arguments: `array $params, array $defaultParams, \MvcCore\IRequest $request`.
	 * @param array			$method				Optional, http method to only match requests by this method. If `NULL` (by default), request with any http method could be matched by this route. Given value is automaticly converted to upper case.
	 * @return \MvcCore\Route
	 */
	public static function CreateInstance (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$advancedConfiguration = []
	) {
		return (new \ReflectionClass(get_called_class()))
			->newInstanceArgs(func_get_args());
	}

	/**
	 * Create new route instance.
	 * First argument should be configuration array or
	 * route pattern value to parse into match and reverse patterns.
	 * Example:
	 * `new Route(array(
	 *		"pattern"			=> "/products-list/<name>/<color>",
	 *		"controllerAction"	=> "Products:List",
	 *		"defaults"			=> array("name" => "default-name",	"color" => "red"),
	 *		"constraints"		=> array("name" => "[^/]*",			"color" => "[a-z]*")
	 * ));`
	 * or:
	 * `new Route(
	 *		"/products-list/<name>/<color>",
	 *		"Products:List",
	 *		array("name" => "default-name",	"color" => "red"),
	 *		array("name" => "[^/]*",		"color" => "[a-z]*")
	 * );`
	 * or:
	 * `new Route(array(
	 *		"name"			=> "products_list",
	 *		"match"			=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 * ));`
	 * @param string|array $patternOrConfig	Required, configuration array or route pattern value to parse into match and reverse patterns.
	 * @param string $controllerAction		Optional, controller and action name in pascale case like: `"Photogallery:List"`.
	 * @param array $defaults				Optional, default param values like: `array("name" => "default-name", "page" => 1)`.
	 * @param array $constraints			Optional, params regex constraints for regular expression match fn no `"match"` record in configuration array as first argument defined.
	 * @param array	$filters				Optional, callable function(s) under keys `"in" | "out"` to filter in and out params accepting arguments: `array $params, array $defaultParams, \MvcCore\IRequest $request`.
	 * @param array $method					Optional, http method to only match requests by this method. If `NULL` (by default), request with any http method could be matched by this route. Given value is automaticly converted to upper case.
	 * @return \MvcCore\Route
	 */
	public function __construct (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$advancedConfiguration = []
	) {
		if (count(func_get_args()) === 0) return;
		if (is_array($patternOrConfig)) {
			$data = (object) $patternOrConfig;
			if (isset($data->pattern)) 
				$this->pattern = $data->pattern;
			if (isset($data->match)) 
				$this->match = $data->match;
			if (isset($data->reverse)) 
				$this->reverse = $data->reverse;
			$this->constructCtrlActionNameDefConstrAndAdvCfg($data);
		} else {
			if ($patternOrConfig !== NULL) 
				$this->pattern = $patternOrConfig;
			$this->constructCtrlActionDefConstrAndAdvCfg(
				$controllerAction, $defaults, $constraints, $advancedConfiguration
			);
		}
		$this->constructCtrlOrActionByName();
	}

	protected function constructCtrlActionNameDefConstrAndAdvCfg (& $data) {
		if (isset($data->controllerAction)) {
			list($ctrl, $action) = explode(':', $data->controllerAction);
			if ($ctrl) $this->controller = $ctrl;
			if ($action) $this->action = $action;
			if (isset($data->name)) {
				$this->name = $data->name;
			} else {
				$this->name = $data->controllerAction;
			}
		} else {
			$this->controller = isset($data->controller) ? $data->controller : NULL;
			$this->action = isset($data->action) ? $data->action : NULL;
			if (isset($data->name)) {
				$this->name = $data->name;
			} else if ($this->controller !== NULL && $this->action !== NULL) {
				$this->name = $this->controller . ':' . $this->action;
			} else {
				$this->name = NULL;
			}
		}
		if (isset($data->defaults)) 
			$this->SetDefaults($data->defaults);
		if (isset($data->constraints)) 
			$this->SetConstraints($data->constraints);
		if (isset($data->filters) && is_array($data->filters)) 
			$this->SetFilters($data->filters);
		$methodParam = static::CONFIG_METHOD;
		if (isset($data->{$methodParam})) 
			$this->method = strtoupper((string) $data->{$methodParam});
		$redirectParam = static::CONFIG_REDIRECT;
		if (isset($data->{$redirectParam})) 
			$this->redirect = (string) $data->{$redirectParam};
		$absoluteParam = static::CONFIG_ABSOLUTE;
		if (isset($data->{$absoluteParam}))
			$this->absolute = (bool) $data->{$absoluteParam};
	}

	protected function constructCtrlActionDefConstrAndAdvCfg (& $ctrlAction, & $defaults, & $constraints, & $advCfg) {
		// Controller:Action, defaults and constraints
		if ($ctrlAction !== NULL) {
			list($ctrl, $action) = explode(':', $ctrlAction);
			if ($ctrl) $this->controller = $ctrl;
			if ($action) $this->action = $action;
		}
		if ($defaults !== NULL)
			$this->defaults = $defaults;
		if ($constraints !== NULL)
			$this->SetConstraints($constraints);
		// filters, method, redirect and absolute
		$filterInParam = static::CONFIG_FILTER_IN;
		if (isset($advCfg[$filterInParam]))
			$this->SetFilter($advCfg[$filterInParam]);
		$filterOutParam = static::CONFIG_FILTER_OUT;
		if (isset($advCfg[$filterOutParam]))
			$this->SetFilter($advCfg[$filterOutParam]);
		$methodParam = static::CONFIG_METHOD;
		if (isset($advCfg[$methodParam]))
			$this->method = strtoupper((string) $advCfg[$methodParam]);
		$redirectParam = static::CONFIG_REDIRECT;
		if (isset($advCfg[$redirectParam]))
			$this->redirect = (string) $advCfg[$redirectParam];
		$absoluteParam = static::CONFIG_ABSOLUTE;
		if (isset($advCfg[$absoluteParam]))
			$this->absolute = (bool) $advCfg[$absoluteParam];
	}

	protected function constructCtrlOrActionByName () {
		if (!$this->controller && !$this->action && strpos($this->name, ':') !== FALSE && strlen($this->name) > 1) {
			list($ctrl, $action) = explode(':', $this->name);
			if ($ctrl) $this->controller = $ctrl;
			if ($action) $this->action = $action;
		}
	}
}
