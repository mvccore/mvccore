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
	 * TODO: neaktualni?
	 * Default constraint used for all rewritten params, if no
	 * constraint for rewritten param has been specified.
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
	 *   those characters will be added automatically.
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
	 * Route rewritten params default values and also any other params default values.
	 * It could be used for any application request input - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `array("name" => "default-name", "color" => "red",);`.
	 * @var array|\array[]
	 */
	protected $defaults		= [];

	/**
	 * TODO: neaktualni
	 * Array with param names and their custom regular expression
	 * matching rules. Not required, for all rewritten params there is used
	 * default matching rule from `\MvcCore\Route::$defaultPathConstraint`.
	 * It should be changed to any value. The value is `"[^/]*"` by default.
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

	protected $groupName		= NULL;

	/**
	 * TODO: neaktuální
	 * Array with `string` keys by all reverse pattern params and with values by 
	 * every param occurance start, length and required in reverse pattern string.
	 * This array is parsed automatically by method `\MvcCore\Route::initMatch();` 
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
	 * (pattern end). This trailing slash param definition automatically trims this last param
	 * value for right trailing slash when route is matched.
	 *
	 * This property is automatically completed by method `\MvcCore\Route::initMatch()`,
	 * when there is parsed `\MvcCore\Route::$pattern` string into `\MvcCore\Route::$match` property
	 * or it is automatically completed by method `\MvcCore\Route::initReverse()`, when
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

	protected $config			= [];

	/**
	 * Copied and cached value from router configuration property:
	 * `\MvcCore\Router::$trailingSlashBehaviour`.
	 * @var int|NULL
	 */
	private $_trailingSlashBehaviour = NULL;
}
