
## To compile this file use:
##
##      buildpro -inline ini_test.cr

##
## Do not delete the following line
##
###     @buildpro crystal build $filepath

#
# 'crystal/src/ini.cr' in library contains only parse() method
# for this reason we require our own copy of INI class.
#

require "./ini.cr"

if 4 != ARGV.size
    print("Usage: \n")
    print("    " + PROGRAM_NAME + " <ini-file> <group> <name> <value>\n")
    exit(1) 
end

abort("file is missing", 1) if !File.file?(ARGV[0])
content = File.read(ARGV[0])

values = INI.parse(content)
values[ARGV[1]][ARGV[2]] = ARGV[3]

text = INI.build(values, true)

ini_file = File.new(ARGV[0], "w")
if ini_file
    ini_file.puts(text)
    ini_file.close()
end

