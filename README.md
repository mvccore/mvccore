# MvcCore

[![Latest Stable Version](https://img.shields.io/badge/Stable-v3.1.2-brightgreen.svg?style=plastic)](https://github.com/mvccore/example-helloworld/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://github.com/mvccore/example-helloworld/blob/master/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

PHP MVC framework to develop and pack projects (partialy or completely) into super fast single file apps and tools.

## Installation
```shell
composer require mvccore/simpleform
```

## Main features
- MVC core framework for classic web apps with any request types and HTML/AJAX responses
- about 35% faster results from packed app then development version with separate PHP scripts and fastcgi/op_cache
- partial or complete application packaging into a single file by [**Packager (mvccore/packager)**](https://github.com/mvccore/packager)
	- including only file extensions you want
	- or including all files (binary or text, doesn't metter)
- packing to PHAR package (slower) or to PHP single file (faster)
- main packing configuration features:
	- including/excluding folders by regular expression
	- result code regexp and string replacements
	- PHTML templates minification
	- PHP scripts minification
	- automatic detection for PHP scripts order (PHP packing)
	- posibility to define any original PHP filesystem function to wrap/replace (PHP packing)
	- 4 modes for orginaly replaced PHP filesystem functions behaviour (PHP packing)
		- strict package
		- strict hdd
		- preserve package
		- preserve hdd
- minimization for PHP/HTML/CSS/JS by third party tools supported
- url rewrite with .htaccess or web.config
- desktop and mobile website versions (multilanguge apps also possible)
- posibility to use any third party library or framework in Libs folder through autoloader or by composer

## Usage
- check out examples:
	- [**Hello World**](https://github.com/mvccore/example-helloworld)
	- [**Pig Latin Translator**](https://github.com/mvccore/example-translator)
	- [**CD Collection**](https://github.com/mvccore/example-cdcol)
	- begin with Hello world example and read the code:
		- MvcCore frameworks has only 3 files (647 lines, including comments, cca 215 lines per file)
		- Hello world example has only 2 important controllers (Default.php and Base.php)
- check out more complex applications:
	- [**Questionnaires**](https://github.com/mvccore/app-questionnaires)
- to develop application - work in development directory
- to build single file application - use make.cmd and configure build proces in php.php or phar.php
- test your builded application in release directory
