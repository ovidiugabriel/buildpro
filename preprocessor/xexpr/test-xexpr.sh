#!/bin/bash
echo "[*] Running PHP ..."
php xexpr.php in.s | xmllint --format - > p.out

echo "[*] Running Racket ..."
racket xexpr.rkt in.s | xmllint --format - > r.out

LINES=$(diff -u --suppress-common-lines p.out r.out | grep -v ^@ | wc -l)
if [ "0" != "$LINES" ] ; then
  LINES=$(($LINES - 2))

  echo "$LINES line(s)"

  diff -u p.out r.out
fi
