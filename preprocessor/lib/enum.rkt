#lang racket

(define (enum lang name lst)
  ((match lang
     ["javascript" enum-javascript]
     ["cpp" enum-cpp]
    ) name lst))
