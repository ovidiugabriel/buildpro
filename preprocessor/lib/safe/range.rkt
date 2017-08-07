#lang racket

;; global storage
(define *vars* (make-hash))

;; types
(define (range a b) (list 'range a b))

;; functions to be used as "language statements"
(define (declare varname type) (hash-set! *vars* varname type))
(define (typeof varname) (hash-ref *vars* varname))

;; examples
(declare 'a  (range 0 2))
(typeof 'a) ;;  '(range 0 2)

(declare 'b (range 0 2))
(typeof 'b) ;;  '(range 0 2)
