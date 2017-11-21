### Miscellaneous Functions

#### declare (macro)

Declares a variable of specified type

```racket
(declare varname type)
```

#### declared? (macro)

Checks if a variable is declared

```racket
(declared? varname)
```

#### typeof (macro)

Returns the type of a previously declared variable. `#f` if the variable is not declared.

```racket
(typeof varname)
```
