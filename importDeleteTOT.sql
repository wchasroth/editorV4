DELETE FROM v4filings 
 WHERE org in ('us', 'us-sen', 'us-hou', 'mi', 'mi-sen', 'mi-hou', 'crt-sup', 'crt-a')
    OR org LIKE 'mi%';

