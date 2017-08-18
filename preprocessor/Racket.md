First simple console script in Racket ...

```racket
#!/usr/bin/env racket
#lang racket

"hello world"

```

* **void** is a function that when called, returns `#<void>`
* **null** is the empty list

```
> void
#<procedure:void>

> (display (void))
#<void>

> (list (void))
'(#<void>)

> (cdr '(void))
'()

> (cdr (list (void)))
'()

> null
'()

```

#### Scribble Example

```racket
#lang scribble/text

@display{@; space not allowed between '@display' and '{'
@; and not allowed between '{' and '@;'
    hello world
} @; string argument

@display["hello world"] @;{ arguments in racket mode }
@(display "hello world") @;{ racket mode expression }

@; disables evaluation
@display|{
  @(number->string (+ 1 2))
}|
```

```racket
@; evaluation enabled
@display{
  @(number->string (+ 1 2))
}

@display[
  (number->string (+ 1 2))
]

@(define (§ text) text)
@§{$hello}
```

Multiple parameters 

```racket
;; example calling array:new with 3 parameters
;; equivalent with:  (array:new int v 256)
@array:new[ int v 256 ]
```
