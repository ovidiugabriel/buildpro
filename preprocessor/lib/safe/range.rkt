#lang racket

;; storage
(define *vars* (make-hash))

;; types
(define (range α β) (list 'range α β))

(define uint8_t (range 0 255))
(define uint16_t (range 0 65535))
(define uint32_t (range 0 4294967295))
(define uint64_t (range 0 18446744073709551615))

(define int8_t (range -128 127))
(define int16_t (range -32768 32767))
(define int32_t (range -2147483648 2147483647))
(define int64_t (range -9223372036854775808 9223372036854775807))

;; functions to be used as "language statements"
(define (declare varname type) (hash-set! *vars* varname type))
(define (typeof varname) (hash-ref *vars* varname))

(define (rangeof μ)
  (match (first μ)
    ['+ (Σ-range (typeof (first (cdr μ))) (typeof (second (cdr μ)))) ] ))

(define (Σ-range α β)
  (range (+ (first (cdr α)) (first (cdr β)))
         (+ (second (cdr α)) (second (cdr β))) ))

(define (check-lt-range μ)
  (let ([type (typeof (first (cdr μ)))])
  (let ([limit (match (first type)
                 ['range (second (cdr type)) ] )])
    (when (< limit (second (cdr μ)))
      (raise-user-error (string-append (~a μ) " is a tautology because "
                                       (~a (second (cdr μ))) " is out of " (~a type) )) ) ) ) )

;; Array specific functions
(define (array type n) (list 'array type n))
(define (array-size var) (second (cdr (typeof var))))
(define (array-type var) (first (cdr (typeof var))))

;; Syntactic sugars
(define-syntax-rule (check-vector-index index vector)
  (check-lt-range `(< index ,(array-size 'vector))) )

;; ***** Examples *****

(declare 'a (range 0 2))
(declare 'b (range 0 2))

(rangeof '(+ a b)) ;; prints: '(range 0 4)

(declare 'i uint8_t)
(declare 'v (array 'int 256))

(check-vector-index i v) ;; throws: (< i 256) is a tautology because 256 is out of (range 0 255)
