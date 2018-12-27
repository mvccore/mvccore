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
 * Responsibility - describing request(s) to match and reversely build URL addresses.
 * - Describing request to match and target it (read more about properties).
 * - Matching request by given request object, see `\MvcCore\Route::Matches()`.
 * - Completing URL address by given params array, see `\MvcCore\Route::Url()`.
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
	 * Route INTERNAL placeholder for `pattern` to match any request top level 
	 * domain and for `reverse` to hold place for given `tld` URL param or for 
	 * currently requested top level domain.
	 */
	const PLACEHOLDER_TLD = '%tld%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request second 
	 * level domain and for `reverse` to hold place for given `sld` URL param 
	 * or for currently requested second level domain.
	 */
	const PLACEHOLDER_SLD = '%sld%';

	/**
	 * Route INTERNAL placeholder for `pattern` to match any request basePath and for `reverse`
	 * to hold place for given `basePath` URL param or for currently requested basePath.
	 */
	const PLACEHOLDER_BASEPATH = '%basePath%';

	/**
	 * Create every time new route instance, no singleton managing.
	 * Called usually from core methods:
	 * - `\MvcCore\Router::AddRoutes();`
	 * - `\MvcCore\Router::AddRoute();`
	 * - `\MvcCore\Router::SetOrCreateDefaultRouteAsCurrent();`
	 * This method is the best place where to implement custom route init for 
	 * configured core class. First argument could be configuration array with 
	 * all necessary constructor values or all separated arguments - first is 
	 * route pattern value to parse into match and reverse values, then 
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
	 *						regular expression match function if no `"match"` 
	 *						property in config array as first argument defined.
	 * @param array			$advancedConfiguration
	 *						Optional, http method to only match requests by this 
	 *						method. If `NULL` (by default), request with any http 
	 *						method could be matched by this route. Given value is 
	 *						automatically converted to upper case.
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
	 * Get route base pattern to complete match pattern string to match requested 
	 * URL and to complete reverse pattern string to build back an URL address.
	 *
	 * To define route by this form is the most comfortable way, but a way 
	 * slower, because there is necessary every request to convert this value 
	 * into `match` and into `reverse` properties correctly. But you can specify 
	 * those both properties directly, if you can write regular expressions.
	 *
	 * This match and reverse definition has to be in very basic form without 
	 * regular expression escaping or advanced rules:
	 * - No regular expression border `#` characters, it's used in `match` only.
	 * - No start `^` or end `$` regular expression chars, also used in `match`.
	 * - No escaping of regular expression characters: `[](){}<>|=+*.!?-/`, 
	 *   those characters will be escaped in route `match` property.
	 * - Star character inside param name (`<color*>`) means greedy param
	 *   matching all to the end of the URL address. It has to be the last one.
	 *
	 * Example: `"/products-list/<name>[/<color*>]"`.
	 * @return string|array|NULL
	 */
	public function GetPattern ();

	/**
	 * Set route base pattern to complete match pattern string to match requested 
	 * URL and to complete reverse pattern string to build back an URL address.
	 *
	 * To define route by this form is the most comfortable way, but a way 
	 * slower, because there is necessary every request to convert this value 
	 * into `match` and into `reverse` properties correctly. But you can specify 
	 * those both properties directly, if you can write regular expressions.
	 *
	 * This match and reverse definition has to be in very basic form without 
	 * regular expression escaping or advanced rules:
	 * - No regular expression border `#` characters, it's used in `match` only.
	 * - No start `^` or end `$` regular expression chars, also used in `match`.
	 * - No escaping of regular expression characters: `[](){}<>|=+*.!?-/`, 
	 *   those characters will be escaped in route `match` property.
	 * - Star character inside param name (`<color*>`) means greedy param
	 *   matching all to the end of the URL address. It has to be the last one.
	 *
	 * Example: `"/products-list/<name>[/<color*>]"`.
	 * @param string|array $pattern
	 * @return \MvcCore\IRoute
	 */
	public function & SetPattern ($pattern);

	/**
	 * Get route match pattern in raw form (to use it as it is) to match requested
	 * URL. This `match` pattern must have the very same structure and content 
	 * as `reverse` pattern, because there is necessary to complete route flags 
	 * from `reverse` pattern string - to prepare proper regular expression 
	 * subject for this `match`, not just only the request `path`. Because those
	 * flags is not possible to detect from raw regular expression string.
	 *
	 * This is required together with route `reverse` property, if you have not 
	 * configured route `pattern` property instead.
	 *
	 * To define the route by assigning properties route `match` and route 
	 * `reverse` together is little bit more annoying way to define it (because 
	 * you have to write almost the same information twice), but it's the best 
	 * speed solution, because there is no route internal metadata completion 
	 * and `pattern` parsing into `match` and `reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]+)(/(?<id>\d+))?/?$#"`
	 * @return string|array|NULL
	 */
	public function GetMatch ();

	/**
	 * Set route match pattern in raw form (to use it as it is) to match requested
	 * URL. This `match` pattern must have the very same structure and content 
	 * as `reverse` pattern, because there is necessary to complete route flags 
	 * from `reverse` pattern string - to prepare proper regular expression 
	 * subject for this `match`, not just only the request `path`. Because those
	 * flags is not possible to detect from raw regular expression string.
	 *
	 * This is required together with route `reverse` property, if you have not 
	 * configured route `pattern` property instead.
	 *
	 * To define the route by assigning properties route `match` and route 
	 * `reverse` together is little bit more annoying way to define it (because 
	 * you have to write almost the same information twice), but it's the best 
	 * speed solution, because there is no route internal metadata completion 
	 * and `pattern` parsing into `match` and `reverse` properties.
	 *
	 * Example: `"#^/products\-list/(?<name>[^/]+)(/(?<id>\d+))?/?$#"`
	 * @param string|array $match
	 * @return \MvcCore\IRoute
	 */
	public function & SetMatch ($match);

	/**
	 * Get route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with route `match` property, if you have not configured 
	 * route `pattern` property instead. This is only very simple string with 
	 * variable section definitions defined by brackets `[]` and with parameters 
	 * replacement places (like `<name>` or `<page>`) for given values by 
	 * `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route by assigning properties route `match` and route 
	 * `reverse` together is little bit more annoying way to define it (because 
	 * you have to write almost the same information twice), but it's the best 
	 * speed solution, because there is no route internal metadata completion 
	 * and `pattern` parsing into `match` and `reverse` properties.
	 *
	 * Example: `"/products-list/<name>[/<color>]"`
	 * @return string|array|NULL
	 */
	public function GetReverse ();

	/**
	 * Set route reverse address replacements pattern to build url.
	 * - No regular expression border `#` characters.
	 * - No regular expression characters escaping (`[](){}<>|=+*.!?-/`).
	 * - No start `^` or end `$` regular expression characters.
	 *
	 * Required together with route `match` property, if you have not configured 
	 * route `pattern` property instead. This is only very simple string with 
	 * variable section definitions defined by brackets `[]` and with parameters 
	 * replacement places (like `<name>` or `<page>`) for given values by 
	 * `\MvcCore\Router::Url($name, $params);` method.
	 *
	 * To define the route by assigning properties route `match` and route 
	 * `reverse` together is little bit more annoying way to define it (because 
	 * you have to write almost the same information twice), but it's the best 
	 * speed solution, because there is no route internal metadata completion 
	 * and `pattern` parsing into `match` and `reverse` properties.
	 *
	 * Example: `"/products-list/<name>[/<color>]"`
	 * @param string|array $reverse
	 * @return \MvcCore\IRoute
	 */
	public function & SetReverse ($reverse);

	/**
	 * Get route name is your custom keyword/term or pascal case combination of 
	 * controller and action describing `"Controller:Action"` target to be 
	 * dispatched. Route name is not required route property.
	 *
	 * By this name there is selected proper route object to complete URL string 
	 * by given params in router method: `\MvcCore\Router:Url($name, $params);`.
	 *
	 * Example: `"products_list" | "Products:Gallery"`
	 * @return string|NULL
	 */
	public function GetName ();

	/**
	 * Set route name is your custom keyword/term or pascal case combination of 
	 * controller and action describing `"Controller:Action"` target to be 
	 * dispatched. Route name is not required route property.
	 *
	 * By this name there is selected proper route object to complete URL string 
	 * by given params in router method: `\MvcCore\Router:Url($name, $params);`.
	 *
	 * Example: `"products_list" | "Products:Gallery"`
	 * @param string|NULL $name
	 * @return \MvcCore\IRoute
	 */
	public function & SetName ($name);

	/**
	 * Get controller name/path to dispatch, in pascal case. This property is not 
	 * required. If there is `controller` param inside route `pattern` or inside 
	 * route `match` pattern property, it's used to define this record to dispatch
	 * specific requested controller.
	 *
	 * It should contain controller class namespaces defined in standard PHP 
	 * notation. If there is backslash at the beginning - controller class will 
	 * be loaded directly from base standard controllers directory 
	 * `/App/Controllers`, not by any relative place defined by possible domain
	 * route from extended router. If there are two standard slashes in the 
	 * beginning, controller class will be loaded without those two slashes
	 * from base PHP place without any automatic MvcCore namespace prepending.
	 * 
	 * Example:
	 *  `"Products"` - normally placed in /App/Controllers/Products.php` (but it 
	 *				   could be also in some sub-directory if there is used 
	 *				   extended route with namespace)
	 *  `"\Front\Business\Products"`
	 *				 - placed in `/App/Controllers/Front/Business/Products.php`
	 *  `"//Anywhere\Else\Controllers\Products"
	 *				 - placed in `/Anywhere/Else/Controllers/Products.php`
	 * @return string
	 */
	public function GetController ();

	/**
	 * Set controller name/path to dispatch, in pascal case. This property is not 
	 * required. If there is `controller` param inside route `pattern` or inside 
	 * route `match` pattern property, it's used to define this record to dispatch
	 * specific requested controller.
	 *
	 * It should contain controller class namespaces defined in standard PHP 
	 * notation. If there is backslash at the beginning - controller class will 
	 * be loaded directly from base standard controllers directory 
	 * `/App/Controllers`, not by any relative place defined by possible domain
	 * route from extended router. If there are two standard slashes in the 
	 * beginning, controller class will be loaded without those two slashes
	 * from base PHP place without any automatic MvcCore namespace prepending.
	 * 
	 * Example:
	 *  `"Products"` - normally placed in /App/Controllers/Products.php` (but it 
	 *				   could be also in some sub-directory if there is used 
	 *				   extended route with namespace)
	 *  `"\Front\Business\Products"`
	 *				 - placed in `/App/Controllers/Front/Business/Products.php`
	 *  `"//Anywhere\Else\Controllers\Products"
	 *				 - placed in `/Anywhere/Else/Controllers/Products.php`
	 * @param string|NULL $controller
	 * @return \MvcCore\IRoute
	 */
	public function & SetController ($controller);

	/**
	 * Get action name to call in dispatched controller, in pascal case. This 
	 * property is not required. If controller instance has default method
	 * `IndexAction()`, its called. If there is no such method, no method is 
	 * called. If there is `action` param inside route `pattern` or inside route 
	 * `match` pattern property, it's used to overwrite this record to dispatch
	 * specific requested action.
	 *
	 * If this property has value `"List"`, then public method in target 
	 * controller has to be named as: `public function ListAction () {...}`.
	 *
	 * Example: `"List"`
	 * @return string
	 */
	public function GetAction ();

	/**
	 * Set action name to call in dispatched controller, in pascal case. This 
	 * property is not required. If controller instance has default method
	 * `IndexAction()`, its called. If there is no such method, no method is 
	 * called. If there is `action` param inside route `pattern` or inside route 
	 * `match` pattern property, it's used to overwrite this record to dispatch
	 * specific requested action.
	 *
	 * If this property has value `"List"`, then public method in target 
	 * controller has to be named as: `public function ListAction () {...}`.
	 *
	 * Example: `"List"`
	 * @param string|NULL $action
	 * @return \MvcCore\IRoute
	 */
	public function & SetAction ($action);

	/**
	 * Get target controller name/path and controller action name together in 
	 * one getter, in pascal case, separated by colon. There are also contained 
	 * controller namespace. Read more about controller name/path definition 
	 * possibilities in `\MvcCore\Route::GetController();` getter method.
	 *
	 * Example: `"Products:List" | "\Front\Business\Products:Gallery"`
	 * @return string
	 */
	public function GetControllerAction ();

	/**
	 * Set target controller name/path and controller action name together in 
	 * one setter, in pascal case, separated by colon. There are also contained 
	 * controller namespace. Read more about controller name/path definition 
	 * possibilities in `\MvcCore\Route::SetController();` setter method.
	 *
	 * Example: `"Products:List" | "\Front\Business\Products:Gallery"`
	 * @return \MvcCore\IRoute
	 */
	public function & SetControllerAction ($controllerAction);
	
	/**
	 * Get route rewrite params default values and also any other query string 
	 * params default values. It could be used for any application request 
	 * param from those application inputs - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `["name" => "default-name", "color" => "red",]`.
	 * @return array|\array[]
	 */
	public function & GetDefaults ();

	/**
	 * Set route rewrite params default values and also any other query string 
	 * params default values. It could be used for any application request 
	 * param from those application inputs - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `["name" => "default-name", "color" => "red",]`.
	 * @param array|\array[] $defaults
	 * @return \MvcCore\IRoute
	 */
	public function & SetDefaults ($defaults = []);

	/**
	 * Get array with param names and their custom regular expression matching 
	 * rules. Not required, for all rewrite params there is used default 
	 * matching rules from route static properties `defaultDomainConstraint` or
	 * `defaultPathConstraint`. It should be changed to any value. Default value 
	 * is `"[^.]+"` for domain part and `"[^/]+"` for path part.
	 *
	 * Example: `["name"	=> "[^/]+", "color"	=> "[a-z]+",]`
	 * @return array|\array[]
	 */
	public function GetConstraints ();

	/**
	 * Set array with param names and their custom regular expression matching 
	 * rules. Not required, for all rewrite params there is used default 
	 * matching rules from route static properties `defaultDomainConstraint` or
	 * `defaultPathConstraint`. It should be changed to any value. Default value 
	 * is `"[^.]+"` for domain part and `"[^/]+"` for path part.
	 *
	 * Example: `["name"	=> "[^/]+", "color"	=> "[a-z]+",]`
	 * @param array|\array[] $constraints
	 * @return \MvcCore\IRoute
	 */
	public function & SetConstraints ($constraints = []);

	/**
	 * Get URL address params filters to filter URL params in and out. By route 
	 * filters you can change incoming request params into application and out 
	 * from application. For example to translate the values or anything else. 
	 * 
	 * Filters are `callable`s and always in this array under keys `"in"` and 
	 * `"out"` accepting arguments: 
	 * - `$params`  associative array with params from requested URL address for 
	 *				in filter and associative array with params to build URL 
	 *				address for out filter.
	 * - `$defaultParams`	associative array with default params to store 
	 *						any custom value necessary to filter effectively.
	 * - `$request`	current request instance implements `\MvcCore\IRequest`.
	 * 
	 * `Callable` filter must return associative `array` with filtered params. 
	 * 
	 * There is possible to call any `callable` as closure function in variable
	 * except forms like `'ClassName::methodName'` and `['childClassName', 
	 * 'parent::methodName']` and `[$childInstance, 'parent::methodName']`.
	 * @return array|\callable[]
	 */
	public function & GetFilters ();

	/**
	 * Set URL address params filters to filter URL params in and out. By route 
	 * filters you can change incoming request params into application and out 
	 * from application. For example to translate the values or anything else. 
	 * 
	 * Filters are `callable`s and always in this array under keys `"in"` and 
	 * `"out"` accepting arguments: 
	 * - `$params`  associative array with params from requested URL address for 
	 *				in filter and associative array with params to build URL 
	 *				address for out filter.
	 * - `$defaultParams`	associative array with default params to store 
	 *						any custom value necessary to filter effectively.
	 * - `$request`	current request instance implements `\MvcCore\IRequest`.
	 * 
	 * `Callable` filter must return associative `array` with filtered params. 
	 * 
	 * There is possible to call any `callable` as closure function in variable
	 * except forms like `'ClassName::methodName'` and `['childClassName', 
	 * 'parent::methodName']` and `[$childInstance, 'parent::methodName']`.
	 * @param array|\callable[] $filters 
	 * @return \MvcCore\IRoute
	 */
	public function & SetFilters (array $filters = []);

	/**
	 * Get URL address params filter to filter URL params in and out. By route 
	 * filter you can change incoming request params into application and out 
	 * from application. For example to translate the values or anything else. 
	 * 
	 * Filter is `callable` accepting arguments: 
	 * - `$params`  associative array with params from requested URL address for 
	 *				in filter and associative array with params to build URL 
	 *				address for out filter.
	 * - `$defaultParams`	associative array with default params to store 
	 *						any custom value necessary to filter effectively.
	 * - `$request`	current request instance implements `\MvcCore\IRequest`.
	 * 
	 * `Callable` filter must return associative `array` with filtered params. 
	 * 
	 * There is possible to call any `callable` as closure function in variable
	 * except forms like `'ClassName::methodName'` and `['childClassName', 
	 * 'parent::methodName']` and `[$childInstance, 'parent::methodName']`.
	 * @return \callable|NULL
	 */
	public function GetFilter ($direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

	/**
	 * Set URL address params filter to filter URL params in and out. By route 
	 * filter you can change incoming request params into application and out 
	 * from application. For example to translate the values or anything else. 
	 * 
	 * Filter is `callable` accepting arguments: 
	 * - `$params`  associative array with params from requested URL address for 
	 *				in filter and associative array with params to build URL 
	 *				address for out filter.
	 * - `$defaultParams`	associative array with default params to store 
	 *						any custom value necessary to filter effectively.
	 * - `$request`	current request instance implements `\MvcCore\IRequest`.
	 * 
	 * `Callable` filter must return associative `array` with filtered params. 
	 * 
	 * There is possible to call any `callable` as closure function in variable
	 * except forms like `'ClassName::methodName'` and `['childClassName', 
	 * 'parent::methodName']` and `[$childInstance, 'parent::methodName']`.
	 * @param \callable $handler 
	 * @param string $direction
	 * @return \MvcCore\Route
	 */
	public function & SetFilter ($handler, $direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

	/**
	 * Get http method to only match requests with this defined method. If `NULL`, 
	 * request with any http method could be matched by this route. Value has to 
	 * be in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @return string|NULL
	 */
	public function GetMethod ();

	/**
	 * Set http method to only match requests with this defined method. If `NULL`, 
	 * request with any http method could be matched by this route. Value has to 
	 * be in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @param string|NULL $method
	 * @return \MvcCore\Route
	 */
	public function & SetMethod ($method = NULL);

	/**
	 * Get other route unique name to redirect request to. To this target route are 
	 * passed params parsed from this matched route. This property is used for 
	 * routes handling old pages or old request forms, redirecting those request
	 * to new form.
	 * Example: `"new_route_name"`
	 * @return string|NULL
	 */
	public function GetRedirect ();

	/**
	 * Set other route unique name to redirect request to. To this target route are 
	 * passed params parsed from this matched route. This property is used for 
	 * routes handling old pages or old request forms, redirecting those request
	 * to new form.
	 * Example: `"new_route_name"`
	 * @param string|NULL $redirectRouteName 
	 * @return \MvcCore\IRoute
	 */
	public function & SetRedirect ($redirectRouteName = NULL);

	/**
	 * Return `TRUE` if route `pattern` (or `reverse`) contains domain part with 
	 * two slashes at the beginning or if route is defined with `absolute` 
	 * boolean flag by advanced configuration in constructor or by setter.
	 * @return bool
	 */
	public function GetAbsolute ();

	/**
	 * Set boolean about to generate absolute URL addresses. If `TRUE`, there is 
	 * always generated absolute URL form. If `FALSE`, absolute URL address is 
	 * generated only if `pattern` (or `reverse`) property contains absolute 
	 * matching form.
	 * @param bool $absolute 
	 * @return \MvcCore\IRoute
	 */
	public function & SetAbsolute ($absolute = TRUE);

	/**
	 * Get route group name to belongs to. Group name is always first word parsed
	 * from request path. First word is content between two first slashes in 
	 * request path. If group name is `NULL`, route belongs to default group 
	 * and that group is used when no other group matching the request path.
	 * @return string|NULL
	 */
	public function GetGroupName ();

	/**
	 * Set route group name to belongs to. Group name is always first word parsed
	 * from request path. First word is content between two first slashes in 
	 * request path. If group name is `NULL`, route belongs to default group 
	 * and that group is used when no other group matching the request path.
	 * @param string|NULL $groupName 
	 * @return \MvcCore\IRoute
	 */
	public function & SetGroupName ($groupName);

	/**
	 * Return only reverse params names as `string`s array. Reverse params array
	 * is array with all rewrite params founded in `patter` (or `reverse`) string.
	 * Example: `["name", "color"];`
	 * @return \string[]|NULL
	 */
	public function GetReverseParams ();

	/**
	 * Set manually matched params from rewrite route matching process into 
	 * current route object. Use this method only on currently matched route!
	 * Passed array must have keys as param names and values as matched values
	 * and it must contain all only matched rewrite params and route controller 
	 * and route action if any.
	 * @param array $matchedParams
	 * @return \MvcCore\IRoute
	 */
	public function & SetMatchedParams ($matchedParams = []);

	/**
	 * Get matched params from rewrite route matching process into current route 
	 * object. Use this method only on currently matched route! Passed array 
	 * must have keys as param names and values as matched values and it must 
	 * contain all only matched rewrite params and route controller and route 
	 * action if any.
	 * @return array|NULL
	 */
	public function & GetMatchedParams ();
	
	/**
	 * Get router instance reference, used mostly in route URL building process.
	 * @return \MvcCore\IRouter
	 */
	public function & GetRouter ();
	
	/**
	 * Set router instance reference, used mostly in route URL building process.
	 * @param \MvcCore\Router|\MvcCore\IRouter $router 
	 * @return \MvcCore\IRoute
	 */
	public function & SetRouter (\MvcCore\IRouter & $router);

	/**
	 * Get any special advanced configuration property from route constructor.
	 * configuration array contains data from route constructor. If route is 
	 * initialized with all params (not only by single array), the configuration
	 * array used in this method contains the fourth param with advanced route 
	 * configuration. If route is initialized only with one single array 
	 * argument, the configuration array used in this method contains that whole 
	 * configuration single array argument. Data in described are without no 
	 * change against initialization moment. You can specify key to the array to
	 * get any initialization value.
	 * @param string $propertyName 
	 * @return mixed
	 */
	public function GetAdvancedConfigProperty ($propertyName);

	/**
	 * Return array of matched params if incoming request match this route
	 * or `NULL` if doesn't. Returned array must contain all matched reverse 
	 * params with matched controller and action names by route and by matched 
	 * params. Route is matched usually if request property `path` matches by 
	 * PHP `preg_match_all()` route `match` pattern. Sometimes, matching subject 
	 * could be different if route specifies it - if route `pattern` (or `match`) 
	 * property contains domain (or base path part) - it means if it is absolute 
	 * or if `pattern` (or `match`) property contains a query string part.
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's sub-methods.
	 * @param \MvcCore\IRequest $request The request object instance.
	 * @throws \LogicException Route configuration property is missing.
	 * @throws \InvalidArgumentException Wrong route pattern format.
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function & Matches (\MvcCore\IRequest & $request);

	/**
	 * Filter given `array $params` by configured `"in" | "out"` filter `callable`.
	 * This function return `array` with first item as `bool` about successful
	 * filter processing in `try/catch` and second item as filtered params `array`.
	 * @param array		$params 
	 * @param array		$defaultParams
	 * @param string	$direction 
	 * @return array	Filtered params array.
	 */
	public function Filter (array & $params = [], array & $defaultParams = [], $direction = \MvcCore\IRoute::CONFIG_FILTER_IN);

	/**
	 * Complete route URL by given params array and route internal reverse 
	 * replacements pattern string. If there are more given params in first 
	 * argument than total count of replacement places in reverse pattern,
	 * then create URL with query string params after reverse pattern, 
	 * containing that extra record(s) value(s). Returned is an array with only
	 * one string as result URL or it could be returned for extended classes
	 * an array with two strings - result URL in two parts - first part as scheme, 
	 * domain and base path and second as path and query string.
	 * Example:
	 *	Input (`$params`):
	 *		`[
	 *			"name"		=> "cool-product-name",
	 *			"color"		=> "blue",
	 *			"variants"	=> ["L", "XL"],
	 *		];`
	 *	Input (`\MvcCore\Route::$reverse`):
	 *		`"/products-list/<name>/<color*>"`
	 *	Output:
	 *		`["/any/app/base/path/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"]`
	 *		or:
	 *		`[
	 *			"/any/app/base/path", 
	 *			"/products-list/cool-product-name/blue?variant[]=L&amp;variant[]=XL"
	 *		]`
	 * @param \MvcCore\Request	$request 
	 *							Currently requested request object.
	 * @param array				$params
	 *							URL params from application point completed 
	 *							by developer.
	 * @param array				$defaultUrlParams 
	 *							Requested URL route params and query string 
	 *							params without escaped HTML special chars: 
	 *							`< > & " ' &`.
	 * @param string			$queryStringParamsSepatator 
	 *							Query params separator, `&` by default. Always 
	 *							automatically completed by router instance.
	 * @param bool				$splitUrl
	 *							Boolean value about to split completed result URL
	 *							into two parts or not. Default is FALSE to return 
	 *							a string array with only one record - the result 
	 *							URL. If `TRUE`, result url is split into two 
	 *							parts and function return array with two items.
	 * @return \string[]		Result URL address in array. If last argument is 
	 *							`FALSE` by default, this function returns only 
	 *							single item array with result URL. If last 
	 *							argument is `TRUE`, function returns result URL 
	 *							in two parts - domain part with base path and 
	 *							path part with query string.
	 */
	public function Url (\MvcCore\IRequest & $request, array & $params = [], array & $defaultUrlParams = [], $queryStringParamsSepatator = '&', $splitUrl = FALSE);

	/**
	 * This method serve only for debug and development purposes. It renders all 
	 * instance properties values into string, to print whole route in logic 
	 * exception message about what property is missing.
	 * @return string
	 */
	public function __toString ();

	/**
	 * Initialize all possible protected values (`match`, `reverse` etc...). This 
	 * method is not recommended to use in production mode, it's designed mostly 
	 * for development purposes, to see what could be inside route object.
	 * @return \MvcCore\IRoute
	 */
	public function & InitAll ();
}
