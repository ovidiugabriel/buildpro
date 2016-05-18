First simple console script in Racket ...

```racket
#!/usr/bin/env racket
#lang racket

"hello world"

```

```
> (list (void))
'(#<void>)
> (cdr (list (void)))
'()
> null
'()

```

#### Scribble Example

```racket
#lang scribble/text

@display{
    hello world
} @; string argument

@display["hello world"] @;{ arguments in racket mode }
@(display "hello world") @;{ racket mode expression }

@; disables evaluation
@display|{
  @(number->string (+ 1 2))
}|

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
