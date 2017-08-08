#lang racket

(require "range.rkt")

(provide ;; unsigned max
 UINT8_MAX
 UINT16_MAX
 UINT32_MAX
 UINT64_MAX

 ;; signed max
 INT8_MAX
 INT16_MAX
 INT32_MAX
 INT64_MAX

 ;; unsigned min
 UINT8_MIN
 UINT16_MIN
 UINT32_MIN
 UINT64_MIN

 ;; signed min
 INT8_MIN
 INT16_MIN
 INT32_MIN
 INT64_MIN)

(define UINT8_MAX (range-max uint8_t))
(define UINT16_MAX (range-max uint16_t))
(define UINT32_MAX (range-max uint32_t))
(define UINT64_MAX (range-max uint64_t))

(define INT8_MAX (range-max int8_t))
(define INT16_MAX (range-max int16_t))
(define INT32_MAX (range-max int32_t))
(define INT64_MAX (range-max int64_t))

(define UINT8_MIN (range-min uint8_t))
(define UINT16_MIN (range-min uint16_t))
(define UINT32_MIN (range-min uint32_t))
(define UINT64_MIN (range-min uint64_t))

(define INT8_MIN (range-min int8_t))
(define INT16_MIN (range-min int16_t))
(define INT32_MIN (range-min int32_t))
(define INT64_MIN (range-min int64_t))
