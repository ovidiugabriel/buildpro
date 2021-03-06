#!/bin/bash

BOLD="\033[1m"
RESET="\033[0m"
Yellow="\e[33m"
LightRed="\e[91m"

PHP_EXE_NAME=hhvm

# When using Cygwin, the Windows path must be supplied
if type "cygpath" 1> /dev/null 2> /dev/null ; then
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
      echo "[-] Removing old output ./output/a.out"
      rm ./output/a.out
    fi

    if [ -e ./output/$1.out ] ; then
        echo "[-] Removing old output ./output/$1.out"
        rm ./output/$1.out
    fi

    if [ -e ./output/$1.php ] ; then
        echo "[-] Removing old output ./output/$1.php"
        rm ./output/$1.php
    fi

    echo -e "*** ${BOLD}${Yellow} [ Running preprocessor ] ${RESET} ***"
    $PHP_EXE_NAME ./cpp_extension.php -o output/$1.out $1

    if [ "$?" != "0" ] ; then
        echo -e "${BOLD}${LightRed}[#] ERROR: Preprocessor error. Stop. $RESET"
        exit 1
    fi

    # clear empty lines
    grep -v '^$' output/$1.out > output/$1.out.tmp
    mv  output/$1.out.tmp output/$1.out

    if [ -e "output/$1.out" ] ; then
      echo "" # newline for better readability
      echo -e "*** ${BOLD}${Yellow}[>] [ Running compiler ] ${RESET} ***"
      echo -n "Compiler: "
      gcc --version | head -n 1

      gcc -I./lib -Werror -xc output/$1.out -o output/a.out
      if [ -e ./output/a.out ] ; then
        echo -e "*** ${BOLD}${Yellow}[>] [ Running program ] ${RESET} ***"
        ./output/a.out
      fi
    else
      echo -e "${BOLD}${LightRed}[#] ERROR: Output file 'output/$1.out' not found $RESET"
    fi
  else
    echo "[#] error: $1: No such file or directory"
  fi
else
  echo "[#] fatal error: no input files"
fi
