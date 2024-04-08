#!/bin/bash

extension_dir=$(php -i | grep -E "^extension_dir(.*)")
extension_dir=$(echo "$extension_dir" | awk -F ' => ' '{print $2}')

if [ $# == 0 ]; then
	../vendor/bin/tester -s -c ./php.ini -d extension_dir=$extension_dir ./MvcCore
else
	../vendor/bin/tester -s -c ./php.ini -d extension_dir=$extension_dir "$@"
fi
