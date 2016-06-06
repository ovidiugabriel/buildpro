#lang scribble/text

#include <sys/types.h>
#include <stdio.h>

#define COUNT(x) ((ssize_t)(sizeof(x)/sizeof((x)[0])))

@(define (type-len type) "COUNT")
@(define (type-implicit-value type) "0")

@(define (foreach all) 
   @(define a @(string-split all " "))
   ((lambda (type e v)
      @string-append{int n = @|(type-len type)|(@|v|);
 @|type| @|e| = @|v|[0];
 int i;
 for (i = 0; i < n; i = i + 1, @|e| = @|v|[i])
}
      ) @(first a) @(second a) @(third a)) 
   )

int main()
{
    int v[5] = {0, 1, 2, 3, 4};
    @foreach{ int e v } {
        printf("%d\n", e);
    }
    return 0;
}
