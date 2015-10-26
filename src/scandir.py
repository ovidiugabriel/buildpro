
import fnmatch
import os
import glob
import sys

if len(sys.argv) < 2:
    exit(1)

root = os.path.dirname(os.path.realpath("__file__")) + sys.argv[1]
root = root.rstrip('/') + '/'
root_len = len(root)

for dirname, dirnames, filenames in os.walk(root):
    for filename in filenames:
        p = os.path.join(dirname, filename).replace('\\', '/')
        print('- ${LIB}/' + p[root_len:])
