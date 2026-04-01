### Fixed
- PHP 8.5 compatibility:
  - deprecated method `ReflectionProperty::setAccessible()`,
  - deprecated magic methods `__sleep()` and `__wakeup()`,
  - deprecated `NULL` value in array index,
  - deprecated `\PDO` class instancing for specific connection types and constants like \PDO::MYSQL_*`,
- output buffer flushing, when no output buffering enabled,
- PHPStan PHPDocs fixes.