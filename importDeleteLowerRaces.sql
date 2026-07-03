CREATE TEMPORARY TABLE deleteLowerRaces
   SELECT c.id
     FROM      v4seats      AS s
     LEFT JOIN v4candidates AS c ON (c.seat_id = s.id)
      AND s.org NOT IN ('mi', 'mi-sen', 'mi-hou', 'us-hou', 'crt-a')
    WHERE c.source != '';

DELETE FROM v4candidates WHERE id IN (SELECT id FROM deleteLowerRaces);
