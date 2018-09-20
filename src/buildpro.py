#!/usr/bin/env python

#
# *************************************************************************
#
#  Title:       buildpro.py
#
#  Created on:  24.10.2015 at 08:59:46
#  Copyright:   (C) 2015-2018 ICE Control srl. All Rights Reserved.
#               (C) 2018 SoftICE Development OÜ. All Rights Reserved.
#
# *************************************************************************
#

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
import platform
import glob
import shlex
import io
import termcolor
import fnmatch

from compiler.base import compiler_base
from prototyping import proto

from sublime_folder import sublime_folder
from sublime_project import sublime_project

# Some 'contants' definitions
BOLD="\033[1m"
RESET="\033[0m"

# Global variables
env = os.environ

#
# Executes command via shell and return the complete output as a string
#
def shell_exec(cmd, show_echo):
    if show_echo:
        print(cmd)

    proc = subprocess.Popen(shlex.split(cmd), stdout=subprocess.PIPE,
        stderr=subprocess.PIPE)
    for line in io.TextIOWrapper(proc.stdout, encoding='UTF-8'):
        if line.startswith('Error'):
            termcolor.cprint(line, 'red')
        elif line.startswith('** error'):
            termcolor.cprint(line, 'red')
        elif re.match(r"\*\*\* [0-9]+ error", line):
            termcolor.cprint(line, 'red')
        elif line.startswith('Warning'):
            termcolor.cprint(line, 'yellow')
        else:
            sys.stdout.write(line)
        sys.stdout.flush()

#
# Generates the Lua Script code for the Tupfile
# The key in dictionary is the list of inputs (separated with spaces)
# while the value is a (command, output) vector
#
def get_tupfile(deps):
    COMMAND = 0;
    OUTPUT  = 1;

    tup_out = ''
    for line in deps:
        key, value = line.popitem()
        tup_out += ': ' + key + ' |> ' + value[0] + ' |> ' + value[1] + "\n"

    return tup_out + "\n"

def bold(text):
    return BOLD + text + RESET

#
# Prints a stage name using the buildpro banner
#
def buildpro_print(text):
    print(bold('[buildpro] ' + text))
    sys.stdout.flush()

#
# Prints the goodbye message and exits the script
#
def buildpro_exit(code):
    print('Bye. [exit ' + str(code) + ']')
    exit(int(code))

#
# Grabs the '@buildpro' command specification when -inline switch is used
#
def get_inline_command(filename):
    cmd = ""
    line_no  = 0
    def_line = 0
    with open(filename, 'r') as f:
        for line in f:
            line_no = line_no + 1
            if line:
                # should not depend on any language specific comments style
                # as we want to keep portability across compilers
                m = re.search('@buildpro\s+(.*)', line)
                if m:
                    if cmd != "":
                        raise Exception('Duplicate @buildpro in ' + filename + ":" + str(line_no))

                    cmd = m.group(1).strip()
                    filepath = os.path.realpath(filename)
                    cmd = cmd.replace('$filepath', filepath)
                    cmd = cmd.replace('$dirname', os.path.dirname(filepath))
                    cmd = cmd.replace('$basename', os.path.basename(filepath))
                    def_line = line_no
    return cmd

"""
    Executes PHP command and returns the output
"""
def php(cmd, show_echo):
    return shell_exec("/usr/bin/env php -r '" + cmd + ";'", show_echo)

def read_sublime_project(path):
    sublime_files = glob.glob(path + '/*.sublime-project')
    project_path = sublime_files[0]

    buildpro_print("Sublime Project: '" + os.path.basename(project_path) + "'")
    return json.load(open(project_path))

def parse_project(project):
    project_file = project + '.project.yml'
    stream = open(project_file, 'r')
    return yaml.load(stream)


def glob_tup_sources(deps, input_pattern, runner, output_pattern):
    output_pattern = output_pattern.replace('\\', '\\\\')
    for filename in glob.glob(input_pattern):
        inputf       = filename.replace('\\', '/')
        object_path = re.sub('(.*)(\..*)$', output_pattern.replace('*', '\\1'), os.path.basename(inputf))
        if object_path in tup_objects:
            print("ERROR: Unable to create output file '"+object_path+"' because it is an output object for both")
            print(" * " + tup_objects[object_path] + ' AND ' + inputf)
            buildpro_exit(1)

        deps.append({inputf: [runner + ' '+ inputf, object_path.replace('\\', '/')]})
        tup_objects[object_path] = inputf

def print_usage_option(options, description):
    print(bold('    ' + options))
    print('        ' + description)
    print('')

def print_usage():
    print(bold('USAGE'))
    print('    ' + bold('buildpro') + ' <project-name>')
    print('        builds a project where the project file is <project-name>.project.yml')
    print('')
    print('    ' + bold('buildpro') + ' [option] [params ...]')
    print('')
    print(bold('OPTIONS'))
    print_usage_option('-h, -help, --help', 'display this help and exit')
    print_usage_option('-inline <file-path>', 'invoke compiler using inline @buildpro annotation')
    print_usage_option('-proto <language> <class-name> <input-file> <output-file>', '')
    print_usage_option('-create', '')
    print_usage_option('-list', '')


#
# ---------------------------------------------------------------------------------------------------------
# End functions
# ---------------------------------------------------------------------------------------------------------
#

if 1 == len(sys.argv) or '-h' == sys.argv[1].strip():
    # print('Error: Invalid command line. Specify the project name.')
    print_usage()
    buildpro_exit(0)

if '-inline' == sys.argv[1].strip():
    buildpro_print('Running build command ...')
    try:
        shell_exec(get_inline_command(sys.argv[2]), True)
    except Exception as ex:
        buildpro_exit(ex.returncode)
    buildpro_exit(0)

if '-proto' == sys.argv[1].strip():
    try:
        proto(buildpro_print, sys.argv)
    except Exception as ex:
        buildpro_exit(int(str(ex)))
    buildpro_exit(0)

if '-create' == sys.argv[1].strip():
    folder_path = sys.argv[2]
    buildpro_print('Create project for: ' + os.path.realpath(folder_path))
    folder_name = folder_path.strip('/').replace('/', '-')
    # create a new folder
    folder = sublime_folder(folder_name, os.path.realpath(folder_path))

    # and add the folder to the project
    project = sublime_project()
    project.add_folder(folder)

    project_file = os.path.realpath(folder_path) + '/' + folder_name + '.sublime-project'
    with open(project_file, 'w+') as file_handle:
        file_handle.write(project.sublime_project())
    if os.path.isfile(project_file):
        print('Saved ' + project_file)
    buildpro_exit(0)

if '-list' == sys.argv[1].strip():
    data = read_sublime_project(sys.argv[2])

    for folder in data["folders"]:
        name = folder['name'] if 'name' in folder else os.path.basename(folder["path"])
        print('Path: ' + folder["path"])
        print('Name: ' + name)
        print('')
    buildpro_exit(0)


if '-create-tupfile' == sys.argv[1].strip():
    tup_objects = {}

    # TODO: buildpro to be able to generate compiler and linker wrappers
    compiler_w = './run-bcc32.bat'
    linker_w   = './run-ilink32.bat'

    data = parse_project(sys.argv[2].strip())

    rootdir = os.path.realpath(data['working-directory']) if ('working-directory' in data) else  os.getcwd()
    objects_pattern = os.path.relpath(rootdir + '/' + data['objects'])

    artifacts_list = list(map(lambda artifact : os.path.relpath(rootdir + '/' + artifact).replace('\\', '/'), data['artifacts'] ))

    deps = []
    for pattern in data['sources']:
        glob_tup_sources(deps, pattern, compiler_w, objects_pattern)
    deps.append({objects_pattern : [linker_w + ' ' + objects_pattern, ' '.join( artifacts_list )]})
    tup_text = get_tupfile(deps)
    print(tup_text)
    buildpro_exit(0)

#
# Continue for non-proto usage
#

data = parse_project(sys.argv[1].strip())

rootdir = os.path.realpath(data['working-directory']) if ('working-directory' in data) else  os.getcwd()
os.chdir(rootdir)
buildpro_print('Working directory is now: "' + rootdir + '"')

if data == None:
    print('Error: Invalid project file.')
    buildpro_exit(1)

# TODO: `final_cmd` must be formatted as specified in the .project.yml file.
# The `final_cmd` may be used to run the compiler directly but also to generate Tupfile

for d in data['environment']:
    key, value = d.popitem()
    env[key] = value.format(**env)

if 'require' in data:
    for value in data['require']:
        shell_exec(value.format(**env), True)

#
# Compiler option is MANDATORY!!!
#
if 'compiler' in data:
    compiler = compiler_base.factory(data['compiler'])
else:
    buildpro_print('No compiler backend requested')
    compiler = compiler_base()
    command = data['command'].format(**env)
    compiler.set_command(command)
    print('Using ' + command)

# http://scribu.net/blog/python-equivalents-to-phps-foreach.html

# Includes paths
if 'includes' in data:
    if data['includes'] != None:
        try:
            for value in data['includes'].values():
                value = value.format(**env).replace('$', '')
                compiler.append_include_path(value)
        except KeyError as ex:
            print('Key Error: Undefined environment variable ${' + str(ex).strip("'") + '}')
            buildpro_exit(1)

defines = {}

if len(defines) > 0:
    for key in defines:
        compiler.append_define(key, defines[key])

if 'sources' in data:
    for value in data['sources']:
        compiler.append_source(value.format(**env).replace('$', ''))

if 'library_paths' in data:
    for value in data['library_paths'].values():
        compiler.append_library_path(value.format(**env).replace('$', ''))

#
# the libs have to go after sources list
# don't ask me why, I don't know, but the order seems to be required
#
if ('libraries' in data) and (data['libraries'] != None):
    for value in data['libraries']:
        compiler.append_library(value)

compiler.set_output_artifacts(data['artifacts'])

# By default the build is no-clean
# but clean may be forced with an environment variable
clean = 'clean' in env and env['clean']
if clean:
    buildpro_print('Clean build requested this time')
    cwd = os.getcwd()
    print('Removing old artifact(s) ...')
    for file in data['artifacts']:
        filepath = os.path.realpath(cwd + '/'+ file)
        if os.path.isfile(filepath):
            os.remove(filepath)
        else:
            print('- ' + file + ' ?')

    print('')
    print('Removing old object(s) ...')
    for file in glob.glob(data['objects']):
        filepath = os.path.realpath(cwd + '/' + file)
        if os.path.isfile(filepath):
            os.remove(filepath)
        else:
            print('- ' + file + ' ?')
else:
    buildpro_print('"No clean" build')
    print('To clean artifacts prepend clean=1 \n')

buildpro_print('Building ...')

try:
    log_file_name = 'buildpro.log'
    compiler.set_logfile(compiler_base.LOG_TYPE_BOTH, log_file_name)
    compiler.set_verbose(True)
    cmd = compiler.get_command()
    if None == cmd:
        buildpro_print('ERROR: compiler.get_command() returned empty command')
        buildpro_exit(1)
    shell_exec(cmd, True)

    if os.path.exists(log_file_name):
        buildpro_print('Printing logs ...')
        with open(log_file_name, 'r') as log_file:
            print(log_file.read())

    # print(shell_exec('cat buildpro.log', False))
except subprocess.CalledProcessError:
    buildpro_print('Build FAILED. Bailing out.')
    buildpro_exit(1)

# buildpro_print('Checking artifacts ...')
# artifact_exists = os.path.exists(output) and os.path.isfile(output)

#if artifact_exists:
#     print(output + ' was created')
#     if 'deploy' in data:
#         for cmd in data['deploy']:
#             cmd = cmd.replace('{artifact.name}', './' + output)
#             cmd = cmd.format(**env).replace('$', '')

#             buildpro_print('Deploying ...')
#             shell_exec(cmd, True)
# else:
#     termcolor.cprint(output + ' does not exists.')
#     buildpro_exit(1)




# if os.path.isfile('./' + output):
#  print('### Running ' + output + ' ... ###');
#  print(shell_exec('./' + output))

buildpro_exit(0)        # **** EXIT ****

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
