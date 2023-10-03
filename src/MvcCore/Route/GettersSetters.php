<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flidr (https://github.com/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md
 */

namespace MvcCore\Route;

/**
 * @mixin \MvcCore\Route
 */
trait GettersSetters {

	/**
	 *
	 * @inheritDoc
	 * @return string|array|NULL
	 */
	public function GetPattern () {
		return $this->pattern;
	}

	/**
	 * @inheritDoc
	 * @param  string|array $pattern
	 * @return \MvcCore\Route
	 */
	public function SetPattern ($pattern) {
		$this->pattern = $pattern;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|array|NULL
	 */
	public function GetMatch () {
		return $this->match;
	}

	/**
	 * @inheritDoc
	 * @param  string|array $match
	 * @return \MvcCore\Route
	 */
	public function SetMatch ($match) {
		$this->match = $match;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|array|NULL
	 */
	public function GetReverse () {
		return $this->reverse;
	}

	/**
	 * @inheritDoc
	 * @param  string|array $reverse
	 * @return \MvcCore\Route
	 */
	public function SetReverse ($reverse) {
		$this->reverse = $reverse;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetName () {
		return $this->name;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $name
	 * @return \MvcCore\Route
	 */
	public function SetName ($name) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetController () {
		return $this->controller;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $controller
	 * @return \MvcCore\Route
	 */
	public function SetController ($controller) {
		$this->controller = $controller;
		if ($this->controller)
			$this->initCtrlHasAbsNamespace();
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetAction () {
		return $this->action;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $action
	 * @return \MvcCore\Route
	 */
	public function SetAction ($action) {
		$this->action = $action;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string
	 */
	public function GetControllerAction () {
		return $this->controller . ':' . $this->action;
	}

	/**
	 * @inheritDoc
	 * @param  string $controllerAction
	 * @return \MvcCore\Route
	 */
	public function SetControllerAction ($controllerAction) {
		list($ctrl, $action) = explode(':', $controllerAction);
		if ($ctrl) {
			$this->controller = $ctrl;
			$this->initCtrlHasAbsNamespace();
		}
		if ($action)	$this->action = $action;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array|\array[]
	 */
	public function GetDefaults () {
		return $this->defaults;
	}

	/**
	 * @inheritDoc
	 * @param  array|\array[] $defaults
	 * @return \MvcCore\Route
	 */
	public function SetDefaults ($defaults = []) {
		$this->defaults = $defaults;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array|\array[]
	 */
	public function GetConstraints () {
		return $this->constraints;
	}

	/**
	 * @inheritDoc
	 * @param  array|\array[] $constraints
	 * @return \MvcCore\Route
	 */
	public function SetConstraints ($constraints = []) {
		$this->constraints = $constraints;
		foreach ($constraints as $key => $value)
			if (!isset($this->defaults[$key]))
				$this->defaults[$key] = NULL;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array|\callable[]
	 */
	public function GetFilters () {
		$filters = [];
		foreach ($this->filters as $direction => $handler) 
			$filters[$direction] = $handler[1];
		return $filters;
	}

	/**
	 * @inheritDoc
	 * @param  array|\callable[] $filters 
	 * @return \MvcCore\Route
	 */
	public function SetFilters (array $filters = []) {
		/** @var $filters array|\callable[] */
		foreach ($filters as $direction => $handler) 
			$this->SetFilter($handler, $direction);
		return $this;
	}

	/**
	 * @inheritDoc
	 * @param  string $direction Strings `in` or `out`. You can use predefined constants:
	 *                           - `\MvcCore\IRoute::CONFIG_FILTER_IN`
	 *                           - `\MvcCore\IRoute::CONFIG_FILTER_OUT`
	 * @return \callable|NULL
	 */
	public function GetFilter ($direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		return isset($this->filters[$direction])
			? $this->filters[$direction]
			: NULL;
	}

	/**
	 * @inheritDoc
	 * @param  \callable $handler 
	 * @param  string    $direction
	 * @return \MvcCore\Route
	 */
	public function SetFilter ($handler, $direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
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
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetMethod () {
		return $this->method;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $method
	 * @return \MvcCore\Route
	 */
	public function SetMethod ($method = NULL) {
		$this->method = strtoupper($method);
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetRedirect () {
		return $this->redirect;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $redirectRouteName 
	 * @return \MvcCore\Route
	 */
	public function SetRedirect ($redirectRouteName = NULL) {
		$this->redirect = $redirectRouteName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetAbsolute () {
		return $this->absolute || ($this->flags & static::FLAG_SCHEME_ANY) != 0;
	}

	/**
	 * @inheritDoc
	 * @param  bool $absolute 
	 * @return \MvcCore\Route
	 */
	public function SetAbsolute ($absolute = TRUE) {
		$this->absolute = $absolute;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return string|NULL
	 */
	public function GetGroupName () {
		return $this->groupName;
	}

	/**
	 * @inheritDoc
	 * @param  string|NULL $groupName 
	 * @return \MvcCore\Route
	 */
	public function SetGroupName ($groupName) {
		$this->groupName = $groupName;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return \string[]|NULL
	 */
	public function GetReverseParams () {
		return $this->reverseParams !== NULL 
			? array_keys($this->reverseParams)
			: [];
	}

	/**
	 * @inheritDoc
	 * @param  array $matchedParams
	 * @return \MvcCore\Route
	 */
	public function SetMatchedParams ($matchedParams = []) {
		$this->matchedParams = $matchedParams;
		return $this;
	}

	/**
	 * @inheritDoc
	 * @return array|NULL
	 */
	public function GetMatchedParams () {
		return $this->matchedParams;
	}
	
	/**
	 * @inheritDoc
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		return $this->router;
	}
	
	/**
	 * @inheritDoc
	 * @param  \MvcCore\Router $router 
	 * @return \MvcCore\Route
	 */
	public function SetRouter (\MvcCore\IRouter $router) {
		/** @var \MvcCore\Router $router */
		$this->router = $router;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return bool
	 */
	public function GetControllerHasAbsoluteNamespace () {
		return ($this->flags & static::FLAG_CONTROLLER_ABSOLUTE_NAMESPACE) != 0;
	}

	/**
	 * @inheritDoc
	 * @param  string $propertyName 
	 * @return mixed
	 */
	public function GetAdvancedConfigProperty ($propertyName) {
		$result = NULL;
		if (isset($this->config[$propertyName]))
			$result = $this->config[$propertyName];
		return $result;
	}
}
