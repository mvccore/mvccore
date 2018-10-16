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

namespace MvcCore;

//include_once(__DIR__ . '/IRoute.php');

/**
 * Responsibility - describing request(s) to match and reversely build url addresses.
 * - Describing request to match it (read more about properties).
 * - Matching request by given request object, see `\MvcCore\Route::Matches()`.
 * - Completing url address by given params array, see `\MvcCore\Route::Url()`.
 *
 * Main Properties:
 * - `$Pattern`
 *   Required, if you have not configured `\MvcCore\Route::$match` and
 *   `\MvcCore\Route::$reverse` property instead. Very basic URL address
 *   form to match and parse rewrited params by it. Address to parse
 *   and prepare `\MvcCore\Route::$match` property and `\MvcCore\Route::$reverse`
 *   property. automaticly in `\MvcCore\Route::Prepare();` call.
 * - `$Match`
 *   Required together with `\MvcCore\Route::$reverse` property, if you
 *   have not configured `\MvcCore\Route::$pattern` property instead.
 *   This property is always used to match request by `\MvcCore\Request::Path`
 *   by classic PHP regualar expression matching by `preg_match_all();`.
 * - `$Reverse`
 *   Required together with `\MvcCore\Route::$match` property, if you
 *   have not configured `\MvcCore\Route::$pattern` property instead.
 *   This property is always used to complete url address by called params
 *   array and by this string with rewrite params replacements inside.
 * - `$Controller`
 *   Required, if there is no `controller` param inside `\MvcCore\Route::$pattern`
 *   or inside `\MvcCore\Route::$match property`. Controller class name to dispatch
 *   in pascal case form, namespaces and including slashes as namespace delimiters.
 * - `$Action`
 *   Required, if there is no `action` param inside `\MvcCore\Route::$pattern`
 *   or inside `\MvcCore\Route::$match property`. Public method in controller
 *   in pascal case form, but in controller named as `public function <CoolName>Action () {...`.
 * - `$Name`
 *   Not required, if you want to create url addresses always by `Controller:Action`
 *   named records. It could be any string, representing route custom name to
 *   complete url address by that name inside your application.
 * - `$Defaults`
 *   Not required, matched route params default values and query params default values.
 *   Last entry in array may be used for property `\MvcCore\Route::$lastPatternParam`
 *   describing last rewrited param inside match pattern to be automaticly trimmed
 *   from right side for possible address trailing slash in route matched moment.
 * - `$Constraints`
 *   not required, array with param names and their custom regular expression
 *   matching rules. If no constraint string for any param defined, there is used
 *   for all rewrited params default constraint rule to match everything except next slash.
 *   Default static property for matching rule shoud be changed here:
 *   - by default: `\MvcCore\Route::$DefaultConstraint = '[^/]*';`
 */
class Route implements IRoute
{
	/**
	 * TODO: neaktualni?
	 * Default constraint used for all rewrited params, if no
	 * constraint for rewrited param has been specified.
	 * configured as `"[^/]*"` by default. This value means:
	 * - "Any character(s) in any length, except next slash."
	 * @var string
	 */
	protected static $defaultPathConstraint = '[^/]+';

	protected static $defaultDomainConstraint = '[^\.]+';

	/**
	 * Route pattern to match request url and to build url address.
	 *
	 * To define route by this form is the most comfortable way,
	 * but a way slower, because there is necessary every request
	 * to convert this value into `\MvcCore\Route::$match` and into
	 * `\MvcCore\Route::$reverse` properties correctly and you can
	 * specify those both properties manualy, if you are not too lazy.
	 *
	 * This match and reverse definition has to be in very basic form
	 * without regular expression escaping or advanced rules:
	 * - No regular expression border `#` characters, it will be
	 *   used internally in route parsing.
	 * - No start `^` or end `$` regular expression characters,
	 *   those characters will be added automaticly.
	 * - No escaping of regular expression characters:
	 *   `[](){}<>|=+*.!?-/`, those characters will be escaped
	 *   in route preparing process.
	 * - star char inside param name (`<color*>`) means greedy param
	 *   matching all to the end of address. It has to be the last one.
	 *
	 * Example: `"/products-list/<name>/<color*>"`.
	 * @var string|\string[]|NULL
	 */
	protected $pattern		= NULL;

	/**
	 * Route match pattern in raw form (to use it as it is) to match proper request.
	 * This property is always used to match request by `\MvcCore\Request::Path`
	 * by classic PHP regualar expression matching by `preg_match_all();`.
	 *
	 * Required together with `\MvcCore\Route::$reverse` property, if you
	 * have not configured `\MvcCore\Route::$pattern` property instead.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$match` and
	 * `\MvcCore\Route::$reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but it's the best
	 * speed solution, because there is no `\MvcCore\Route::$pattern` parsing and
	 * conversion into `\MvcCore\Route::$match` and `\MvcCore\Route::$reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)#"`
	 * @var string|\string[]|NULL
	 */
	protected $match		= NULL;

	/**
	 * Route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with `\MvcCore\Route::$match` property, if you
	 * have not configured `\MvcCore\Route::$pattern` property instead.
	 *
	 * This is only very simple string with replacement places (like `<name>` or
	 * `<page>`) for given values by `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$match` and
	 * `\MvcCore\Route::$reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but it's the best
	 * speed solution, because there is no `\MvcCore\Route::$pattern` parsing and
	 * conversion into `\MvcCore\Route::$match` and `\MvcCore\Route::$reverse` properties.
	 *
	 * Example: `"/products-list/<name>/<color>"`
	 * @var string|\string[]|NULL
	 */
	protected $reverse		= NULL;

	/**
	 * Not required. Route name is your custom keyword/term
	 * or pascal case combination of controller and action
	 * describing `"Controller:Action"` target to be dispatched.
	 *
	 * By this name there is selected proper route object to
	 * complete url string by given params in router method:
	 * `\MvcCore\Router:Url($name, $params);`.
	 *
	 * Example: `"products_list" | "Products:Gallery"`
	 * @var string
	 */
	protected $name			= '';

	/**
	 * Controller name to dispatch, in pascal case. Required only if
	 * there is no `controller` param inside `\MvcCore\Route::$pattern`
	 * or inside `\MvcCore\Route::$match properties as url params`.
	 *
	 * It should contain controller class namespaces defined in standard PHP notation.
	 * If there is backslash at the beginning - controller class will not be loaded from
	 * standard controllers directory (`/App/Controllers`) but from different specified place
	 * by full controller class name.
	 *
	 * Example:
	 *  `"Products"								// placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"				// placed in /App/Controllers/Front/Business/Products.php`
	 *  `"\Anywhere\Else\Controllers\Products"	// placed in /Anywhere/Else/Controllers/Products.php`
	 * @var string
	 */
	protected $controller	= '';

	/**
	 * Action name to call in controller dispatching, in pascal case.
	 * Required, if there is no `action` param inside `\MvcCore\Route::$pattern`
	 * or inside `\MvcCore\Route::$match property`.
	 *
	 * If this property has value `"List"`, then public
	 * method in target controller has to be named as:
	 * `public function ListAction () {...}`.
	 *
	 * Example: `"List"`
	 * @var string
	 */
	protected $action		= '';

	/**
	 * Route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `array("name" => "default-name", "color" => "red",);`.
	 * @var array|\array[]
	 */
	protected $defaults		= [];

	/**
	 * TODO: neaktualni
	 * Array with param names and their custom regular expression
	 * matching rules. Not required, for all rewrited params there is used
	 * default matching rule from `\MvcCore\Route::$defaultPathConstraint`.
	 * It shoud be changed to any value. The value is `"[^/]*"` by default.
	 * It means "Any character(s) in any length, except next slash".
	 *
	 * Example:
	 *	`array(
	 *		"name"	=> "[^/]*",
	 *		"color"	=> "[a-z]*",
	 *	);`
	 * @var array|\array[]
	 */
	protected $constraints		= [];

	/**
	 * URL address params filters fo filter URL aprams in and out. Filters are 
	 * `callable`s always and only under keys `"in" | "out"`, accepting arguments: 
	 * `array $params, array $defaultParams, \MvcCore\IRequest $request`. 
	 * First argument is associative array with params from requested URL address 
	 * for `"in"` filter and associative array with params to build URL address 
	 * for `"out"` filter. Second param is always associative `array` with default 
	 * params and third argument is current request instance.
	 * `Callable` filter function must return `array` with filtered params.
	 * @var array
	 */
	protected $filters			= [];

	/**
	 * Http method to only match requests with this defined method.
	 * If `NULL`, request with any http method could be matched by this route.
	 * Value has to be upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @var string|NULL
	 */
	protected $method			= NULL;

	protected $redirect			= NULL;

	protected $absolute			= FALSE;

	/**
	 * TODO: neaktuální
	 * Array with `string` keys by all reverse pattern params and with values by 
	 * every param occurance start, length and required in reverse pattern string.
	 * This array is parsed automaticly by method `\MvcCore\Route::initMatch();` 
	 * if necessary or by method `\MvcCore\Route::initReverse();` after it's 
	 * necessary, to be able to complete URL address string in method and sub
	 * methods of `\MvcCore\Route::Url();`.
	 * Example: 
	 * // For pattern `/products-list/<name>/<color>`
	 * `["name" => [15, 6, TRUE], "color" => [22, 7, TRUE]];`
	 * @var array|NULL
	 */
	protected $reverseParams	= NULL;

	protected $matchedParams	= [];

	// TODO: napsat komentář
	protected $reverseSections	= NULL;
	
	/**
	 * // TODO: přepsat - asi nektuální
	 * Optional, param name, which has to be also inside `\MvcCore\Route::$pattern` or
	 * inside `\MvcCore\Route::$match` or inside `\MvcCore\Route::$reverse` pattern property
	 * as the last one. And after it's value, there could be only trailing slash or nothing
	 * (pattern end). This trailing slash param definition automaticly trims this last param
	 * value for right trailing slash when route is matched.
	 *
	 * This property is automaticly completed by method `\MvcCore\Route::initMatch()`,
	 * when there is parsed `\MvcCore\Route::$pattern` string into `\MvcCore\Route::$match` property
	 * or it is automaticly completed by method `\MvcCore\Route::initReverse()`, when
	 * there is parsed `\MvcCore\Route::$reverse` string into `\MvcCore\Route::$reverseParams`
	 * array to build url addresses.
	 *
	 * @var \string|NULL
	 */
	protected $lastPatternParam	= NULL;

	/**
	 * Array with route reverse pattern flags. First item is integer flag about
	 * absolute or relative reverse form and second item is about query string
	 * inside reverse string.
	 * @var \int[]
	 */
	protected $flags			= [
		/*
		\MvcCore\IRoute::FLAG_SHEME_NO, 
		\MvcCore\IRoute::FLAG_HOST_NO,
		\MvcCore\IRoute::FLAG_QUERY_NO,
		*/
	];

	protected $router			= NULL;

	/**
	 * Copied and cached value from router configuration property:
	 * `\MvcCore\Router::$trailingSlashBehaviour`.
	 * @var int|NULL
	 */
	private $_trailingSlashBehaviour = NULL;


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

	/**
	 * Get route pattern to match request url and to build url address.
	 *
	 * To define route by this form is the most comfortable way,
	 * but a way slower, because there is necessary every request
	 * to convert this value into `\MvcCore\Route::$match` and into
	 * `\MvcCore\Route::$reverse` properties correctly and you can
	 * specify those both properties manualy, if you are not too lazy.
	 *
	 * This match and reverse definition has to be in very basic form
	 * without regular expression escaping or advanced rules:
	 * - No regular expression border `#` characters, it will be
	 *   used internally in route parsing.
	 * - No start `^` or end `$` regular expression characters,
	 *   those characters will be added automaticly.
	 * - No escaping of regular expression characters:
	 *   `[](){}<>|=+*.!?-/`, those characters will be escaped
	 *   in route preparing process.
	 * - star char inside param name (`<color*>`) means greedy param
	 *   matching all to the end of address. It has to be the last one.
	 *
	 * Example: `"/products-list/<name>/<color*>"`.
	 * @return string|\string[]|NULL
	 */
	public function GetPattern () {
		return $this->pattern;
	}

	/**
	 * Set route pattern to match request url and to build url address.
	 *
	 * To define route by this form is the most comfortable way,
	 * but a way slower, because there is necessary every request
	 * to convert this value into `\MvcCore\Route::$match` and into
	 * `\MvcCore\Route::$reverse` properties correctly and you can
	 * specify those both properties manualy, if you are not too lazy.
	 *
	 * This match and reverse definition has to be in very basic form
	 * without regular expression escaping or advanced rules:
	 * - No regular expression border `#` characters, it will be
	 *   used internally in route parsing.
	 * - No start `^` or end `$` regular expression characters,
	 *   those characters will be added automaticly.
	 * - No escaping of regular expression characters:
	 *   `[](){}<>|=+*.!?-/`, those characters will be escaped
	 *   in route preparing process.
	 * - star char inside param name (`<color*>`) means greedy param
	 *   matching all to the end of address. It has to be the last one.
	 *
	 * Example: `"/products-list/<name>/<color*>"`.
	 * @param string|\string[] $pattern
	 * @return \MvcCore\Route
	 */
	public function & SetPattern ($pattern) {
		$this->pattern = $pattern;
		return $this;
	}

	/**
	 * Get route match pattern in raw form (to use it as it is) to match proper request.
	 * This property is always used to match request by `\MvcCore\Request::Path`
	 * by classic PHP regualar expression matching by `preg_match_all();`.
	 *
	 * Required together with `\MvcCore\Route::$reverse` property, if you
	 * have not configured `\MvcCore\Route::$pattern` property instead.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$match` and
	 * `\MvcCore\Route::$reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but it's the best
	 * speed solution, because there is no `\MvcCore\Route::$pattern` parsing and
	 * conversion into `\MvcCore\Route::$match` and `\MvcCore\Route::$reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)#"`
	 * @return string|\string[]|NULL
	 */
	public function GetMatch () {
		return $this->match;
	}

	/**
	 * Set route match pattern in raw form (to use it as it is) to match proper request.
	 * This property is always used to match request by `\MvcCore\Request::Path`
	 * by classic PHP regualar expression matching by `preg_match_all();`.
	 *
	 * Required together with `\MvcCore\Route::$reverse` property, if you
	 * have not configured `\MvcCore\Route::$pattern` property instead.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$match` and
	 * `\MvcCore\Route::$reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but it's the best
	 * speed solution, because there is no `\MvcCore\Route::$pattern` parsing and
	 * conversion into `\MvcCore\Route::$match` and `\MvcCore\Route::$reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)#"`
	 * @param string|\string[] $match
	 * @return \MvcCore\Route
	 */
	public function & SetMatch ($match) {
		$this->match = $match;
		return $this;
	}

	/**
	 * Get route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with `\MvcCore\Route::$match` property, if you
	 * have not configured `\MvcCore\Route::$pattern` property instead.
	 *
	 * This is only very simple string with replacement places (like `<name>` or
	 * `<page>`) for given values by `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$match` and
	 * `\MvcCore\Route::$reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but it's the best
	 * speed solution, because there is no `\MvcCore\Route::$pattern` parsing and
	 * conversion into `\MvcCore\Route::$match` and `\MvcCore\Route::$reverse` properties.
	 *
	 * Example: `"/products-list/<name>/<color>"`
	 * @return string|\string[]|NULL
	 */
	public function GetReverse () {
		return $this->reverse;
	}

	/**
	 * Set route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with `\MvcCore\Route::$match` property, if you
	 * have not configured `\MvcCore\Route::$pattern` property instead.
	 *
	 * This is only very simple string with replacement places (like `<name>` or
	 * `<page>`) for given values by `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$match` and
	 * `\MvcCore\Route::$reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but it's the best
	 * speed solution, because there is no `\MvcCore\Route::$pattern` parsing and
	 * conversion into `\MvcCore\Route::$match` and `\MvcCore\Route::$reverse` properties.
	 *
	 * Example: `"/products-list/<name>/<color>"`
	 * @param string|\string[] $reverse
	 * @return \MvcCore\Route
	 */
	public function & SetReverse ($reverse) {
		$this->reverse = $reverse;
		return $this;
	}

	/**
	 * Get route name. It's your custom keyword/term
	 * or pascal case combination of controller and action
	 * describing `"Controller:Action"` target to be dispatched.
	 *
	 * By this name there is selected proper route object to
	 * complete url string by given params in router method:
	 * `\MvcCore\Router:Url($name, $params);`.
	 *
	 * Example: `"products_list" | "Products:Gallery"`
	 * @return string
	 */
	public function GetName () {
		return $this->name;
	}

	/**
	 * Set route name. Not required. It's your custom keyword/term
	 * or pascal case combination of controller and action
	 * describing `"Controller:Action"` target to be dispatched.
	 *
	 * By this name there is selected proper route object to
	 * complete url string by given params in router method:
	 * `\MvcCore\Router:Url($name, $params);`.
	 *
	 * Example: `"products_list" | "Products:Gallery"`
	 * @param string $name
	 * @return \MvcCore\Route
	 */
	public function & SetName ($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * Get controller name to dispatch, in pascal case. Required only if
	 * there is no `controller` param inside `\MvcCore\Route::$pattern`
	 * or inside `\MvcCore\Route::$match properties as url params`.
	 *
	 * It should contain controller class namespaces defined in standard PHP notation.
	 * If there is backslash at the beginning - controller class will not be loaded from
	 * standard controllers directory (`/App/Controllers`) but from different specified place
	 * by full controller class name.
	 *
	 * Example:
	 *  `"Products"							 // placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"			  // placed in /App/Controllers/Front/Business/Products.php`
	 *  `"\Anywhere\Else\Controllers\Products"  // placed in /Anywhere/Else/Controllers/Products.php`
	 * @return string
	 */
	public function GetController () {
		return $this->controller;
	}

	/**
	 * Set controller name to dispatch, in pascal case. Required only if
	 * there is no `controller` param inside `\MvcCore\Route::$pattern`
	 * or inside `\MvcCore\Route::$match properties as url params`.
	 *
	 * It should contain controller class namespaces defined in standard PHP notation.
	 * If there is backslash at the beginning - controller class will not be loaded from
	 * standard controllers directory (`/App/Controllers`) but from different specified place
	 * by full controller class name.
	 *
	 * Example:
	 *  `"Products"							 // placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"			  // placed in /App/Controllers/Front/Business/Products.php`
	 *  `"\Anywhere\Else\Controllers\Products"  // placed in /Anywhere/Else/Controllers/Products.php`
	 * @param string $controller
	 * @return \MvcCore\Route
	 */
	public function & SetController ($controller) {
		$this->controller = $controller;
		return $this;
	}

	/**
	 * Get action name to call it in controller dispatch processing, in pascal case.
	 * Required, if there is no `action` param inside `\MvcCore\Route::$pattern`
	 * or inside `\MvcCore\Route::$match properties as url params`.
	 *
	 * If this property has value `"List"`, then public
	 * method in target controller has to be named as:
	 * `public function ListAction () {...}`.
	 *
	 * Example: `"List"`
	 * @return string
	 */
	public function GetAction () {
		return $this->action;
	}

	/**
	 * Set action name to call it in controller dispatch processing, in pascal case.
	 * Required, if there is no `action` param inside `\MvcCore\Route::$pattern`
	 * or inside `\MvcCore\Route::$match properties as url params`.
	 *
	 * If this property has value `"List"`, then public
	 * method in target controller has to be named as:
	 * `public function ListAction () {...}`.
	 *
	 * Example: `"List"`
	 * @param string $action
	 * @return \MvcCore\Route
	 */
	public function & SetAction ($action) {
		$this->action = $action;
		return $this;
	}

	/**
	 * Get target controller name and controller action name
	 * together in one setter, in pascal case, separated by colon.
	 * There are also controller namespace definition posibilities as
	 * in `\MvcCore\Route::GetController();` getter method.
	 *
	 * Example: `"Products:List"`
	 * @return string
	 */
	public function GetControllerAction () {
		return $this->controller . ':' . $this->action;
	}

	/**
	 * Set target controller name and controller action name
	 * together in one setter, in pascal case, separated by colon.
	 * There are also controller namespace definition posibilities as
	 * in `\MvcCore\Route::SetController();` setter method.
	 *
	 * Example: `"Products:List"`
	 * @return \MvcCore\Route
	 */
	public function & SetControllerAction ($controllerAction) {
		list($ctrl, $action) = explode(':', $controllerAction);
		if ($ctrl) $this->controller = $ctrl;
		if ($action) $this->action = $action;
		return $this;
	}

	/**
	 * Get route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example:
	 *  `array(
	 *	  "name"  => "default-name",
	 *	  "color" => "red"
	 *  );`
	 * @return array|\array[]
	 */
	public function & GetDefaults () {
		return $this->defaults;
	}

	/**
	 * Set route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example:
	 *  `array(
	 *	  "name"  => "default-name",
	 *	  "color" => "red"
	 *  );`.
	 * @param array|\array[] $defaults
	 * @return \MvcCore\Route
	 */
	public function & SetDefaults ($defaults = []) {
		$this->defaults = & $defaults;
		return $this;
	}

	/**
	 * TODO: neaktualni
	 * Get array with param names and their custom regular expression
	 * matching rules. Not required, for all rewrited params there is used
	 * default matching rule from `\MvcCore\Route::$defaultPathConstraint`.
	 * It shoud be changed to any value. The value is `"[^/]*"` by default.
	 * It means "Any character(s) in any length, except next slash".
	 *
	 * Example:
	 *	`array(
	 *		"name"	=> "[^/]*",
	 *		"color"	=> "[a-z]*",
	 *	);`
	 * @return array|\array[]
	 */
	public function GetConstraints () {
		return $this->constraints;
	}

	/**
	 * TODO: neaktualni
	 * Set array with param names and their custom regular expression
	 * matching rules. Not required, for all rewrited params there is used
	 * default matching rule from `\MvcCore\Route::$defaultPathConstraint`.
	 * It shoud be changed to any value. The value is `"[^/]*"` by default.
	 * It means "Any character(s) in any length, except next slash".
	 *
	 * Example:
	 *	`array(
	 *		"name"	=> "[^/]*",
	 *		"color"	=> "[a-z]*",
	 *	);`
	 * @param array|\array[] $constraints
	 * @return \MvcCore\Route
	 */
	public function & SetConstraints ($constraints = []) {
		$this->constraints = & $constraints;
		foreach ($constraints as $key => $value)
			if (!isset($this->defaults[$key]))
				$this->defaults[$key] = NULL;
		return $this;
	}

	/**
	 * Get URL address params filters to filter URL params in and out. Filters are 
	 * `callable`s always and only under keys `"in" | "out"`, accepting arguments: 
	 * `array $params, array $defaultParams, \MvcCore\IRequest $request`. 
	 * First argument is associative array with params from requested URL address 
	 * for `"in"` filter and associative array with params to build URL address 
	 * for `"out"` filter. Second param is always associative `array` with default 
	 * params and third argument is current request instance.
	 * `Callable` filter function must return `array` with filtered params.
	 * @return array
	 */
	public function & GetFilters () {
		$filters = [];
		foreach ($this->filters as $direction => $handler) 
			$filters[$direction] = $handler[1];
		return $filters;
	}

	/**
	 * Set URL address params filters to filter URL params in and out. Filters are 
	 * `callable`s always and only under keys `"in" | "out"`, accepting arguments: 
	 * `array $params, array $defaultParams, \MvcCore\IRequest $request`. 
	 * First argument is associative array with params from requested URL address 
	 * for `"in"` filter and associative array with params to build URL address 
	 * for `"out"` filter. Second param is always associative` array` with default 
	 * params and third argument is current request instance.
	 * `Callable` filter function must return `array` with filtered params.
	 * @param array $filters 
	 * @return \MvcCore\Route
	 */
	public function & SetFilters (array $filters = []) {
		// there is possible to call any `callable` as closure function in variable
		// except forms like `'ClassName::methodName'` and `['childClassName', 'parent::methodName']`
		// and `[$childInstance, 'parent::methodName']`.
		foreach ($filters as $direction => $handler) 
			$this->SetFilter($handler, $direction);
		return $this;
	}

	/**
	 * Get URL address params filter to filter URL params in and out. Filter is 
	 * `callable` always and only under `$direction` keys `"in" | "out"`, accepting arguments: 
	 * `array $params, array $defaultParams, \MvcCore\IRequest $request`. 
	 * First argument is associative array with params from requested URL address 
	 * for `"in"` filter and associative array with params to build URL address 
	 * for `"out"` filter. Second param is always associative `array` with default 
	 * params and third argument is current request instance.
	 * `Callable` filter function must return `array` with filtered params.
	 * @param string $direction
	 * @return array
	 */
	public function GetFilter ($direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		return isset($this->filters[$direction])
			? $this->filters[$direction]
			: NULL;
	}

	/**
	 * Set URL address params filter to filter URL params in and out. 
	 * Filter is `callable` accepting arguments: 
	 * `array $params, array $defaultParams, \MvcCore\IRequest $request`. 
	 * First argument is associative array with params from requested URL address 
	 * for `"in"` filter and associative array with params to build URL address 
	 * for `"out"` filter. Second param is always associative` array` with default 
	 * params and third argument is current request instance.
	 * `Callable` filter function must return `array` with filtered params.
	 * @param callable $handler 
	 * @param string $direction
	 * @return \MvcCore\Route
	 */
	public function & SetFilter ($handler, $direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		// there is possible to call any `callable` as closure function in variable
		// except forms like `'ClassName::methodName'` and `['childClassName', 'parent::methodName']`
		// and `[$childInstance, 'parent::methodName']`.
		$closureCalling = (
			(is_string($handler) && strpos($handler, '::') !== FALSE) ||
			(is_array($handler) && strpos($handler[1], '::') !== FALSE)
		) ? FALSE : TRUE;
		$this->filters[$direction] = [$closureCalling, $handler];
		return $this;
	}

	/**
	 * Get http method to only match requests with this defined method.
	 * If `NULL` (by default), request with any http method could be matched by this route.
	 * Value is automaticly in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @return string|NULL
	 */
	public function GetMethod () {
		return $this->method;
	}

	/**
	 * Set http method to only match requests with this defined method.
	 * If `NULL` (by default), request with any http method could be matched by this route.
	 * Given value is automaticly converted to upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @param string|NULL $method
	 * @return \MvcCore\Route
	 */
	public function & SetMethod ($method = NULL) {
		$this->method = strtoupper($method);
		return $this;
	}
	
	/**
	 * TODO: dopsat
	 * @return string|NULL
	 */
	public function GetRedirect () {
		return $this->redirect;
	}

	/**
	 * TODO: dopsat
	 * @param string|NULL $redirectRouteName 
	 * @return \MvcCore\Route
	 */
	public function & SetRedirect ($redirectRouteName = NULL) {
		$this->redirect = $redirectRouteName;
		return $this;
	}

	/**
	 * Return only reverse params names as `string`s array.
	 * Example: `["name", "color"];`
	 * @return \string[]|NULL
	 */
	public function GetReverseParams () {
		return $this->reverseParams !== NULL 
			? array_keys($this->reverseParams)
			: [];
	}

	/**
	 * Set manualy matched params from rewrite route for current route.
	 * Use this method only on currently matched route!
	 * @param array $matchedParams
	 * @return \MvcCore\Route
	 */
	public function & SetMatchedParams ($matchedParams = []) {
		$this->matchedParams = & $matchedParams;
		return $this;
	}

	/**
	 * Return request matched params by current route.
	 * @return array|NULL
	 */
	public function & GetMatchedParams () {
		return $this->matchedParams;
	}
	
	/**
	 * TODO:
	 * @param \MvcCore\Router|\MvcCore\IRouter $router 
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & SetRouter (\MvcCore\IRouter & $router) {
		$this->router = & $router;
		return $this;
	}

	/**
	 * Return `TRUE` if route reverse pattern contains 
	 * domain part with two slases at the beginning
	 * or if route is defined with `absolute` boolean flag 
	 * by advanced configuration in constructor.
	 * @return bool
	 */
	public function GetAbsolute () {
		return boolval($this->flags[0]) || $this->absolute;
	}

	public function & SetAbsolute ($absolute = TRUE) {
		$this->absolute = $absolute;
		return $this;
	}

	/**
	 * Return array of matched params, with matched controller and action names,
	 * if route matches request always `\MvcCore\Request::$path` property by `preg_match_all()`.
	 *
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's submethods.
	 *
	 * @param \MvcCore\Request $request Request object instance.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function & Matches (\MvcCore\IRequest & $request) {
		$matchedParams = [];
		$pattern = & $this->matchesGetPattern();
		$subject = $this->matchesGetSubject($request);
		preg_match_all($pattern, $subject, $matchedValues);
		if (isset($matchedValues[0]) && count($matchedValues[0]) > 0) {
			$matchedParams = $this->matchesParseRewriteParams($matchedValues, $this->GetDefaults());
			if (isset($matchedParams[$this->lastPatternParam])) 
				$matchedParams[$this->lastPatternParam] = rtrim(
				$matchedParams[$this->lastPatternParam], '/'
			);
		}
		return $matchedParams;
	}

	protected function & matchesGetPattern () {
		if ($this->match === NULL) {
			$this->initMatchAndReverse();
		} else {
			$this->initReverse();
		}
		return $this->match;
	}

	protected function matchesGetSubject (\MvcCore\IRequest & $request) {
		static $prefixes = NULL;
		if ($prefixes === NULL) $prefixes = [
			static::FLAG_SCHEME_NO		=> '',
			static::FLAG_SCHEME_ANY		=> '//',
			static::FLAG_SCHEME_HTTP	=> 'http://',
			static::FLAG_SCHEME_HTTPS	=> 'https://',
		];
		$schemeFlag = $this->flags[0];
		$hostFlag = $this->flags[1];
		$basePathDefined = FALSE;
		$basePath = '';
		if ($hostFlag >= static::FLAG_HOST_BASEPATH /* 10 */) {
			$hostFlag -= static::FLAG_HOST_BASEPATH;
			$basePath = static::PLACEHOLDER_BASEPATH;
			$basePathDefined = TRUE;
		}
		if ($schemeFlag) {
			$hostPart = '';
			if ($hostFlag == static::FLAG_HOST_HOST /* 1 */) {
				$hostPart = static::PLACEHOLDER_HOST;
			} else if ($hostFlag == static::FLAG_HOST_DOMAIN /* 2 */) {
				$hostPart = $request->GetThirdLevelDomain() . '.' . static::PLACEHOLDER_DOMAIN;
			} else if ($hostFlag == static::FLAG_HOST_TLD /* 3 */) {
				$hostPart = $request->GetThirdLevelDomain() 
					. '.' . $request->GetSecondLevelDomain()
					. '.' . static::PLACEHOLDER_TLD;
			} else if ($hostFlag == static::FLAG_HOST_SLD /* 4 */) {
				$hostPart = $request->GetThirdLevelDomain() 
					. '.' . static::PLACEHOLDER_SLD
					. '.' . $request->GetTopLevelDomain();
			} else if ($hostFlag == static::FLAG_HOST_TLD + static::FLAG_HOST_SLD /* 7 */) {
				$hostPart = $request->GetThirdLevelDomain() 
					. '.' . static::PLACEHOLDER_SLD
					. '.' . static::PLACEHOLDER_TLD;
			}
			if (!$basePathDefined)
				$basePath = $request->GetBasePath();
			$subject = $prefixes[$schemeFlag] . $hostPart . $basePath . $request->GetPath(TRUE);
		} else {
			$subject = ($basePathDefined ? $basePath : '') . $request->GetPath(TRUE);
		}
		if ($this->flags[2]) 
			$subject .= $request->GetQuery(TRUE, TRUE);
		return $subject;
	}

	protected function & matchesParseRewriteParams (& $matchedValues, & $defaults) {
		$controllerName = $this->controller ?: '';
		$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
		$matchedParams = [
			'controller'	=>	$toolClass::GetDashedFromPascalCase(str_replace(['_', '\\'], '/', $controllerName)),
			'action'		=>	$toolClass::GetDashedFromPascalCase($this->action ?: ''),
		];
		array_shift($matchedValues); // first item is always matched whole `$request->GetPath()` string.
		foreach ($matchedValues as $key => $matchedValueArr) {
			if (is_numeric($key)) continue;
			$matchedValue = (string) current($matchedValueArr);
			if (!isset($defaults[$key])) 
				$defaults[$key] = NULL;
			if (mb_strlen($matchedValue) === 0)
				$matchedValue = $defaults[$key];
			$matchedParams[$key] = $matchedValue;
		}
		return $matchedParams;
	}

	/**
	 * Filter given `array $params` by configured `"in" | "out"` filter `callable`.
	 * This function return `array` with first item as `bool` about successfull
	 * filter processing in `try/catch` and second item as filtered params `array`.
	 * @param array $params 
	 * @param array $defaultParams
	 * @param string $direction 
	 * @return array
	 */
	public function Filter (array & $params = [], array & $defaultParams = [], $direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		if (!$this->filters || !isset($this->filters[$direction])) 
			return [TRUE, $params];
		list($closureCalling, $handler) = $this->filters[$direction];
		try {
			$req = & \MvcCore\Application::GetInstance()->GetRequest();
			if ($closureCalling) {
				$newParams = $handler($params, $defaultParams, $req);
			} else {
				$newParams = call_user_func_array($handler, [$params, $defaultParams, $req]);
			}
			$success = TRUE;
		} catch (\RuntimeException $e) {
			\MvcCore\Debug::Log($e, \MvcCore\IDebug::ERROR);
			$success = FALSE;
			$newParams = $params;
		}
		return [$success, $newParams];
	}

	/**
	 * Complete route url by given params array and route
	 * internal reverse replacements pattern string.
	 * If there are more given params in first argument
	 * than count of replacement places in reverse pattern,
	 * then create url with query string params after reverse
	 * pattern, containing that extra record(s) value(s).
	 *
	 * Example:
	 *	Input (`$params`):
	 *		`array(
	 *			"name"		=> "cool-product-name",
	 *			"color"		=> "blue",
	 *			"variants"	=> array("L", "XL"),
	 *		);`
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`"/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param \MvcCore\Request $request Currently requested request object.
	 * @param array $params URL params from application point completed by developer.
	 * @param array $requestedUrlParams Requested url route prams nad query string params without escaped HTML special chars: `< > & " ' &`.
	 * @param string $queryStringParamsSepatator Query params separator, `&` by default. Always automaticly completed by router instance.
	 * @return \string[] Result URL addres in two parts - domain part with base path and path part with query string.
	 */
	public function Url (\MvcCore\IRequest & $request, array & $params = [], array & $requestedUrlParams = [], $queryStringParamsSepatator = '&') {
		// check reverse initialization
		if ($this->reverseParams === NULL) $this->initReverse();
		// complete and filter all params to build reverse pattern
		if (count($this->reverseParams) === 0) {
			$allParamsClone = array_merge([], $params);
		} else {// complete params with necessary values to build reverse pattern (and than query string)
			$emptyReverseParams = array_fill_keys(array_keys($this->reverseParams), '');
			$allMergedParams = array_merge($this->defaults, $requestedUrlParams, $params);
			$allParamsClone = array_merge(
				$emptyReverseParams, array_intersect_key($allMergedParams, $emptyReverseParams), $params
			);
		}
		// filter params
		list(,$filteredParams) = $this->Filter($allParamsClone, $requestedUrlParams, \MvcCore\IRoute::CONFIG_FILTER_OUT);
		// split params into domain params array and into path and query params array
		$domainParams = $this->urlGetAndRemoveDomainParams($filteredParams);
		// build reverse pattern
		$result = $this->urlComposeByReverseSectionsAndParams(
			$this->reverse, 
			$this->reverseSections, 
			$this->reverseParams, 
			$filteredParams, 
			$this->defaults
		);
		// add all remaining params to query string
		if ($filteredParams) {
			// `http_build_query()` automaticly converts all XSS chars to entities (`< > & " ' &`):
			$result .= (mb_strpos($result, '?') !== FALSE ? $queryStringParamsSepatator : '?')
				. str_replace('%2F', '/', http_build_query($filteredParams, '', $queryStringParamsSepatator, PHP_QUERY_RFC3986));
		}
		return $this->urlSplitResultToBaseAndPathWithQuery($request, $result, $domainParams);
	}

	protected function urlComposeByReverseSectionsAndParams (& $reverse, & $reverseSections, & $reverseParams, & $params, & $defaults) {
		$sections = [];
		$paramIndex = 0;
		$reverseParamsKeys = array_keys($reverseParams);
		$paramsCount = count($reverseParamsKeys);
		$anyParams = $paramsCount > 0;
		foreach ($reverseSections as $sectionIndex => & $section) {
			$fixed = $section->fixed;
			$sectionResult = '';
			if ($anyParams) {
				$sectionOffset = $section->start;
				$sectionParamsCount = 0;
				$defaultValuesCount = 0;
				while ($paramIndex < $paramsCount) {
					$paramKey = $reverseParamsKeys[$paramIndex];
					$param = $reverseParams[$paramKey];
					if ($param->sectionIndex !== $sectionIndex) break;
					$sectionParamsCount++;
					$paramStart = $param->reverseStart;
					if ($sectionOffset < $paramStart)
						$sectionResult .= mb_substr($reverse, $sectionOffset, $paramStart - $sectionOffset);
					$paramName = $param->name;
					$paramValue = (string) $params[$paramName];
					if (isset($defaults[$paramName]) && $paramValue == (string) $defaults[$paramName]) 
						$defaultValuesCount++;
					$sectionResult .= htmlspecialchars($paramValue, ENT_QUOTES);
					unset($params[$paramName]);
					$paramIndex += 1;
					$sectionOffset = $param->reverseEnd;
				}
				$sectionEnd = $section->end;
				if (!$fixed && $sectionParamsCount === $defaultValuesCount) {
					$sectionResult = '';
				} else if ($sectionOffset < $sectionEnd) {
					$sectionResult .= mb_substr($reverse, $sectionOffset, $sectionEnd - $sectionOffset);
				}
			} else if ($fixed) {
				$sectionResult = mb_substr($reverse, $section->start, $section->length);
			}
			$sections[] = $sectionResult;
		}
		$result = implode('', $sections);
		$result = & $this->urlCorrectTrailingSlashBehaviour($result);
		return $result;
	}

	/**
	 * Return request base path and completed result URL address by route, if 
	 * route instance is not defined as absolute pattern/match/reverse. Otherwise,
	 * if route IS defined as absolute, split completed result URL address by 
	 * route into parts with base part (domain part and base path) and into part
	 * with request path and query string.
	 * @param string $resultUrl 
	 * @return \string[]
	 */
	protected function urlSplitResultToBaseAndPathWithQuery (\MvcCore\IRequest & $request, $resultUrl, & $domainParams) {
		$domainParamsFlag = $this->flags[1];
		$basePathInReverse = FALSE;
		if ($domainParamsFlag >= static::FLAG_HOST_BASEPATH) {
			$basePathInReverse = TRUE;
			$domainParamsFlag -= static::FLAG_HOST_BASEPATH;
		}
		if ($this->flags[0]) {
			// route is defined as absolute with possible `%domain%` and other params
			// process possible replacements in reverse result - `%host%`, `%domain%`, `%tld%` and `%sld%`
			$this->urlReplaceDomainReverseParams($request, $resultUrl, $domainParams, $domainParamsFlag);
			// try to find url position after domain part and after base path part
			if ($basePathInReverse) {
				return $this->urlSplitResultByReverseBasePath($request, $resultUrl, $domainParams);
			} else {
				return $this->urlSplitResultByRequestedBasePath($request, $resultUrl);
			}
		} else {
			// route is not defined as absolute, there could be only flag 
			// in domain params array to complete absolute url by developer
			// and there could be also `basePath` param defined.
			return $this->urlSplitResultByAbsoluteAndBasePath($request, $resultUrl, $domainParams, $domainParamsFlag);
		}
	}

	protected function urlReplaceDomainReverseParams (\MvcCore\IRequest & $request, & $resultUrl, & $domainParams, $domainParamsFlag) {
		$replacements = [];
		$values = [];
		$router = & $this->router;
		if ($domainParamsFlag == static::FLAG_HOST_HOST) {
			$hostParamName = $router::URL_PARAM_HOST;
			$replacements[] = static::PLACEHOLDER_HOST;
			$values[] = isset($domainParams[$hostParamName])
				? $domainParams[$hostParamName]
				: $request->GetHost();
		} else if ($domainParamsFlag == static::FLAG_HOST_DOMAIN) {
			$domainParamName = $router::URL_PARAM_DOMAIN;
			$replacements[] = static::PLACEHOLDER_DOMAIN;
			$values[] = isset($domainParams[$domainParamName])
				? $domainParams[$domainParamName]
				: $request->GetSecondLevelDomain() . '.' . $request->GetTopLevelDomain();
		} else {
			if ($domainParamsFlag == static::FLAG_HOST_TLD) {
				$tldParamName = $router::URL_PARAM_TLD;
				$replacements[] = static::PLACEHOLDER_TLD;
				$values[] = isset($domainParams[$tldParamName])
					? $domainParams[$tldParamName]
					: $request->GetTopLevelDomain();
			} else if ($domainParamsFlag == static::FLAG_HOST_SLD) {
				$sldParamName = $router::URL_PARAM_SLD;
				$replacements[] = static::PLACEHOLDER_SLD;
				$values[] = isset($domainParams[$sldParamName])
					? $domainParams[$sldParamName]
					: $request->GetSecondLevelDomain();
			} else if ($domainParamsFlag == static::FLAG_HOST_TLD + static::FLAG_HOST_SLD) {
				$tldParamName = $router::URL_PARAM_TLD;
				$sldParamName = $router::URL_PARAM_SLD;
				$replacements[] = static::PLACEHOLDER_TLD;
				$replacements[] = static::PLACEHOLDER_SLD;
				$values[] = isset($domainParams[$tldParamName])
					? $domainParams[$tldParamName]
					: $request->GetTopLevelDomain();
				$values[] = isset($domainParams[$sldParamName])
					? $domainParams[$sldParamName]
					: $request->GetSecondLevelDomain();
			}
		}
		$resultUrl = str_replace($replacements, $values, $resultUrl);
	}

	protected function urlSplitResultByReverseBasePath (\MvcCore\IRequest & $request, $resultUrl, & $domainParams) {
		$doubleSlashPos = mb_strpos($resultUrl, '//');
		$doubleSlashPos = $doubleSlashPos === FALSE
			? 0
			: $doubleSlashPos + 2;
		$router = & $this->router;
		$basePathPlaceHolderPos = mb_strpos($resultUrl, static::PLACEHOLDER_BASEPATH, $doubleSlashPos);
		if ($basePathPlaceHolderPos === FALSE) {
			return $this->urlSplitResultByRequestedBasePath ($request, $resultUrl);
		} else {
			$pathPart = mb_substr($resultUrl, $basePathPlaceHolderPos + mb_strlen(static::PLACEHOLDER_BASEPATH));
			$basePart = mb_substr($resultUrl, 0, $basePathPlaceHolderPos);
			$basePathParamName = $router::URL_PARAM_BASEPATH;
			$basePart .= isset($domainParams[$basePathParamName])
				? $domainParams[$basePathParamName]
				: $request->GetBasePath();
		}
		if ($this->flags[0] === static::FLAG_SCHEME_ANY)
			$basePart = $request->GetProtocol() . $basePart;
		return [$basePart, $pathPart];
	}

	protected function urlSplitResultByRequestedBasePath (\MvcCore\IRequest & $request, $resultUrl) {
		$doubleSlashPos = mb_strpos($resultUrl, '//');
		$doubleSlashPos = $doubleSlashPos === FALSE
			? 0
			: $doubleSlashPos + 2;
		$nextSlashPos = mb_strpos($resultUrl, '/', $doubleSlashPos);
		if ($nextSlashPos === FALSE) {
			$queryStringPos = mb_strpos($resultUrl, '?', $doubleSlashPos);
			$baseUrlPartEndPos = $queryStringPos === FALSE 
				? mb_strlen($resultUrl) 
				: $queryStringPos;
		} else {
			$baseUrlPartEndPos = $nextSlashPos;
		}
		$requestedBasePath = $request->GetBasePath();
		$basePathLength = mb_strlen($requestedBasePath);
		if ($basePathLength > 0) {
			$basePathPos = mb_strpos($resultUrl, $requestedBasePath, $baseUrlPartEndPos);
			if ($basePathPos === $baseUrlPartEndPos) 
				$baseUrlPartEndPos += $basePathLength;
		}
		$basePart = mb_substr($resultUrl, 0, $baseUrlPartEndPos);
		if ($this->flags[0] === static::FLAG_SCHEME_ANY)
			$basePart = $request->GetProtocol() . $basePart;
		return [
			$basePart,
			mb_substr($resultUrl, $baseUrlPartEndPos)
		];
	}

	protected function urlSplitResultByAbsoluteAndBasePath (\MvcCore\IRequest & $request, $resultUrl, & $domainParams, $basePathInReverse) {
		$router = & $this->router;
		$basePathParamName = $router::URL_PARAM_BASEPATH;
		$basePart = isset($domainParams[$basePathParamName])
			? isset($domainParams[$basePathParamName])
			: $request->GetBasePath();
		// if there is `%basePath%` placeholder in reverse, put before `$basePart`
		// what is before matched `%basePath%` placeholder and edit `$resultUrl`
		// to use only part after `%basePath%` placeholder:
		if ($basePathInReverse) {
			$placeHolderBasePath = static::PLACEHOLDER_BASEPATH;
			$basePathPlaceHolderPos = mb_strpos($resultUrl, $placeHolderBasePath);
			if ($basePathPlaceHolderPos !== FALSE) {
				$basePart = mb_substr($resultUrl, 0, $basePathPlaceHolderPos) . $basePart;
				$resultUrl = mb_substr($resultUrl, $basePathPlaceHolderPos + mb_strlen($placeHolderBasePath));
			}
		}
		$absoluteParamName = $router::URL_PARAM_ABSOLUTE;
		if (
			$this->absolute || (
				isset($domainParams[$absoluteParamName]) && $domainParams[$absoluteParamName]
			) 
		)
			$basePart = $request->GetDomainUrl() . $basePart;
		return [$basePart, $resultUrl];
	}

	/**
	 * Get `TRUE` if given `array $params` contains `boolean` record under 
	 * `"absolute"` array key and if the record is `TRUE`. Unset the absolute 
	 * flag from `$params` in any case.
	 * @param array $params 
	 * @return boolean
	 */
	protected function urlGetAndRemoveDomainParams (array & $params = []) {
		static $domainParams = [];
		$absolute = FALSE;
		$router = & $this->router;
		$absoluteParamName = $router::URL_PARAM_ABSOLUTE;
		$result = [];
		if (!$domainParams) {
			$domainParams = [
				$router::URL_PARAM_HOST,
				$router::URL_PARAM_DOMAIN,
				$router::URL_PARAM_TLD,
				$router::URL_PARAM_SLD,
				$router::URL_PARAM_BASEPATH,
			];
		}
		foreach ($domainParams as $domainParam) {
			if (isset($params[$domainParam])) {
				$absolute = TRUE;
				$result[$domainParam] = $params[$domainParam];
				unset($params[$domainParam]);
			}
		}
		if ($absolute) {
			$result[$absoluteParamName] = TRUE;
		} else if (isset($params[$absoluteParamName])) {
			$result[$absoluteParamName] = (bool) $params[$absoluteParamName];
			unset($params[$absoluteParamName]);
		}
		return $result;
	}

	/**
	 * Render all instance properties values into string.
	 * @return string
	 */
	public function __toString () {
		$type = new \ReflectionClass($this);
		/** @var $props \ReflectionProperty[] */
		$allProps = $type->getProperties(
			\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE
		);
		$result = [];
		/** @var $prop \ReflectionProperty */
		foreach ($allProps as & $prop) {
			if ($prop->isStatic()) continue;
			if ($prop->isPrivate()) $prop->setAccessible(TRUE);
			$value = NULL;
			try {
				$value = $prop->getValue($this);
			} catch (\Exception $e) {};
			$result[] = '"' . $prop->getName() . '":"' . ($value === NULL ? 'NULL' : var_export($value)) . '"';
		}
		return '{'.implode(', ', $result) . '}';
	}

	/**
	 * TODO:
	 * Initialize all possible protected values (`match`, `reverse` etc...)
	 * This method is not recomanded to use in production mode, it's
	 * designed mostly for development purposes, to see what could be inside route.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & InitAll () {
		if ($this->match === NULL && $this->reverse === NULL) {
			$this->initMatchAndReverse();
		} else if ($this->match !== NULL && ($this->reverseParams === NULL || $this->lastPatternParam === NULL)) {
			$this->initReverse();
		}
		return $this;
	}

	/**
	 * TODO: asi neaktuální
	 * Initialize `\MvcCore\Router::$Match` property (and `\MvcCore\Router::$lastPatternParam`
	 * property) from `\MvcCore\Router::$Pattern`, optionaly initialize
	 * `\MvcCore\Router::$Reverse` property if there is nothing inside.
	 * - Add backslashes for all special regex chars excluding `<` and `>` chars.
	 * - Parse all `<param>` occurrances in pattern into statistics array `$patternParams`.
	 * - Complete from the statistic array the match property and if there no reverse property,
	 *   complete also reverse property.
	 * This method is usually called in core request routing process from
	 * `\MvcCore\Router::Matches();` method.
	 * @return void
	 */
	protected function initMatchAndReverse () {
		if ($this->pattern === NULL)
			$this->throwExceptionIfNoPattern();

		$this->lastPatternParam = NULL;
		$match = addcslashes($this->pattern, "#(){}-?!=^$.+|:*\\");
		$reverse = $this->reverse !== NULL
			? $this->reverse
			: $this->pattern;

		list($this->reverseSections, $matchSections) = $this->initSectionsInfoForMatchAndReverse(
			$reverse, $match
		);
		$this->reverse = & $reverse;
		$this->reverseParams = $this->initReverseParams(
			$reverse, $this->reverseSections, $this->constraints, $match
		);
		//$this->initFlagsByPatternOrReverse($reverse);
		$this->match = $this->initMatchComposeRegex(
			$match, $matchSections, $this->reverseParams, $this->constraints
		);
	}

	protected function initSectionsInfoForMatchAndReverse (& $match, & $reverse) {
		$matchInfo = [];
		$reverseInfo = [];
		$reverseIndex = 0;
		$matchIndex = 0;
		$reverseLength = mb_strlen($reverse);
		$matchLength = mb_strlen($match);
		$matchOpenPos = FALSE;
		$matchClosePos = FALSE;
		while ($reverseIndex < $reverseLength ) {
			$reverseOpenPos = mb_strpos($reverse, '[', $reverseIndex);
			$reverseClosePos = FALSE;
			if ($reverseOpenPos !== FALSE) {
				$reverseClosePos = mb_strpos($reverse, ']', $reverseOpenPos);
				$matchOpenPos = mb_strpos($match, '[', $matchIndex);
				$matchClosePos = mb_strpos($match, ']', $matchOpenPos);
			}
			if ($reverseClosePos === FALSE) {
				$reverseInfo[] = (object) ['fixed' => TRUE, 'start' => $reverseIndex, 'end' => $reverseLength, 'length' => $reverseLength - $reverseIndex];
				$matchInfo[] = (object) ['fixed' => TRUE, 'start' => $matchIndex, 'end' => $matchLength, 'length' => $matchLength - $matchIndex];
				break;
			} else {
				if ($reverseIndex < $reverseOpenPos) {
					$reverseInfo[] = (object) ['fixed' => TRUE, 'start' => $reverseIndex, 'end' => $reverseOpenPos, 'length' => $reverseOpenPos - $reverseIndex];
					$matchInfo[] = (object) ['fixed' => TRUE, 'start' => $matchIndex, 'end' => $matchOpenPos, 'length' => $matchOpenPos - $matchIndex];
				}
				$reverseOpenPosPlusOne = $reverseOpenPos + 1;
				$reverseLocalLength = $reverseClosePos - $reverseOpenPosPlusOne;
				$reverse = mb_substr($reverse, 0, $reverseOpenPos) 
					. mb_substr($reverse, $reverseOpenPosPlusOne, $reverseLocalLength) 
					. mb_substr($reverse, $reverseClosePos + 1);
				$reverseLength -= 2;
				$reverseClosePos -= 1;
				$reverseInfo[] = (object) ['fixed' => FALSE, 'start' => $reverseOpenPos, 'end' => $reverseClosePos, 'length' => $reverseLocalLength];
				$matchOpenPosPlusOne = $matchOpenPos + 1;
				$matchLocalLength = $matchClosePos - $matchOpenPosPlusOne;
				$match = mb_substr($match, 0, $matchOpenPos) 
					. mb_substr($match, $matchOpenPosPlusOne, $matchLocalLength) 
					. mb_substr($match, $matchClosePos + 1);
				$matchLength -= 2;
				$matchClosePos -= 1;
				$matchInfo[] = (object) ['fixed' => FALSE, 'start' => $matchOpenPos, 'end' => $matchClosePos, 'length' => $matchLocalLength];
			}
			$reverseIndex = $reverseClosePos;
			$matchIndex = $matchClosePos;
		}
		return [$matchInfo, $reverseInfo];
	}

	protected function initReverse () {
		$reverse = NULL;
		if ($this->reverse !== NULL) {
			$reverse = $this->reverse;
		} else if ($this->pattern !== NULL) {
			$reverse = $this->pattern;
		} else/* if ($this->pattern === NULL)*/ {
			if ($this->redirect !== NULL) 
				return $this->initFlagsByPatternOrReverse(
					$this->pattern !== NULL 
						? $this->pattern 
						: str_replace(['\\', '(?', ')?', '/?'], '', $this->match)
				);
			$this->throwExceptionIfNoPattern();
		}

		$this->lastPatternParam = NULL;
		
		$this->reverseSections = $this->initSectionsInfo($reverse);
		$this->reverse = $reverse;

		$match = NULL;
		$this->reverseParams = $this->initReverseParams(
			$reverse, $this->reverseSections, $this->constraints, $match
		);

		$this->initFlagsByPatternOrReverse($reverse);
	}

	protected function & initSectionsInfo (& $pattern) {
		$result = [];
		$index = 0;
		$length = mb_strlen($pattern);
		while ($index < $length) {
			$openPos = mb_strpos($pattern, '[', $index);
			$closePos = FALSE;
			if ($openPos !== FALSE) 
				$closePos = mb_strpos($pattern, ']', $openPos);
			if ($closePos === FALSE) {
				$result[] = (object) ['fixed' => TRUE, 'start' => $index, 'end' => $length, 'length' => $length - $index];
				break;
			} else {
				if ($index < $openPos) 
					$result[] = (object) ['fixed' => TRUE, 'start' => $index, 'end' => $openPos, 'length' => $openPos - $index];
				$openPosPlusOne = $openPos + 1;
				$lengthLocal = $closePos - $openPosPlusOne;
				$pattern = mb_substr($pattern, 0, $openPos) 
					. mb_substr($pattern, $openPosPlusOne, $lengthLocal)
					. mb_substr($pattern, $closePos + 1);
				$length -= 2;
				$closePos -= 1;
				$result[] = (object) ['fixed' => FALSE, 'start' => $openPos, 'end' => $closePos, 'length' => $lengthLocal];
			}
			$index = $closePos;
		}
		return $result;
	}

	protected function & initReverseParams (& $reverse, & $reverseSectionsInfo, & $constraints, & $match = NULL) {
		$result = [];
		$completeMatch = $match !== NULL;
		$reverseIndex = 0;
		$matchIndex = 0;
		$sectionIndex = 0;
		$section = $reverseSectionsInfo[$sectionIndex];
		$reverseLength = mb_strlen($reverse);
		$greedyCatched = FALSE;
		$matchOpenPos = -1;
		$matchClosePos = -1;
		$this->lastPatternParam = '';
		while ($reverseIndex < $reverseLength) {
			$reverseOpenPos = mb_strpos($reverse, '<', $reverseIndex);
			$reverseClosePos = FALSE;
			if ($reverseOpenPos !== FALSE) {
				$reverseClosePos = mb_strpos($reverse, '>', $reverseOpenPos);
				if ($completeMatch) {
					$matchOpenPos = mb_strpos($match, '<', $matchIndex);
					$matchClosePos = mb_strpos($match, '>', $matchOpenPos) + 1;
				}}
			if ($reverseClosePos === FALSE) break;// no other param catched
			// check if param belongs to current section 
			// and if not, move to next (or next...) section
			$reverseClosePos += 1;
			if ($reverseClosePos > $section->end) {
				while (TRUE) {
					$nextSection = $reverseSectionsInfo[$sectionIndex + 1];
					if ($reverseClosePos > $nextSection->end) {
						$sectionIndex += 1;
					} else {
						$sectionIndex += 1;
						$section = $reverseSectionsInfo[$sectionIndex];
						break;
					}}}
			// complete param section length and param name
			$paramLength = $reverseClosePos - $reverseOpenPos;
			$paramName = mb_substr($reverse, $reverseOpenPos + 1, $paramLength - 2);
			list ($greedyFlag, $sectionIsLast) = $this->initReverseParamsGetGreedyInfo(
				$reverseSectionsInfo, $constraints, 
				$paramName, $sectionIndex, $greedyCatched
			);
			if ($greedyFlag && $sectionIsLast) {
				$lastSectionChar = mb_substr(
					$reverse, $reverseClosePos, $reverseSectionsInfo[$sectionIndex]->end - $reverseClosePos
				);
				if ($lastSectionChar == '/') {
					$lastSectionChar = '';
					$reverseSectionsInfo[$sectionIndex]->end -= 1;
				}
				if ($lastSectionChar === '')
					$this->lastPatternParam = $paramName;
			}
			$result[$paramName] = (object) [
				'name'			=> $paramName,
				'greedy'		=> $greedyFlag,
				'sectionIndex'	=> $sectionIndex,
				'length'		=> $paramLength,
				'reverseStart'	=> $reverseOpenPos,
				'reverseEnd'	=> $reverseClosePos,
				'matchStart'	=> $matchOpenPos,
				'matchEnd'		=> $matchClosePos,
			];
			$reverseIndex = $reverseClosePos;
			$matchIndex = $matchClosePos;
		}
		return $result;
	}

	protected function initReverseParamsGetGreedyInfo (& $reverseSectionsInfo, & $constraints, & $paramName, & $sectionIndex, & $greedyCatched) {
		// complete greedy flag by star character inside param name
		$greedyFlag = mb_strpos($paramName, '*') !== FALSE;
		$sectionIsLast = NULL;
		// check greedy param specifics
		if ($greedyFlag) {
			if ($greedyFlag && $greedyCatched) throw new \InvalidArgumentException(
				"[\".__CLASS__.\"] Route pattern definition can have only one greedy `<param_name*>` "
				." with star (to include everything - all characters and slashes . `.*`) (\$this)."
			);
			$reverseSectionsCount = count($reverseSectionsInfo);
			$sectionIndexPlusOne = $sectionIndex + 1;
			if (// next section is optional
				$sectionIndexPlusOne < $reverseSectionsCount &&
				!($reverseSectionsInfo[$sectionIndexPlusOne]->fixed)
			) {
				// check if param is realy greedy or not
				$constraintDefined = isset($constraints[$paramName]);
				$constraint = $constraintDefined ? $constraints[$paramName] : NULL ;
				$greedyReal = !$constraintDefined || ($constraintDefined && (
					mb_strpos($constraint, '.*') !== FALSE || mb_strpos($constraint, '.+') !== FALSE
				));
				if ($greedyReal) throw new \InvalidArgumentException(
					"[\".__CLASS__.\"] Route pattern definition can not have greedy `<param_name*>` with star "
					."(to include everything - all characters and slashes . `.*`) immediately before optional "
					."section (\$this)."
				);
			}
			$greedyCatched = TRUE;
			$paramName = str_replace('*', '', $paramName);
			$sectionIsLast = $sectionIndexPlusOne === $reverseSectionsCount;
		}
		return [$greedyFlag, $sectionIsLast];
	}

	protected function initFlagsByPatternOrReverse ($pattern) {
		$scheme = static::FLAG_SCHEME_NO;
		if (mb_strpos($pattern, '//') === 0) {
			$scheme = static::FLAG_SCHEME_ANY;
		} else if (mb_strpos($pattern, 'http://') === 0) {
			$scheme = static::FLAG_SCHEME_HTTP;
		} else if (mb_strpos($pattern, 'https://') === 0) {
			$scheme = static::FLAG_SCHEME_HTTPS;
		}
		$host = static::FLAG_HOST_NO;
		if ($scheme) {
			if (mb_strpos($pattern, static::PLACEHOLDER_HOST) !== FALSE) {
				$host = static::FLAG_HOST_HOST;
			} else if (mb_strpos($pattern, static::PLACEHOLDER_DOMAIN) !== FALSE) {
				$host = static::FLAG_HOST_DOMAIN;
			} else {
				if (mb_strpos($pattern, static::PLACEHOLDER_TLD) !== FALSE) 
					$host += static::FLAG_HOST_TLD;
				if (mb_strpos($pattern, static::PLACEHOLDER_SLD) !== FALSE) 
					$host += static::FLAG_HOST_SLD;
			}
			if (mb_strpos($pattern, static::PLACEHOLDER_BASEPATH) !== FALSE) 
				$host += static::FLAG_HOST_BASEPATH;
		}
		$queryString = mb_strpos($pattern, '?') !== FALSE 
			? static::FLAG_QUERY_INCL 
			: static::FLAG_QUERY_NO;
		$this->flags = [$scheme, $host, $queryString];
	}
	
	protected function initMatchComposeRegex (& $match, & $matchSectionsInfo, & $reverseParams, & $constraints) {
		$sections = [];
		$paramIndex = 0;
		$reverseParamsKeys = array_keys($reverseParams);
		$paramsCount = count($reverseParamsKeys);
		$anyParams = $paramsCount > 0;
		$defaultPathConstraint = static::$defaultPathConstraint;
		$defaultDomainConstraint = static::$defaultDomainConstraint;
		$schemeFlag = $this->flags[0];
		$matchIsAbsolute = boolval($schemeFlag);
		$firstPathSlashPos = 0;
		if ($matchIsAbsolute) {
			$matchIsAbsolute = TRUE;
			$defaultConstraint = $defaultDomainConstraint;
			// if scheme flag is `http://` or `https://`, there is necessary to increase
			// `mb_strpos()` index by one, because there is always backslash in match pattern 
			// before `:` - like `http\://` or `https\://`
			$firstPathSlashPos = mb_strpos($match, '/', $schemeFlag + ($schemeFlag > static::FLAG_SCHEME_ANY ? 1 : 0));
		} else {
			$defaultConstraint = $defaultPathConstraint;
		}
		$pathFixedSectionsCount = 0;
		$lastPathFixedSectionIndex = 0;
		$trailingSlash = '?';
		$one = $matchIsAbsolute ? 0 : 1;
		$sectionsCountMinusOne = count($matchSectionsInfo) - 1;
		foreach ($matchSectionsInfo as $sectionIndex => $section) {
			$sectionEnd = $section->end;
			if ($anyParams) {
				$sectionOffset = $section->start;
				$sectionResult = '';
				while ($paramIndex < $paramsCount) {
					$paramKey = $reverseParamsKeys[$paramIndex];
					$param = $reverseParams[$paramKey];
					if ($param->sectionIndex !== $sectionIndex) break;
					$paramStart = $param->matchStart;
					if ($matchIsAbsolute && $paramStart > $firstPathSlashPos) 
						$defaultConstraint = $defaultPathConstraint;
					if ($sectionOffset < $paramStart)
						$sectionResult .= mb_substr($match, $sectionOffset, $paramStart - $sectionOffset);
					$paramName = $param->name;
					$customConstraint = isset($constraints[$paramName]);
					if (!$customConstraint && $param->greedy) $defaultConstraint = '.*';
					if ($customConstraint) {
						$constraint = $constraints[$paramName];
					} else {
						$constraint = $defaultConstraint;
					}
					$sectionResult .= '(?<' . $paramName . '>' . $constraint . ')';
					$paramIndex += 1;
					$sectionOffset = $param->matchEnd;
				}
				if ($sectionOffset < $sectionEnd) 
					$sectionResult .= mb_substr($match, $sectionOffset, $sectionEnd - $sectionOffset);
			} else {
				$sectionResult = mb_substr($match, $section->start, $section->length);
			}
			if ($matchIsAbsolute && $sectionEnd > $firstPathSlashPos) $one = 1;
			if ($section->fixed) {
				$pathFixedSectionsCount += $one;
				$lastPathFixedSectionIndex = $sectionIndex;
			} else {
				$sectionResult = '(' . $sectionResult . ')?';
			}
			$sections[] = $sectionResult;
		}
		if ($pathFixedSectionsCount > 0) {
			$lastFixedSectionContent = & $sections[$lastPathFixedSectionIndex];
			if ($sectionsCountMinusOne == 0 && $lastPathFixedSectionIndex == 0 && 
				$lastFixedSectionContent === '/'
			) {
				$trailingSlash = ''; // homepage -> `/`
			} else {
				$lastCharIsSlash = mb_substr($lastFixedSectionContent, -1, 1) == '/';
				if ($lastPathFixedSectionIndex == $sectionsCountMinusOne) {// last section is fixed section
					if (!$lastCharIsSlash) $trailingSlash = '/?';
				} else {// last section is optional section or sections
					$lastFixedSectionContent .= ($lastCharIsSlash ? '' : '/') . '?';
					$trailingSlash = '/?';
				}}}
		return '#^' . implode('', $sections) . $trailingSlash . '$#';
	}

	/**
	 * Correct last character in path element completed in `Url()` method by 
	 * cached router configuration property `\MvcCore\Router::$trailingSlashBehaviour;`.
	 * @param string $urlPath
	 * @return string
	 */
	protected function & urlCorrectTrailingSlashBehaviour (& $urlPath) {
		$trailingSlashBehaviour = $this->router->GetTrailingSlashBehaviour();
		$urlPathLength = mb_strlen($urlPath);
		$lastCharIsSlash = $urlPathLength > 0 && mb_substr($urlPath, $urlPathLength - 1) === '/';
		if (!$lastCharIsSlash && $trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_ALWAYS) {
			$urlPath .= '/';
		} else if ($lastCharIsSlash && $trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_REMOVE) {
			$urlPath = mb_substr($urlPath, 0, $urlPathLength - 1);
		}
		if ($urlPath === '') 
			$urlPath = '/';
		return $urlPath;
	}
	
	protected function throwExceptionIfNoPattern () {
		throw new \LogicException(
			"[".__CLASS__."] Route configuration property `\MvcCore\Route::\$pattern` is missing "
			."to parse it and complete property(ies) `\MvcCore\Route::\$match` "
			."(and `\MvcCore\Route::\$reverse`) correctly ($this)."
		);
	}


	
	/**
	 * Internal method for `\MvcCore\Route::initMatch();` processing,
	 * always called from `\MvcCore\Router::Matches();` request routing.
	 *
	 * Go throught given route pattern value and try to search for
	 * any url param occurances inside, like `<name>` or `<color*>`.
	 * Return and array with describing records for each founded param.
	 * Example:
	 *	Input (`$pattern`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`array(
	 *			array(
	 *				"name",		// param name
	 *				"<name>",	// param name for regex match pattern
	 *				16,			// `"<name>"` occurance position in escaped pattern for match
	 *				15,			// `"<name>"` occurance position in original pattern for reverse
	 *				6,			// `"<name>"` string length
	 *				FALSE		// greedy param star flag
	 *			),
	 *			array(
	 *				"color",	// param name
	 *				"<color>",	// param name for regex match pattern
	 *				23,			// `"<color*>"` occurance position in escaped pattern for match
	 *				22,			// `"<color*>"` occurance position in original pattern for reverse
	 *				7,			// `"<color>"` string length
	 *				TRUE		// greedy param star flag
	 *			)
	 *		);
	 * @param string $pattern Route pattern.
	 * @throws \LogicException Thrown, when founded any other param after greedy param.
	 * @return array Match pattern sring and statistics about founded params occurances.
	 */
	/*protected function _old_parsePatternParams (& $pattern) {
		$patternParams = [];
		$reverseIndex = 0;
		$matchIndex = 0;
		// escape all regular expression special characters before parsing except `<` and `>`:
		$match = addcslashes($pattern, "#[](){}-?!=^$.+|:\\");
		$patternLength = mb_strlen($pattern);
		$greedyCatched = FALSE;
		while ($reverseIndex < $patternLength) {
			// complete pattern opening and closing param positions
			$reverseParamOpenPos = mb_strpos($pattern, '<', $reverseIndex);
			if ($reverseParamOpenPos === FALSE) break;
			$reverseParamClosePos = mb_strpos($pattern, '>', $reverseParamOpenPos);
			if ($reverseParamClosePos === FALSE) break;
			$reverseParamClosePos += 1;
			// complete match opening and closing param positions
			$matchParamOpenPos = mb_strpos($match, '<', $matchIndex);
			$matchParamClosePos = mb_strpos($match, '>', $matchParamOpenPos);
			$matchParamClosePos += 1;
			// complete param section length
			$reverseLength = $reverseParamClosePos - $reverseParamOpenPos;
			// complete param name
			$paramName = mb_substr($pattern, $reverseParamOpenPos + 1, $reverseParamClosePos - $reverseParamOpenPos - 2);
			// complete greedy flag by star character inside param name
			$greedy = mb_strpos($paramName, '*');
			if ($greedy !== FALSE) {
				if ($greedyCatched) throw new \LogicException(
					"[".__CLASS__."] Route could have greedy `<param_name*>` with star "
					."to include slashes only as the very last parameter ($this)."
				);
				$greedyCatched = TRUE;
				$paramName = str_replace('*', '', $paramName);
			}
			$patternParams[] = [
				$paramName, 
				'<'.$paramName.'>', 
				$matchParamOpenPos, 
				$reverseParamOpenPos, 
				$reverseLength, 
				$greedyCatched,
				$match,
				$pattern
			];
			// shift parsing indexes
			$reverseIndex = $reverseParamClosePos;
			$matchIndex = $matchParamClosePos;
		}
		return [$match, $patternParams];
	}*/

	/**
	 * Internal method for `\MvcCore\Route::initMatch();` processing,
	 * always called from `\MvcCore\Router::Matches();` request routing.
	 *
	 * Compile and return value for `\MvcCore\Route::$match` pattern,
	 * (optionaly by `$compileReverse` also for `\MvcCore\Route::$reverse`)
	 * from escaped `\MvcCore\Route::$pattern` and given params statistics
	 * and from configured route constraints for regular expression:
	 * - If pattern starts with slash `/`, set automaticly into
	 *   result regular expression start rule (`#^/...`).
	 * - If there is detected trailing slash in match pattern,
	 *   set automaticly into result regular expression end rule
	 *   for trailing slash `...(?=/$|$)#` or just only end rule `...$#`.
	 * - If there is detected any last param with possible trailing slash
	 *   after, complete `\MvcCore\Route::$lastPatternParam` property
	 *   by this detected param name.
	 *
	 * Example:
	 *	Input (`$matchPattern`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Input (`$patternParams`):
	 *		`array(
	 *			array(
	 *				"name",		// param name
	 *				"<name>",	// param name for regex match pattern
	 *				16,			// `"<name>"` occurance position in escaped pattern for match
	 *				15,			// `"<name>"` occurance position in original pattern for reverse
	 *				6,			// `"<name>"` string length
	 *				FALSE		// greedy param star flag
	 *			),
	 *			array(
	 *				"color",	// param name
	 *				"<color>",	// param name for regex match pattern
	 *				23,			// `"<color*>"` occurance position in escaped pattern for match
	 *				22,			// `"<color*>"` occurance position in original pattern for reverse
	 *				7,			// `"<color>"` string length
	 *				TRUE		// greedy param star flag
	 *			)
	 *		);`
	 *	Input (`$compileReverse`):
	 *		`TRUE`
	 *	Input (`$this->constraints`):
	 *		`array(
	 *			"name"	=> "[^/]*",
	 *			"color"	=> "[a-z]*",
	 *		);`
	 *	Output:
	 *		`array(
	 *			"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *			"/products-list/<name>/<color>"
	 *		)`
	 * @param string $pattern
	 * @param string $matchPattern
	 * @param \array[] $patternParams
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \string[]
	 */
	/*protected function _old_initMatchAndReverse ($patterns, & $patternParams, $compileReverse, $localization = NULL) {
		$trailingSlash = FALSE;
		list($matchPattern,) = $patterns;
		if ($patternParams) {
			list ($matchPattern, $reverse, $reverseParams, $trailingSlash) = $this->initMatchAndReverseProcessParams(
				$patterns, $patternParams, $compileReverse, $localization
			);
		} else {
			if ($matchPattern == '/') {
				$reverse = '/';
				$reverseParams = [];
			} else {
				$lengthWithoutLastChar = mb_strlen($matchPattern) - 1;
				if (mb_strrpos($matchPattern, '/') === $lengthWithoutLastChar) {
					$matchPattern = mb_substr($matchPattern, 0, $lengthWithoutLastChar);
				}
				$trailingSlash = TRUE;
				if ($compileReverse) {
					$reverse = $this->GetPattern($localization);
					$reverseParams = [];
				} else {
					$reverse = '';
				}
			}
		}
		if ($compileReverse) {
			$this->initFlagsByPatternOrReverse($reverse);
			$this->setReverseParams($reverseParams, $localization);
		}
		return [
			'#' . (mb_strpos($matchPattern, '/') === 0 ? '^' : '') . $matchPattern
				. ($trailingSlash ? '(?=/$|$)' : '$') . '#',
			$reverse
		];
	}*/

	/*protected function _old_initMatchAndReverseProcessParams (& $patterns, & $patternParams, $compileReverse, $localization = NULL) {
		$constraints = $this->GetConstraints($localization);
		list($matchPattern, $reversePattern) = $patterns;
		$defaultConstraint = static::$DefaultConstraint;
		$trailingSlash = FALSE;
		$reverseParams = [];
		$reverse = '';
		$match = mb_substr($matchPattern, 0, $patternParams[0][2]);
		if ($compileReverse) {
			$reverse = mb_substr($reversePattern, 0, $patternParams[0][3]);
			$reverseParams = [];
		}
		foreach ($patternParams as $i => $patternParam) {
			list($paramName, $paramSection, $matchIndex, $reverseIndex, $length, $greedy) = $patternParam;
			$customConstraint = isset($constraints[$paramName]);
			if (!$customConstraint && $greedy) $defaultConstraint = '.*';
			if (isset($patternParams[$i + 1])) {
				// if there is next matched param:
				$nextRecordIndexes = $patternParams[$i + 1];
				$matchNextItemStart = $nextRecordIndexes[2];
				$reverseNextItemStart = $nextRecordIndexes[3];
				$matchStart = $matchIndex + $length;
				$reverseStart = $reverseIndex + $length;
				$matchUrlPartBeforeNext = mb_substr(
					$matchPattern, $matchStart, $matchNextItemStart - $matchStart
				);
				$reverseUrlPartBeforeNext = mb_substr(
					$reversePattern, $reverseStart, $reverseNextItemStart - $reverseStart
				);
			} else {
				// else if this param is the last one:
				$matchUrlPartBeforeNext = mb_substr($matchPattern, $matchIndex + $length);
				$reverseUrlPartBeforeNext = mb_substr($reversePattern, $reverseIndex + $length);
				// if there is nothing more in url or just only a slash char `/`:
				if ($matchUrlPartBeforeNext == '' || $matchUrlPartBeforeNext == '/') {
					$trailingSlash = TRUE;
					$this->lastPatternParam = $paramName;
					$matchUrlPartBeforeNext = '';
				};
			}
			if ($customConstraint) {
				$constraint = $constraints[$paramName];
			} else {
				$constraint = $defaultConstraint;
			}
			$match .= '(?' . $paramSection . $constraint . ')' . $matchUrlPartBeforeNext;
			if ($compileReverse) {
				$reverse .= $paramSection . $reverseUrlPartBeforeNext;
				$reverseParams[$paramName] = [$reverseIndex, $reverseIndex + $length];
			}
		}
		return [$match, $reverse, $reverseParams, $trailingSlash];
	}*/

	/**
	 * Internal method, always called from `\MvcCore\Router::Matches();` request routing,
	 * when route has been matched and when there is still no `\MvcCore\Route::$reverseParams`
	 * defined (`NULL`). It means that matched route has been defined by match and reverse
	 * patterns, because there was no pattern property parsing to prepare values bellow before.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return string
	 */
	/*protected function _old_initReverse ($localization = NULL) {
		$index = 0;
		$reverse = $this->GetReverse($localization);
		if ($reverse === NULL && $this->GetPattern($localization) !== NULL) {
			list(, $reverse) = $this->initMatch($localization);
			return $reverse;
		}
		$reverseParams = [];
		$closePos = -1;
		$paramName = '';
		while (TRUE) {
			$openPos = mb_strpos($reverse, '<', $index);
			if ($openPos === FALSE) break;
			$openPosPlusOne = $openPos + 1;
			$closePos = mb_strpos($reverse, '>', $openPosPlusOne);
			if ($closePos === FALSE) break;
			$index = $closePos + 1;
			$paramName = mb_substr($reverse, $openPosPlusOne, $closePos - $openPosPlusOne);
			$reverseParams[$paramName] = [$openPos, $openPos + ($index - $openPos)];
		}
		$this->setReverseParams($reverseParams, $localization);
		// Init `\MvcCore\Route::$lastPatternParam`.
		// Init that property only if this function is
		// called from `\MvcCore\Route::Matches()`, after current route has been matched
		// and also when there were configured for this route `\MvcCore\Route::$match`
		// value and `\MvcCore\Route::$reverse` value together:
		if ($this->lastPatternParam === NULL && $paramName) {
			$reverseLengthMinusTwo = mb_strlen($reverse) - 2;
			$lastCharIsSlash = mb_substr($reverse, $reverseLengthMinusTwo, 1) == '/';
			$closePosPlusOne = $closePos + 1;
			if (
				// if pattern ends with param section closing bracket `...param>`
				$closePosPlusOne === $reverseLengthMinusTwo + 1 || 
				// or if last pattern char is slash after closed param section `...param>/`
				($lastCharIsSlash && $closePosPlusOne === $reverseLengthMinusTwo)
			) {
				$this->lastPatternParam = $paramName;
			}
		}
		$this->initFlagsByPatternOrReverse($reverse);
		return $reverse;
	}*/

}
