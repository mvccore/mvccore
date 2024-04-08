<?php

include_once(__DIR__ . '/../../Base.phpt');
include_once(__DIR__ . '/../../../src/MvcCore/Tool.php');

use \Tester\Assert,
	\MvcCore\Tool;

class JsonTest extends Base {

	public function testIsJsonString () {
		$trueData = [
			'1',
			'3.14',
			'-1',
			'"2"',
			'"Abc"',
			'true',
			'false',
			'[1]',
			'[-1,2]',
			'{"val":1}',
			'{"a":"A","b":false}'
		];
		$falseData = [
			"'Abc'",
			'"Abc""',
			'3 .14',
			'1,2',
			'-1,2',
			'{1}',
			'{-1,2}',
			'{"a":"A",b:false}',
			'["Hello",3.14,,true]'
		];
		foreach ($trueData as $trueItem) {
			$isJson = Tool::IsJsonString($trueItem);
			Assert::equal($isJson, TRUE);
		}
		foreach ($falseData as $falseItem) {
			$isNotJson = Tool::IsJsonString($falseItem);
			Assert::equal($isNotJson, FALSE);
		}
	}

}

run(function () {
	(new JsonTest)->run();
});