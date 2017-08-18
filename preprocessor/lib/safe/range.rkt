#lang racket

(provide range-min
         range-max)

(provide isset?
         target:isset?)

(provide declare-var
         declare
         target:declare)

;; (provide array)
(provide array:each
         array:new)
(provide c-printf)
(provide array-var)

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

;; symbols that are defined to the target (output) language
(define *target-sym* (make-hash))

(define (isset? key) (hash-has-key? *vars* key))
(define (target:isset? key) (hash-has-key? *target-sym* key))

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

(define-syntax-rule (declare-var var type)
  (declare 'var type) )

;; Declares a type in the verifier and does not produce any output
;; A specific language generator shall call this, at the time of generated declaration.
(define (declare varname type) (hash-set! *vars* varname type))
(define (target:declare varname type) (hash-set! *target-sym* varname type))

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

(define-syntax-rule (array-var type size)
  (array 'type size) )

;; Array specific functions
(define (array type n) (list 'array type n))
(define (array-size var) (second (cdr (typeof var))))
(define (array-type var) (first (cdr (typeof var))))

;;
;; Generates a for-each to traverse a vector
;;    block is a function with var[i] as string parameter
;;
(define (c-array-each var block)
  ; the generated code is required to generate a declaration for the vector variable
  (when (not (target:isset? var))
    (raise (string-append (~a var) " is not declared in the generated code")))
  (let ([index-type (type-index-for (array-size 'v))])
    (declare-var i index-type)
    ; prevent generating an infinite loop
    (with-handlers ([string? (lambda (s) (raise (string-append "infinite loop: " s)) )])
      (check-lt-range `(< i ,(array-size var))) )
    ; generate (C code)
    (string-append "for (" (stdint:typename index-type) " i = 0; i < " (~a (array-size var)) "; i++) {\n"
                   (string-append "    " (block (string-append (~a var) "[i]")) ";\n")
                   "}\n" ) ))

(define-syntax-rule (array:each var x block)
  (c-array-each 'var (λ (x) (quasiquote ,block)) ))

(define (c-declare-array type name size)
  (target:declare name (array type size))
  (declare name (array type size))
  (string-append (~a type) " " (~a name) "[" (~a size) "] = {0};\n"))

;; Syntactic sugars
(define-syntax-rule (check-vector-index index vector)
  (check-lt-range `(< index ,(array-size 'vector))) )

;;
;; Usage example:
;;
;;     @array:new[ int v 256 ]
;;
(define-syntax-rule (array:new type name size)
  (c-declare-array 'type 'name size))

(define (c-printf format . rest)
  (string-append "printf(\"" format "\", " (string-join (map ~a rest) ", ") ")") )
