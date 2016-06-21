#lang racket

;;
;; Usage Example:
;;
;;  int v[5] = {0, 1, 2, 3, 4};
;;    @foreach["int e v"]{
;;        printf("%d\n", e);
;;    }
;;

(require racket/match)

(provide foreach)

(define (tab n) (build-string (* 4 n) (lambda (i) #\ )))

(define nl (~a #\newline))

(define (type-len type)
  (match type
    ["int" "COUNT"]
    ))

(define (type-implicit-value type)
  (match type
    ["int" "0"]
    ))

(define (foreach all block) 
  (define arg (string-split all " "))
  ((lambda (type e v)
     (string-append
      "{" nl
      (tab 2) "int n = " (type-len type) "(" v ");" nl
      (tab 2) type " " e " = " v "[0];" nl
      (tab 2) "int i;" nl
      (tab 2) "for (i = 0; i < n; i = i + 1, " e " = " v "[i]) {" nl
      (tab 3) block nl
      (tab 2) "}" nl
      (tab 2) "}"
      )
     ) (first arg) (second arg) (third arg) )
  )
