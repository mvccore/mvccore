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

//include_once(__DIR__ . '/Interfaces/IRoute.php');

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
class Route implements Interfaces\IRoute
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
	 * @var string|NULL
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
	 * @var string|NULL
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
	 * @var string|NULL
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
	protected $name		= '';

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
	 *  `"Products"                             // placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"              // placed in /App/Controllers/Front/Business/Products.php`
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
	 * @var array
	 */
	protected $defaults		= array();

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
	 * @var array
	 */
	protected $constraints		= array();

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
	 * @var string|NULL
	 */
	protected $lastPatternParam = NULL;

	/**
	 * Array with strings, containing all reverse pattern params, parsed automaticly
	 * by method `\MvcCore\Route::initMatchm();` if necessary or by method
	 * `\MvcCore\Route::initReverse();` after it's necessary
	 * to complete url address string in method `\MvcCore\Route::Url();`.
	 * @var string[]|NULL
	 */
	protected $reverseParams = NULL;


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
	 *		"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 * ));`
	 * @param $patternOrConfig	string|array	Required, configuration array or route pattern value to parse into match and reverse patterns.
	 * @param $controllerAction	string			Optional, controller and action name in pascale case like: `"Photogallery:List"`.
	 * @param $defaults			string			Optional, default param values like: `array("name" => "default-name", "page" => 1)`.
	 * @param $constraints		array			Optional, params regex constraints for regular expression match fn no `"match"` record in configuration array as first argument defined.
	 * @return \MvcCore\Route
	 */
	public static function GetInstance (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = array(),
		$constraints = array()
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
	 *		"pattern"		=> "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		"reverse"		=> "/products-list/<name>/<color>",
	 *		"controller"	=> "Products",
	 *		"action"		=> "List",
	 *		"defaults"		=> array("name" => "default-name",	"color" => "red"),
	 * ));`
	 * @param $patternOrConfig	string|array	Required, configuration array or route pattern value to parse into match and reverse patterns.
	 * @param $controllerAction	string			Optional, controller and action name in pascale case like: `"Photogallery:List"`.
	 * @param $defaults			array			Optional, default param values like: `array("name" => "default-name", "page" => 1)`.
	 * @param $constraints		array			Optional, params regex constraints for regular expression match fn no `"match"` record in configuration array as first argument defined.
	 * @return \MvcCore\Route
	 */
	public function __construct (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = array(),
		$constraints = array()
	) {
		$args = func_get_args();
		$argsCount = count($args);
		if ($argsCount === 0) return $this;
		if (gettype($patternOrConfig) == 'array') {
			$data = (object) $patternOrConfig;
			$this->name = isset($data->name) ? $data->name : '';
			if (isset($data->controllerAction)) {
				list($this->controller, $this->action) = explode(':', $data->controllerAction);
			} else {
				$this->controller = isset($data->controller) ? $data->controller : '';
				$this->action = isset($data->action) ? $data->action : '';
			}
			$this->pattern = isset($data->pattern) ? $data->pattern : NULL;
			$this->match = isset($data->match) ? $data->match : NULL;
			$this->reverse = isset($data->reverse) ? $data->reverse : NULL;
			$this->defaults = isset($data->defaults) ? $data->defaults : array();
			$this->constraints = isset($data->constraints) ? $data->constraints : array();
		} else {
			$this->pattern = $patternOrConfig;
			list($this->controller, $this->action) = explode(':', $controllerAction);
			$this->name = '';
			$this->match = NULL;
			$this->reverse = NULL;
			$this->defaults = $defaults;
			$this->constraints = $constraints;
		}
		if (!$this->controller && !$this->action && strpos($this->name, ':') !== FALSE) {
			list($this->controller, $this->action) = explode(':', $this->name);
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
	 * @return string|NULL
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
	 * @param string $pattern
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
	 * @return string|NULL
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
	 * @param string $match
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
	 * @return string|NULL
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
	 * @param string $reverse
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
	 *  `"Products"                             // placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"              // placed in /App/Controllers/Front/Business/Products.php`
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
	 *  `"Products"                             // placed in /App/Controllers/Products.php`
	 *  `"Front\Business\Products"              // placed in /App/Controllers/Front/Business/Products.php`
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
		list($this->controller, $this->action) = explode(':', $controllerAction);
		return $this;
	}

	/**
	 * Get route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example:
	 *  `array(
	 *      "name"  => "default-name",
	 *      "color" => "red"
	 *  );`.
	 * @return array
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
	 *      "name"  => "default-name",
	 *      "color" => "red"
	 *  );`.
	 * @param array $defaults
	 * @return \MvcCore\Route
	 */
	public function & SetDefaults ($defaults = array()) {
		$this->defaults = $defaults;
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
	 * @return array
	 */
	public function & GetConstraints () {
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
	 * @param array $constraints
	 * @return \MvcCore\Route
	 */
	public function & SetConstraints ($constraints = array()) {
		$this->constraints = $constraints;
		return $this;
	}

	/**
	 * Return array of matched params, with matched controller and action names,
	 * if route matches request `\MvcCore\Request::$Path` property by `preg_match_all()`.
	 *
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's submethods.
	 *
	 * @param string $requestPath
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function Matches (& $requestPath) {
		$matchedParams = array();
		if ($this->match === NULL) $this->initMatch();
		preg_match_all($this->match, $requestPath, $matchedValues, PREG_OFFSET_CAPTURE);
		if (isset($matchedValues[0]) && count($matchedValues[0])) {
			$controllerName = $this->controller ?: '';
			$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$matchedParams = array(
				'controller'	=>	$toolClass::GetDashedFromPascalCase(str_replace(array('_', '\\'), '/', $controllerName)),
				'action'		=>	$toolClass::GetDashedFromPascalCase($this->action ?: ''),
			);
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
			if ($this->lastPatternParam === NULL) $this->initReverse();
			if (isset($matchedParams[$this->lastPatternParam])) {
				$matchedParams[$this->lastPatternParam] = rtrim($matchedParams[$this->lastPatternParam], '/');
			}
		}
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
	 * @param array $cleanedGetRequestParams Request query params with escaped chars: `<` and `>`.;
	 * @return string
	 */
	public function Url (& $params, & $cleanedGetRequestParams) {
		if ($this->reverseParams === NULL) $this->initReverse();
		$result = $this->reverse;
		$givenParamsKeys = array_merge(array(), $params);
		foreach ($this->reverseParams as $paramName) {
			$paramKeyReplacement = '<'.$paramName.'>';
			$paramValue = (
				isset($params[$paramName])
					? $params[$paramName]
					: (isset($cleanedGetRequestParams[$paramName])
						? $cleanedGetRequestParams[$paramName]
						: (isset($this->defaults[$paramName])
							? $this->defaults[$paramName]
							: ''))
			);
			$result = str_replace($paramKeyReplacement, $paramValue, $result);
			unset($givenParamsKeys[$paramName]);
		}
		if ($givenParamsKeys)
			$result .= ($this->reverseParams ? '&amp;' : '?')
				. http_build_query($givenParamsKeys);
		return $result;
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
	 * @return void
	 */
	public function initMatch () {
		// if there is no match regular expression - parse `\MvcCore\Route::\$Pattern`
		// and compile `\MvcCore\Route::\$Match` regular expression property.
		if (mb_strlen($this->pattern) === 0) throw new \LogicException(
			"[".__CLASS__."] Route configuration property `\MvcCore\Route::\$Pattern` is missing "
			."to parse it and complete property(ies) `\MvcCore\Route::\$Match` (and `\MvcCore\Route::\$Reverse`) correctly."
		);
		// escape all regular expression special characters before parsing except `<` and `>`:
		$matchPattern = addcslashes($this->pattern, "#[](){}-?!=^$.+|:\\");
		// parse all presented `<param>` occurances in `$pattern` argument:
		$matchPatternParams = $this->parsePatternParams($matchPattern);
		// compile match regular expression from parsed params and custom constraints:
		if ($this->reverse === NULL) {
			list($this->match, $this->reverse) = $this->compileMatchAndReversePattern(
				$matchPattern, $matchPatternParams, TRUE
			);
		} else {
			list($this->match, $reverse) = $this->compileMatchAndReversePattern(
				$matchPattern, $matchPatternParams, FALSE
			);
		}
	}

	/**
	 * Initialize all possible protected values (`match`, `reverse` etc...)
	 * This method is not recomanded to use in production mode, it's
	 * designed mostly for development purposes, to see what could be inside route.
	 * @return \MvcCore\Route|\MvcCore\Interfaces\IRoute
	 */
	public function & InitAll () {
		if ($this->match === NULL) $this->initMatch();
		if ($this->lastPatternParam === NULL) $this->initReverse();
		if ($this->reverseParams === NULL) $this->initReverse();
		return $this;
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
	 * @return array[] Statistics about founded params occurances.
	 */
	protected function & parsePatternParams (& $match) {
		$matched = array();
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
					."to include slashes only as the very last parameter."
				);
				$greedyCatched = TRUE;
				$paramName = str_replace('*', '', $paramName);
			}
			$matched[] = array($paramName, '<'.$paramName.'>', $openPos, $length, $greedy);
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
	 *		    "#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#",
	 *		    "/products-list/<name>/<color>"
	 *		)`
	 * @param string $matchPattern
	 * @param array[] $matchPatternParams
	 * @return string[]
	 */
	protected function compileMatchAndReversePattern (& $matchPattern, & $matchPatternParams, $compileReverse) {
		$constraints = $this->constraints;
		$defaultConstraint = static::$DefaultConstraint;
		$trailingSlash = FALSE;
		$reverse = '';
		if ($matchPatternParams) {
			$match = mb_substr($matchPattern, 0, $matchPatternParams[0][2]);
			if ($compileReverse) {
				$reverse = $match;
				$this->reverseParams = array();
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
				$constraint = $customConstraint
					? $constraints[$paramName]
					: $defaultConstraint;
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
				$this->reverseParams = array();
			} else {
				$lengthWithoutLastChar = mb_strlen($matchPattern) - 1;
				if (mb_strrpos($matchPattern, '/') === $lengthWithoutLastChar) {
					$matchPattern = mb_substr($matchPattern, 0, $lengthWithoutLastChar);
					$trailingSlash = TRUE;
				}
				if ($compileReverse) {
					$reverse = $this->pattern;
					$this->reverseParams = array();
				} else {
					$reverse = '';
				}
			}
		}
		return array(
			'#'
			. (mb_strpos($matchPattern, '/') === 0 ? '^' : '')
			. $matchPattern
			. ($trailingSlash ? '(?=/$|$)' : '$')
			. '#',
			$reverse
		);
	}

	/**
	 * Internal method, always called from `\MvcCore\Router::Matches();` request routing,
	 * when route has been matched and when there is still no `\MvcCore\Route::$reverseParams`
	 * defined (`NULL`). It means that matched route has been defined by match and reverse
	 * patterns, because there was no pattern property parsing to prepare values bellow before.
	 * @return void
	 */
	protected function initReverse () {
		$index = 0;
		$reverse = & $this->reverse;
		if ($this->reverse == NULL && $this->pattern !== NULL)
			return $this->initMatch();
		$reverseParams = array();
		$closePos = -1;
		$paramName = '';
		while (TRUE) {
			$openPos = mb_strpos($reverse, '<', $index);
			if ($openPos === FALSE) break;
			$openPosPlusOne = $openPos + 1;
			$closePos = mb_strpos($reverse, '<', $openPosPlusOne);
			if ($closePos === FALSE) break;
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
	}
}
