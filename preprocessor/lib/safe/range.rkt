#lang racket

(provide range-min
         range-max)

;; stdint
;; {{{
(provide stdint:typename)
(provide  ;; unsigned types
 uint8_t
 uint16_t
 uint32_t
 uint64_t
 
 ;; signed types
 int8_t
 int16_t
 int32_t
 int64_t)
;; }}}

;; storage
(define *vars* (make-hash))

;; types
(define-syntax-rule (range alpha beta) (list 'range alpha beta))
(define bit      (range 0 1))

;; To superseed 'lwfront/core/stdc/stdint.rkt'
;; {{{
(define uint8_t  (range 0 255))
(define uint16_t (range 0 65535))
(define uint32_t (range 0 4294967295))
(define uint64_t (range 0 18446744073709551615))

(define int8_t  (range -128 127))
(define int16_t (range -32768 32767))
(define int32_t (range -2147483648 2147483647))
(define int64_t (range -9223372036854775808 9223372036854775807))
;; }}}

;; Helpers for range type

;; Test if given type is a range
(define (range? type)
  (match type
    [(list 'range _ _) #t]
    [_ #f] ))

(define (range-min type) (first (cdr type)))
(define (range-max type) (second (cdr type)))

;; Gets the type name as a string for a given range as input
(define/contract (stdint:typename type)
  (->i ([type range?])
       [result string?] )
  (cond
    [(equal? type uint8_t)  "uint8_t" ]
    [(equal? type uint16_t) "uint16_t"]
    [(equal? type uint32_t) "uint32_t"]
    [(equal? type uint64_t) "uint64_t"]
    
    [(equal? type int8_t)  "int8_t" ]
    [(equal? type int16_t) "int16_t"]
    [(equal? type int32_t) "int32_t"]
    [(equal? type int64_t) "int64_t"]
    
    [(equal? type bit) "bit"]
    [else (error "Unknown typename for " (~a type))] ))

;;
;; Gets the correct index type for a given numer of elements
;; in a vector
;;
(define (type-index-for n-elem)
  (cond
    [(< n-elem (range-max int8_t)) int8_t]
    [(< n-elem (range-max int16_t)) int16_t]
    [(< n-elem (range-max int32_t)) int32_t]
    [(< n-elem (range-max int64_t)) int64_t] ))

;; functions to be used as "language statements"

;; Declares a type in the verifier and does not produce any output
;; A specific language generator shall call this, at the time of generated declaration.
(define (declare varname type) (hash-set! *vars* varname type))

(define (typeof varname) (hash-ref *vars* varname))

(define (rangeof expr)
  (match (first expr)
    ['+ (sum-range (typeof (first (cdr expr))) (typeof (second (cdr expr)))) ] ))

(define (sum-range alpha beta)
  (range (+ (first (cdr alpha)) (first (cdr beta)))
         (+ (second (cdr alpha)) (second (cdr beta))) ))

(define (check-lt-range expr)
  (let ([type (typeof (first (cdr expr)))])
  (let ([limit (match (first type)
                 ['range (second (cdr type)) ] )])
    (when (< limit (second (cdr expr)))
      (raise (string-append (~a expr) " is a tautology because "
                                       (~a (second (cdr expr))) " is out of " (~a type) )) ) ) ) )

;; Array specific functions
(define (array type n) (list 'array type n))
(define (array-size var) (second (cdr (typeof var))))
(define (array-type var) (first (cdr (typeof var))))

;;
;; Generates a for-each to traverse a vector
;;    block is a function with var[i] as string parameter
;;
(define (c-array-each var block)
  (let ([index-type (type-index-for (array-size 'v))])
    (declare 'i index-type)
    ; prevent generating an infinite loop
    (with-handlers ([string? (lambda (s) (raise (string-append "infinite loop: " s)) )])
      (check-lt-range `(< i ,(array-size var))) )
    ; generate (C code)
    (string-append "for (" (stdint:typename index-type) " i = 0; i < " (~a (array-size var)) "; i++) {\n"
                   (string-append "    " (block (string-append (~a var) "[i]")) ";\n")
                   "}\n" ) ))

;; Syntactic sugars
(define-syntax-rule (check-vector-index index vector)
  (check-lt-range `(< index ,(array-size 'vector))) )
