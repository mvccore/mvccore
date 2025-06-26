### Fixed
- `\MvcCore\Tool::GetPascalCaseFromDashed()` in PHP<=5.6,
- Base path init when some ENV PATH variable configured in webserver 
  in `\MvcCore\Request::initScriptNameAndBasePath()`.

### Added
- `\MvcCore\Controller::GetCallerControllerClass()` to get creation class.