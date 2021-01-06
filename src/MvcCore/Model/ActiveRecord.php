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

namespace MvcCore\Models;

trait ActiveRecord {

	/**
	 * @inheritDocs
	 * @param int $readingFlags
	 * @param bool $getNullValues 
	 * @return array
	 */
	public function GetValues ($readingFlags = 0, $getNullValues = FALSE) {
		if ($readingFlags === 0) 
			$readingFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		$metaData = static::__getPropsMetaData($readingFlags);



		return [];
	}

	/**
	 * @inheritDocs
	 * @param array $data 
	 * @param int $readingFlags 
	 * @return \MvcCore\Model|\MvcCore\Models\ActiveRecord
	 */
	public function SetUp ($data = [], $readingFlags = 0) {
		if ($readingFlags === 0) 
			$readingFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;
		
		$metaData = static::__getPropsMetaData($readingFlags);
		xxx($metaData);


		return (object) [];
	}

	/**
	 * @inheritDocs
	 * @param int $readingFlags 
	 * @return array
	 */
	public function GetTouched ($readingFlags = 0) {
		if ($readingFlags === 0) 
			$readingFlags = \MvcCore\IModel::PROPS_INHERIT | \MvcCore\IModel::PROPS_PROTECTED;



		return [];
	}
}