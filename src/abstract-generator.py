
#
# Script used to generate abstract types that overload a specific set of operators
#
# operations("<abstract-type>", "<underlying-type>", {
#        "<method-name>" : "<operator>",
#    })
#

def operations(type, underlying, ops):
    def operation(type, underlying, name, op):
        print("    @:op(A "+op+" B)")
        print("    inline public function "+name+"(rvalue: "+underlying+"): "+type+" {")
        print("        return new "+type+"(this "+op+" rvalue);")
        print("    }")
        print("")

    print("abstract " + type + "(" + underlying + ") {")
    print("    inline public function new(value: " + underlying + ") {")
    print("        this = value;")
    print("    }")
    print("")
    for name in ops:
        operation(type, underlying, name, ops[name])
    print("}")
    print("")
