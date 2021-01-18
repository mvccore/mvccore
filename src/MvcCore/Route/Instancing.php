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

namespace MvcCore\Route;

trait Instancing {

	/**
	 * @inheritDocs
	 * @param string|array	$patternOrConfig
	 *						Required, configuration array or route pattern value
	 *						to parse into match and reverse patterns.
	 * @param string		$controllerAction
	 *						Optional, controller and action name in pascal case
	 *						like: `"Products:List"`.
	 * @param array			$defaults
	 *						Optional, default param values like:
	 *						`["name" => "default-name", "page" => 1]`.
	 * @param array			$constraints
	 *						Optional, params regular expression constraints for
	 *						regular expression match function if no `"match"`
	 *						property in config array as first argument defined.
	 * @param array			$advancedConfiguration
	 *						Optional, http method to only match requests by this
	 *						method. If `NULL` (by default), request with any http
	 *						method could be matched by this route. Given value is
	 *						automatically converted to upper case.
	 * @return \MvcCore\Route
	 */
	public static function CreateInstance (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$advancedConfiguration = []
	) {
		/** @var $this \MvcCore\Route */
		return (new \ReflectionClass(get_called_class()))
			->newInstanceArgs(func_get_args());
	}

	/**
	 * Create new route instance. First argument could be configuration array
	 * with all necessary constructor values or all separated arguments - first
	 * is route pattern value to parse into match and reverse values, then
	 * controller with action, params default values and constraints.
	 * Example:
	 * `new Route([
	 *		"pattern"			=> "/products-list/<name>/<color>",
	 *		"controllerAction"	=> "Products:List",
	 *		"defaults"			=> ["name" => "default-name",	"color" => "red"],
	 *		"constraints"		=> ["name" => "[^/]*",			"color" => "[a-z]*"]
	 * ]);`
	 * or:
	 * `new Route(
	 *		"/products-list/<name>/<color>",
	 *		"Products:List",
	 *		["name" => "default-name",	"color" => "red"],
	 *		["name" => "[^/]*",			"color" => "[a-z]*"]
	 * );`
	 * or:
	 * `new Route([
	 *		"name"			=> "products_list",
	 *		"match"			=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> ["name" => "default-name",	"color" => "red"],
	 * ]);`
	 * @param string|array	$patternOrConfig
	 *						Required, configuration array or route pattern value
	 *						to parse into match and reverse patterns.
	 * @param string		$controllerAction
	 *						Optional, controller and action name in pascal case
	 *						like: `"Products:List"`.
	 * @param array			$defaults
	 *						Optional, default param values like:
	 *						`["name" => "default-name", "page" => 1]`.
	 * @param array			$constraints
	 *						Optional, params regular expression constraints for
	 *						regular expression match function no `"match"` record
	 *						in configuration array as first argument defined.
	 * @param array			$advancedConfiguration
	 *						Optional, http method to only match requests by this
	 *						method. If `NULL` (by default), request with any http
	 *						method could be matched by this route. Given value is
	 *						automatically converted to upper case.
	 * @return void
	 */
	public function __construct (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$advancedConfiguration = []
	) {
		/** @var $this \MvcCore\Route */
		if (count(func_get_args()) === 0) return;
		if (is_array($patternOrConfig)) {
			$data = (object) $patternOrConfig;
			$this->constructDataPatternsDefaultsConstraintsFilters($data);
			$this->constructDataCtrlActionName($data);
			$this->constructDataAdvConf($data);
			$this->config = & $patternOrConfig;
		} else {
			$this->constructVarsPatternDefaultsConstraintsFilters(
				$patternOrConfig, $defaults, $constraints, $advancedConfiguration
			);
			$this->constructVarCtrlActionNameByData($controllerAction);
			$this->constructVarAdvConf($advancedConfiguration);
			$this->config = & $advancedConfiguration;
		}
		$this->constructCtrlOrActionByName();
	}

	/**
	 * If route is initialized by single array argument with all data,
	 * initialize following properties if those exist in given object:
	 * `pattern`, `match` and `reverse`. If properties `defaults`, `constraints`
	 * and `filters` exist in given object, initialize them by setter methods.
	 * @param \stdClass $data	Object containing properties `pattern`,
	 *							`match`, `reverse`, `filters` and `defaults`.
	 * @return void
	 */
	protected function constructDataPatternsDefaultsConstraintsFilters (& $data) {
		/** @var $this \MvcCore\Route */
		if (isset($data->pattern))
			$this->pattern = $data->pattern;
		if (isset($data->match))
			$this->match = $data->match;
		if (isset($data->reverse))
			$this->reverse = $data->reverse;
		if (isset($data->defaults))
			$this->SetDefaults($data->defaults);
		if (isset($data->constraints))
			$this->SetConstraints($data->constraints);
		if (isset($data->filters) && is_array($data->filters))
			$this->SetFilters($data->filters);
	}

	/**
	 * If route is initialized by single array argument with all data,
	 * initialize following properties if those exist in given object:
	 * `controller`, `action` (or `controllerAction`) and `name`.
	 * @param \stdClass $data	Object containing properties `controller`,
	 *							`action` (or `controllerAction`) and `name`.
	 * @return void
	 */
	protected function constructDataCtrlActionName (& $data) {
		/** @var $this \MvcCore\Route */
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
	}

	/**
	 * If route is initialized by single array argument with all data,
	 * initialize following properties if those exist in given object:
	 * `method`, `redirect` and `absolute`.
	 * @param \stdClass $data	Object containing properties `method`,
	 *							`redirect` and `absolute`.
	 * @return void
	 */
	protected function constructDataAdvConf (& $data) {
		/** @var $this \MvcCore\Route */
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

	/**
	 * If route is initialized by each constructor function arguments,
	 * initialize `pattern` and `defaults`, if those are not `NULL` and
	 * initialize constraints by setter if not `NULL` and initialize filter in
	 * and filter out by filter setter from `$advCfg` array if there are those
	 * filter keys found.
	 * @param string|NULL	$pattern		Route pattern string.
	 * @param array|NULL	$defaults		Route defaults array, keys are param
	 *										names, values are default values.
	 * @param array|NULL	$constraints	Route params regular expression
	 *										constraints array, keys are param
	 *										names, values are allowed regular
	 *										expression rules.
	 * @param array			$advCfg			An array with possible keys `in` and
	 *										`out` to define route filter in and
	 *										filter out callable.
	 * @return void
	 */
	protected function constructVarsPatternDefaultsConstraintsFilters (& $pattern, & $defaults, & $constraints, & $advCfg) {
		/** @var $this \MvcCore\Route */
		if ($pattern !== NULL)
			$this->pattern = $pattern;
		if ($defaults !== NULL)
			$this->defaults = $defaults;
		if ($constraints !== NULL)
			$this->SetConstraints($constraints);
		$filterInParam = static::CONFIG_FILTER_IN;
		if (isset($advCfg[$filterInParam]))
			$this->SetFilter($advCfg[$filterInParam], $filterInParam);
		$filterOutParam = static::CONFIG_FILTER_OUT;
		if (isset($advCfg[$filterOutParam]))
			$this->SetFilter($advCfg[$filterOutParam], $filterOutParam);
	}

	/**
	 * If route is initialized by each constructor function arguments,
	 * initialize `controller` and `action`, if any of them is defined in given
	 * argument `$ctrlAction`.
	 * @param string|NULL $ctrlAction	Controller and action combination
	 *									definition, it could be `"Products:List"`
	 *									or only `"Products:"` etc.
	 * @return void
	 */
	protected function constructVarCtrlActionNameByData (& $ctrlAction) {
		/** @var $this \MvcCore\Route */
		if ($ctrlAction !== NULL) {
			list($ctrl, $action) = explode(':', $ctrlAction);
			if ($ctrl) $this->controller = $ctrl;
			if ($action) $this->action = $action;
		}
	}

	/**
	 * If route is initialized by each constructor function arguments,
	 * initialize `method`, `redirect` and `absolute`.
	 * @param array $advCfg An array with possible keys `method`,
	 *						`redirect` and `absolute`.
	 * @return void
	 */
	protected function constructVarAdvConf (& $advCfg) {
		/** @var $this \MvcCore\Route */
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

	/**
	 * If route is initialized by each constructor function arguments or also
	 * if route is initialized by single array argument with all data, this
	 * function is called to initialize `controller` and `action` properties if
	 * those are still `NULL`. Function tries to initialize those properties
	 * from route `action` property`, if it contains colon char `:`.
	 * @param array $advCfg An array with possible keys `method`,
	 *						`redirect` and `absolute`.
	 * @return void
	 */
	protected function constructCtrlOrActionByName () {
		/** @var $this \MvcCore\Route */
		if (!$this->controller && !$this->action && strpos($this->name, ':') !== FALSE && strlen($this->name) > 1) {
			list($ctrl, $action) = explode(':', $this->name);
			if ($ctrl) $this->controller = $ctrl;
			if ($action) $this->action = $action;
		}
	}
}
