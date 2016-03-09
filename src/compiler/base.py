
class compiler_base:
    LOG_TYPE_BOTH   = 0
    LOG_TYPE_STDOUT = 1
    LOG_TYPE_STDERR = 2

    def __init__(self):
        self.include_paths = []
        self.defines = []
        self.sources = []
        self.library_paths = []
        self.libraries = []
        self.output = ''
        self.logfile = ['', '', '']

    @staticmethod
    def factory(compiler_name):
        module_object = importlib.import_module('compiler.'+compiler_name)
        class_object  = getattr(module_object, 'compiler_' + compiler_name)
        return class_object()

    def append_include_path(self, value):
        self.include_paths.append(value)

    def append_define(self, value):
        self.defines.append({'key':key, 'value':value})

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
