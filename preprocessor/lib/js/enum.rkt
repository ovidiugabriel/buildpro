#lang racket

(define (enum-javascript name lst)
  (string-append "var " name " = {"
                 (string-join
                  (map (λ (cns)
                         (string-append "\"" (~a (car cns)) "\" : " (~a (cdr cns)))
                         ) ml) ", ") "};")
  )
