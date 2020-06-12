#!/bin/bash
set -x

# Always use curl instead of wget, because curl is available also on MacOS
MASTER=https://raw.githubusercontent.com/ovidiugabriel/buildpro/master

if [ -f ./setup.py ] ; then
    rm setup.py
fi
curl -s $MASTER/setup.py -o setup.py
pip3 install pyyaml --user
pip3 install termcolor --user

if [ -e /usr/bin/python3 ] ; then
    /usr/bin/python3 setup.py
else
    python setup.py
fi
chmod +x ./buildpro
if [ ! -f ~/buildpro ] ; then
    ln -s $(realpath ./buildpro) ~/buildpro
fi
if [ ! -f ~/.bashrc ] ; then
    touch ~/.bashrc
fi
if [ $(cat ~/.bashrc  | grep buildpro | wc -l) == "0" ] ; then
    echo "alias buildpro='~/buildpro'" >> ~/.bashrc
fi

# to avoid opening another terminal just to have this alias
source ~/.bashrc
