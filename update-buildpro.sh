#!/bin/bash

set -x

# Always use curl instead of wget, because curl is available also on MacOS

MASTER=https://raw.githubusercontent.com/ovidiugabriel/buildpro/master

# since the repository contains more projects right now (that are not separated)
# just pick-up the needed files from the repo
curl -s $MASTER/src/buildpro.py -o buildpro.py
mkdir src
mv ./buildpro.py src/

curl -s $MASTER/src/prototyping.py -o prototyping.py
mv ./prototyping.py src/

curl -s $MASTER/src/sublime_folder.py -o sublime_folder.py
mv ./sublime_folder.py src/

curl -s $MASTER/src/sublime_project.py -o sublime_project.py
mv ./sublime_project.py src/

# compilers package
curl -s $MASTER/src/compiler/__init__.py -o __init__.py
mkdir -p src/compiler
mv ./__init__.py src/compiler/

curl -s $MASTER/src/compiler/base.py -o base.py
mv ./base.py src/compiler/

# gcc compiler
curl -s $MASTER/src/compiler/gcc.py -o gcc.py
mv ./gcc.py src/compiler

# download tests project
curl -s $MASTER/test/buildpro_test.project.yml -o buildpro_test.project.yml
mkdir -p test
mv ./buildpro_test.project.yml test/

curl -s $MASTER/test/buildpro_test.cc -o buildpro_test.cc
mv ./buildpro_test.cc test/
