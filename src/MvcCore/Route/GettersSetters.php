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

trait GettersSetters {

	/**
	 *
	 * @inheritDocs
	 * @return string|array|NULL
	 */
	public function GetPattern () {
		/** @var $this \MvcCore\Route */
		return $this->pattern;
	}

	/**
	 * @inheritDocs
	 * @param  string|array $pattern
	 * @return \MvcCore\Route
	 */
	public function SetPattern ($pattern) {
		/** @var $this \MvcCore\Route */
		$this->pattern = $pattern;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|array|NULL
	 */
	public function GetMatch () {
		/** @var $this \MvcCore\Route */
		return $this->match;
	}

	/**
	 * @inheritDocs
	 * @param  string|array $match
	 * @return \MvcCore\Route
	 */
	public function SetMatch ($match) {
		/** @var $this \MvcCore\Route */
		$this->match = $match;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|array|NULL
	 */
	public function GetReverse () {
		/** @var $this \MvcCore\Route */
		return $this->reverse;
	}

	/**
	 * @inheritDocs
	 * @param  string|array $reverse
	 * @return \MvcCore\Route
	 */
	public function SetReverse ($reverse) {
		/** @var $this \MvcCore\Route */
		$this->reverse = $reverse;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetName () {
		/** @var $this \MvcCore\Route */
		return $this->name;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $name
	 * @return \MvcCore\Route
	 */
	public function SetName ($name) {
		/** @var $this \MvcCore\Route */
		$this->name = $name;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetController () {
		/** @var $this \MvcCore\Route */
		return $this->controller;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $controller
	 * @return \MvcCore\Route
	 */
	public function SetController ($controller) {
		/** @var $this \MvcCore\Route */
		$this->controller = $controller;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetAction () {
		/** @var $this \MvcCore\Route */
		return $this->action;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $action
	 * @return \MvcCore\Route
	 */
	public function SetAction ($action) {
		/** @var $this \MvcCore\Route */
		$this->action = $action;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string
	 */
	public function GetControllerAction () {
		/** @var $this \MvcCore\Route */
		return $this->controller . ':' . $this->action;
	}

	/**
	 * @inheritDocs
	 * @param  string $controllerAction
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
	 * @inheritDocs
	 * @return array|\array[]
	 */
	public function & GetDefaults () {
		/** @var $this \MvcCore\Route */
		return $this->defaults;
	}

	/**
	 * @inheritDocs
	 * @param  array|\array[] $defaults
	 * @return \MvcCore\Route
	 */
	public function SetDefaults ($defaults = []) {
		/** @var $this \MvcCore\Route */
		$this->defaults = $defaults;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array|\array[]
	 */
	public function GetConstraints () {
		/** @var $this \MvcCore\Route */
		return $this->constraints;
	}

	/**
	 * @inheritDocs
	 * @param  array|\array[] $constraints
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
	 * @inheritDocs
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
	 * @inheritDocs
	 * @param  array|\callable[] $filters 
	 * @return \MvcCore\Route
	 */
	public function SetFilters (array $filters = []) {
		/** @var $this \MvcCore\Route */
		/** @var $filters array|\callable[] */
		foreach ($filters as $direction => $handler) 
			$this->SetFilter($handler, $direction);
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $direction Strings `in` or `out`. You can use predefined constants:
	 *                           - `\MvcCore\IRoute::CONFIG_FILTER_IN`
	 *                           - `\MvcCore\IRoute::CONFIG_FILTER_OUT`
	 * @return \callable|NULL
	 */
	public function GetFilter ($direction = \MvcCore\IRoute::CONFIG_FILTER_IN) {
		/** @var $this \MvcCore\Route */
		return isset($this->filters[$direction])
			? $this->filters[$direction]
			: NULL;
	}

	/**
	 * @inheritDocs
	 * @param  \callable $handler 
	 * @param  string    $direction
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
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetMethod () {
		/** @var $this \MvcCore\Route */
		return $this->method;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $method
	 * @return \MvcCore\Route
	 */
	public function SetMethod ($method = NULL) {
		/** @var $this \MvcCore\Route */
		$this->method = strtoupper($method);
		return $this;
	}
	
	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetRedirect () {
		/** @var $this \MvcCore\Route */
		return $this->redirect;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $redirectRouteName 
	 * @return \MvcCore\Route
	 */
	public function SetRedirect ($redirectRouteName = NULL) {
		/** @var $this \MvcCore\Route */
		$this->redirect = $redirectRouteName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return bool
	 */
	public function GetAbsolute () {
		/** @var $this \MvcCore\Route */
		return $this->absolute || (isset($this->flags[0]) && ((bool)$this->flags[0]));
	}

	/**
	 * @inheritDocs
	 * @param  bool $absolute 
	 * @return \MvcCore\Route
	 */
	public function SetAbsolute ($absolute = TRUE) {
		/** @var $this \MvcCore\Route */
		$this->absolute = $absolute;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return string|NULL
	 */
	public function GetGroupName () {
		/** @var $this \MvcCore\Route */
		return $this->groupName;
	}

	/**
	 * @inheritDocs
	 * @param  string|NULL $groupName 
	 * @return \MvcCore\Route
	 */
	public function SetGroupName ($groupName) {
		/** @var $this \MvcCore\Route */
		$this->groupName = $groupName;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return \string[]|NULL
	 */
	public function GetReverseParams () {
		/** @var $this \MvcCore\Route */
		return $this->reverseParams !== NULL 
			? array_keys($this->reverseParams)
			: [];
	}

	/**
	 * @inheritDocs
	 * @param  array $matchedParams
	 * @return \MvcCore\Route
	 */
	public function SetMatchedParams ($matchedParams = []) {
		/** @var $this \MvcCore\Route */
		$this->matchedParams = $matchedParams;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @return array|NULL
	 */
	public function & GetMatchedParams () {
		/** @var $this \MvcCore\Route */
		return $this->matchedParams;
	}
	
	/**
	 * @inheritDocs
	 * @return \MvcCore\Router
	 */
	public function GetRouter () {
		/** @var $this \MvcCore\Route */
		return $this->router;
	}
	
	/**
	 * @inheritDocs
	 * @param  \MvcCore\Router $router 
	 * @return \MvcCore\Route
	 */
	public function SetRouter (\MvcCore\IRouter $router) {
		/** @var $this \MvcCore\Route */
		/** @var $router \MvcCore\Router */
		$this->router = $router;
		return $this;
	}

	/**
	 * @inheritDocs
	 * @param  string $propertyName 
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
