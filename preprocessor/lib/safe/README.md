### Problems solved

#### 1. Indexitis

**Problem 1 - Infinite loops**

Since the loop counter, as 8-bits unsigned integer, takes values between 0 and 255, the condition is always true, so the loop will never break.

```cpp
uint8_t i = 0;
char bytes[256] = {0};

for (i = 0; i < sizeof(bytes); i++) {
    // This is an infinite loop
    printf("%X ", bytes[i]);
}
```

**Solutions**

* automatically infer counter type
* (avoid indexitis) use a foreach construct that automatically traverses the vector from the first element to the last one

```racket
@array:new[ char bytes 256 ]
@array:each[ bytes value
         (c-printf "%X " value)
     ]
```

**Generated code**: The meta-language correctly generated the `int16_t` type declaration for the counter.

```cpp
char bytes[256] = {0};

for (int16_t i = 0; i < 256; i++) {
    printf("%X ", bytes[i]);
}
```

**Synopsis**
```racket
@array:new[ type name size ]
```

* `type` - token representing the type of each element
* `name` - token representing the name of the array
* `size` - token representing the number of elements in the array (this is an integer)

Generates a fixed-size initialized array declaration.

```cpp
type name[size] = {0};
```

```racket
@array:each[ vector value
         body
     ]

```
* `body` - must be a valid racket expression where scoping can be used, `value` is available to be used in `body`.
* `vector` - the name of the vector we want to iterate over
* `value` - the value available for current iteration

**@array:each Ensures**

* the vector declaration is already generated in the target code
* an infinite loop is not generated due to a tautologic expression


Also see: [Miscellaneous Functions](Misc.md)
