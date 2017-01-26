# MvcCore

[![Latest Stable Version](https://img.shields.io/badge/Stable-v3.2.0-brightgreen.svg?style=plastic)](https://github.com/mvccore/mvccore/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/3.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

PHP MVC framework to develop and pack projects (partialy or completely) into super fast single file apps and tools.

## Installation
```shell
composer require mvccore/mvccore
```

## Main features
- MVC core framework for classic web apps with:
	- Long_Classes_Names structure or \Namespaces\Classes\Names
	- any request types and HTML/AJAX responses
	- automatic Controller/Action routing by query params or routes
	- automatic url generating
	- unlimited controllers and views structure
	- views, sub views and layout views rendering and view helpers
	- automatic model classes connecting into databases with PDO
	- system and custom configuration files
	- session namespaces management
	- custom debug tools and logging
	- possibility to extend MvcCore core classes
	- request and response object customization
	- with no unused code, everything spacial is MvcCore extension
- partial or complete application packaging into single PHP file by [**Packager (mvccore/packager)**](https://github.com/mvccore/packager)
	- including only file extensions you want
	- or including all files (binary or text, doesn't metter, everything is possible to pack)
- packing to PHAR package (slower) or to PHP single file (faster)
  - application build into PHP package is **very fast on FastCGI and OPcache extension enabled**
  - packed application has **constant execution times** and it's generaly about 35% faster then 
    development version also running on FastCGI/OPcache
- main packing configuration features:
	- including/excluding folders by regular expressions
	- result code regexp and string replacements
	- PHTML templates minification
	- PHP scripts minification (@var doc comments possible to keep)
	- **AUTOMATIC ORDER DETECTION** for packed PHP scripts (PHP packing only)
	- posibility to wrap/keep mostly any original PHP filesystem function to load files from PHP package (PHP packing only)
	- developed app is possible to pack/build into single PHP file with [**Packager library - mvccore/packager**](https://github.com/mvccore/packager)):
	- possible result pack types:
	  - **PHAR file**
		- standard PHAR package with whole devel directory content
	  - **PHP file**
		- **strict package mode**
		  - everything is contained in result `index.php`
		  - only `.htaccess` or `web.config` are necessary to use mod_rewrite
		- **preserve package mode**
		  - result `index.php` file contains PHP files, 
			PHTML templates but no CSS/JS/fonts or images
		  - all wrapped file system functions are looking inside 
			package first, then they try to read data from HDD
		  - currently used for packed app in result directory
		- **preserve hdd mode**
		  - result `index.php` file contains PHP files, 
			PHTML templates but no CSS/JS/fonts or images
		  - all wrapped file system functions are looking on HDD first, 
			then they try to read data from package inself
		- **strict hdd mode**
		  - result `index.php` file contains only PHP files, 
			but PHTML templates, all CSS/JS/fonts and images are on HDD
		  - no PHP file system function is wrapped
- minifycation for PHP/HTML/CSS/JS by third party tools supported
- url rewrite with .htaccess or web.config still possible with packed application
- desktop and mobile website versions by MvcCore extensions (multilanguge apps also possible)
- posibility to use any third party library or framework in Libs folder through MvcCore autoloader or by composer

## Usage
- check out examples:
	- [**Hello World**](https://github.com/mvccore/example-helloworld)
	- [**Pig Latin Translator**](https://github.com/mvccore/example-translator)
	- [**CD Collection**](https://github.com/mvccore/example-cdcol)
	- begin with Hello World example and read the source code:
		- Hello world example has only 2 important controllers (Default.php and Base.php)
		- MvcCore framework has only 12 core classes (well documented comments, not everything used every time)
- check out more complex applications:
	- [**Questionnaires**](https://github.com/mvccore/app-questionnaires)
- to develop new application - work in development directory
- to build single file application - use make.cmd and configure build proces in make-php.php or make-phar.php (see examples)
- test your builded application in release directory
