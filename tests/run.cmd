:: @setlocal EnableExtensions EnableDelayedExpansion
@setlocal EnableDelayedExpansion

:: count number of args:
@set args_count=0
@for %%x in (%*) do @set /A args_count+=1

:: load current cli php executable extensions directory
@set extension_dir=
@for /F "tokens=* USEBACKQ" %%F IN (`php -i`) do @(
	set line=%%F
	if /i "!line:~0,13!"=="extension_dir" (
		set "line=!line:~17!"
		for /f "delims=>" %%i in ("!line!") do @(
			set item=%%i
			SET "extension_dir=!item:~0,-2!"
		)
	)
)

:: run tests
@if %args_count% EQU 0 (
	@call ../vendor/bin/tester.bat -s -c ./php.ini -d extension_dir=%extension_dir% ./MvcCore
) else @(
	@call ../vendor/bin/tester.bat -s -c ./php.ini -d extension_dir=%extension_dir% %*
)

