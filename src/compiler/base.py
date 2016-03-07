
class compiler_base:
    def __init__(self):
        self.include_paths = []
        self.defines = []
        self.sources = []
        self.library_paths = []
        self.libraries = []
        self.output = ''
        self.logfile = ''

    def append_include_path(self, value):
        self.include_paths.append(value)

    def append_define(self, key, value):
        self.defines.append([key, value])

    def append_source(self, value):
        self.sources.append(value)

    def append_library_path(self, value):
        self.library_paths.append(value)

    def append_library(self, value):
        self.libraries.append(value)

    def set_output_artifact(self, tyipe, name):
        self.output = name;

    def set_logfile(self, tyipe, name):
        self.logfile[tyipe] = name
