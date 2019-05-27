
import os
import sys
import re

cpp_type_map = {
    'Bool':     ['bool', None],
    'String':   ['std::string', '<string>'],
}

# target specific
def type_map(type):
    return cpp_type_map[type][0]

# target specific
def header_map(type):
    return cpp_type_map[type][1]

# target specific
def header_types():
    types = []
    # TODO: use `filter`
    for type_data in cpp_type_map:
        if cpp_type_map[type_data][1] != None:
            types.append(type_data)
    return types

# target specific
def to_c_params(params):
    sig = map(lambda x:  type_map(x[1]) + " " + x[0], params)
    return '(' + ', '.join(sig) + ')'


def append_header(headers, value):
    headers.append(value)
    headers = list(set(headers))

# C++ header code generation
# target specific
def generate_header(dirpath, parser):
    if parser.class_name:
        if not os.path.exists(dirpath):
            os.makedirs(dirpath)

        with open(dirpath + '/' + parser.class_name + '.h', 'w') as out_header:
            out_header.write("#pragma once\n\n")
            out_header.write("class {class_name} {{\n".format( class_name=parser.class_name ))

            for method in parser.methods:
                out_header.write("{visibility}:\n".format(visibility=method.visibility))
                out_header.write("    {return_type} {function_name}{signature};\n".format(
                    return_type=type_map(method.return_type),
                    function_name=method.function_name, signature=to_c_params(method.params) ))
            out_header.write("};\n\n")

# C++ source code generation
# target specific
def generate_source(dirpath, parser):
    if not os.path.exists(dirpath):
        os.makedirs(dirpath)
    with open(dirpath + '/' + parser.class_name + '.cpp', 'w') as out_source:
        headers = []

        for method in parser.methods:
            for type in header_types():
                if method.is_using(type):
                    append_header(headers, header_map('String'))

        for header in headers:
            out_source.write('#include ' + header + "\n")
        out_source.write('#include "{class_name}.h"\n'.format(class_name=parser.class_name))
        out_source.write("\n")

        for method in parser.methods:
            out_source.write("{return_type} {class_name}::{function_name}{signature} {{\n".format(
                return_type=type_map(method.return_type),
                class_name=parser.class_name,
                function_name=method.function_name, signature=to_c_params(method.params)) )

            s = replace_tab(parser.method_code[method.function_name])

            # remove the first tab on each line
            for line in s.splitlines():
                out_source.write(line[4:].rstrip("\n\r") + "\n")

            out_source.write("}\n")

##
## Generic code below
##

def replace_tab(s, tabstop = 4):
    result = ''
    for c in s:
        if c == '\t':
            for i in range(tabstop):
                result += ' ';
        else:
            result += c
    return result

def split_and_trim(x, delim):
    return map(str.strip, x.split(delim))

def parse_signature(sig):
    return map(lambda x : split_and_trim(x, ':'), split_and_trim(sig, ','))

def parse_file(filename):
    parser = Parser()
    with open(filename) as fp:
        parser.scan_file(fp)
    return parser

class Method:
    def __init__(self, visibility, return_type, function_name, params):
        self.visibility     = visibility
        self.return_type    = return_type
        self.function_name  = function_name
        self.params         = params

    def dump(self):
        print('method '+self.function_name+' {')
        print('    visibility: ' + self.visibility)
        print('    return_type: ' + self.return_type)
        print('    params: ' + str(self.params))
        print('}')

    def is_using(self, type):
        if self.return_type == type:
            return True

        for param in self.params:
            if param[1] == type:
                return True

        return False

class ParserState:
    def __init__(self):
        self.in_function        = False
        self.in_class           = False
        self.current_function   = ''
        self.line               = ''


def parse_func_proto(func_proto):
    matches = re.findall(r"^\s*(.*?)\s*function\s+(.*?)\((.*?)\)\s*:\s*(.*?)\s*$", func_proto, re.DOTALL)
    if matches:
        # print(matches)
        func  = matches[0]
        params  = parse_signature(func[2])

        return Method(func[0], func[3], func[1], params)


class Parser:
    def __init__(self):
        self.methods = []
        self.method_code = {}
        self.class_name = ''

        self.state = ParserState()

    def dump(self):
        for method in self.methods:
            method.dump()
        print('method_code: ' + str(self.method_code))
        print('class_name: ' + self.class_name)
        print('state: '+ str(self.state))


    def scan_file(self, fp):
        in_header   = False
        in_body     = False
        in_class    = False
        func_proto  = ''
        current_function = ''

        for line in fp:
            line = line.rstrip("\n\r")

            # empty line
            if 0 == len(line.strip()):
                continue

            # match class name
            matches = re.findall(r"class\s*(.*?)\s*{", line)
            if matches:
                self.class_name = matches[0]
                in_class = True
                continue

            # states
            if in_header:
                func_proto += replace_tab(line).lstrip(' ') + ' '

                matches = re.findall(r"{", line)
                if matches:
                    # entered function body
                    in_header = False
                    in_body = True
                    func_proto = re.sub('\\s+', ' ',func_proto).rstrip('{ ')
                    func_proto = func_proto.replace('( ', '(')
                    method = parse_func_proto(func_proto)
                    current_function = method.function_name
                    self.methods.append(method)

            elif in_body:

                if '}' == line.strip():
                    in_function      = False
                    current_function = ''

                else:
                    if current_function not in self.method_code:
                        self.method_code[ current_function ] = ''

                    self.method_code[ current_function ] += line

            else:
                # not yet in a function

                # 'function' token or other tokens that can appeare before
                # 'function' keyword
                matches = re.findall(r"function|static|public|private", line)
                if matches:
                    # just entered a function
                    func_proto += replace_tab(line).lstrip(' ') + ' '
                    in_header = True
                else:
                    # definitely not in a function
                    pass

#
# Main code
#
parser = parse_file(sys.argv[1])

generate_header(sys.argv[2], parser)
generate_source(sys.argv[2], parser)
