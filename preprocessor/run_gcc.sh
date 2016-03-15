#!/bin/bash

export INCLUDE_PATH=`pwd`/lib
if [ "$1" != "" ] ; then
  if [ -e $1 ] ; then
    ./cpp_extension $1
    gcc -xc output/$1.out
  else
    echo "error: $1: No such file or directory"
  fi
else
  echo "fatal error: no input files"
fi
