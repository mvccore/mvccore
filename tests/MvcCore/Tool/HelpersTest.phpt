<?php

include_once(__DIR__ . '/../../Base.phpt');
include_once(__DIR__ . '/../../../src/MvcCore/Tool.php');

use \Tester\Assert,
	\MvcCore\Tool;

class HelpersTest extends Base {

	public function testRealPathVirtual () {
		$data = [
			'./some/thing'			=> 'some/thing',
			'./../some/thing'		=> '../some/thing',
			'./../../some/thing'	=> '../../some/thing',
			'../some/thing'			=> '../some/thing',
			'../../some/thing'		=> '../../some/thing',
			'./some/../thing'		=> 'thing',
			'./some/thing/'			=> 'some/thing',
			'./some/thing.png'		=> 'some/thing.png',
			'~/some/thing.png'		=> '~/some/thing.png',
			'~/some/../thing'		=> '~/thing',
			'~/../../some/thing'	=> '~/../../some/thing',
		];
		foreach ($data as $value => $expected) {
			$actual = Tool::RealPathVirtual($value);
			//bdump([$value, $expected, $actual]);
			Assert::equal($expected, $actual);
		}
	}

}

run(function () {
	(new HelpersTest)->run();
});