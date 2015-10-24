#!/usr/bin/env python

#
# The main idea of this tool is that it can generate tupfiles, makefiles, etc.
# Or it can pass strings directly to your favourite compiler, gcc, dmc, etc.
#

#
# After binary artifact is generated, unit-test is performed, and test report
# with test coverage is displayed
#

#
# This tool is using YAML format.
# http://pyyaml.org/wiki/PyYAMLDocumentation
#

import sys
import os
import subprocess
import yaml
import re
import json

#
# Executes command via shell and return the complete output as a string
#
def shell_exec(cmd, show_echo):
    if show_echo:
        print(cmd)
    return subprocess.Popen(cmd, stdout=subprocess.PIPE, shell=True).stdout.read().decode('utf-8').rstrip()

#
# Generates the Lua Script code for the Tupfile
# The key in dictionary is the list of inputs (separated with spaces)
# while the value is a (command, output) vector
#
def get_tupfile(deps):
    COMMAND = 0;
    OUTPUT  = 1;

    tup_out = ''
    for key in deps:
        tup_out += (': ' + key + ' |> ' + deps[key][COMMAND] + ' |> ' + deps[key][OUTPUT] + "\n")
    return tup_out

#
# Prints a stage name using the buildpro banner
#
def buildpro_print(text):
    print(BOLD + '[buildpro] ' + text + RESET);

#
# Prints the goodbye message and exits the script
#
def buildpro_exit(code):
    print('Bye. [exit ' + str(code) + ']')
    exit(code)

def proto():
    if len(sys.argv) < 5:
        print('Error: Invalid command line.')
        print('Usage: -proto <lang> <class>')
        buildpro_exit(1)

    lang       = sys.argv[2]
    full_class_name = sys.argv[3]
    pkg = sys.argv[3].split('.')

    class_name = pkg.pop()
    package_name = '.'.join(pkg)

    filename   = sys.argv[4]
    buildpro_print('proto ' + lang)

    # Read all @proto annotations
    functions = []
    with open(filename, 'r') as fileh:
        for line in fileh:
            m = re.search('@proto\s+(static|\.?)\s*(public|private|[\+\#\~\-]?)\s*(.*)', line.rstrip())
            if None != m:
                static = m.group(1)
                if '.' == static:
                    static = 'static'

                if static != '':
                    static += ' '
                visibility = m.group(2)
                if ('#' == m.group(2)) or ('-' == m.group(2)) or ('~' == m.group(2)):
                    visibility = 'private'
                if '+' == m.group(2):
                    visibility = 'public'

                if visibility != '':
                    visibility += ' '

                proto = m.group(3)
                functions.append(static + visibility + 'function ' + proto)

    outfile = sys.argv[5]

    outfd = open(outfile, 'w')

    outfd.write('package ' + package_name + ';\n\n')

    outfd.write('extern class ' + class_name + ' {\n')
    for func in functions:
        outfd.write('    ' + func + ';\n')
    outfd.write('} /* end class ' + full_class_name +' */')

    outfd.close()

""" 
    https://sublime-text-unofficial-documentation.readthedocs.org/en/latest/file_management/file_management.html\
    #the-sublime-project-format
"""
def sublime_project():
    # TODO: Work sublime project generator
    data = {"folders":[]}
    data["folders"].append({"file_exclude_patterns":[], "name":"", "path":""})

    print(json.dumps(data, sort_keys=True,
        indent=4, separators=(',', ': ') ))


""" 
    Unlike .sublime-project files, .sublime-workspace files are not meant to be shared or edited manually. 
    You should never commit .sublime-workspace files into a source code repository.
"""
def sublime_workspace():
    # So this function is ignoring .sublime-workspace file for your given versioning system
    pass

#
# ---------------------------------------------------------------------------------------------------------
# End functions
# ---------------------------------------------------------------------------------------------------------
#

if 1 == len(sys.argv):
    print('Error: Invalid command line. Specify the project name.')
    buildpro_exit(1)

# Some 'contants' definitions
BOLD  = shell_exec('tput bold', False)
RESET = shell_exec('tput sgr0', False)

env = os.environ

if '-proto' == sys.argv[1]:
    proto()
    buildpro_exit(0)

if '-sublime-project' == sys.argv[1]:
    sublime_project()
    buildpro_exit(0)

if '-sublime-workspace' == sys.argv[1]:
    sublime_workspace()
    buildpro_exit(0)

#
# Continue for non-proto usage
#

project_file = sys.argv[1] + '.project.yml'
stream = file(project_file, 'r')
data = yaml.load(stream)

if data == None:
    print('Error: Invalid project file.')
    buildpro_exit(1)

# TODO: `final_cmd` must be formatted as specified in the .project.yml file.
# The `final_cmd` may be used to run the compiler directly but also to generate Tupfile

# FIXME: As it is implemented now, it works only with GCC.
final_cmd = 'gcc'
if 'var' in data and 'CC' in data['var'] and data['var']['CC'] != None:
    final_cmd = data['var']['CC']   # maybe cc is not an inspired name

# http://scribu.net/blog/python-equivalents-to-phps-foreach.html

# Includes paths
if 'includes' in data:
    if data['includes'] != None:
        # https://wiki.python.org/moin/HandlingExceptions
        try:
            include_paths = []
            for (key, value) in enumerate(data['includes']):
                value = value.format(**env).replace('$', '')
                # data['includes'][key] = value
                include_paths.append('-I' + value)

            final_cmd += (' '.join(include_paths) + ' ')
        except KeyError, ex:
            print('Key Error: Undefined environment variable ${' + str(ex).strip("'") + '}')
            buildpro_exit(1)

defines = {}
# defines['False'] = '0'
# defines['True']  = '1'

if len(defines) > 0:
    cdefs = []
    for key in defines:
        cdefs.append('-D' + key + '=' + defines[key])
    final_cmd += (' '.join(cdefs) + ' ')

if 'sources' in data:
    for value in data['sources']:
        value = value.format(**env).replace('$', '')
        final_cmd += value + ' '

if 'library_paths' in data:
    libpaths = []

    for (key, value) in enumerate(data['library_paths']):
        value = value.format(**env).replace('$', '')
        # data['library_paths'][key] = value
        libpaths.append('-L' + value)

    final_cmd += (' '.join(libpaths) + ' ')

#
# the libs have to go after sources list
# don't ask me why, I don't know, but the order seems to be required
#
if ('libraries' in data) and (data['libraries'] != None):
    libraries = []
    for value in data['libraries']:
        libraries.append('-l' + value)
    final_cmd += (' '.join(libraries) + ' ')

# append artifact name
output = 'a.out'
if 'artifact' in data:
    output = data['artifact']['name']
else:
    if 'artefact' in data:
        output = data['artefact']['name']

# g flag - Produce debugging information in the operating system's native format
# GDB can work with this debugging information.
final_cmd += '-g -o ' + output

# By default the build is no-clean
# but clean may be enforced with an environment variable
clean = 'clean' in env and env['clean']
if clean:
    buildpro_print('Removing old artifact(s) ...')
    rmscript = 'if [ -f ./' + output + ' ] ; then rm ./' + output + ' ; fi'
    print(shell_exec(rmscript, True))
else:
    buildpro_print('"No clean" build')
    print('To clean artifacts prepend clean=1 \n')

buildpro_print('Building ...')
final_cmd_result = shell_exec(final_cmd + ' > buildpro.log 2>&1 ; echo $?', True)

print(shell_exec('cat buildpro.log', False))
if "0" != final_cmd_result:

    buildpro_print('Build FAILED. Bailing out.')
    buildpro_exit(1)

artifact_exists = shell_exec('if [ -f ./' + output + ' ] ; then echo "1" ; fi', False)

if "1" == artifact_exists:
    for cmd in data['deploy']:
        cmd = cmd.replace('{artifact.name}', './' + output)
        cmd = cmd.format(**env).replace('$', '')

        buildpro_print('Deploying ...')
        print(shell_exec(cmd, True))
else:
    print(output + ' does not exists.')
    buildpro_exit(1)

# if os.path.isfile('./' + output):
#  print('### Running ' + output + ' ... ###');
#  print(shell_exec('./' + output))

buildpro_exit(0)

# Scanning dependencies ...

f = open('main.d') # FIXME: remove this hardcoded value
text = f.read()

m = re.search('(.*):(.*)', text)
output = m.group(1)
inputs = m.group(2).strip().split(' ')

# map will be given as input
deps = {}
# TODO: the command must be read from the .project.yml file
deps[' '.join(inputs)] = ['gcc -c {input} -o {output}'.format(input=inputs[0], output=output), output]

# print(get_tupfile(deps))
