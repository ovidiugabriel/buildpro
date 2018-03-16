#lang racket

;; ============================================================================
;; This will allow compiling HTML to native code
;; ============================================================================

(require xml)

(define expr (string->xexpr "<html>
    <head>
        <title>Hello</title>
    </head>
    <body bgcolor=\"fffff\" color=\"white\">
      <p>Hi</p>
    </body>
</html>"))

;; ***
;; Functions
;; ***

(define *stack* empty)

(define (pop-stack!)
  (define arg (first *stack*))
  (set! *stack* (rest *stack*))
  arg )

(define (push-stack! arg)
  (set! *stack* (cons arg *stack*)) )

(define (create-element type)
  (format "~a = document.createElement(\"~a\");\n" type type) )

(define (set-attribute obj attr val)
  (format "~a.setAttribute(\"~a\", \"~a\");\n" obj attr val) )

(define (object-attributes object-name attrs)
  (string-join 
   (for/list ([a attrs])
     (set-attribute object-name (first a) (second a)) ) "" ) )

(define (inner-text elem text)
  (format "~a.innerText = \"~a\";\n" elem text) )

(define (append-child elem child)
  (format "~a.appendChild(~a);\n" elem child) )

(define (start-element name attr root?)
  (unless root?
    (display (append-child (first *stack*) name)) )
  (push-stack! name) )

(define (end-element name)
  (pop-stack!) )

(define (character-data data)
  (display (inner-text (first *stack*) data)) )

(define (html-element-type type)
  (string-append (string-titlecase (~a type)) "Element") )

(define (parse-html line)
  (for/list ([p line])
    (cond [(and (list? p) (> (length p) 0))
           (cond
             [(not (list? (car p)))
              (start-element (car p) null #f) ; is child
              (parse-html (cdr p))
              (end-element (car p)) ]
             [else (display (object-attributes (first *stack*) p))] ) ]
          [(not (list? p)) (character-data p)] ) ) )

(define (read-datum line)
  (unless (eof-object? line)
    (start-element (car line) null #t) ; is root
    (parse-html (cdr line))
    (end-element (car line)) ) )

;; ***
;; Main Code
;; ***

(define in (open-input-string (~a expr) ) )
(let loop ()
  (let ([line (read in)])
    (read-datum line)
    (unless (eof-object? line) (loop))))
