#!/usr/bin/env python

import fnmatch
import os
import glob
import sys
import re
import json

if len(sys.argv) < 2:
    exit(1)

root = os.path.dirname(os.path.realpath("__file__")) + sys.argv[1]
root = root.rstrip('/') + '/'
root_len = len(root)

depends = {}
for dirname, dirnames, filenames in os.walk(root):
    for filename in filenames:
        p = os.path.join(dirname, filename).replace('\\', '/')
        parts = p.split('/')
        filename = parts[-1]

        file_oject = open(p, 'r')
        if file_oject:
            package = ''
            n_matches = 0
            for line in iter(file_oject):
                line = line.strip()

                match = re.search('^package\s+(.*);', line)
                if match:
                    package = match.group(1)

                match = re.search('^import\s+(.*);', line)

                if match:
                    n_matches = n_matches + 1
                    if filename not in depends:
                        depends[filename] = 1
                        sys.stdout.write(package + '.' + filename[0:-3] + ':')
                    sys.stdout.write(' ' + match.group(1))
            if n_matches:
                sys.stdout.write("\n")
            file_oject.close()
