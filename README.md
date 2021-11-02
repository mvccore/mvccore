# MvcCore

[![Latest Stable Version](https://img.shields.io/badge/Stable-v5.1.12-brightgreen.svg?style=plastic)](https://github.com/mvccore/mvccore/releases)
[![License](https://img.shields.io/badge/License-BSD%203-brightgreen.svg?style=plastic)](https://mvccore.github.io/docs/mvccore/5.0.0/LICENSE.md)
![PHP Version](https://img.shields.io/badge/PHP->=5.4-brightgreen.svg?style=plastic)

PHP MVC framework to create web applications in standard way, with many extensions and examples, with option to pack whole project (partially or completely) into super fast single file app or web tool.

## Outline  
1. [Installation](#user-content-1-installation)  
2. [Usage](#user-content-2-usage)  
   2.1. [Usage - Create Classic Web Application](#user-content-21-usage---create-classic-web-application)  
   2.2. [Usage - Create Application In Single PHP Or PHAR File](#user-content-22-usage---create-application-in-single-php-or-phar-file)  
3. [Examples](#user-content-3-examples)  
   3.1. [Examples - Basic](#user-content-31-examples---basic)  
   3.2. [Examples - Empty Templates](#user-content-32-examples---empty-templates)  
   3.3. [Examples - Advanced (Applications)](#user-content-33-examples---advanced-applications)  
4. [Features](#user-content-4-features)  
   4.1. [Features - Classic MVC Web Framework Features](#user-content-41-features---classic-mvc-web-framework-features)  
   4.2. [Features - `MvcCore` Classes Features](#user-content-42features----mvccore-classes-features)  
   4.3. [Features - Main Extensions That Is Definitely Worth Talking About](#user-content-43-features---main-extensions-that-is-definitely-worth-talking-about)  
   4.4. [Features - Packing & Single File Building](#user-content-44-features---packing--single-file-building)  


## 1. Installation
```shell
composer require mvccore/mvccore
```

[go to top](#user-content-outline)

## 2. Usage
With MvcCore framework, you can develop classic web applicatons or application in single PHP or PHAR file:

### 2.1. Usage - Create Classic Web Application
Use basic [empty project template (`mvccore/project-basic`)](https://github.com/mvccore/project-basic):
```shell
composer create-project mvccore/project-basic
```

[go to top](#user-content-outline)

### 2.2. Usage - Create Application In Single PHP Or PHAR File
Use basic [single file project template (`mvccore/project-portable`)](https://github.com/mvccore/project-portable):
```shell
# load MvcCore portable project structure
composer create-project mvccore/project-portable

# go to project root dir
cd project-portable

# load MvcCore basic portable project
composer create-project mvccore/project-basic-portable development

# ... now you can do anything in development dir
```

[go to top](#user-content-outline)

## 3. Examples

### 3.1. Examples - Basic

#### 3.1.1. [Examples - Basic - Hello World (`mvccore/example-helloworld`)](https://github.com/mvccore/example-helloworld)
- Best example where to start - simple request and response via controller instance and it's view.
- How controller and view is defined and rendered.
- How is possible to pack single file application - all JS/CSS files and images is possible to pack into single PHP or PHAR.
- Example has very simple `Bootstrap.php`, 2 controllers - `Default.php` and `Base.php`, very simple layout and few views.
- Example could work as single file application.

[go to top](#user-content-outline)

#### 3.1.2. [Examples - Basic - Pig-Latin Translator (`mvccore/example-translator`)](https://github.com/mvccore/example-translator)
- Translator from English to Pig-Latin.
- Example with standard and AJAX request/responses.
- Example contains simple form created only in HTML.
- Example could work as single file application.

[go to top](#user-content-outline)

#### 3.1.3. [Examples - Basic - CD Collection (`mvccore/example-cdcol`)](https://github.com/mvccore/example-cdcol)
- Standard CRUD example working with SQLite file database.
- Example contains MySQL and MSSQL database dumps and system config commented connections settings.
- Example with a rewrite routes, CRUD controller and very simple database SQL model.
- Example contains forms created and validated by forms extension.
- Example could work as single file application.

[go to top](#user-content-outline)

### 3.2. Examples - Empty Templates

#### 3.2.1. [Examples - Empty Templates - Basic (`mvccore/project-basic`)](https://github.com/mvccore/project-basic)
- Website project designed for standard Web usage, not designed for full portable build/pack.
- Example has not defined any controllers in sub-namespaces, but it could.
- Example prints only table names from database, connection from database is defined in example system config.
- Example has defined single `layout.phtml` and 2 action views - `home.phtml` and `not-found.phtml`.

[go to top](#user-content-outline)

#### 3.2.2. [Examples - Empty Templates - Portable (`mvccore/project-portable`)](https://github.com/mvccore/project-portable)
- Website project designed for full portable build/pack.
- To develop new application - work in `/development` directory.
- To build single file application - use `make.cmd` and configure build process in `make-php.php` or `make-phar.php` (see examples).
- Test your built application in `/release` directory.

[go to top](#user-content-outline)

### 3.3. Examples - Advanced (Applications)
After exploring basic examples, you could look into more complex MvcCore applications:

#### 3.3.1. [Examples - Advanced - XML Documents (`mvccore/app-xmldocs`)](https://github.com/mvccore/app-xmldocs)
- Very simple website with documents defined in XML files.
- Example contains controllers structured deeply in `Admin` and `Front` namespaces.
- Example contains sub-controllers.
- Example contains XML document model class example.

[go to top](#user-content-outline)

#### 3.3.2. [Examples - Advanced - Questionnaires (`mvccore/app-xmldocs`)](https://github.com/mvccore/app-questionnaires)
- Application to create questionnaire with predefined question types defined by XML.
- All questions are rendered as forms, created and validated by forms extension.
- All answers are stored in MySQL database to create reports.
- All questionnaires has automatically created reports with visual graphs by question types.
- Example could work as single file application.

[go to top](#user-content-outline)

#### 3.3.3. [Examples - Advanced - Single File Editor & Manager (`mvccore/example-file-manager`)](https://github.com/mvccore/example-file-manager)
- Files and directories editor, working for now only with files.
- File couldn't work as single file application yet.

[go to top](#user-content-outline)

## 4. Features

### 4.1. Features - Classic MVC Web Framework Features
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

[go to top](#user-content-outline)

### 4.2.Features -  `MvcCore` Classes Features

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

[go to top](#user-content-outline)

#### 4.2.2. Features - Classes - `\MvcCore\Model`
- automatic connection into database when any database getter is called for first time
- connection is realized by direct method params or by system config database indexed sections
- connection index could be defined for whole class or in database getter method param
- models resources management
- data methods to get only dirty properties
- data methods to set up raw database data into model properties

[go to top](#user-content-outline)

#### 4.2.3. Features - Classes - `\MvcCore\View`
- many extension with view helpers
  - assets, formatting numbers, money, dates, truncating, data URL, line breaks, writing content in JS etc...

[go to top](#user-content-outline)

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

[go to top](#user-content-outline)

#### 4.2.5. Features - Classes - `\MvcCore\Request`
- request describing object, not a singleton
- getter and setter methods for any request part property
- collections storage and filtering - files, headers, cookies, params
- any describing property is parsed from given constructor global variables only 
  when is necessary (on demand), not all properties initialization at start
- `Accept-Language`header static parsing

[go to top](#user-content-outline)

#### 4.2.6. Features - Classes - `\MvcCore\Response`
- response describing object, not a singleton
- getter and setter methods for any response part property - code, headers and content
- headers management - content type, encoding etc.
- safe cookies writing and removing
- content sending management

[go to top](#user-content-outline)

#### 4.2.7. Features - Classes - `\MvcCore\Session`
- session namespaces with different validity
- validity by specific time or number of hoops
- possibility to create/read/update/delete any property in session namespace
- automatic session start, metadata parsing, write and close
- write and close is always called in `register_shutdown_function()` handler

[go to top](#user-content-outline)

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

[go to top](#user-content-outline)

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

[go to top](#user-content-outline)

#### 4.2.10. Features - Classes - `\MvcCore\Config`
- system config or custom config files read/write with environment specific sections (extended INI format)
- many more features in extended classes - YAML syntax and environment specific files

[go to top](#user-content-outline)

#### 4.2.11. Features - Classes - `\MvcCore\Environment`
- environment name management
- automatic environment detection or detection by system config

[go to top](#user-content-outline)

#### 4.2.12. Features - Classes - `\MvcCore\Tool`
- OOP programming and checking helper methods
- JSON encoding/decoding
- string case conversions
- single process file writing
- many more features in extended classes - images processing, locales, floats parsing etc...

[go to top](#user-content-outline)

#### 4.2.13. Features - Classes - `\MvcCore\Debug`
- browser debug bar to dump any variable in HTML or in AJAX request
- any variable/data/exceptions logging into predefined logging level files
- global debug shortcut methods `x()`, `xx()` and `xxx()`
- many more features in extended classes

[go to top](#user-content-outline)

### 4.3. Features - Main Extensions That Is Definitely Worth Talking About
- [Web Forms Extension](https://github.com/mvccore/ext-form) and [Web Forms Fields Extensions](https://github.com/mvccore/ext-form-all#user-content-form-extensible-packages-map)
- [Authentication Extension](https://github.com/mvccore/ext-auth)
- many routers types
  - [Router With Localization](https://github.com/mvccore/ext-router-localization)  
  - [Router With Media Site Version](https://github.com/mvccore/ext-router-media)  
  - [Router With Localization And Media Site Version](https://github.com/mvccore/ext-router-media-localization)  
  - [Router With Modules By Domains](https://github.com/mvccore/ext-router-module)  
  - [Router With Modules By Domains With Localization](https://github.com/mvccore/ext-router-module-localization)  
  - [Router With Modules By Domains With Media Site Version](https://github.com/mvccore/ext-router-module-media-localization)  
  - [Router With Modules By Domains With Localization And Media Site Version](https://github.com/mvccore/ext-router-module-media-localization)  
- [View Helpers Extensions](https://github.com/mvccore/ext-view-helper-all#user-content-extensions)
- Integration for debug bar and panels from [tracy/tracy](https://github.com/tracy/tracy)
- MvcCore special tools
  - [Images Processing Extension](https://github.com/mvccore/ext-tool-image)
  - [Any Platform Locale Setup](https://github.com/mvccore/ext-tool-locale)
  - [Float Numbers Parsing](https://github.com/ext-tool-locale-floatparser)  
  - [Working With Mime-Types And File Extensions](https://github.com/ext-tool-mimetype-extension)  
- [YAML Config Extension](https://github.com/mvccore/ext-config-yaml)

[go to top](#user-content-outline)

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

[go to top](#user-content-outline)