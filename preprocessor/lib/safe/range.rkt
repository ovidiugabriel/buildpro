#lang racket

(provide range-min
         range-max)

(provide declared?
         declare
         typeof)
         

(provide target:declare
         target:isset?)

(provide type-index-for)
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

(define-syntax-rule (declared? varname) (_declared? 'varname))
(define (_declared? varname)
  (hash-has-key? *vars* varname))

(define (target:isset? key) (hash-has-key? *target-sym* key))

;; types

;; defines a range-type with step=1
(define-syntax-rule (range-type min max step)
  (list 'range-type min max step))

(define bit      (range-type 0 1 1))

;; To superseed 'lwfront/core/stdc/stdint.rkt'
;; {{{
(define uint8_t  (range-type 0 255 1))
(define uint16_t (range-type 0 65535 1))
(define uint32_t (range-type 0 4294967295 1))
(define uint64_t (range-type 0 18446744073709551615 1))

(define int8_t  (range-type -128 127 1))
(define int16_t (range-type -32768 32767 1))
(define int32_t (range-type -2147483648 2147483647 1))
(define int64_t (range-type -9223372036854775808 9223372036854775807 1))
;; }}}

;;
;; If the type is not an unsigned integer, 0 is returned
;;
(define (sizeof-type type)
  (match (stdint:typename type)
    ["uint8_t"  1]
    ["int8_t"   1]
    ["uint16_t" 2]
    ["int16_t"  2]
    ["uint32_t" 4]
    ["int32_t"  4]
    ["uint64_t" 8]
    ["int64_t"  8]
    [_ (raise (string-append "Unknown type: " (~a type) ". Not a byte multiple type?"))] ))

;; Helpers for range-type

;; Test if given type is a range
(define (range-type? type)
  (match type
    [(list 'range-type _ _ _) #t]
    [_ #f] ))

(define (range-min type) (first (cdr type)))
(define (range-max type) (second (cdr type)))
(define (range-step type) (third (cdr type)))

;; Gets the type name as a string for a given range as input
(define/contract (stdint:typename type)
  (->i ([type range-type?])
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
;; in a vector. This index shall be compared with the number of elements
;;
;; For example if we have 128 elements in a vector, the index cannot be
;; int8_t because int8_t is (range -128 127), and index will never be 128
;; to break the loop; so index needs to be int16_t
;;
(define (type-index-for n-elem)
  (cond
    [(<= n-elem (range-max int8_t)) int8_t]
    [(<= n-elem (range-max int16_t)) int16_t]
    [(<= n-elem (range-max int32_t)) int32_t]
    [(<= n-elem (range-max int64_t)) int64_t] ))

(define (type->bits type)
  (let ([n-bits
         (match type
           [(list range-type 0 1 1) 1]
           [_ (* 8 (sizeof-type type))] ) ])
    (for/list ([_ (in-range 0 n-bits)]) bit) ) )

;; functions to be used as "language statements"

;;
;; === declare ===
;;

(define-syntax-rule (declare varname type)
  (_declare 'varname type))

;; Declares a type in the verifier and does not produce any output
;; A specific language generator shall call this, at the time of generated declaration.
(define/contract (_declare varname type)
  (->i ([varname symbol?]
        [type list?])
       [result void?] )  
  (hash-set! *vars* varname type))

;; ---

(define (target:declare varname type)
  (hash-set! *target-sym* varname type))

;;
;; === typeof ===
;;
(define-syntax-rule (typeof varname)
  (_typeof 'varname))

(define/contract (_typeof varname)
  (->i ([varname symbol?])        
       [result (or/c false? list?)] )
  (when (_declared? varname)
    (hash-ref *vars* varname)))

;; ---

(define (rangeof expr)
  (match (first expr)
    ['+ (sum-range (_typeof (first (cdr expr))) (_typeof (second (cdr expr)))) ] ))

(define (sum-range alpha beta)
  (range-type (+ (first (cdr alpha)) (first (cdr beta)))
         (+ (second (cdr alpha)) (second (cdr beta)))
         1))

(define (check-lt-range expr)
  (let ([type (_typeof (first (cdr expr)))])
  (let ([limit (match (first type)
                 ['range-type (second (cdr type)) ] )])
    (when (< limit (second (cdr expr)))
      (raise (string-append (~a expr) " is a tautology because "
                                       (~a (second (cdr expr))) " is out of " (~a type) )) ) ) ) )

(define-syntax-rule (array-var type size)
  (array 'type size) )

;; Array specific functions
(define (array type n) (list 'array type n))
(define (array-size var) (second (cdr (_typeof var))))
(define (array-type var) (first (cdr (_typeof var))))

;;
;; Generates a for-each to traverse a vector
;;    block is a function with var[i] as string parameter
;;
(define (c-array-each var block)
  ; the generated code is required to generate a declaration for the vector variable
  (when (not (target:isset? var))
    (raise (string-append (~a var) " is not declared in the generated code")))
  (let ([index-type (type-index-for (array-size 'v))])
    (declare i index-type)
    ; prevent generating an infinite loop
    (with-handlers ([string? (lambda (s) (raise (string-append "infinite loop: " s)) )])
      (check-lt-range `(< i ,(array-size var))) )
    ; generate (C code)
    (string-append "for (" (stdint:typename index-type) " i = 0; i < " (~a (array-size var)) "; i++) {\n"
                   (string-append "    " (block (string-append (~a var) "[i]")) ";\n")
                   "}\n" ) ))

;;
;; Usage example:
;;
;;     @array:each[ v x
;;         (c-printf "%d" x)
;;     ]
;;
(define-syntax-rule (array:each var x block)
  (c-array-each 'var (lambda (x) (quasiquote ,block)) ))

(define (c-declare-array type name size)
  (target:declare name (array type size))
  (_declare name (array type size))
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
