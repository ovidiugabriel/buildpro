# buildpro
**buildpro** - a tup like dependency based build system

###### The name

buildpro stands for **Build Pro**ject, the action requested to build a project, but also for **build pro**fessional. 

It is a tool for build professionals (with a role of build manager in some companies, or whatever ...)

##### Usages

**buildpro** was designed with **incremental builds** in mind, so each build run is a **no-clean build**

```bash
#
# Build a project with no-clean mode
# Where the project file is projectname.project.yml
#
python buildpro.py projectname
```

But you can force clean also

```bash
#
# Build a project with clean enabled
#
clean=1 python buildpro.py projectname
```
###### Inline compiler invocation (-inline)

If you don't want to create a new project, you can use buildpro to compile a single source file, without having to write a separate bash script or a makefile to compile it. Just embed the compile command in the source file.

```bash
python buildpro.py -inline <file-path>
```

This will search in the file specified by *file-path*
for the following line

```cpp
// @buildpro <compiler> <arguments-list ...>
```

The arguments may interpolate variables:

* `$filepath` - The full path to the current file
* `$dirname` - The directory of the current file
* `$basename` - The name only portion of the current file (including extension)


###### Code Generation (-proto)

**buildpro** is able to generate **Haxe extern classes** to wrap over php code of the Barebone MVC framework.

There is no big deal about this. You just have to specify the prototype using a **@proto** doc block comment tag.
For example doing so:

```php
/**
 * @proto static public main(args:Array):Int
 */
```

Will generate the following method signature into the **Haxe extern class** 

```haxe
static public main(args:Array):Int;
```


```bash
# python buildpro.py -proto language class-name input-file output-file
python buildpro.py -proto haxe barebone.$1 $1.class.php barebone/$1.hx
haxe -php output barebone/$1.hx # other haxe files may follow
cat -n barebone/$1.hx
```

###### Project File

The file `<project_name>.project.yml` is an [YAML](http://www.yaml.org/spec/1.2/spec.html) file, and contains the following sections:

* `var` : variables
* `defines` : dictionary (key:string, value:string)
* `includes` : list of strings (in `qmake` it is called `INCLUDEPATH`)
* `sources` : list of strings
* `library_paths` : list of strings
* `libraries`: list of strings (in `qmake` both `library_paths` and `libraries` were included in `LIBS`)
* `artifact` : dictionary (key:string, value:string) - `artefact` is also supported since it is more common everywhere outside North America.
* `compiler` :
* `command` :
* `deploy` :

---

* `require` :
* `ensure` :
* `invariant` :

```yaml

compiler: gcc

defines:
  NULL_PTR: 0

includes: # in qmake it is called INCLUDEPATH
  - /usr/includes

sources:
  - main.c

library_paths:
  - /usr/lib

libraries:
  - glibc

artifact:
  name: main

```
Online Resources:
* [YAML 1.2 Specification](http://www.yaml.org/spec/1.2/spec.html)
* [Online YAML Parser](http://yaml-online-parser.appspot.com/)
* [Python YAML Parser - pyYAML](http://pyyaml.org/wiki/PyYAMLDocumentation)

###### YAML Library Dependencies

**buildpro** is based on PyYAML library, so you need to install **PyYAML** before starting with **buildpro**

1. download PyYAML 3.11 from [PyYAML Page](http://pyyaml.org/wiki/PyYAML)
2. Run `python setup.py install`

```bash
wget http://pyyaml.org/download/pyyaml/PyYAML-3.11.tar.gz
tar -zxvf PyYAML-3.11.tar.gz
cd PyYAML-3.11/

sudo python setup.py install
```

Note: On Windows the path of wget is something like:  `/cygdrive/c/Program\ Files/GnuWin32/bin/wget.exe`

###### Dependencies Scanning

**Note:** For GCC dependencies can be generated with: ` gcc [options] -MM <name>.c -MF <name>.d  `

The result is something like:

```
main.o: main.c test/xy.h
```


```
  -MM 
    Like -M but do not mention header files that are found in system
    header directories, nor header files that are included, directly
    or indirectly, from such a header.

    This implies that the choice of angle brackets or double quotes in
    an #include directive does not in itself determine whether that
    header will appear in -MM dependency output.  This is a slight
    change in semantics from GCC versions 3.0 and earlier.

  -MF file
    When used with -M or -MM, specifies a file to write the
    dependencies to.  If no -MF switch is given the preprocessor sends
    the rules to the same place it would have sent preprocessed
    output.

    When used with the driver options -MD or -MMD, -MF overrides the
    default dependency output file.

```
* [Automatically generate makefile dependencies](http://www.microhowto.info/howto/automatically_generate_makefile_dependencies.html)
* [Autodependencies with GNU make](http://scottmcpeak.com/autodepend/autodepend.html)
