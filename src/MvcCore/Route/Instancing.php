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

/**
 * @mixin \MvcCore\Route
 * @phpstan-type FilterCallable callable(array<string,mixed>, array<string,mixed>, \MvcCore\IRequest): array<string,mixed>
 * @phpstan-type RouteConfig object{"name":?string,"pattern":?string,"match":?string,"reverse":?string,"controller":?string,"action":?string,"controllerAction":?string,"defaults":array<string,mixed>|null,"constraints":array<string,string>|null,"filters":array<string,FilterCallable>|null}
 */
trait Instancing {

	/**
	 * @inheritDoc
	 * @param string|array<string,mixed> $pattern
	 * Required, configuration array or route pattern value
	 * to parse into match and reverse patterns.
	 * @param ?string                    $controllerAction
	 * Optional, controller and action name in pascal case
	 * like: `"Products:List"`.
	 * @? array<string,mixed>   $defaults
	 * Optional, default param values like:
	 * `["name" => "default-name", "page" => 1]`.
	 * @param ?array<string,string>      $constraints
	 * Optional, params regular expression constraints for
	 * regular expression match function if no `"match"`
	 * property in config array as first argument defined.
	 * @param array<string,mixed>        $config
	 * Optional, array with adwanced configuration.
	 * There could be defined:
	 * - string   `method`   HTTP method name. If `NULL` (by default), 
	 *                       request with any http method could be matched 
	 *                       by this route. Given value is automatically 
	 *                       converted to upper case.
	 * - string   `redirect` Redirect route name.
	 * - bool     `absolute` Absolutize URL.
	 * - callable `in`       URL filter in, callable accepting arguments:
	 *                       `array $params, array $defaultParams, \MvcCore\Request $request`.
	 * - callable `out`      URL filter out, callable accepting arguments:
	 *                       `array $params, array $defaultParams, \MvcCore\Request $request`.
	 * @return \MvcCore\Route
	 */
	public static function CreateInstance (
		$pattern = NULL,
		$controllerAction = NULL,
		$defaults = NULL,
		$constraints = NULL,
		$config = []
	) {
		return (new \ReflectionClass(get_called_class()))
			->newInstanceArgs(func_get_args());
	}

	/**
	 * Create new route instance. First argument could be configuration array
	 * with all necessary constructor values or all separated arguments - first
	 * is route pattern value to parse into match and reverse values, then
	 * controller with action, params default values and constraints.
	 * Example:
	 * ````
	 *   new Route([
	 *       "pattern"          => "/products-list/<name>/<color>",
	 *       "controllerAction" => "Products:List",
	 *       "defaults"         => ["name" => "default-name", "color" => "red"],
	 *       "constraints"      => ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *   ]);
	 * ````
	 * or:
	 * ````
	 *   new Route(
	 *       "/products-list/<name>/<color>",
	 *       "Products:List",
	 *       ["name" => "default-name", "color" => "red"],
	 *       ["name" => "[^/]*",        "color" => "[a-z]*"]
	 *   );
	 * ````
	 * or:
	 * ````
	 *   new Route([
	 *       "name"       => "products_list",
	 *       "match"      => "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *       "reverse"    => "/products-list/<name>/<color>",
	 *       "controller" => "Products",
	 *       "action"     => "List",
	 *       "defaults"   => ["name" => "default-name", "color" => "red"],
	 *   ]);
	 * ````
	 * @param string|array<string,mixed> $pattern
	 * Required, configuration array or route pattern value
	 * to parse into match and reverse patterns.
	 * @param ?string                    $controllerAction
	 * Optional, controller and action name in pascal case
	 * like: `"Products:List"`.
	 * @param ?array<string,mixed>       $defaults
	 * Optional, default param values like:
	 * `["name" => "default-name", "page" => 1]`.
	 * @param ?array<string,string>      $constraints
	 * Optional, params regular expression constraints for
	 * regular expression match function no `"match"` record
	 * in configuration array as first argument defined.
	 * @param array<string,mixed>        $config
	 * Optional, array with adwanced configuration.
	 * There could be defined:
	 * - string   `method`   HTTP method name. If `NULL` (by default), 
	 *                       request with any http method could be matched 
	 *                       by this route. Given value is automatically 
	 *                       converted to upper case.
	 * - string   `redirect` Redirect route name.
	 * - bool     `absolute` Absolutize URL.
	 * - callable `in`       URL filter in, callable accepting arguments:
	 *                       `array $params, array $defaultParams, \MvcCore\Request $request`.
	 * - callable `out`      URL filter out, callable accepting arguments:
	 *                       `array $params, array $defaultParams, \MvcCore\Request $request`.
	 * @return void
	 */
	public function __construct (
		$pattern = NULL,
		$controllerAction = NULL,
		$defaults = NULL,
		$constraints = NULL,
		$config = []
	) {
		if (count(func_get_args()) === 0) return;
		$patternOrConfig = $pattern;
		$advancedConfiguration = $config;
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
	 * @param  RouteConfig $data
	 * Object containing properties `pattern`,
	 * `match`, `reverse`, `filters` and `defaults`.
	 * @return void
	 */
	protected function constructDataPatternsDefaultsConstraintsFilters (& $data) {
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
		if (isset($data->filters) && is_array($data->filters)) // @phpstan-ignore-line
			$this->SetFilters($data->filters);
	}

	/**
	 * If route is initialized by single array argument with all data,
	 * initialize following properties if those exist in given object:
	 * `controller`, `action` (or `controllerAction`) and `name`.
	 * @param  RouteConfig $data
	 * Object containing properties `controller`,
	 * `action` (or `controllerAction`) and `name`.
	 * @return void
	 */
	protected function constructDataCtrlActionName (& $data) {
		if (isset($data->controllerAction)) {
			list($ctrl, $action) = explode(':', $data->controllerAction);
			if ($ctrl) {
				$this->controller = $ctrl;
				$this->initCtrlHasAbsNamespace();
			};
			if ($action) 
				$this->action = $action;
			if (isset($data->name)) {
				$this->name = $data->name;
			} else {
				$this->name = $data->controllerAction;
			}
		} else {
			if (isset($data->controller)) {
				$this->controller = $data->controller;
				$this->initCtrlHasAbsNamespace();
			}
			if (isset($data->action))
				$this->action = $data->action;
			if (isset($data->name)) {
				$this->name = $data->name;
			} else if ($this->controller !== '' && $this->action !== '') {
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
	 * @param  RouteConfig $data
	 * Object containing properties `method`,
	 * `redirect` and `absolute`.
	 * @return void
	 */
	protected function constructDataAdvConf (& $data) {
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
	 * @param ?string                   $pattern
	 * Route pattern string.
	 * @param ?array<string,mixed>      $defaults
	 * @Route defaults array, keys are param
	 * names, values are default values.
	 * @param ?array<string,string>     $constraints
	 * Route params regular expression
	 * constraints array, keys are param
	 * names, values are allowed regular
	 * expression rules.
	 * @param array<string,mixed>       $advancedConfiguration
	 * Optional, array with adwanced configuration.
	 * There could be defined:
	 * - callable `in`       URL filter in.
	 * - callable `out`      URL filter out.
	 * @return void
	 */
	protected function constructVarsPatternDefaultsConstraintsFilters (& $pattern, & $defaults, & $constraints, & $advancedConfiguration) {
		if ($pattern !== NULL)
			$this->pattern = $pattern;
		if ($defaults !== NULL)
			$this->defaults = $defaults;
		if ($constraints !== NULL)
			$this->SetConstraints($constraints);
		$filterInParam = static::CONFIG_FILTER_IN;
		if (isset($advancedConfiguration[$filterInParam]))
			$this->SetFilter($advancedConfiguration[$filterInParam], $filterInParam);
		$filterOutParam = static::CONFIG_FILTER_OUT;
		if (isset($advancedConfiguration[$filterOutParam]))
			$this->SetFilter($advancedConfiguration[$filterOutParam], $filterOutParam);
	}

	/**
	 * If route is initialized by each constructor function arguments,
	 * initialize `controller` and `action`, if any of them is defined in given
	 * argument `$ctrlAction`.
	 * @param  ?string     $ctrlAction Controller and action combination
	 *                                 definition, it could be `"Products:List"`
	 *                                 or only `"Products:"` etc.
	 * @return void
	 */
	protected function constructVarCtrlActionNameByData (& $ctrlAction) {
		if ($ctrlAction !== NULL) {
			list($ctrl, $action) = explode(':', $ctrlAction);
			if ($ctrl) {
				$this->controller = $ctrl;
				$this->initCtrlHasAbsNamespace();
			}
			if ($action) $this->action = $action;
		}
	}

	/**
	 * If route is initialized by each constructor function arguments,
	 * initialize `method`, `redirect` and `absolute`.
	 * @param array<string,mixed> $advancedConfiguration
	 * Optional, array with adwanced configuration.
	 * There could be defined:
	 * - string   `method`   HTTP method name.
	 * - string   `redirect` Redirect route name.
	 * - bool     `absolute` Absolutize URL.
	 * @return void
	 */
	protected function constructVarAdvConf (& $advancedConfiguration) {
		$methodParam = static::CONFIG_METHOD;
		if (isset($advancedConfiguration[$methodParam]))
			$this->method = strtoupper((string) $advancedConfiguration[$methodParam]);
		$redirectParam = static::CONFIG_REDIRECT;
		if (isset($advancedConfiguration[$redirectParam]))
			$this->redirect = (string) $advancedConfiguration[$redirectParam];
		$absoluteParam = static::CONFIG_ABSOLUTE;
		if (isset($advancedConfiguration[$absoluteParam]))
			$this->absolute = (bool) $advancedConfiguration[$absoluteParam];
	}

	/**
	 * If route is initialized by each constructor function arguments or also
	 * if route is initialized by single array argument with all data, this
	 * function is called to initialize `controller` and `action` properties if
	 * those are still `NULL`. Function tries to initialize those properties
	 * from route `action` property`, if it contains colon char `:`.
	 * @return void
	 */
	protected function constructCtrlOrActionByName () {
		if (
			!$this->controller && 
			!$this->action && 
			$this->name !== NULL &&
			strpos($this->name, ':') !== FALSE && 
			strlen($this->name) > 1
		) {
			list($ctrl, $action) = explode(':', $this->name);
			if ($ctrl) {
				$this->controller = $ctrl;
				$this->initCtrlHasAbsNamespace();
			}
			if ($action) $this->action = $action;
		}
	}
}
