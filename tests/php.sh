#!/usr/bin/env bash

cd "$(dirname "$0")"

./../vendor/bin/codecept build
./../vendor/bin/codecept run $*
