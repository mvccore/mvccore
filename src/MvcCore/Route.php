<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md
 */

namespace MvcCore;

require_once(__DIR__ . '/Interfaces/IRoute.php');

/**
 * Responsibility - describing request(s) to match and reversely build url addresses.
 * - Describing request to match it (read more about properties).
 * - Matching request by given request object, see `\MvcCore\Route::Matches()`.
 * - Completing url address by given params array, see `\MvcCore\Route::Url()`.
 *
 * Main Properties:
 * - `$Pattern`
 *   Required, if you have not configured `\MvcCore\Route::$Match` and
 *   `\MvcCore\Route::$Reverse` property instead. Very basic URL address
 *   form to match and parse rewrited params by it. Address to parse
 *   and prepare `\MvcCore\Route::$Match` property and `\MvcCore\Route::$Reverse`
 *   property. automaticly in `\MvcCore\Route::Prepare();` call.
 * - `$Match`
 *   Required together with `\MvcCore\Route::$Reverse` property, if you
 *   have not configured `\MvcCore\Route::$Pattern` property instead.
 *   This property is always used to match request by `\MvcCore\Request::Path`
 *   by classic PHP regualar expression matching by `preg_match_all();`.
 * - `$Reverse`
 *   Required together with `\MvcCore\Route::$Match` property, if you
 *   have not configured `\MvcCore\Route::$Pattern` property instead.
 *   This property is always used to complete url address by called params
 *   array and by this string with rewrite params replacements inside.
 * - `$Controller`
 *   Required, if there is no `controller` param inside `\MvcCore\Route::$Pattern`
 *   or inside `\MvcCore\Route::$Match property`. Controller class name to dispatch
 *   in pascal case form, namespaces and including slashes as namespace delimiters.
 * - `$Action`
 *   Required, if there is no `action` param inside `\MvcCore\Route::$Pattern`
 *   or inside `\MvcCore\Route::$Match property`. Public method in controller
 *   in pascal case form, but in controller named as `public function <CoolName>Action () {...`.
 * - `$Name`
 *   Not required, if you want to create url addresses always by `Controller:Action`
 *   named records. It could be any string, representing route custom name to
 *   complete url address by that name inside your application.
 * - `$Defaults`
 *   Not required, matched route params default values and query params default values.
 *   Last entry in array may be used for property `\MvcCore\Route::$LastPatternParam`
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
	 * to convert this value into `\MvcCore\Route::$Match` and into
	 * `\MvcCore\Route::$Reverse` properties correctly and you can
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
	 * @var string
	 */
    public $Pattern		= '';

	/**
	 * Route match pattern in raw form (to use it as it is) to match proper request.
	 * This property is always used to match request by `\MvcCore\Request::Path`
	 * by classic PHP regualar expression matching by `preg_match_all();`.
	 *
	 * Required together with `\MvcCore\Route::$Reverse` property, if you
	 * have not configured `\MvcCore\Route::$Pattern` property instead.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$Match` and
	 * `\MvcCore\Route::$Reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but is't the best
	 * speed solution, because there is no `\MvcCore\Route::$Pattern` parsing and
	 * conversion into `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)#"`
	 * @var string
	 */
	public $Match		= '';

	/**
	 * Route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with `\MvcCore\Route::$Match` property, if you
	 * have not configured `\MvcCore\Route::$Pattern` property instead.
	 *
	 * This is only very simple string with replacement places (like `<name>` or
	 * `<page>`) for given values by `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$Match` and
	 * `\MvcCore\Route::$Reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but is't the best
	 * speed solution, because there is no `\MvcCore\Route::$Pattern` parsing and
	 * conversion into `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` properties.
	 *
	 * Example: `"/products-list/<name>/<color>"`
	 * @var string
	 */
	public $Reverse		= '';

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
    public $Name		= '';

	/**
	 * Controller name to dispatch, in pascal case. Required only if
	 * there is no `controller` param inside `\MvcCore\Route::$Pattern`
	 * or inside `\MvcCore\Route::$Match property`.
	 *
	 * It should contain namespaces and backslashes
	 * as class namespaces delimiters.
	 *
	 * Example: `"Products" | "Front\Business\Products"`
	 * @var string
	 */
	public $Controller	= '';

	/**
	 * Action name to call in controller dispatching, in pascal case.
	 * Required, if there is no `action` param inside `\MvcCore\Route::$Pattern`
	 * or inside `\MvcCore\Route::$Match property`.
	 *
	 * If this property has value `"List"`, then public
	 * method in target controller has to be named as:
	 * `public function ListAction () {...}`.
	 *
	 * Example: `"List"`
	 * @var string
	 */
	public $Action		= '';

	/**
	 * Route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * If you like more faster routes by more specific declaration by declarating
	 * `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` property together,
	 * you could define `\MvcCore\Route::$LastPatternParam` values as last record
	 * key in this `\MvcCore\Route::$Defaults` array.
	 *
	 * Example: `array("name" => "default-name", "color" => "red",);`.
	 * @var array
	 */
	public $Defaults		= array();

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
	public $Constraints		= array();

	/**
	 * Optional, param name, which has to be also inside `\MvcCore\Route::$Pattern` or
	 * inside `\MvcCore\Route::$Match` pattern property as the last one. And after it,
	 * there could be only trailing slash or nothing (pattern end). This trailing slash
	 * param definition automaticly trims this last param in pattern for right trailing
	 * slash automaticly when route is matched.
	 *
	 * This property is automaticly completed by route preparing, when is parsed
	 * `\MvcCore\Route::$Pattern` string into `\MvcCore\Route::$Match` regex pattern.
	 *
	 * If you like more faster routes by more specific declaration by declarating
	 * `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` property together,
	 * you could define this param as last record key in `\MvcCore\Route::$Defaults`
	 * or by setter method `\MvcCore\Route::SetLastPatternParam` or by constructor
	 * configuration array record names as `lastPatternParam`.
	 *
	 * If you don't define this, there will be a trailing slash inside value of this param
	 * sometimes. Which you have to clean manualy anytime in controller. No big deal.
	 *
	 * @var string|NULL
	 */
	public $LastPatternParam = NULL;

	/**
	 * Automaticly completed to `TRUE` in `\MvcCore\Route::Prepare()` process,
	 * if route has `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` defined.
	 * Route preparing process is called in routing process in `\MvcCore\Router::Route();`.
	 *
	 * If there are not defined properties `\MvcCore\Route::$Match` or `\MvcCore\Route::$Reverse`
	 * anywhere in routes configuration in application start, then there is necessary
	 * to process `\MvcCore\Route::$Pattern` property parsing and compiling match pattern or
	 * reverse replacements pattern from that information.
	 *
	 * Much faster is to define those properties directly.
	 *
	 * @var bool
	 */
	protected $prepared = FALSE;


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
	 * @param $defaults			string			Optional, default param values like: `array("name" => "default-name", "page" => 1)`.
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
			$name = $data->name ?: '';
			if (isset($data->controllerAction)) {
				list($controller, $action) = explode(':', $data->controllerAction);
			} else {
				$controller = $data->controller ? : '';
				$action = $data->action ?: '';
			}
			$pattern = $data->pattern ?: '';
			$match = $data->match ?: '';
			$reverse = $data->reverse ?: '';
			$defaults = $data->defaults ?: array();
			$constraints = $data->constraints ?: array();
		} else {
			$pattern = $patternOrConfig;
			list($controller, $action) = explode(':', $controllerAction);
			$name = '';
			$match = '';
			$reverse = '';
		}
		if (!$controller && !$action && strpos($name, ':') !== FALSE) {
			list($controller, $action) = explode(':', $name);
		}
		$this->Name = $name;
		$this->Controller = $controller;
		$this->Action = $action;
		$this->Pattern = $pattern;
		$this->Match = $match;
		$this->Reverse = $reverse;
		$this->Defaults = $defaults;
		$this->Constraints = $constraints;
	}

	/**
	 * Set route pattern to match request url and to build url address.
	 *
	 * To define route by this form is the most comfortable way,
	 * but a way slower, because there is necessary every request
	 * to convert this value into `\MvcCore\Route::$Match` and into
	 * `\MvcCore\Route::$Reverse` properties correctly and you can
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
	 * @var string
	 * @return \MvcCore\Route
	 */
	public function & SetPattern ($pattern) {
		$this->Pattern = $pattern;
		return $this;
	}

	/**
	 * Set route match pattern in raw form (to use it as it is) to match proper request.
	 * This property is always used to match request by `\MvcCore\Request::Path`
	 * by classic PHP regualar expression matching by `preg_match_all();`.
	 *
	 * Required together with `\MvcCore\Route::$Reverse` property, if you
	 * have not configured `\MvcCore\Route::$Pattern` property instead.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$Match` and
	 * `\MvcCore\Route::$Reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but is't the best
	 * speed solution, because there is no `\MvcCore\Route::$Pattern` parsing and
	 * conversion into `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)#"`
	 * @var string
	 * @return \MvcCore\Route
	 */
	public function & SetMatch ($match) {
		$this->Match = $match;
		return $this;
	}

	/**
	 * Set route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with `\MvcCore\Route::$Match` property, if you
	 * have not configured `\MvcCore\Route::$Pattern` property instead.
	 *
	 * This is only very simple string with replacement places (like `<name>` or
	 * `<page>`) for given values by `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route object by assigning properties `\MvcCore\Route::$Match` and
	 * `\MvcCore\Route::$Reverse` together is little bit more anoying way to define it
	 * (because you have to write almost the same information twice), but is't the best
	 * speed solution, because there is no `\MvcCore\Route::$Pattern` parsing and
	 * conversion into `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` properties.
	 *
	 * Example: `"/products-list/<name>/<color>"`
	 * @var string
	 * @return \MvcCore\Route
	 */
	public function & SetReverse ($reverse) {
		$this->Reverse = $reverse;
		return $this;
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
		$this->Name = $name;
		return $this;
	}

	/**
	 * Set controller name to dispatch, in pascal case. Required only if
	 * there is no `controller` param inside `\MvcCore\Route::$Pattern`
	 * or inside `\MvcCore\Route::$Match property`.
	 *
	 * It should contain namespaces and backslashes
	 * as class namespaces delimiters.
	 *
	 * Example: `"Products" | "Front\Business\Products"`
	 * @param string $controller
	 * @return \MvcCore\Route
	 */
	public function & SetController ($controller) {
		$this->Controller = $controller;
		return $this;
	}

	/**
	 * Set action name to call in controller dispatching, in pascal case.
	 * Required, if there is no `action` param inside `\MvcCore\Route::$Pattern`
	 * or inside `\MvcCore\Route::$Match property`.
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
		$this->Action = $action;
		return $this;
	}

	/**
	 * Set target controller name and controller action name to dispatch
	 * to gether in one setter, in pascal case, separated by colon.
	 *
	 * Example: `"Products:List"`
	 * @return \MvcCore\Route
	 */
	public function & SetControllerAction ($controllerAction) {
		list($this->Controller, $this->Action) = explode(':', $controllerAction);
		return $this;
	}

	/**
	 * Set route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * If you like more faster routes by more specific declaration by declarating
	 * `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` property together,
	 * you could define `\MvcCore\Route::$LastPatternParam` values as last record
	 * key in this `\MvcCore\Route::$Defaults` array.
	 *
	 * Example: `array("name" => "default-name", "color" => "red",);`.
	 * @param array $defaults
	 * @return \MvcCore\Route
	 */
	public function & SetDefaults ($defaults = array()) {
		$this->Defaults = $defaults;
		return $this;
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
		$this->Constraints = $constraints;
		return $this;
	}

	/**
	 * Set very optionally param name, which has to be also inside `\MvcCore\Route::$Pattern`
	 * or inside `\MvcCore\Route::$Match` pattern property defined as the last one. And after it,
	 * there could be only trailing slash or nothing (pattern end). This trailing slash
	 * param definition automaticly trims this last param in pattern for right trailing
	 * slash automaticly when route is matched.
	 *
	 * This property is automaticly completed by route preparing, when is parsed
	 * `\MvcCore\Route::$Pattern` string into `\MvcCore\Route::$Match` regex pattern.
	 *
	 * If you like more faster routes by more specific declaration by declarating
	 * `\MvcCore\Route::$Match` and `\MvcCore\Route::$Reverse` property together,
	 * you could define this param as last record key in `\MvcCore\Route::$Defaults`
	 * or by setter method `\MvcCore\Route::SetLastPatternParam` or by constructor
	 * configuration array record names as `lastPatternParam`.
	 *
	 * If you don't define this, there will be a trailing slash inside value of this param
	 * sometimes. Which you have to clean manualy anytime in controller. No big deal.
	 *
	 * @param string $lastPatternParam
	 * @return \MvcCore\Route
	 */
	public function & SetLastPatternParam ($lastPatternParam = '') {
		$this->LastPatternParam = $lastPatternParam;
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
	public function Match ($requestPath) {
		$matchedParams = array();
		preg_match_all($this->Match, $requestPath, $matchedValues, PREG_OFFSET_CAPTURE);
		if (isset($matchedValues[0]) && count($matchedValues[0])) {
			$controllerName = $this->Controller ?: '';
			$toolClass = \MvcCore\Application::GetInstance()->GetToolClass();
			$matchedParams = array(
				'controller'	=>	$toolClass::GetDashedFromPascalCase(str_replace(array('_', '\\'), '/', $controllerName)),
				'action'		=>	$toolClass::GetDashedFromPascalCase($this->Action ?: ''),
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
				if (!isset($this->Defaults[$matchedKey])) $this->Defaults[$matchedKey] = NULL;
				$matchedParams[$matchedKey] = $matchedValue[0][0];
				$index += 1;
			}
			if (isset($matchedParams[$this->LastPatternParam])) {
				$matchedParams[$this->LastPatternParam] = rtrim($matchedParams[$this->LastPatternParam], '/');
			}
		}
		return $matchedParams;
	}

	/**
	 * Process route internal data completion (if necessary)
	 * before processing route matching by `\MvcCore\Route::Matches();`.
	 *
	 * Because for matching, there is always necessary to have completed:
	 * - `\MvcCore\Route::$Match`
	 * - `\MvcCore\Route::$Reverse`
	 * - `\MvcCore\Route::$LastPatternParam`
	 *
	 * If those properties are completed, there is internally
	 * switched route to prepared state. This method is usually
	 * called in core request routing process from
	 * `\MvcCore\Router::Route();` method and it's submethods.
	 * @return void
	 */
	public function Prepare () {
		if ($this->prepared) return;
		$matchLength = mb_strlen($this->Match);
		$reverseLength = mb_strlen($this->Reverse);
		// if there is anything in match pattern and reverse replacements pattern - route is prepared:
		if (!$matchLength || !$reverseLength) {
			if (mb_strlen($this->Pattern) === 0) {
				throw new \LogicException(
					"[".__CLASS__."] Route configuration property `\MvcCore\Route::\$Pattern` is missing "
					."to parse it and complete properties `\MvcCore\Route::\$Match` and `\MvcCore\Route::\$Reverse` correctly."
				);
			}
			// if there is no match regular expression - parse `\MvcCore\Route::\$Pattern`
			// and compile `\MvcCore\Route::\$Match` regular expression property:
			if (!$matchLength) {
				// escape all regular expression special characters before parsing except `<` and `>`:
				$matchPattern = addcslashes($this->Pattern, "#[](){}-?!=^$.+|:\\");
				// parse all presented `<param>` occurances in `$pattern` argument:
				$matchPatternParams = $this->parsePatternParams($matchPattern);
				// compile match regular expression from parsed params and custom constraints:
				$this->Match = $this->compileMatchPattern($matchPattern, $matchPatternParams);
			}
			// if there is no reverse replacements expression,
			// process greedy replacement fix on `\MvcCore\Route::\$Pattern` only:
			if (!$reverseLength) $this->Reverse = str_replace(
				array('<*', '*>'), array('<', '>'), $this->Pattern
			);
			$this->Reverse = rtrim($this->Reverse, '?&');
		}
		// if there is still no last param parsed - take the last record(s) name(s) from defaults,
		// (because there is a little probability, that developer defined records in default array
		// in simillar order as params in reverse pattern) and check if `<paramName>` by this last
		// default record is somewhere at the end of reverse pattern - if it is - set up last param.
		if (is_null($this->LastPatternParam) && $this->Defaults) {
			$defaultsKeys = array_reverse(array_keys($this->Defaults));
			$reverseLength = mb_strlen($this->Reverse);
			$lastCharIsSlash = mb_substr($this->Reverse, $reverseLength - 2, 1) == '/';
			foreach ($defaultsKeys as $defaultsKey) {
				$pos = mb_strpos($this->Reverse, '<'.$defaultsKey.'>') + mb_strlen($defaultsKey) + 2;
				if ($pos === $reverseLength - 1 || ($pos === $reverseLength - 2 && $lastCharIsSlash)) {
					$this->LastPatternParam = $defaultsKey;
					break;
				}
			}
		}
		$this->prepared = TRUE;
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
	 *	Input (`\MvcCore\Route::$Reverse`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`"/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"`
	 * @param array $params
	 * @return string
	 */
	public function Url (& $params) {
		$result = $this->Reverse;
		$allParams = array_merge(
			is_array($this->Defaults) ? $this->Defaults : array(), $params
		);
		foreach ($allParams as $key => $value) {
			$paramKeyReplacement = '<'.$key.'>';
			if (mb_strpos($result, $paramKeyReplacement) === FALSE) {
				$glue = (mb_strpos($result, '?') === FALSE) ? '?' : '&amp;';
				$result .= $glue . http_build_query(array($key => $value));
			} else {
				$result = str_replace($paramKeyReplacement, $value, $result);
			}
		}
		return $result;
	}

	/**
	 * Internal method for `\MvcCore\Route::Prepare();` processing,
	 * always called from `\MvcCore\Router::Route();` request routing.
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
	 * Internal method for `\MvcCore\Route::Prepare();` processing,
	 * always called from `\MvcCore\Router::Route();` request routing.
	 *
	 * Compile and return value for `\MvcCore\Route::$Match` pattern
	 * from escaped `\MvcCore\Route::$Pattern` and given params statistics
	 * and from configured route constraints for regular expression:
	 * - If pattern starts with slash `/`, set automaticly into
	 *   result regular expression start rule (`#^/...`).
	 * - If there is detected trailing slash in match pattern,
	 *   set automaticly into result regular expression end rule
	 *   for trailing slash `...(?=/$|$)#` or just only end rule `...$#`.
	 * - If there is detected any last param with possible trailing slash
	 *   after, complete `\MvcCore\Route::$LastPatternParam` property
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
	 *		);
	 *	Input (`$this->Constraints`):
	 *		`array(
	 *			"name"	=> "[^/]*",
	 *			"color"	=> "[a-z]*",
	 *		);`
	 *	Output:
	 *		`"#^/products\-list/(?<name>[^/]*)/(?<color>[a-z]*)(?=/$|$)#"`
	 * @param string $matchPattern
	 * @param array[] $matchPatternParams
	 * @return string
	 */
	protected function compileMatchPattern (& $matchPattern, & $matchPatternParams) {
		$constraints = $this->Constraints;
		$defaultConstraint = static::$DefaultConstraint;
		$trailingSlash = FALSE;
		if ($matchPatternParams) {
			$newMatch = mb_substr($matchPattern, 0, $matchPatternParams[0][2]);
			foreach ($matchPatternParams as $i => $matchPatternParam) {
				list($paramName, $matchedParamName, $index, $length, $greedy) = $matchPatternParam;
				$customConstraint = isset($constraints[$paramName]);
				if (!$customConstraint && $greedy) $defaultConstraint = '.*';
				if (isset($matchPatternParams[$i + 1])) {
					// if there is next matched param:
					$nextItemStart = $matchPatternParams[$i + 1][2];
					$start = $index + $length;
					$urlPartBeforeNext = mb_substr($matchPattern, $start, $nextItemStart - $start);
				} else {
					// else if this param is the last one:
					$urlPartBeforeNext = mb_substr($matchPattern, $index + $length);
					// if there is nothing more in url or just only a slash char `/`:
					if ($urlPartBeforeNext == '' || $urlPartBeforeNext == '/') {
						$trailingSlash = TRUE;
						$this->LastPatternParam = $paramName;
						$urlPartBeforeNext = '';
					};
				}
				$constraint = $customConstraint
					? $constraints[$paramName]
					: $defaultConstraint;
				$newMatch .= '(?' . $matchedParamName . $constraint . ')' . $urlPartBeforeNext;
			}
			$matchPattern = $newMatch;
		} else if ($matchPattern != '/') {
			$lengthWithotLastChar = mb_strlen($matchPattern) - 1;
			if (mb_strrpos($matchPattern, '/') === $lengthWithotLastChar) {
				$matchPattern = mb_substr($matchPattern, 0, $lengthWithotLastChar);
				$trailingSlash = TRUE;
			}
		}
		return '#'
			. (mb_strpos($matchPattern, '/') === 0 ? '^' : '')
			. $matchPattern
			. ($trailingSlash ? '(?=/$|$)' : '$')
			. '#';
	}
}
