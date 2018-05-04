
### Typing

Reference: http://www.cplusplus.com/doc/tutorial/variables/

| Type     | Size (byte count) | Range Min.           | Range Max.           | Range Step |
|----------|-------------------|----------------------|----------------------|------------|
| uint8_t  | 1                 | 0                    | 255                  | 1          |
| uint16_t | 2                 | 0                    | 65535                | 1          |
| uint32_t | 4                 | 0                    | 4294967295           | 1          |
| uint64_t | 8                 | 0                    | 18446744073709551615 | 1          |
| int8_t   | 1                 | -128                 | 127                  | 1          |
| int16_t  | 2                 | -32768               | 32767                | 1          |
| int32_t  | 4                 | -2147483648          | 2147483647           | 1          |
| int64_t  | 8                 | -9223372036854775808 | 9223372036854775807  | 1          |

Implemented in: [safe/type-size.rkt](https://github.com/ovidiugabriel/buildpro/blob/master/preprocessor/lib/safe/type-size.rkt)

Every type is implemented as a range.
This approach is heavily inspired by Ada constrained types (where a subtype is a type together with an associated constraint)

> Their major purpose, is for greater program reliability: a constraint expresses a logical requirement on our program in an explicit manner, and it therefore opens up the possibility of reporting violations of this logical requirement, should they ever occur. -- "Rationale for the Design of the Ada® Programming Language"

This method is very powerful, yet flexible and allows to do a lot of things in a safe manner,
for example to define separate types for odd and even numbers.

```racket
;; Type for all odd numbers between [1 and 65535]
(define odd_t (type 2 (range 1 65535 2)))

;; Type for all even numbers between [0 and 65534]
(define even_t (type 2 (range 0 65534 2)))
```

#### climits

##### `(range-max type)`

Returns the value for `<type>_MAX` by reading the value of `max` member of `range` struct.

##### `(range-min type)`

Returns the value for `<type>_MIN` by reading the value of `min` member of `range` struct.

Reference: http://www.cplusplus.com/reference/climits/

| Symbol     | Member | Type     |
|------------|--------|----------|
| UINT8_MAX  | max    | uint8_t  |
| UINT16_MAX | max    | uint16_t |
| UINT32_MAX | max    | uint32_t |
| UINT64_MAX | max    | uint64_t |
| INT8_MAX   | max    | int8_t   |
| INT16_MAX  | max    | int16_t  |
| INT32_MAX  | max    | int32_t  |
| INT64_MAX  | max    | int64_t  |
| UINT8_MIN  | min    | uint8_t  |
| UINT16_MIN | min    | uint16_t |
| UINT32_MIN | min    | uint32_t |
| UINT64_MIN | min    | uint64_t |
| INT8_MIN   | min    | int8_t   |
| INT16_MIN  | min    | int16_t  |
| INT32_MIN  | min    | int32_t  |
| INT64_MIN  | min    | int64_t  |
