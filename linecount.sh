#!/bin/bash
find -type f | grep -v -e vendor -e composer -e .git -e node_module -e polymer -e build | xargs cat |perl -lne 'print unless /^\s*$/' | wc -l
