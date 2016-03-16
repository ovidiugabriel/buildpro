#!/bin/bash

# When using Cygwin, the Windows path must be supplied
if type "cygpath" > /dev/null ; then
  MY_PWD=$(cygpath -w `pwd`)
else
  MY_PWD=`pwd`
fi

export INCLUDE_PATH=$MY_PWD/lib
if [ "$1" != "" ] ; then
  if [ -e $1 ] ; then
    if [ ! -e ./output ] ; then
      mkdir output
    fi
    if [ -e ./output/a.out ] ; then
      rm ./output/a.out
    fi
    php ./cpp_extension.php $1
    if [ -e "output/$1.out" ] ; then
      gcc -I./lib -Werror -xc output/$1.out -o output/a.out
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
