#
# The GNU Compiler Collection includes front ends for C, C++, Objective-C, Fortran, Java, Ada, and Go,
# as well as libraries for these languages (libstdc++, libgcj,...).
# GCC was originally written as the compiler for the GNU operating system.
# The GNU system was developed to be 100% free software, free in the sense that it respects the user's freedom.
#
# https://gcc.gnu.org/

import os
import platform
from compiler.base import compiler_base

class compiler_gcc(compiler_base):
    def __init__(self):
        compiler_base.__init__(self)
        self.compiler = 'gcc'
        self.final_cmd = []

    def append_include_path(self, value):
        compiler_base.append_include_path(self, '-I'+value)

    def append_define(self, key, value):
        self.defines.append('-D' + key + '=' + value)

    def append_library_path(self, value):
        compiler_base.append_library_path('-L'+value)

    def append_library(self, value):
        compiler_base.append_library('-l'+value)

    def get_command(self):
        self.final_cmd.append(self.compiler)
        self.final_cmd.append(' '.join(self.include_paths))
        self.final_cmd.append(' '.join(self.defines))
        self.final_cmd.append(' '.join(self.sources))
        self.final_cmd.append(' '.join(self.library_paths))
        self.final_cmd.append(' '.join(self.libraries))

        # Append .exe extension if we are on Windows system
        if 'Windows' == platform.system():
            self.output += '.exe'
        if self.verbose:
            self.final_cmd.append('-v')
        self.final_cmd.append('-o ' + self.output)
        return (' '.join(self.final_cmd) + ' > '+self.logfile[compiler_base.LOG_TYPE_BOTH]+' 2>&1')

