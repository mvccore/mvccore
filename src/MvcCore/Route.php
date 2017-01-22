<?php

/**
 * MvcCore
 *
 * This source file is subject to the BSD 3 License
 * For the full copyright and license information, please view 
 * the LICENSE.md file that are distributed with this source code.
 *
 * @copyright	Copyright (c) 2016 Tom Flídr (https://github.com/mvccore/mvccore)
 * @license		https://mvccore.github.io/docs/mvccore/3.0.0/LICENCE.md
 */

class MvcCore_Route {
    /**
     * Route name, your custom keyword/term 
	 * or pascal case combination of 'Controller:Action'.
     * @var string
     */
    public $Name		= '';
	/**
	 * Controller name in pascal case.
	 * @var string
	 */
	public $Controller	= '';
	/**
	 * Action name in pascal case.
	 * @var string
	 */
	public $Action		= '';
	/**
	 * Route preg_match pattern in classic PHP form:
	 * "#^/url\-begin/([^/]*)/([^/]*)/(.*)#".
	 * @var string
	 */
    public $Pattern		= '';
	/**
	 * Route reverse address form from preg_replace pattern 
	 * in form: "/url-begin/{%first}/{%second}/{%third}".
	 * @var string
	 */
	public $Reverse		= '';
	/**
	 * Route params with default values in form:
	 * array('first' => 1, 'second' => 2, 'third' => 3).
	 * @var array
	 */
	public $Params		= array();


	/**
	 * Get new instance by array or stdClass, if created, return it
	 * @param array|stdClass $object route configuration data
	 * @return MvcCore_Route
	 */
	public static function GetInstance ($object) {
		if (gettype($object) == 'array') {
			return new static($object);
		} else {
			return new static((array) $object);
		}
	}


	/**
	 * Create new route
	 * @param $nameOrConfig		string|array	required
	 * @param $controller		string			optional
	 * @param $action			string			optional
	 * @param $pattern			string			optional
	 * @param $reverse			string			optional
	 * @param $params			array			optional
	 */
	public function __construct ($nameOrConfig = NULL, $controller = NULL, $action = NULL, $pattern = NULL, $reverse = NULL, $params = array()) {
		$args = func_get_args();
		if (count($args) == 1 && gettype($args[0]) == 'array') {
			$data = (object) $args[0];
			$name = isset($data->name) ? $data->name : '';
			$controller = isset($data->controller) ? $data->controller : '';
			$action = isset($data->action) ? $data->action : '';
			$pattern = isset($data->pattern) ? $data->pattern : '';
			$reverse = isset($data->reverse) ? $data->reverse : '';
			$params = isset($data->params) ? $data->params : array();
		} else {
			list($name, $controller, $action, $pattern, $reverse, $params) = $args;
		}
		if (!$controller && !$action && strpos($name, ':') !== FALSE) {
			list($controller, $action) = explode(':', $name);
		}
		$this->Name = $name;
		$this->Controller = $controller;
		$this->Action = $action;
		$this->Pattern = $pattern;
		$this->Reverse = $reverse ? $reverse : trim($pattern, '#^$');
		$this->Params = $params;
	}

	/**
	 * Set route name, your custom keyword/term
	 * or pascal case combination of 'Controller:Action'
	 * @param string $name 
	 * @return MvcCore_Route
	 */
	public function SetName ($name) {
		$this->Name = $name;
		return $this;
	}
	/**
	 * Set controller name in pascal case.
	 * @param string $controller 
	 * @return MvcCore_Route
	 */
	public function SetController ($controller) {
		$this->Controller = $controller;
		return $this;
	}
	/**
	 * Set action name in pascal case.
	 * @param string $action 
	 * @return MvcCore_Route
	 */
	public function SetAction ($action) {
		$this->Action = $action;
		return $this;
	}
	/**
	 * Set route preg_match pattern in classic PHP form:
	 * "#^/url\-begin/([^/]*)/([^/]*)/(.*)#".
	 * @param string $pattern 
	 * @return MvcCore_Route
	 */
	public function SetPattern ($pattern) {
		$this->Pattern = $pattern;
		return $this;
	}
	/**
	 * Set route reverse address form from preg_replace pattern
	 * in form: "/url-begin/{%first}/{%second}/{%third}".
	 * @param string $reverse 
	 * @return MvcCore_Route
	 */
	public function SetReverse ($reverse) {
		$this->Reverse = $reverse;
		return $this;
	}
	/**
	 * Set route params with default values in form:
	 * array('first' => 1, 'second' => 2, 'third' => 3).
	 * @param array $params 
	 * @return MvcCore_Route
	 */
	public function SetParams ($params = array()) {
		$this->Params = $params;
		return $this;
	}
}