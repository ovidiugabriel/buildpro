#
# The GNU Compiler Collection includes front ends for C, C++, Objective-C, Fortran, Java, Ada, and Go, 
# as well as libraries for these languages (libstdc++, libgcj,...).
# GCC was originally written as the compiler for the GNU operating system. 
# The GNU system was developed to be 100% free software, free in the sense that it respects the user's freedom.
#
# https://gcc.gnu.org/

class compiler_gcc(compiler_base):
    def __init__(self):
        self.compiler = 'gcc'
        self.final_cmd = []

    def get_command(self):
        self.final_cmd.append(self.compiler)
        return ' '.join(self.final_cmd)

