SELECT c.id AS ctyid, c.name AS ctyname, 'City' as type, j.id, j.name
  FROM      s4counties      AS c
  LEFT JOIN s4jurisdictions AS j ON (j.county_id = c.id)
 WHERE j.type = 'c'
   AND c.id IN (SELECT county from v4imported)
   AND j.id IN 
      ( SELECT j.id
          FROM      s4counties      AS c
          LEFT JOIN s4jurisdictions AS j ON (j.county_id = c.id)
         WHERE j.type = 'c'
           AND c.id NOT IN (SELECT county from v4imported))

UNION

SELECT c.id AS ctyid, c.name AS ctyname, 'Village' as type, v.id, v.name
  FROM      s4counties AS c
  LEFT JOIN s4villages AS v ON (v.county_id = c.id)
 WHERE c.id IN (SELECT county from v4imported)
   AND v.id IN 
      ( SELECT v.id
          FROM      s4counties AS c
          LEFT JOIN s4villages AS v ON (v.county_id = c.id)
         WHERE c.id NOT IN (SELECT county from v4imported))

UNION

SELECT c.id AS ctyid, c.name AS ctyname, 'School' as type, s.id, s.name
  FROM      s4counties AS c
  LEFT JOIN s4schools  AS s ON (s.county_id = c.id)
 WHERE c.id IN (SELECT county from v4imported)
   AND s.id IN 
      ( SELECT s.id
          FROM      s4counties AS c
          LEFT JOIN s4schools  AS s ON (s.county_id = c.id)
         WHERE c.id NOT IN (SELECT county from v4imported))
UNION

SELECT c.id AS ctyid, c.name AS ctyname, 'College' as type, g.id, g.name
  FROM      s4counties            AS c
  LEFT JOIN v4commcolleges_county AS y ON (y.county_id = c.id)
  LEFT JOIN s4commcolleges        AS g ON (g.id = y.id)
 WHERE c.id IN (SELECT county from v4imported)
   AND g.id IN 
      ( SELECT g.id
          FROM      s4counties            AS c
          LEFT JOIN v4commcolleges_county AS y ON (y.county_id = c.id)
          LEFT JOIN s4commcolleges        AS g ON (g.id = y.id)
         WHERE c.id NOT IN (SELECT county from v4imported))

ORDER BY ctyname, type, name
;


