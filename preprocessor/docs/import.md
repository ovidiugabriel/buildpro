```c++
#import "msado15.dll" no_namespace rename("EOF", "EndOfFile")
```

```racket
@(import "msado15.dll" (no_namespace (rename "EOF" "EndOfFile")))
```

```c++
#import "progid:my.prog.id.1.5"
```

```racket
@(import "progid:my.prog.id.1.5")
```

```c++
#import "libid:12341234-1234-1234-1234-123412341234" version("4.0") lcid("9")
```

```racket
@(import "libid:12341234-1234-1234-1234-123412341234" ((version "4.0") (lcid "9")))
```

```c++
#import "..\drawctl\drawctl.tlb" no_namespace, raw_interfaces_only
// or
#import "..\drawctl\drawctl.tlb" no_namespace raw_interfaces_only
```

```racket
@(import "..\drawctl\drawctl.tlb" (no_namespace raw_interfaces_only))
```
