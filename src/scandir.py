
import fnmatch
import os
import glob

root = os.path.dirname(os.path.realpath("__file__")) + '/output/release/dist/lib'
root = root.rstrip('/') + '/'
root_len = len(root)

for dirname, dirnames, filenames in os.walk(root):
    for filename in filenames:
        p = os.path.join(dirname, filename).replace('\\', '/')
        print('- ${LIB}/' + p[root_len:])
