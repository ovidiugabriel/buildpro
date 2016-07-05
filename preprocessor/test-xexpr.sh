#!/bin/bash
php xexpr.php in.s > p.out
racket xexpr.rkt in.s | xmllint --format - > r.out
LINES=$(diff -u --suppress-common-lines p.out r.out | grep -v ^@ | wc -l)
LINES=$(($LINES - 2))

echo $LINES
