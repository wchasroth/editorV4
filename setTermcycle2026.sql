UPDATE v4seats 
   SET termcycle = 2026
 WHERE termlen     > 0
   AND termlen % 2 = 0
   AND termcycle   > 0
   AND  ( (termcycle + 6 * termlen) - 2026) % termlen = 0
