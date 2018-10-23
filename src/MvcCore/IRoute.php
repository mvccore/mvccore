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
interface IRoute
{
	/**
	 * Route advanced configuration key for filtering params in - `"in"`.
	 */
	const CONFIG_FILTER_IN = 'in';

	/**
	 * Route advanced configuration key for filtering params out - `"out"`.
	 */
	const CONFIG_FILTER_OUT = 'out';

	/**
	 * Route advanced configuration key to define the only matching http method.
	 */
	const CONFIG_METHOD = 'method';

	/**
	 * Route advanced configuration key to define another route name to redirect matched request to.
	 */
	const CONFIG_REDIRECT = 'redirect';

	/**
	 * Route advanced configuration key to complete always absolute URL address.
	 */
	const CONFIG_ABSOLUTE = 'absolute';


	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` is relative with requested path only.
	 */
	const FLAG_SCHEME_NO = 0;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `//` at the beginning.
	 * The value is also length of string `//`.
	 */
	const FLAG_SCHEME_ANY = 2;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `http://` at the beginning.
	 * The value is also length of string `http://`.
	 */
	const FLAG_SCHEME_HTTP = 7;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `https://` at the beginning.
	 * The value is also length of string `https://`.
	 */
	const FLAG_SCHEME_HTTPS = 8;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` doesn't contain any query string chars.
	 */
	const FLAG_QUERY_NO = 0;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains some query string part.
	 */
	const FLAG_QUERY_INCL = 1;
	
	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` doesn't contain any host targeting.
	 */
	const FLAG_HOST_NO = 0;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains whole `%host%` targeting.
	 */
	const FLAG_HOST_HOST = 1;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%domain%` host targeting.
	 */
	const FLAG_HOST_DOMAIN = 2;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%tld%` host targeting.
	 */
	const FLAG_HOST_TLD = 3;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%sld%` host targeting.
	 */
	const FLAG_HOST_SLD = 4;

	/**
	 * Route INTERNAL flag if route `pattern` or `reverse` contains `%basePath%` targeting.
	 */
	const FLAG_HOST_BASEPATH = 10;
	
	/**
	 * Route INTERNAL placeholder for `pattern` to match any request host and for `reverse`
	 * to hold place for given `host` URL param or for currently requested host.
	 */
	const PLACEHOLDER_HOST = '%host%';
	
	/**
	 * Route INTERNAL placeholder for `pattern` to match any request domain and for `reverse`
	 * to hold place for given `domain` URL param or for currently requested domain.
	 */
	const PLACEHOLDER_DOMAIN = '%domain%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request tld and for `reverse`
	 * to hold place for given `tld` URL param or for currently requested tld.
	 */
	const PLACEHOLDER_TLD = '%tld%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request sld and for `reverse`
	 * to hold place for given `sld` URL param or for currently requested sld.
	 */
	const PLACEHOLDER_SLD = '%sld%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request basePath and for `reverse`
	 * to hold place for given `basePath` URL param or for currently requested basePath.
	 */
	const PLACEHOLDER_BASEPATH = '%basePath%';

	/**
	 * Create every time new route instance, no singleton managing!
	 * Called usually from core methods:
	 * - `\MvcCore\Router::AddRoutes();`
	 * - `\MvcCore\Router::AddRoute();`
	 * - `\MvcCore\Router::queryStringRouting();`
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
	 * @return \MvcCore\IRoute
	 */
	public static function CreateInstance (
		$patternOrConfig = NULL,
		$controllerAction = NULL,
		$defaults = [],
		$constraints = [],
		$method = NULL
	);

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
	public function GetPattern ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetPattern ($pattern);

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
	public function GetMatch ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetMatch ($match);

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
	public function GetReverse ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetReverse ($reverse);

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
	public function GetName ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetName ($name);

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
	public function GetController ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetController ($controller);

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
	public function GetAction ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetAction ($action);

	/**
	 * Get target controller name and controller action name
	 * together in one setter, in pascal case, separated by colon.
	 * There are also controller namespace definition posibilities as
	 * in `\MvcCore\Route::GetController();` getter method.
	 *
	 * Example: `"Products:List"`
	 * @return string
	 */
	public function GetControllerAction ();

	/**
	 * Set target controller name and controller action name
	 * together in one setter, in pascal case, separated by colon.
	 * There are also controller namespace definition posibilities as
	 * in `\MvcCore\Route::SetController();` setter method.
	 *
	 * Example: `"Products:List"`
	 * @return \MvcCore\IRoute
	 */
	public function & SetControllerAction ($controllerAction);

	/**
	 * Get route rewrited params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example:
	 *  `array(
	 *	  "name"  => "default-name",
	 *	  "color" => "red"
	 *  );`.
	 * @return array|\array[]
	 */
	public function & GetDefaults ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetDefaults ($defaults = []);

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
	 * @return array|\array[]
	 */
	public function GetConstraints ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetConstraints ($constraints = []);

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
	public function & GetFilters ();

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetFilters (array $filters = []);

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
	public function GetFilter ($direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

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
	 * @return \MvcCore\IRoute
	 */
	public function & SetFilter ($handler, $direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

	/**
	 * Get http method to only match requests with this defined method.
	 * If `NULL` (by default), request with any http method could be matched by this route.
	 * Value is automaticly in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @return string|NULL
	 */
	public function GetMethod ();

	/**
	 * Set http method to only match requests with this defined method.
	 * If `NULL` (by default), request with any http method could be matched by this route.
	 * Given value is automaticly converted to upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @param string|NULL $method
	 * @return \MvcCore\IRoute
	 */
	public function & SetMethod ($method = NULL);

	/**
	 * TODO: dopsat
	 * @return string|NULL
	 */
	public function GetRedirect ();

	/**
	 * TODO: dopsat
	 * @param string|NULL $redirectRouteName 
	 * @return \MvcCore\IRoute
	 */
	public function & SetRedirect ($redirectRouteName = NULL);

	/**
	 * Return only reverse params names as `string`s array.
	 * Example: `["name", "color"];`
	 * @return \string[]|NULL
	 */
	public function GetReverseParams ();

	/**
	 * Set manualy matched params from rewrite route for current route.
	 * Use this method only on currently matched route!
	 * @param array $matchedParams
	 * @return \MvcCore\IRoute
	 */
	public function & SetMatchedParams ($matchedParams = []);

	/**
	 * Return request matched params by current route.
	 * @return array|NULL
	 */
	public function & GetMatchedParams ();
	
	/**
	 * TODO:
	 * @return \MvcCore\IRouter
	 */
	public function & GetRouter ();
	
	/**
	 * TODO:
	 * @param \MvcCore\IRouter $router 
	 * @return \MvcCore\IRoute
	 */
	public function & SetRouter (\MvcCore\IRouter & $router);

	/**
	 * Return `TRUE` if route reverse pattern contains 
	 * domain part with two slases at the beginning
	 * or if route is defined with `absolute` boolean flag 
	 * by advanced configuration in constructor.
	 * @return bool
	 */
	public function GetAbsolute ();

	/**
	 * TODO: dopsat
	 * @param bool $absolute 
	 * @return \MvcCore\IRoute
	 */
	public function & SetAbsolute ($absolute = TRUE);

	/**
	 * TODO: dopsat
	 * @return string|NULL
	 */
	public function GetGroupName ();

	/**
	 * TODO: dopsat
	 * @param string|NULL $groupName
	 * @return \MvcCore\Route|\MvcCore\IRoute
	 */
	public function & SetGroupName ($groupName);

	/**
	 * Return array of matched params, with matched controller and action names,
	 * if route matches request always `\MvcCore\Request::$path` property by `preg_match_all()`.
	 *
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's submethods.
	 *
	 * @param \MvcCore\Request $request Request object instance.
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function & Matches (\MvcCore\IRequest & $request);

	/**
	 * Filter given `array $params` by configured `"in" | "out"` filter `callable`.
	 * This function return `array` with first item as `bool` about successfull
	 * filter processing in `try/catch` and second item as filtered params `array`.
	 * @param array $params 
	 * @param array $defaultParams
	 * @param string $direction 
	 * @return array
	 */
	public function Filter (array & $params = [], array & $defaultParams = [], $direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

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
	 * @param \MvcCore\IRequest $request Currently requested request object.
	 * @param array $params URL params from application point completed by developer.
	 * @param array $defaultUrlParams Requested url route parms nad query string params without escaped HTML special chars: `< > & " ' &`.
	 * @param string $queryStringParamsSepatator Query params separator, `&` by default. Always automaticly completed by router instance.
	 * @return \string[] Result URL addres in two parts - domain part with base path and path part with query string.
	 */
	public function Url (\MvcCore\IRequest & $request, array & $params = [], array & $defaultUrlParams = [], $queryParamsSepatator = '&');

	/**
	 * Render all instance properties values into string.
	 * @return string
	 */
	public function __toString ();

	/**
	 * Initialize all possible protected values (`match`, `reverse` etc...)
	 * This method is not recomanded to use in production mode, it's
	 * designed mostly for development purposes, to see what could be inside route.
	 * @return \MvcCore\IRequest
	 */
	public function & InitAll ();
}
