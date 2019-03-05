
### Racket Installation

The version link in this documentation may be outdated. 
Make sure you always check out the latest version available at https://download.racket-lang.org/

```bash
# make sure you download the latest version here ...
wget https://mirror.racket-lang.org/installers/7.2/racket-7.2-x86_64-linux.sh

# remove old version of racket
sudo rm -rf /opt/racket

# install the new version
sudo bash ./racket-7.2-x86_64-linux.sh --in-place --dest /opt/racket --create-dir

# create a symlink for the executables
sudo ln -s /opt/racket/bin/racket /usr/bin/racket
sudo ln -s /opt/racket/bin/drracket /usr/bin/drracket

```
### First Steps

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

@; this is a single line comment

@;{
    this is a
    multiline comment
}

@; calling `display` with string argument
@; will be the same as (display "hello world")

@display{@; space not allowed between '@display' and '{'
@; and not allowed between '{' and '@;'
    hello world
}

@;{ you can use arguments in racket mode }
@display["hello world"] 

@;{ or racket mode expression }
@(display "hello world") 

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

To you Racket inside scribble you have to `reuquire racket`. For instance:

```racket
#lang scribble/text

@(require racket)
@(define ($ . rest) (map ~a rest))
```
