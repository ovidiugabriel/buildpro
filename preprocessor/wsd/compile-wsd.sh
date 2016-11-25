#!/bin/bash
php wsd-codegen.php --main Alice::main wsd > output/wsd.cpp
cat -n output/wsd.cpp
g++ -I./include output/wsd.cpp -o output/a.out
./output/a.out
