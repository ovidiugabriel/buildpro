
# from __future__ imports must occur at the beginning of the file
from __future__ import print_function
import re
import sys


#
# phpDocumentor style tags like @param or @return
# can be automatically translated to their Haxe counterparts
#
phpdoc_hx_types = {
    'mixed'  : 'Dynamic',
    'array'  : 'php.NativeArray',
    'integer': 'Int',
    'boolean': 'Bool',
    'string' : 'String',
    'float'  : 'Float',
    'int'    : 'Int',
    'void'   : 'Void'
}

def parse_tags(inp, functions):
    matches = re.findall(r"\/\*\*(.*?)\*\/(.*?)function\s+(.*?)\((.*?)\)", inp, re.MULTILINE | re.DOTALL)
    for match in matches:
        proto_m = re.findall("(@proto\s+.*)", match[0])
        if len(proto_m) > 0:
            proto_line(proto_m[0], functions)
        else:
            params = re.findall("@param\s+(.*?)\s+\$(.*?)\s", match[0])
            sig = []
            for param in params:
                sig.append(param[1] + ':'+ phpdoc_hx_types[param[0]])
            returns = re.findall("@return\s+(.*?)\s", match[0])

            proto_str = "{visibility} function {funcname}({params}):{returns};".format(visibility=match[1].strip(),
                    funcname=match[2],
                    params=', '.join(sig),
                    returns=phpdoc_hx_types[returns[0]]
                )
            functions.append(proto_str)

def proto_line(line, functions):
    m = re.search('@proto\s+(static|\.?)\s*(public|private|protected|[\+\#\~\-]?)\s*(.*)', line.rstrip())
    if None != m:
        static = m.group(1)
        if '.' == static:
            static = 'static'

        if static != '':
            static += ' '

        #
        # Haxe has no notion of a protected keyword known from Java, C++ and other object-oriented languages.
        # However, its private behavior is equal to those language's protected behavior, 
        # so Haxe actually lacks their real private behavior.
        #
        visibility = m.group(2)
        if ('#' == visibility) or ('-' == visibility) or ('~' == visibility) or ('protected' == visibility):
            visibility = 'private'
        if '+' == m.group(2):
            visibility = 'public'

        if visibility != '':
            visibility += ' '

        proto = m.group(3)
        functions.append(static + visibility + 'function ' + proto)
    else:
        raise Exception("No match for @proto tag")

def proto(buildpro_print, argv):
    if len(argv) < 5:
        print('Error: Invalid command line.')
        print('Usage: -proto <lang> <class> <inputFile> <outputFile>')
        raise Exception(1)

    (_, _, lang, full_class_name, filename, outfile) = map(str.strip, argv)

    pkg          = full_class_name.split('.')
    class_name   = pkg.pop()
    package_name = '.'.join(pkg)

    buildpro_print('prototyping ' + lang)

    # Read all @proto annotations
    functions = []
    with open(filename, 'r') as fileh:
        content = fileh.read()
        parse_tags(content, functions)

    outfd = open(outfile, 'w')

    if package_name:
        outfd.write('package ' + package_name + ';\n\n')

    outfd.write('extern class ' + class_name + ' {\n')
    for func in functions:
        outfd.write('    ' + func + ';\n')
    outfd.write('} /* end class ' + full_class_name +' */' + "\n")

    outfd.close()

if __name__ == "__main__":
    try:
        proto(lambda x: print(x), sys.argv)
    except Exception as ex:
        exit(int(str(ex)))
