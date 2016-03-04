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

#
# *************************************************************************
#
#  Title:       buildpro.py
#
#  Created on:  24.10.2015 at 08:59:46
#  Email:       ovidiugabriel@gmail.com
#  Copyright:   (C) 2016 ICE Control srl. All Rights Reserved.
#
#  $Id$
#
# *************************************************************************
#
# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
# History (Start).
# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
#
# Date         Name    Reason
# -------------------------------------------------------------------------
# 03.03.2016           Fixed .exe target on Windows
# 02.03.2016           Small final_cmd changes
# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
# History (END).
# +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++


import sys
import os
import subprocess
import yaml
import re
import json
import platform

#
# Executes command via shell and return the complete output as a string
#
def shell_exec(cmd, show_echo):
    if show_echo:
        print(cmd)
    return subprocess.check_output(cmd, shell=True).decode('utf-8').rstrip()

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
        print('Usage: -proto <lang> <class> <inputFile> <outputFile>')
        buildpro_exit(1)

    lang            = sys.argv[2].strip()
    full_class_name = sys.argv[3].strip()
    filename        = sys.argv[4].strip()
    outfile         = sys.argv[5].strip()

    pkg = sys.argv[3].strip().split('.')
    class_name = pkg.pop()
    package_name = '.'.join(pkg)

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

"""
    Executes PHP command and returns the output
"""
def php(cmd, show_echo):
    return shell_exec("/usr/bin/env php -r '" + cmd + ";'", show_echo)

#
# ---------------------------------------------------------------------------------------------------------
# End functions
# ---------------------------------------------------------------------------------------------------------
#

if 1 == len(sys.argv):
    print('Error: Invalid command line. Specify the project name.')
    buildpro_exit(1)

# Some 'contants' definitions
BOLD="\033[1m"
RESET="\033[0m"

env = os.environ

if '-proto' == sys.argv[1].strip():
    proto()
    buildpro_exit(0)

if '-sublime-project' == sys.argv[1].strip():
    sublime_project()
    buildpro_exit(0)

if '-sublime-workspace' == sys.argv[1].strip():
    sublime_workspace()
    buildpro_exit(0)

#
# Continue for non-proto usage
#

project_file = sys.argv[1].strip() + '.project.yml'
stream = file(project_file, 'r')
data = yaml.load(stream)

if data == None:
    print('Error: Invalid project file.')
    buildpro_exit(1)

# TODO: `final_cmd` must be formatted as specified in the .project.yml file.
# The `final_cmd` may be used to run the compiler directly but also to generate Tupfile

#
# Compiler option is MANDATORY!!!
#
final_cmd = []
final_cmd.append(data['compiler'])

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

if len(defines) > 0:
    for key in defines:
        final_cmd.append('-D' + key + '=' + defines[key])

if 'sources' in data:
    for value in data['sources']:
        final_cmd.append(value.format(**env).replace('$', ''))

if 'library_paths' in data:
    for (key, value) in enumerate(data['library_paths']):
        final_cmd.append('-L' + value.format(**env).replace('$', ''))

#
# the libs have to go after sources list
# don't ask me why, I don't know, but the order seems to be required
#
if ('libraries' in data) and (data['libraries'] != None):
    for value in data['libraries']:
        final_cmd.append('-l' + value)

# append artifact name
output = 'a.out'
if 'artifact' in data:
    output = data['artifact']['name']
else:
    if 'artefact' in data:
        output = data['artefact']['name']

# g flag - Produce debugging information in the operating system's native format
# GDB can work with this debugging information.
final_cmd.append('-g -o ' + output)

# Append .exe extension if we are on Windows system
if 'Windows' == platform.system():
    output += '.exe'

# By default the build is no-clean
# but clean may be enforced with an environment variable
clean = 'clean' in env and env['clean']
if clean:
    buildpro_print('Removing old artifact(s) ...')
    print('- ' + output)
    os.remove(output);
else:
    buildpro_print('"No clean" build')
    print('To clean artifacts prepend clean=1 \n')

buildpro_print('Building ...')

try:
    final_cmd_output = shell_exec(' '.join(final_cmd) + ' > buildpro.log 2>&1', True)
    print(shell_exec('cat buildpro.log', False))
except CalledProcessError:
    buildpro_print('Build FAILED. Bailing out.')
    buildpro_exit(1)

buildpro_print('Checking artifacts ...')
artifact_exists = os.path.exists(output) and os.path.isfile(output)
if artifact_exists:
    print(output + ' was created')
    if 'deploy' in data:
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
