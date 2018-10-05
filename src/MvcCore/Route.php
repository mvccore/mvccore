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
	 * Default constraint used for all rewrited params, if no
	 * constraint for rewrited param has been specified.
	 * configured as `"[^/]*"` by default. This value means:
	 * - "Any character(s) in any length, except next slash."
	 * @var string
	 */
	public static $DefaultConstraint = '[^/]*';

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
	 *  `"Products"							 // placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"			  // placed in /App/Controllers/Front/Business/Products.php`
	 *  `"\Anywhere\Else\Controllers\Products"  // placed in /Anywhere/Else/Controllers/Products.php`
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
	 * Array with param names and their custom regular expression
	 * matching rules. Not required, for all rewrited params there is used
	 * default matching rule from `\MvcCore\Route::$DefaultConstraint`.
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
	 * Http method to only match requests with this defined method.
	 * If `NULL`, request with any http method could be matched by this route.
	 * Value has to be upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @var string|NULL
	 */
	protected $method			= NULL;

	/**
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
	 * Array with strings, containing all reverse pattern params, parsed automaticly
	 * by method `\MvcCore\Route::initMatch();` if necessary or by method
	 * `\MvcCore\Route::initReverse();` after it's necessary
	 * to complete url address string in method `\MvcCore\Route::Url();`.
	 * @var \string[]|NULL
	 */
	protected $reverseParams	= NULL;

	/**
	 * Request matched params by current route.
	 * Filled only in current route object.
	 * @var array|NULL
	 */
	protected $matchedParams	= NULL;

	/**
	 * Copied and cached value from router configuration property:
	 * `\MvcCore\Router::$trailingSlashBehaviour`.
	 * @var int|NULL
	 */
	private $_trailingSlashBehaviour = NULL;


	/**
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
	 * @param array			$method				Optional, http method to only match requests by this method. If `NULL` (by default), request with any http method could be matched by this route. Given value is automaticly converted to upper case.
	 * @return \MvcCore\Route
	 */
	public static function CreateInstance (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$method = NULL
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
	 * @param array $method					Optional, http method to only match requests by this method. If `NULL` (by default), request with any http method could be matched by this route. Given value is automaticly converted to upper case.
	 * @return \MvcCore\Route
	 */
	public function __construct (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$method = NULL
	) {
		$args = func_get_args();
		$argsCount = count($args);
		if ($argsCount === 0) return;
		if (is_array($patternOrConfig)) {
			$data = (object) $patternOrConfig;
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
			if (isset($data->pattern)) $this->pattern = $data->pattern;
			if (isset($data->match)) $this->match = $data->match;
			if (isset($data->reverse)) $this->reverse = $data->reverse;
			if (isset($data->defaults)) $this->defaults = $data->defaults;
			$this->SetConstraints(isset($data->constraints) ? $data->constraints : []);
			if (isset($data->method)) $this->method = strtoupper($data->method);
		} else {
			if ($patternOrConfig !== NULL)
				$this->pattern = $patternOrConfig;
			if ($controllerAction !== NULL) {
				list($ctrl, $action) = explode(':', $controllerAction);
				if ($ctrl) $this->controller = $ctrl;
				if ($action) $this->action = $action;
			}
			if ($defaults !== NULL)
				$this->defaults = $defaults;
			if ($constraints !== NULL)
				$this->SetConstraints($constraints);
			if ($method !== NULL)
				$this->method = strtoupper($method);
		}
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return string|\string[]|NULL
	 */
	public function GetPattern ($localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \MvcCore\Route
	 */
	public function & SetPattern ($pattern, $localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return string|\string[]|NULL
	 */
	public function GetMatch ($localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \MvcCore\Route
	 */
	public function & SetMatch ($match, $localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return string|\string[]|NULL
	 */
	public function GetReverse ($localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \MvcCore\Route
	 */
	public function & SetReverse ($reverse, $localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return array|\array[]
	 */
	public function & GetDefaults ($localization = NULL) {
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
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \MvcCore\Route
	 */
	public function & SetDefaults ($defaults = [], $localization = NULL) {
		$this->defaults = & $defaults;
		return $this;
	}

	/**
	 * Get array with param names and their custom regular expression
	 * matching rules. Not required, for all rewrited params there is used
	 * default matching rule from `\MvcCore\Route::$DefaultConstraint`.
	 * It shoud be changed to any value. The value is `"[^/]*"` by default.
	 * It means "Any character(s) in any length, except next slash".
	 *
	 * Example:
	 *	`array(
	 *		"name"	=> "[^/]*",
	 *		"color"	=> "[a-z]*",
	 *	);`
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return array|\array[]
	 */
	public function GetConstraints ($localization = NULL) {
		return $this->constraints;
	}

	/**
	 * Set array with param names and their custom regular expression
	 * matching rules. Not required, for all rewrited params there is used
	 * default matching rule from `\MvcCore\Route::$DefaultConstraint`.
	 * It shoud be changed to any value. The value is `"[^/]*"` by default.
	 * It means "Any character(s) in any length, except next slash".
	 *
	 * Example:
	 *	`array(
	 *		"name"	=> "[^/]*",
	 *		"color"	=> "[a-z]*",
	 *	);`
	 * @param array|\array[] $constraints
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \MvcCore\Route
	 */
	public function & SetConstraints ($constraints = [], $localization = NULL) {
		$this->constraints = & $constraints;
		foreach ($constraints as $key => $value)
			if (!isset($this->defaults[$key]))
				$this->defaults[$key] = NULL;
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
	 * Return parsed reverse params as array with param names from reverse pattern string.
	 * Example: `array("name", "color");`
	 * @return \string[]|NULL
	 */
	public function & GetReverseParams () {
		if ($this->reverseParams === NULL) {
			$reverse = $this->initReverse();
			if ($this->reverse === NULL) $this->reverse = $reverse;
		}
		return $this->reverseParams;
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
	 * Return array of matched params, with matched controller and action names,
	 * if route matches request `\MvcCore\Request::$Path` property by `preg_match_all()`.
	 *
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's submethods.
	 *
	 * @param string $requestPath Requested application path, never with any query string.
	 * @param string $requestMethod Uppercase request http method.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function & Matches ($requestPath, $requestMethod, $localization = NULL) {
		$matchedParams = [];
		if ($this->method !== NULL && $this->method !== $requestMethod) 
			return $matchedParams;
		if ($this->match === NULL) {
			list($this->match, $reverse) = $this->initMatch();
			if ($this->reverse === NULL) $this->reverse = $reverse;
		}
		preg_match_all($this->match, $requestPath, $matchedValues, PREG_OFFSET_CAPTURE);
		if (isset($matchedValues[0]) && count($matchedValues[0])) {
			$controllerName = $this->controller ?: '';
			$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$matchedParams = [
				'controller'	=>	$toolClass::GetDashedFromPascalCase(str_replace(['_', '\\'], '/', $controllerName)),
				'action'		=>	$toolClass::GetDashedFromPascalCase($this->action ?: ''),
			];
			array_shift($matchedValues); // first item is always matched whole `$request->GetPath()` string.
			$index = 0;
			$matchedKeys = array_keys($matchedValues);
			$matchedKeysCount = count($matchedKeys) - 1;
			while ($index < $matchedKeysCount) {
				$matchedKey = $matchedKeys[$index];
				$matchedValue = $matchedValues[$matchedKey];
				// if captured offset value is the same like in next matched record - skip next matched record:
				if (isset($matchedKeys[$index + 1])) {
					$nextKey = $matchedKeys[$index + 1];
					$nextValue = $matchedValues[$nextKey];
					if ($matchedValue[0][1] === $nextValue[0][1]) $index += 1;
				}
				// 1 line bellow is only for route debug panel, only for cases when you
				// forget to define current rewrite param, this defines null value by default
				if (!isset($this->defaults[$matchedKey])) $this->defaults[$matchedKey] = NULL;
				$matchedParams[$matchedKey] = $matchedValue[0][0];
				$index += 1;
			}
			if ($this->lastPatternParam === NULL) 
				$this->reverse = $this->initReverse();
			if (isset($matchedParams[$this->lastPatternParam])) {
				$matchedParams[$this->lastPatternParam] = rtrim($matchedParams[$this->lastPatternParam], '/');
			}
		}
		$this->matchedParams = $matchedParams;
		return $matchedParams;
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
	 * @param array $params
	 * @param array $requestedUrlParams Requested url route prams nad query string params without escaped HTML special chars: `< > & " ' &`.
	 * @param string $queryStringParamsSepatator Query params separator, `&` by default. Always automaticly completed by router instance.
	 * @return string
	 */
	public function Url (& $params = [], & $requestedUrlParams = [], $queryStringParamsSepatator = '&') {
		if ($this->reverseParams === NULL) 
			$this->reverse = $this->initReverse();
		$result = $this->reverse;
		$givenParamsKeys = array_merge([], $params);
		foreach ($this->reverseParams as $paramName) {
			$paramKeyReplacement = '<'.$paramName.'>';
			if (isset($params[$paramName])) {
				$paramValue = $params[$paramName];
			} else if (isset($requestedUrlParams[$paramName])) {
				$paramValue = $requestedUrlParams[$paramName];
			} else if (isset($this->defaults[$paramName])) {
				$paramValue = $this->defaults[$paramName];
			} else {
				$paramValue = '';
			}
			// convert possible XSS chars to entities (`< > & " ' &`):
			$paramValue = htmlspecialchars($paramValue, ENT_QUOTES);
			$result = str_replace($paramKeyReplacement, $paramValue, $result);
			unset($givenParamsKeys[$paramName]);
		}
		$result = & $this->correctTrailingSlashBehaviour($result);
		if ($givenParamsKeys) {
			// `http_build_query()` automaticly converts all XSS chars to entities (`< > & " ' &`):
			$result .= (mb_strpos($result, '?') !== FALSE ? $queryStringParamsSepatator : '?')
				. str_replace('%2F', '/', http_build_query($givenParamsKeys, '', $queryStringParamsSepatator));
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
	 * Initialize all possible protected values (`match`, `reverse` etc...)
	 * This method is not recomanded to use in production mode, it's
	 * designed mostly for development purposes, to see what could be inside route.
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & InitAll () {
		if ($this->match === NULL) {
			list($this->match, $reverse) = $this->initMatch();
			if ($this->reverse === NULL) $this->reverse = $reverse;
		}
		if ($this->lastPatternParam === NULL || $this->reverseParams === NULL) 
			$this->reverse = $this->initReverse();
		return $this;
	}

	/**
	 * Initialize `\MvcCore\Router::$Match` property (and `\MvcCore\Router::$lastPatternParam`
	 * property) from `\MvcCore\Router::$Pattern`, optionaly initialize
	 * `\MvcCore\Router::$Reverse` property if there is nothing inside.
	 * - Add backslashes for all special regex chars excluding `<` and `>` chars.
	 * - Parse all `<param>` occurrances in pattern into statistics array `$matchPatternParams`.
	 * - Complete from the statistic array the match property and if there no reverse property,
	 *   complete also reverse property.
	 * This method is usually called in core request routing process from
	 * `\MvcCore\Router::Matches();` method.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \string[]|array
	 */
	protected function initMatch ($localization = NULL) {
		$match = NULL;
		$reverse = NULL;
		// if there is no match regular expression - parse `\MvcCore\Route::\$Pattern`
		// and compile `\MvcCore\Route::\$Match` regular expression property.
		if ($this->pattern === NULL) throw new \LogicException(
			"[".__CLASS__."] Route configuration property `\MvcCore\Route::\$pattern` is missing "
			."to parse it and complete property(ies) `\MvcCore\Route::\$match` "
			."(and `\MvcCore\Route::\$reverse`) correctly ($this)."
		);
		// escape all regular expression special characters before parsing except `<` and `>`:
		$matchPattern = addcslashes($this->pattern, "#[](){}-?!=^$.+|:\\");
		// parse all presented `<param>` occurances in `$pattern` argument:
		$matchPatternParams = $this->parsePatternParams($matchPattern);
		// compile match regular expression from parsed params and custom constraints:
		if ($this->reverse === NULL) {
			list($match, $reverse) = $this->compileMatchAndReversePattern(
				$matchPattern, $matchPatternParams, TRUE, $localization
			);
		} else {
			list($match, $reverse) = $this->compileMatchAndReversePattern(
				$matchPattern, $matchPatternParams, FALSE, $localization
			);
		}
		return [$match, $reverse];
	}

	/**
	 * Internal method for `\MvcCore\Route::initMatch();` processing,
	 * always called from `\MvcCore\Router::Matches();` request routing.
	 *
	 * Go throught given route pattern value and try to search for
	 * any url param occurances inside, like `<name>` or `<color*>`.
	 * Return and array with describing records for each founded param.
	 * Example:
	 *	Input (`$match`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`array(
	 *			array(
	 *				"name",		// param name
	 *				"<name>",	// param name for regex match pattern
	 *				15,			// `"<name>"` occurance position
	 *				6,			// `"<name>"` string length
	 *				FALSE		// greedy param star flag
	 *			),
	 *			array(
	 *				"color",	// param name
	 *				"<color>",	// param name for regex match pattern
	 *				22,			// `"<color*>"` occurance position
	 *				8,			// `"<color*>"` string length
	 *				TRUE		// greedy param star flag
	 *			)
	 *		);
	 * @param string $match Route pattern with escaped all special regex characters except `<` and `>`.
	 * @throws \LogicException Thrown, when founded any other param after greedy param.
	 * @return \array[] Statistics about founded params occurances.
	 */
	protected function & parsePatternParams (& $match) {
		$matched = [];
		$index = 0;
		$matchLength = mb_strlen($match);
		$greedyCatched = FALSE;
		while ($index < $matchLength) {
			$openPos = mb_strpos($match, '<', $index);
			if ($openPos === FALSE) break;
			$closePos = mb_strpos($match, '>', $openPos);
			if ($closePos === FALSE) break;
			$closePos += 1;
			$index = $closePos;
			$length = $closePos - $openPos;
			$paramName = mb_substr($match, $openPos + 1, $length - 2);
			$greedy = mb_strpos($paramName, '*');
			if ($greedy) {
				if ($greedyCatched) throw new \LogicException(
					"[".__CLASS__."] Route could have greedy `<param_name*>` with star "
					."to include slashes only as the very last parameter ($this)."
				);
				$greedyCatched = TRUE;
				$paramName = str_replace('*', '', $paramName);
			}
			$matched[] = [$paramName, '<'.$paramName.'>', $openPos, $length, $greedy];
		}
		return $matched;
	}

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
	 *	Input (`$matchPatternParams`):
	 *		`array(
	 *			array(
	 *				"name",		// param name
	 *				"<name>",	// param name for regex match pattern
	 *				15,			// `"<name>"` occurance position
	 *				6,			// `"<name>"` string length
	 *				FALSE		// greedy param star flag
	 *			),
	 *			array(
	 *				"color",	// param name
	 *				"<color>",	// param name for regex match pattern
	 *				22,			// `"<color*>"` occurance position
	 *				8,			// `"<color*>"` string length
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
	 * @param string $matchPattern
	 * @param \array[] $matchPatternParams
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return \string[]
	 */
	protected function compileMatchAndReversePattern (& $matchPattern, & $matchPatternParams, $compileReverse, $localization = NULL) {
		$constraints = $this->GetConstraints($localization);
		$defaultConstraint = static::$DefaultConstraint;
		$trailingSlash = FALSE;
		$reverse = '';
		if ($matchPatternParams) {
			$match = mb_substr($matchPattern, 0, $matchPatternParams[0][2]);
			if ($compileReverse) {
				$reverse = $match;
				$this->reverseParams = [];
			}
			foreach ($matchPatternParams as $i => $matchPatternParam) {
				list($paramName, $matchedParamName, $index, $length, $greedy) = $matchPatternParam;
				$customConstraint = isset($constraints[$paramName]);
				if (!$customConstraint && $greedy) $defaultConstraint = '.*';
				if (isset($matchPatternParams[$i + 1])) {
					// if there is next matched param:
					$nextItemStart = $matchPatternParams[$i + 1][2];
					$start = $index + $length;
					$urlPartBeforeNext = mb_substr($matchPattern, $start, $nextItemStart - $start);
					$urlPartBeforeNextReverse = $urlPartBeforeNext;
				} else {
					// else if this param is the last one:
					$urlPartBeforeNext = mb_substr($matchPattern, $index + $length);
					$urlPartBeforeNextReverse = $urlPartBeforeNext;
					// if there is nothing more in url or just only a slash char `/`:
					if ($urlPartBeforeNext == '' || $urlPartBeforeNext == '/') {
						$trailingSlash = TRUE;
						$this->lastPatternParam = $paramName;
						$urlPartBeforeNext = '';
					};
				}
				if ($customConstraint) {
					$constraint = $constraints[$paramName];
				} else {
					$constraint = $defaultConstraint;
				}
				$match .= '(?' . $matchedParamName . $constraint . ')' . $urlPartBeforeNext;
				if ($compileReverse) {
					$reverse .= $matchedParamName . $urlPartBeforeNextReverse;
					$this->reverseParams[] = $paramName;
				}
			}
			$matchPattern = $match;
		} else {
			if ($matchPattern == '/') {
				$reverse = '/';
				$this->reverseParams = [];
			} else {
				$lengthWithoutLastChar = mb_strlen($matchPattern) - 1;
				if (mb_strrpos($matchPattern, '/') === $lengthWithoutLastChar) {
					$matchPattern = mb_substr($matchPattern, 0, $lengthWithoutLastChar);
				}
				$trailingSlash = TRUE;
				if ($compileReverse) {
					$reverse = $this->GetPattern($localization);
					$this->reverseParams = [];
				} else {
					$reverse = '';
				}
			}
		}
		return [
			'#'
			. (mb_strpos($matchPattern, '/') === 0 ? '^' : '')
			. $matchPattern
			. ($trailingSlash ? '(?=/$|$)' : '$')
			. '#',
			$reverse
		];
	}

	/**
	 * Internal method, always called from `\MvcCore\Router::Matches();` request routing,
	 * when route has been matched and when there is still no `\MvcCore\Route::$reverseParams`
	 * defined (`NULL`). It means that matched route has been defined by match and reverse
	 * patterns, because there was no pattern property parsing to prepare values bellow before.
	 * @param string $localization Lowercase language code, optionally with dash and uppercase locale code, `NULL` by default, not implemented in core.
	 * @return string
	 */
	protected function initReverse ($localization = NULL) {
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
			$reverseParams[] = $paramName;
		}
		$this->reverseParams = $reverseParams;
		// Init `\MvcCore\Route::$lastPatternParam`.
		// Init that property only if this function is
		// called from `\MvcCore\Route::Matches()`, after current route has been matched
		// and also when there were configured for this route `\MvcCore\Route::$match`
		// value and `\MvcCore\Route::$reverse` value together:
		if ($this->lastPatternParam === NULL && $paramName) {
			$reverseLengthMinusTwo = mb_strlen($reverse) - 2;
			$lastCharIsSlash = mb_substr($reverse, $reverseLengthMinusTwo, 1) == '/';
			$closePosPlusOne = $closePos + 1;
			if ($closePosPlusOne === $reverseLengthMinusTwo + 1 || ($lastCharIsSlash && $closePosPlusOne === $reverseLengthMinusTwo)) {
				$this->lastPatternParam = $paramName;
			}
		}
		return $reverse;
	}

	/**
	 * Correct last character in path element completed in `Url()` method by 
	 * cached router configuration property `\MvcCore\Router::$trailingSlashBehaviour;`.
	 * @param string $urlPath
	 * @return string
	 */
	protected function & correctTrailingSlashBehaviour (& $urlPath) {
		if ($this->_trailingSlashBehaviour === NULL)
			$this->_trailingSlashBehaviour = \MvcCore\Application::GetInstance()->GetRouter()->GetTrailingSlashBehaviour();
		$urlPathLength = mb_strlen($urlPath);
		$lastCharIsSlash = $urlPathLength > 0 && mb_substr($urlPath, $urlPathLength - 1) === '/';
		if (!$lastCharIsSlash && $this->_trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_ALWAYS) {
			$urlPath .= '/';
		} else if ($lastCharIsSlash && $this->_trailingSlashBehaviour === \MvcCore\IRouter::TRAILING_SLASH_REMOVE) {
			$urlPath = mb_substr($urlPath, 0, $urlPathLength - 1);
		}
		if ($urlPath === '') 
			$urlPath = '/';
		return $urlPath;
	}

		
}
