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

trait Props
{
	/**
	 * Default constraint used for all rewrite params in request path, if no 
	 * constraint for that param has been specified in route instance. 
	 * Default value is `"[^/]+"`, what means "any character(s) except slash, 
	 * one or more character(s) long."
	 * @var string
	 */
	protected static $defaultPathConstraint = '[^/]+';

	/**
	 * Default constraint used for all rewrite params in domain pattern part, if 
	 * no constraint for that param has been specified in route instance. 
	 * Default value is `"[^\.]+"`, what means "any character(s) except dot, 
	 * one or more character(s) long."
	 * @var string
	 */
	protected static $defaultDomainConstraint = '[^\.]+';

	/**
	 * Route base pattern to complete match pattern string to match requested 
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
	 * @var string|\string[]|NULL
	 */
	protected $pattern		= NULL;

	/**
	 * Route match pattern in raw form (to use it as it is) to match requested
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
	 * @var string|\string[]|NULL
	 */
	protected $match		= NULL;

	/**
	 * Route reverse address replacements pattern to build url.
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
	 * @var string|\string[]|NULL
	 */
	protected $reverse		= NULL;

	/**
	 * Not required. Route name is your custom keyword/term or pascal case 
	 * combination of controller and action describing `"Controller:Action"` 
	 * target to be dispatched.
	 *
	 * By this name there is selected proper route object to complete URL string 
	 * by given params in router method: `\MvcCore\Router:Url($name, $params);`.
	 *
	 * Example: `"products_list" | "Products:Gallery"`
	 * @var string
	 */
	protected $name			= '';

	/**
	 * Controller name/path to dispatch, in pascal case. This property is not 
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
	 * @var string
	 */
	protected $controller	= '';

	/**
	 * Action name to call in dispatched controller, in pascal case. This 
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
	 * @var string
	 */
	protected $action		= '';

	/**
	 * Route rewrite params default values and also any other query string 
	 * params default values. It could be used for any application request 
	 * param from those application inputs - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `["name" => "default-name", "color" => "red",]`.
	 * @var array|\array[]
	 */
	protected $defaults		= [];

	/**
	 * Array with param names and their custom regular expression matching 
	 * rules. Not required, for all rewrite params there is used default 
	 * matching rules from route static properties `defaultDomainConstraint` or
	 * `defaultPathConstraint`. It should be changed to any value. Default value 
	 * is `"[^.]+"` for domain part and `"[^/]+"` for path part.
	 *
	 * Example: `["name"	=> "[^/]+", "color"	=> "[a-z]+",]`
	 * @var array|\array[]
	 */
	protected $constraints		= [];

	/**
	 * URL address params filters to filter URL params in and out. By route 
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
	 * @var array
	 */
	protected $filters			= [];

	/**
	 * Http method to only match requests with this defined method. If `NULL`, 
	 * request with any http method could be matched by this route. Value has to 
	 * be in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @var string|NULL
	 */
	protected $method			= NULL;

	/**
	 * Other route unique name to redirect request to. To this target route are 
	 * passed params parsed from this matched route. This property is used for 
	 * routes handling old pages or old request forms, redirecting those request
	 * to new form.
	 * Example: `"new_route_name"`
	 * @var string|NULL
	 */
	protected $redirect			= NULL;

	/**
	 * Boolean about to generate absolute URL addresses. If `TRUE`, there is 
	 * always generated absolute URL form. If `FALSE`, absolute URL address is 
	 * generated only if `pattern` (or `reverse`) property contains absolute 
	 * matching form.
	 * @var bool
	 */
	protected $absolute			= FALSE;

	/**
	 * Route group name to belongs to. Group name is always first word parsed
	 * from request path. First word is content between two first slashes in 
	 * request path. If group name is `NULL`, route belongs to default group 
	 * and that group is used when no other group matching the request path.
	 * @var string|NULL
	 */
	protected $groupName		= NULL;

	/**
	 * Associative array with `\stdClass` objects with metadata about all 
	 * rewrite params in `pattern` (or `reverse`) property. Every object item
	 * has those keys about founded param place: `name`, `greedy`, `sectionIndex`, 
	 * `reverseStart` and`reverseEnd`. Keys `matchStart` and `matchEnd` could 
	 * have value `-1` and no real string indexes, if this metadata array is 
	 * completed only by `reverse` pattern (It happens if `match` property is 
	 * configured directly). This array is parsed automatically by route method 
	 * `initMatchAndReverse();` or by route method `initReverse();` after it's 
	 * necessary - to be able to complete `match` to match incoming request (if 
	 * `match` is configured as `NULL`) and to complete URL address string in 
	 * method `Url();` and it's sub-methods.
	 * Example: 
	 * // For pattern `/products-list/<name>[/<color*>]`
	 * `[
	 *		'name' => (object) [
	 *			'name'			=> 'name',	'greedy'		=> FALSE,	
	 *			'sectionIndex'	=> 0,		'reverseStart'	=> 15,	
	 *			'reverseEnd'	=> 21,		'matchStart'	=> 15,	
	 *			'matchEnd'		=> 21,
	 *		],
	 *		'color' => (object) [
	 *			'name'			=> 'color',	'greedy'		=> TRUE,
				'sectionIndex'	=> 1,		'reverseStart'	=> 22,		
				'reverseEnd'	=> 30,		'matchStart'	=> 22,
				'matchEnd'		=> 30,
	 *		]
	 * ];`
	 * @var array|NULL
	 */
	protected $reverseParams	= NULL;

	/**
	 * This associative array is used only in cloned matched route object and 
	 * there is stored all only matched rewrite params and route controller and 
	 * route action if any. All params are in raw form. Not filtered.
	 * @var array
	 */
	protected $matchedParams	= [];

	/**
	 * An array with `\stdClass` objects with metadata about all fixed or 
	 * variable sections in `pattern` (or `reverse`) property. Every object item
	 * has those keys about section: `fixed`, `start`, `end` and `length`. This 
	 * array is parsed automatically by route method `initMatchAndReverse();` or 
	 * by route method `initReverse();` after it's necessary - to be able to 
	 * complete `match` to match incoming request (if `match` is configured as 
	 * `NULL`) and to complete URL address string in method `Url();` and it's 
	 * sub-methods.
	 * Example: 
	 * // For pattern `/products-list/<name>[/<color*>]`
	 * `[
	 *		(object) [
	 *			'fixed'	=> TRUE,	'start'		=> 0,
	 *			'end'	=> 21,		'length'	=> 21,
	 *		],
	 *		(object) [
	 *			'fixed'	=> FALSE,	'start'		=> 21,
	 *			'end'	=> 30,		'length'	=> 9,
	 *		]
	 * ];`
	 * @var \stdClass[]
	 */
	protected $reverseSections	= NULL;
	
	/**
	 * Optional, param name, which is founded in internal initialization process 
	 * inside `pattern` or inside `reverse` property as the last one. And after 
	 * it's value, there could be only trailing slash or nothing (pattern end). 
	 * This trailing slash param definition is used to automatically trim last 
	 * param value from right site when route is matched and rewrite params parsed.
	 * @var \string|NULL
	 */
	protected $lastPatternParam	= NULL;

	/**
	 * Array with route reverse pattern flags. First item is integer flag about
	 * defined scheme in `pattern` (or `reverse`), second flag is about domain parts
	 * founded in `pattern` (or `reverse`) and third flag is about existing query 
	 * string part in `pattern` (or in `reverse`)
	 * absolute or relative reverse form and second item is about query string
	 * inside reverse string.
	 * @var \int[]
	 */
	protected $flags			= [
		/* \MvcCore\IRoute::FLAG_SHEME_NO, */
		/* \MvcCore\IRoute::FLAG_HOST_NO,  */
		/* \MvcCore\IRoute::FLAG_QUERY_NO, */
	];

	/**
	 * Router instance reference used mostly in route URL building process.
	 * @var \MvcCore\Router|\MvcCore\IRouter
	 */
	protected $router			= NULL;

	/**
	 * This array contains data from route constructor. If route is initialized
	 * with all params (not only by single array), this array contains the fourth 
	 * param with advanced route configuration. If route is initialized only with
	 * one single array argument, this array contains that whole configuration 
	 * single array argument. Data in this array are without no change against 
	 * initialization moment. This could be used for any advanced property to 
	 * define in extended class.
	 * @var array
	 */
	protected $config			= [];

	/**
	 * Copied and cached value from router configuration property:
	 * `\MvcCore\Router::$trailingSlashBehaviour`.
	 * @var int|NULL
	 */
	private $_trailingSlashBehaviour = NULL;
}
