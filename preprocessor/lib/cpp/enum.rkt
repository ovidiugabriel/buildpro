#lang racket

(define (enum-cpp name lst)
  (string-append "enum T_" name " {\n"
                 (string-join
                  (map (λ (cns)
                         (string-append (~a (car cns)) " = " (~a (cdr cns)))
                         ) lst) ", \n") "\n};\n" "typedef enum T_" name " " name ";")
  )
