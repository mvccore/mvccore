# MvcCore

[![Latest Stable Version](https://img.shields.io/badge/Stable-v4.3.1-brightgreen.svg?style=plastic)](https://github.com/mvccore/mvccore/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

PHP MVC framework to develop and pack projects (partially or completely) into super fast single file apps and tools.

## Outline  
1. [Installation](#user-content-1-installation)  
2. [Features](#user-content-2-features)  
    2.1. [Clasic MVC web framework features](#user-content-21-features---routing)  
    2.2. [`MvcCore` classes and their features](#user-content-22-features---url-generating)  
        2.2.1. [`\MvcCore\Application`](#user-content-22-features---url-generating) 
        2.2.2. [`\MvcCore\Model`](#user-content-22-features---url-generating) 
        2.2.3. [`\MvcCore\View`](#user-content-22-features---url-generating) 
        2.2.4. [`\MvcCore\Controller`](#user-content-22-features---url-generating) 
        2.2.5. [`\MvcCore\Request`](#user-content-22-features---url-generating) 
        2.2.6. [`\MvcCore\Response`](#user-content-22-features---url-generating) 
        2.2.7. [`\MvcCore\Session`](#user-content-22-features---url-generating) 
        2.2.8. [`\MvcCore\Router`](#user-content-22-features---url-generating) 
        2.2.9. [`\MvcCore\Route`](#user-content-22-features---url-generating) 
        2.2.10. [`\MvcCore\Config`](#user-content-22-features---url-generating) 
        2.2.11. [`\MvcCore\Tool`](#user-content-22-features---url-generating) 
        2.2.12. [`\MvcCore\Debug`](#user-content-22-features---url-generating) 
    2.3. [`Main extension that is definitely worth talking about](#user-content-22-features---url-generating)  
3. [Packing/Building features](#user-content-3-how-it-works)  
4. [Usage](#user-content-3-how-it-works)  


## 1. Installation
```shell
composer require mvccore/mvccore
```

## 2. Features

### 2.1. Clasic MVC web framework features
- any request types handling and HTML/XML/JSON responses
- great `Namespace\Controller:Action` routing by query params or rewrite routes
- automatic URL generating by route name or `Namespace\Controller:Action` combination
- unlimited controllers and views structure in any directories depth
- views, sub views and layout views rendering and view helpers
- automatic model classes with connections into databases with `\PDO`
- system and custom configuration files reading/writing
- automatic environment detection or detection by system config
- session namespaces and cookies management
- debug tools and logging
- special framework tools for OOP
- possibility to extend or patch (replace) any `MvcCore` core class
- no unnecessary code in framework, everything spacial is always `MvcCore` extension

### 2.2. `MvcCore` classes and their features

#### 2.2.1. `\MvcCore\Application`
- application instance singleton
- main MVC object instances storage (getters/setters)
- storage for core classes names (getters/setters)
- custom pre/post handlers for specific app lifecycle points (getters/setters)
- exceptions and errors dispatching
- application `Run()` method lifecycle:
  - request and response creation
  - debug class and logging initialization
  - router routing by rewrite routes or query string
  - routed controller creation
  - routed controller `Run()` method (dispatching):
	- controller main properties setup
	- controller `Init()` method
	- controller `PreDispatch()` method
	  - creating view instance if necessary
	- controller routed action method
	- controller `Render()` method
	  - layout and view rendering
  - application request termination
    - sending response headers
    - sending response content
    - session write and close
- there is possible to redirect or terminate the whole 
  lifecycle in any application lifecycle point

#### 2.2.2. `\MvcCore\Model`
- automatic connection into database when any database getter is called for first time
- connection is realized by direct method params or by system config database indexed sections
- connection index could be defined for whole class or in database getter method param
- models resources management
- data methods to get only dirty properties
- data methods to set up raw database data into model properties

#### 2.2.3. `\MvcCore\View`
- many extension with view helpers
  - assets, formatting numbers, money, dates, truncating, data URL, line breaks, writing content in JS etc...

#### 2.2.4. `\MvcCore\Controller`
- application logic pattern class
- template helper methods to get main MVC objects and shortcut methods
- useful build-in properties automatically filled before dispatching
- template lifecycle methods `Init()`, `PreDispatch()`, `<Custom>Action()` and `Render()`
- error template methods `RenderError()` and `RenderNotFound()`
- managing self lifecycle and any added sub-controllers or their sub-controllers
- response HTML/XML/JSON setters with termination
- view creating and rendering by automatically detected path or custom path
- single file application assets dispatching

#### 2.2.5. `\MvcCore\Request`
- request describing object, not a singleton
- getter and setter methods for any request part property
- collections storage and filtering - files, headers, cookies, params
- any describing property is parsed from given constructor global variables only 
  when is necessary (on demand), not all properties initialization at start
- `Accept-Language`header static parsing

#### 2.2.6. `\MvcCore\Response`
- response describing object, not a singleton
- getter and setter methods for any response part property - code, headers and content
- headers management - content type, encoding etc.
- safe cookies writing and removing
- content sending management

#### 2.2.7. `\MvcCore\Session`
- session namespaces with different validity
- validity by specific time or number of hoops
- possibility to create/read/update/delete any property in session namespace
- automatic session start, metadata parsing, write and close
- write and close is always called in `register_shutdown_function()` handler

#### 2.2.8. `\MvcCore\Router`
- request matching by two strategies
  - query string strategy - if there are `controller` and `action` query string params)
  - rewrite routes strategy . if there are any rewrite routes defined
- routing to complete current route object instance for application lifecycle
- URL generating by given unique name or `Namespace\Controller:Action` combination and given params
- storage for all routes in one place or in groups by first word in requested path
- possibility to load routes dynamically from database on demand when it is necessary to match request or generate URL
- automatic check if requested URL is canonical (automatic redirect to shorter version)
- many more features in extended classes
  - localization routing and management, media (devices) routing and management, modules routing and management etc...

#### 2.2.9. `\MvcCore\Route`
- request and target method describing object
- every route must has unique name or `Namespace\Controller:Action` combination
- describing request to match request and generating URL to defined application point
- describing request by `pattern` (or more precisely by `match` and `reverse`)
- `pattern` (or `match` and `reverse`) could contain params like `/items[/<path>]`,
  where brackets `<>` defines param and brackets `[]` defines optional URL part.
- `pattern` (or `match` and `reverse`) could contain in base part those dynamic definitions:
  - `%scheme%`		- for scheme URL part (`http:` or `https:`)
  - `%host%`		- for whole domain (`www.example.com` or `www.example.co.uk`)
  - `%domain%`		- for first and second domain level (`example.com` or `example.co.uk`)
  - `%sld%`			- for second level domain (`example`)
  - `%tld%`			- for top level domain (`com` or `co.uk`)
  - `%basePath%`	- for application base path if any
- request params default values and params constraints
- target application point described by controller namespace, name and action method
- possibility to define absolute `pattern` (or `match` and `reverse`)
- possibility to define route as absolute to generate always absolute URL
- possibility to define another route name to redirect old request path to new request path
- many more features in extended classes - localized routes, domain routes etc...

#### 2.2.10. `\MvcCore\Config`
- environment name management
- automatic environment detection or detection by system config
- system config or custom config files read/write with environment specific sections (extended INI format)
- many more features in extended classes - YAML syntax and environment specific files

#### 2.2.11. `\MvcCore\Tool`
- OOP programming and checking helper methods
- JSON encoding/decoding
- string case conversions
- single process file writing
- many more features in extended classes - images processing, locales, floats parsing etc...

#### 2.2.12. `\MvcCore\Debug`
- browser debug bar to dump any variable in HTML or in AJAX request
- any variable/data/exceptions logging into predefined logging level files
- global debug shortcut methods `x()`, `xx()` and `xxx()`
- many more features in extended classes

### 2.3. Main extension that is definitely worth talking about
- form and form fields
- authentications
- many routers types
- `tracy/tracy` debug bar and panels
- many view helpers
- tools
- YAML config

### 3. Packing/Building features
- partial or complete application packaging/building into single PHP file by [**Packager (mvccore/packager)**](https://github.com/mvccore/packager) library
	- you can include only file extensions you want
	- or include all files (binary or text, doesn't matter, everything is possible)
- packing to PHAR package (slower) or to PHP single file (faster)
	- application build into PHP package is **very fast on FastCGI and OPcache extension enabled**
	- packed application has **constant execution times** and it's generally about 35% faster then 
	  development version also running on FastCGI/OPcache
- packing configuration features:
	- including/excluding folders by regular expressions
	- result code regexp and string replacements
	- PHTML templates minification
	- PHP scripts minification (`@var` doc comments possible to keep)
	- **AUTOMATIC ORDER DETECTION** for packed PHP scripts (PHP packing only)
	- possibility to wrap/keep mostly any original PHP file system function to load files from PHP package (PHP packing only)
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
			- **preserve HDD mode**
				- result `index.php` file contains PHP files, 
				  PHTML templates but no CSS/JS/fonts or images
				- all wrapped file system functions are looking on HDD first, 
				  then they try to read data from package itself
			- **strict HDD mode**
				- result `index.php` file contains only PHP files, 
				  but PHTML templates, all CSS/JS/fonts and images are on HDD
				- no PHP file system function is wrapped
- minification for PHP/HTML/CSS/JS by third party tools supported
- url rewrite with `.htaccess` or `web.config` still possible with packed application
- desktop and mobile website versions and languages versions by MvcCore Router extensions
- possibility to use any third party library or framework in Libs folder through MvcCore autoloader or by composer vendor package

## 4. Usage
- explore MvcCore examples:
	- [**Hello World (mvccore/example-helloworld)**](https://github.com/mvccore/example-helloworld)
	- [**Pig Latin Translator (mvccore/example-translator)**](https://github.com/mvccore/example-translator)
	- [**CD Collection (mvccore/example-cdcol)**](https://github.com/mvccore/example-cdcol)
	- begin with Hello World example and read the source code:
		- Hello world example has only 2 important controllers (Default.php and Base.php)
		- MvcCore framework has only 12 core classes (well documented comments, not everything used every time)
- than explore more complex MvcCore applications:
	- [**XML Documents (mvccore/app-xmldocs)**](https://github.com/mvccore/app-xmldocs)
	- [**Questionnaires (mvccore/app-questionnaires)**](https://github.com/mvccore/app-questionnaires)
- check out some of empty MvcCore project templates
	- [**Project - Basic**](https://github.com/mvccore/project-basic)
		- Website project not designed for full portable build/pack.
	- [**Project - Portable**](https://github.com/mvccore/app-xmldocs)
		- Website project designed for full portable build/pack.
		- To develop new application - work in `/development` directory
		- To build single file application - use `make.cmd` and configure build process in `make-php.php` 
		  or `make-phar.php` (see examples)
		- Test your built application in `/release` directory
