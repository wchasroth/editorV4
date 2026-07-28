DELETE FROM v4filings 
 WHERE org LIKE 'us%' 
    OR org LIKE 'mi%'
    OR org = 'crt-sup'
    OR org = 'crt-a';

