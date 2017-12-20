#lang racket

(struct symbol (varname type) #:inspector #f)
(struct range (min max step) #:inspector #f)
(struct type (byte-count range) #:inspector #f)
(struct array (type size) #:inspector #f)

;;
;; gives the number of bytes used to store `expr`
;;
(define (byte-count expr)
  (match expr
    [(symbol _ type) (type-byte-count type)]
    [(type count _) count]
    [(array type size) (* size (type-byte-count type))] ) )

(define uint8_t  (type 1 (range 0 255 1)))
(define uint16_t (type 2 (range 0 65535 1)))
(define uint32_t (type 4 (range 0 4294967295 1)))
(define uint64_t (type 8 (range 0 18446744073709551615 1)))

(define int8_t   (type 1 (range -128 127 1)))
(define int16_t  (type 2 (range -32768 32767 1)))
(define int32_t  (type 4 (range -2147483648 2147483647 1)))
(define int64_t  (type 8 (range -9223372036854775808 9223372036854775807 1)))

