### Added
- Application contains all fixed paths not depend on request.
- `"prefer-stable": true` into `composer.json`.
- Response method `SetBodySent()` to define start point of response  
  content sending in continuous rendering without output buffer.

### Changed
- Internal view rendering methods.
- Switched last two params in Route method `Url()`.
- All base classes interfaces separated into smaller parts.
- Semicolon is not escaped anymore in generated URLs.
- URL adresses are always generated for all output types with `&amp;`.
- Default path to public assets.

### Removed
- Path getters and setters, which have been moved into Application object.
- Replacement `%appPath%` from system config default path.
- Controller output type dependency to view DOCTYPE.  
  Output type depends now on `Content-Type` header only.

### Fixed
- PHPStan docs.