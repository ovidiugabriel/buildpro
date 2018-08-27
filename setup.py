#!/usr/bin/env python

import os

buildpro_home = os.path.dirname(os.path.realpath(__file__)).replace('\\', '\\\\')
with open('buildpro', 'w') as file:
    file.write('#!/bin/bash\n')
    file.write('python ' + buildpro_home + '/src/buildpro.py $*\n')

# do not use .bat files 
# please use git-bash for Windows
