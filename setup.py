#!/usr/bin/env python

import os

buildpro_home = os.path.dirname(os.path.realpath(__file__)).replace('\\', '\\\\')
with open('buildpro', 'w') as file:
    file.write('#!/bin/bash\n')
    file.write('export PYTHONPATH=' + buildpro_home + '/PyYAML-3.11/lib\n')
    file.write('python ' + buildpro_home + '/src/buildpro.py $*\n')

# .bat files are temporary not in use, please use git-bash for windows
# with open('buildpro.bat', 'w') as file:
#    file.write('@echo off\n')
#    file.write('python '+buildpro_home+'/src/buildpro.py %*\n')
