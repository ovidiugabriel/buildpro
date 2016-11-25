#lang racket

(require xml)

;; XML is Lisp with angle brackets instead of parens?

;; http://download.plt-scheme.org/doc/4.1.5/html/xml/
;; https://rosettacode.org/wiki/S-Expressions

(define argv (current-command-line-arguments))

(define in (open-input-file (vector-ref argv 0)))
(define expression (read in))

(display (xexpr->string expression))

;;
;; For example:
;; (xexpr->string '
;;               (html
;;                (head (title "Hello") )
;;                (body "Hi!")
;;                )
;;               )
;;
;; Running this will output
;; $ racket html.rkt 
;; "<html><head><title>Hello</title></head><body>Hi!</body></html>"
;; 
;; Or put the following s-expression in a file:
;; (html (head (title "Hello")) (body "Hi!")))
;;
