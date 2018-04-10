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

namespace MvcCore\Interfaces;

/**
 * Responsibilities:
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
interface IRoute
{
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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public static function GetInstance ($object);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetPattern ($pattern);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetMatch ($match);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetReverse ($reverse);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetName ($name);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetController ($controller);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetAction ($action);

	/**
	 * Set target controller name and controller action name to dispatch
	 * to gether in one setter, in pascal case, separated by colon.
	 *
	 * Example: `"Products:List"`
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetControllerAction ($controllerAction);

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetDefaults ($defaults = array());

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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetConstraints ($constraints = array());


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
	 * @return \MvcCore\Interfaces\IRoute
	 */
	public function & SetLastPatternParam ($lastPatternParam = '');

	/**
	 * Return array of matched params, with matched controller and action names,
	 * if route matches request `\MvcCore\Request::$Path` property by `preg_match_all()`.
	 *
	 * This method is usually called in core request routing process
	 * from `\MvcCore\Router::Route();` method and it's submethods.
	 *
	 * @param \MvcCore\Request $request
	 * @return array Matched and params array, keys are matched
	 *				 params or controller and action params.
	 */
	public function Match (& $request);

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
	public function Prepare ();

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
	public function Url (& $params);
}
