#!/bin/bash
phpdbg -qrr vendor/bin/phpunit -d memory_limit=1G --coverage-html report   tests/
