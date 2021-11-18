#!/bin/bash
phpdbg -qrr vendor/bin/phpunit -d memory_limit=512M --coverage-clover=coverage.xml tests/ flush=1
