#lang racket

(require xml)

;; XML is Lisp with angle brackets instead of parens?

;; http://download.plt-scheme.org/doc/4.1.5/html/xml/
;; https://rosettacode.org/wiki/S-Expressions

(define argv (current-command-line-arguments))

(define in (open-input-file (vector-ref argv 0)))
(define expression (read in))

(display (xexpr->string expression))
