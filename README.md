# MvcCore

[![Latest Stable Version](https://img.shields.io/badge/Stable-v4.3.1-brightgreen.svg?style=plastic)](https://github.com/mvccore/mvccore/releases)
[![License](https://img.shields.io/badge/Licence-BSD-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/4.0.0/LICENCE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.3-brightgreen.svg?style=plastic)

PHP MVC framework to develop and pack projects (partially or completely) into super fast single file apps and tools.

## Outline  
1. [Installation](#user-content-1-installation)  
2. [Usage](#user-content-2-usage)  
3. [Examples](#user-content-3-examples)  
   3.1. [Examples - Basic](#user-content-3-1-examples---basic)  
        3.1.1. [Examples - Basic - Hello World](#user-content-)  
        3.1.2. [Examples - Basic - Pig-Latin Translator](#user-content-)  
        3.1.3. [Examples - Basic - CD Collection](#user-content-)  
   3.2. [Examples - Empty Templates](#user-content-)  
        3.2.1. [Examples - Empty Templates - Basic](#user-content-)  
        3.2.1. [Examples - Empty Templates - Portable](#user-content-)  
   3.3. [Examples - Advanced (Applications)](#user-content-)  
        3.3.1. [Examples - Advanced - XML Documents](#user-content-)  
        3.3.2. [Examples - Advanced - Questionnaires](#user-content-)  
        3.3.3. [Examples - Advanced - Single File Editor & Manager](#user-content-)  
4. [Features](#user-content-2-features)  
   4.1. [Features - Classic MVC web framework features](#user-content-21-features---routing)  
   4.2. [Features - `MvcCore` classes features](#user-content-22-features---url-generating)  
        4.2.1. [Features - Classes - `\MvcCore\Application`](#user-content-22-features---url-generating)  
        4.4.2. [Features - Classes - `\MvcCore\Model`](#user-content-22-features---url-generating)  
        4.2.3. [Features - Classes - `\MvcCore\View`](#user-content-22-features---url-generating)  
        4.2.4. [Features - Classes - `\MvcCore\Controller`](#user-content-22-features---url-generating)  
        4.2.5. [Features - Classes - `\MvcCore\Request`](#user-content-22-features---url-generating)  
        4.2.6. [Features - Classes - `\MvcCore\Response`](#user-content-22-features---url-generating)  
        4.2.7. [Features - Classes - `\MvcCore\Session`](#user-content-22-features---url-generating)  
        4.2.8. [Features - Classes - `\MvcCore\Router`](#user-content-22-features---url-generating)  
        4.2.9. [Features - Classes - `\MvcCore\Route`](#user-content-22-features---url-generating)  
        4.2.10. [Features - Classes - `\MvcCore\Config`](#user-content-22-features---url-generating)  
        4.2.11. [Features - Classes - `\MvcCore\Tool`](#user-content-22-features---url-generating)  
        4.2.12. [Features - Classes - `\MvcCore\Debug`](#user-content-22-features---url-generating)  
  4.3. [Features - Main extension that is definitely worth talking about](#user-content-22-features---url-generating)  
  4.4. [Features - Packing & Single File Building](#user-content-3-how-it-works)  


## 1. Installation
```shell
composer require mvccore/mvccore
```

## 3. Examples

### 3.1. Examples - Basic

#### 3.1.1. [Examples - Basic - Hello World (`mvccore/example-helloworld`)](https://github.com/mvccore/example-helloworld)
- Best example where to start - simple request and response via controller instance and it's view.
- How controller and view is defined and rendered.
- How is possible to pack single file application - all JS/CSS files and images is possible to pack into single PHP or PHAR.
- Example has very simple `Bootstrap.php`, 2 controllers - `Default.php` and `Base.php`, very simple layout and few views.
- Example could work as single file application.

#### 3.1.2. [Examples - Basic - Pig-Latin Translator (`mvccore/example-translator`)](https://github.com/mvccore/example-translator)
- Translator from English to Pig-Latin.
- Example with standard and AJAX request/responses.
- Example contains simple form created only in HTML.
- Example could work as single file application.

#### 3.1.3. [Examples - Basic - CD Collection (`mvccore/example-cdcol`)](https://github.com/mvccore/example-cdcol)
- Standard CRUD example working with SQLite file database.
- Example contains MySQL and MSSQL database dumps and system config commented connections settings.
- Example with a rewrite routes, CRUD controller and very simple database SQL model.
- Example contains forms created and validated by forms extension.
- Example could work as single file application.

### 3.2. [Examples - Empty Templates]

#### 3.2.1. [Examples - Empty Templates - Basic (`mvccore/project-basic`)](https://github.com/mvccore/project-basic)
- Website project designed for standard Web usage, not designed for full portable build/pack.
- Example has not defined any controllers in sub-namespaces, but it could.
- Example prints only table names from database, connection from database is defined in example system config.
- Example has defined single `layout.phtml` and 2 action views - `home.phtml` and `not-found.phtml`.

#### 3.2.2. [Examples - Empty Templates - Portable (`mvccore/project-portable`)](https://github.com/mvccore/project-portable)
- Website project designed for full portable build/pack.
- To develop new application - work in `/development` directory.
- To build single file application - use `make.cmd` and configure build process in `make-php.php` or `make-phar.php` (see examples).
- Test your built application in `/release` directory.

### 3.3. [Examples - Advanced (Applications)]
After exploring basic examples, you could look into more complex MvcCore applications:

#### 3.3.1. [Examples - Advanced - XML Documents (`mvccore/app-xmldocs`)](https://github.com/mvccore/app-xmldocs)
- Very simple website with documents defined in XML files.
- Example contains controllers structured deeply in `Admin` and `Front` namespaces.
- Example contains sub-controllers.
- Example contains XML document model class example.

#### 3.3.2. [Examples - Advanced - Questionnaires (`mvccore/app-xmldocs`)](https://github.com/mvccore/app-questionnaires)
- Application to create questionnaire with predefined question types defined by XML.
- All questions are rendered as forms, created and validated by forms extension.
- All answers are stored in MySQL database to create reports.
- All questionnaires has automatically created reports with visual graphs by question types.
- Example could work as single file application.

#### 3.3.3. [Examples - Advanced - Single File Editor & Manager (`mvccore/example-file-manager`)](https://github.com/mvccore/example-file-manager)
- Files and directories editor, working for now only with files.
- File couldn't work as single file application yet.

## 4. Features

### 4.1. Features - Classic MVC web framework features
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

### 4.2.Features -  `MvcCore` classes features

#### 4.2.1. Features - Classes - `\MvcCore\Application`
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

#### 4.2.2. Features - Classes - `\MvcCore\Model`
- automatic connection into database when any database getter is called for first time
- connection is realized by direct method params or by system config database indexed sections
- connection index could be defined for whole class or in database getter method param
- models resources management
- data methods to get only dirty properties
- data methods to set up raw database data into model properties

#### 4.2.3. Features - Classes - `\MvcCore\View`
- many extension with view helpers
  - assets, formatting numbers, money, dates, truncating, data URL, line breaks, writing content in JS etc...

#### 4.2.4. Features - Classes - `\MvcCore\Controller`
- application logic pattern class
- template helper methods to get main MVC objects and shortcut methods
- useful build-in properties automatically filled before dispatching
- template lifecycle methods `Init()`, `PreDispatch()`, `<Custom>Action()` and `Render()`
- error template methods `RenderError()` and `RenderNotFound()`
- managing self lifecycle and any added sub-controllers or their sub-controllers
- response HTML/XML/JSON setters with termination
- view creating and rendering by automatically detected path or custom path
- single file application assets dispatching

#### 4.2.5. Features - Classes - `\MvcCore\Request`
- request describing object, not a singleton
- getter and setter methods for any request part property
- collections storage and filtering - files, headers, cookies, params
- any describing property is parsed from given constructor global variables only 
  when is necessary (on demand), not all properties initialization at start
- `Accept-Language`header static parsing

#### 4.2.6. Features - Classes - `\MvcCore\Response`
- response describing object, not a singleton
- getter and setter methods for any response part property - code, headers and content
- headers management - content type, encoding etc.
- safe cookies writing and removing
- content sending management

#### 4.2.7. Features - Classes - `\MvcCore\Session`
- session namespaces with different validity
- validity by specific time or number of hoops
- possibility to create/read/update/delete any property in session namespace
- automatic session start, metadata parsing, write and close
- write and close is always called in `register_shutdown_function()` handler

#### 4.2.8. Features - Classes - `\MvcCore\Router`
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

#### 4.2.9. Features - Classes - `\MvcCore\Route`
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

#### 4.2.10. Features - Classes - `\MvcCore\Config`
- environment name management
- automatic environment detection or detection by system config
- system config or custom config files read/write with environment specific sections (extended INI format)
- many more features in extended classes - YAML syntax and environment specific files

#### 4.2.11. Features - Classes - `\MvcCore\Tool`
- OOP programming and checking helper methods
- JSON encoding/decoding
- string case conversions
- single process file writing
- many more features in extended classes - images processing, locales, floats parsing etc...

#### 4.2.12. Features - Classes - `\MvcCore\Debug`
- browser debug bar to dump any variable in HTML or in AJAX request
- any variable/data/exceptions logging into predefined logging level files
- global debug shortcut methods `x()`, `xx()` and `xxx()`
- many more features in extended classes

### 4.3. Features - Main extension that is definitely worth talking about
- form and form fields
- authentications
- many routers types
- `tracy/tracy` debug bar and panels
- many view helpers
- tools
- YAML config

### 4.4. Features - Packing & Single File Building
- partial or complete application packaging/building into single PHP file by [**Packager (mvccore/packager)**](https://github.com/mvccore/packager) library
	- you can include only file extensions you want
	- or include all files (binary or text, doesn't matter, everything is possible)
- packing to PHAR package (slower) or to PHP single file (faster)
	- application build into PHP package is **very fast on FastCGI and OPcache extension enabled**
	- packed application has **constant execution times** and it's generally about 35% faster then 
	  development version also running on FastCGI/OPcache
- packing configuration features:
	- including/excluding folders by regular expressions
	- result code regular expression and string replacements
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
