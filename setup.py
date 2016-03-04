#!/usr/bin/env python

import os

buildpro_home = os.path.dirname(os.path.realpath(__file__))
with open('buildpro', 'w') as file:
    file.write('#!/bin/bash\n')
    file.write('/bin/env python '+buildpro_home+'/src/buildpro.py $*\n')

with open('buildpro.bat', 'w') as file:
    file.write('@echo off\n')
    file.write('python '+buildpro_home+'/src/buildpro.py %*\n')
