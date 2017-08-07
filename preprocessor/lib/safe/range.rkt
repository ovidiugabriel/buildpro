#lang racket

;; storage
(define *vars* (make-hash))

;; types
(define (range α β) (list 'range α β))

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
      (raise-user-error (string-append (~a (second (cdr μ))) " is out of " (~a type) )) ) ) ) )


;; ***** Examples *****

(declare 'a  (range 0 2))
(declare 'b (range 0 2))

(rangeof '(+ a b)) ;; prints: '(range 0 4)

(declare 'i (range 0 255))
(check-lt-range '(< i 256)) ;; throws '256 is out of (range 0 255)'
