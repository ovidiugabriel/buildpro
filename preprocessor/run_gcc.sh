#!/bin/bash

export INCLUDE_PATH=`pwd`/lib
if [ "$1" != "" ] ; then
  if [ -e $1 ] ; then
    if [ -e ./output/a.out ] ; then
      rm ./output/a.out
    fi
    ./cpp_extension $1
    if [ -e "output/$1.out" ] ; then
      gcc -I./lib -xc output/$1.out -o output/a.out
      if [ -e ./output/a.out ] ; then
        ./output/a.out
      fi
    fi
  else
    echo "error: $1: No such file or directory"
  fi
else
  echo "fatal error: no input files"
fi
