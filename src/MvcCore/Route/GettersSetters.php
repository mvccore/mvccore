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

trait GettersSetters
{
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
	public function GetPattern () {
		/** @var $this \MvcCore\Route */
		return $this->pattern;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetPattern ($pattern) {
		/** @var $this \MvcCore\Route */
		$this->pattern = $pattern;
		return $this;
	}

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
	public function GetMatch () {
		/** @var $this \MvcCore\Route */
		return $this->match;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetMatch ($match) {
		/** @var $this \MvcCore\Route */
		$this->match = $match;
		return $this;
	}

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
	public function GetReverse () {
		/** @var $this \MvcCore\Route */
		return $this->reverse;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetReverse ($reverse) {
		/** @var $this \MvcCore\Route */
		$this->reverse = $reverse;
		return $this;
	}

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
	public function GetName () {
		/** @var $this \MvcCore\Route */
		return $this->name;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetName ($name) {
		/** @var $this \MvcCore\Route */
		$this->name = $name;
		return $this;
	}

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
	public function GetController () {
		/** @var $this \MvcCore\Route */
		return $this->controller;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetController ($controller) {
		/** @var $this \MvcCore\Route */
		$this->controller = $controller;
		return $this;
	}

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
	public function GetAction () {
		/** @var $this \MvcCore\Route */
		return $this->action;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetAction ($action) {
		/** @var $this \MvcCore\Route */
		$this->action = $action;
		return $this;
	}

	/**
	 * Get target controller name/path and controller action name together in 
	 * one getter, in pascal case, separated by colon. There are also contained 
	 * controller namespace. Read more about controller name/path definition 
	 * possibilities in `\MvcCore\Route::GetController();` getter method.
	 *
	 * Example: `"Products:List" | "\Front\Business\Products:Gallery"`
	 * @return string
	 */
	public function GetControllerAction () {
		/** @var $this \MvcCore\Route */
		return $this->controller . ':' . $this->action;
	}

	/**
	 * Set target controller name/path and controller action name together in 
	 * one setter, in pascal case, separated by colon. There are also contained 
	 * controller namespace. Read more about controller name/path definition 
	 * possibilities in `\MvcCore\Route::SetController();` setter method.
	 *
	 * Example: `"Products:List" | "\Front\Business\Products:Gallery"`
	 * @return \MvcCore\Route
	 */
	public function SetControllerAction ($controllerAction) {
		/** @var $this \MvcCore\Route */
		list($ctrl, $action) = explode(':', $controllerAction);
		if ($ctrl)		$this->controller = $ctrl;
		if ($action)	$this->action = $action;
		return $this;
	}

	/**
	 * Get route rewrite params default values and also any other query string 
	 * params default values. It could be used for any application request 
	 * param from those application inputs - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `["name" => "default-name", "color" => "red",]`.
	 * @return array|\array[]
	 */
	public function & GetDefaults () {
		/** @var $this \MvcCore\Route */
		return $this->defaults;
	}

	/**
	 * Set route rewrite params default values and also any other query string 
	 * params default values. It could be used for any application request 
	 * param from those application inputs - `$_GET`, `$_POST` or `php://input`.
	 *
	 * Example: `["name" => "default-name", "color" => "red",]`.
	 * @param array|\array[] $defaults
	 * @return \MvcCore\Route
	 */
	public function SetDefaults ($defaults = []) {
		/** @var $this \MvcCore\Route */
		$this->defaults = $defaults;
		return $this;
	}

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
	public function GetConstraints () {
		/** @var $this \MvcCore\Route */
		return $this->constraints;
	}

	/**
	 * Set array with param names and their custom regular expression matching 
	 * rules. Not required, for all rewrite params there is used default 
	 * matching rules from route static properties `defaultDomainConstraint` or
	 * `defaultPathConstraint`. It should be changed to any value. Default value 
	 * is `"[^.]+"` for domain part and `"[^/]+"` for path part.
	 *
	 * Example: `["name"	=> "[^/]+", "color"	=> "[a-z]+",]`
	 * @param array|\array[] $constraints
	 * @return \MvcCore\Route
	 */
	public function SetConstraints ($constraints = []) {
		/** @var $this \MvcCore\Route */
		$this->constraints = $constraints;
		foreach ($constraints as $key => $value)
			if (!isset($this->defaults[$key]))
				$this->defaults[$key] = NULL;
		return $this;
	}

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
	public function & GetFilters () {
		/** @var $this \MvcCore\Route */
		$filters = [];
		foreach ($this->filters as $direction => $handler) 
			$filters[$direction] = $handler[1];
		return $filters;
	}

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
	 * @return \MvcCore\Route
	 */
	public function SetFilters (array $filters = []) {
		/** @var $this \MvcCore\Route */
		foreach ($filters as $direction => $handler) 
			$this->SetFilter($handler, $direction);
		return $this;
	}

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
	public function GetFilter ($direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		/** @var $this \MvcCore\Route */
		return isset($this->filters[$direction])
			? $this->filters[$direction]
			: NULL;
	}

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
	public function SetFilter ($handler, $direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		/** @var $this \MvcCore\Route */
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
	 * Get http method to only match requests with this defined method. If `NULL`, 
	 * request with any http method could be matched by this route. Value has to 
	 * be in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @return string|NULL
	 */
	public function GetMethod () {
		/** @var $this \MvcCore\Route */
		return $this->method;
	}

	/**
	 * Set http method to only match requests with this defined method. If `NULL`, 
	 * request with any http method could be matched by this route. Value has to 
	 * be in upper case.
	 * Example: `"POST" | \MvcCore\IRequest::METHOD_POST`
	 * @param string|NULL $method
	 * @return \MvcCore\Route
	 */
	public function SetMethod ($method = NULL) {
		/** @var $this \MvcCore\Route */
		$this->method = strtoupper($method);
		return $this;
	}
	
	/**
	 * Get other route unique name to redirect request to. To this target route are 
	 * passed params parsed from this matched route. This property is used for 
	 * routes handling old pages or old request forms, redirecting those request
	 * to new form.
	 * Example: `"new_route_name"`
	 * @return string|NULL
	 */
	public function GetRedirect () {
		/** @var $this \MvcCore\Route */
		return $this->redirect;
	}

	/**
	 * Set other route unique name to redirect request to. To this target route are 
	 * passed params parsed from this matched route. This property is used for 
	 * routes handling old pages or old request forms, redirecting those request
	 * to new form.
	 * Example: `"new_route_name"`
	 * @param string|NULL $redirectRouteName 
	 * @return \MvcCore\Route
	 */
	public function SetRedirect ($redirectRouteName = NULL) {
		/** @var $this \MvcCore\Route */
		$this->redirect = $redirectRouteName;
		return $this;
	}

	/**
	 * Return `TRUE` if route `pattern` (or `reverse`) contains domain part with 
	 * two slashes at the beginning or if route is defined with `absolute` 
	 * boolean flag by advanced configuration in constructor or by setter.
	 * @return bool
	 */
	public function GetAbsolute () {
		/** @var $this \MvcCore\Route */
		return $this->absolute || (isset($this->flags[0]) && ((bool)$this->flags[0]));
	}

	/**
	 * Set boolean about to generate absolute URL addresses. If `TRUE`, there is 
	 * always generated absolute URL form. If `FALSE`, absolute URL address is 
	 * generated only if `pattern` (or `reverse`) property contains absolute 
	 * matching form.
	 * @param bool $absolute 
	 * @return \MvcCore\Route
	 */
	public function SetAbsolute ($absolute = TRUE) {
		/** @var $this \MvcCore\Route */
		$this->absolute = $absolute;
		return $this;
	}

	/**
	 * Get route group name to belongs to. Group name is always first word parsed
	 * from request path. First word is content between two first slashes in 
	 * request path. If group name is `NULL`, route belongs to default group 
	 * and that group is used when no other group matching the request path.
	 * @return string|NULL
	 */
	public function GetGroupName () {
		/** @var $this \MvcCore\Route */
		return $this->groupName;
	}

	/**
	 * Set route group name to belongs to. Group name is always first word parsed
	 * from request path. First word is content between two first slashes in 
	 * request path. If group name is `NULL`, route belongs to default group 
	 * and that group is used when no other group matching the request path.
	 * @param string|NULL $groupName 
	 * @return \MvcCore\Route
	 */
	public function SetGroupName ($groupName) {
		/** @var $this \MvcCore\Route */
		$this->groupName = $groupName;
		return $this;
	}

	/**
	 * Return only reverse params names as `string`s array. Reverse params array
	 * is array with all rewrite params founded in `patter` (or `reverse`) string.
	 * Example: `["name", "color"];`
	 * @return \string[]|NULL
	 */
	public function GetReverseParams () {
		/** @var $this \MvcCore\Route */
		return $this->reverseParams !== NULL 
			? array_keys($this->reverseParams)
			: [];
	}

	/**
	 * Set manually matched params from rewrite route matching process into 
	 * current route object. Use this method only on currently matched route!
	 * Passed array must have keys as param names and values as matched values
	 * and it must contain all only matched rewrite params and route controller 
	 * and route action if any.
	 * @param array $matchedParams
	 * @return \MvcCore\Route
	 */
	public function SetMatchedParams ($matchedParams = []) {
		/** @var $this \MvcCore\Route */
		$this->matchedParams = $matchedParams;
		return $this;
	}

	/**
	 * Get matched params from rewrite route matching process into current route 
	 * object. Use this method only on currently matched route! Passed array 
	 * must have keys as param names and values as matched values and it must 
	 * contain all only matched rewrite params and route controller and route 
	 * action if any.
	 * @return array|NULL
	 */
	public function & GetMatchedParams () {
		/** @var $this \MvcCore\Route */
		return $this->matchedParams;
	}
	
	/**
	 * Get router instance reference, used mostly in route URL building process.
	 * @return \MvcCore\Router|\MvcCore\IRouter
	 */
	public function GetRouter () {
		/** @var $this \MvcCore\Route */
		return $this->router;
	}
	
	/**
	 * Set router instance reference, used mostly in route URL building process.
	 * @param \MvcCore\Router|\MvcCore\IRouter $router 
	 * @return \MvcCore\Route
	 */
	public function SetRouter (\MvcCore\IRouter $router) {
		/** @var $this \MvcCore\Route */
		$this->router = $router;
		return $this;
	}

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
	public function GetAdvancedConfigProperty ($propertyName) {
		/** @var $this \MvcCore\Route */
		$result = NULL;
		if (isset($this->config[$propertyName]))
			$result = $this->config[$propertyName];
		return $result;
	}
}
