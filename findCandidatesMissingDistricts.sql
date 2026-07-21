
SELECT DISTINCT f.org, f.office, f.district, f.subdist, f.name
  FROM v4filings    AS f
  LEFT JOIN v4seats AS s  ON (s.org=f.org AND s.office=f.office AND s.district=f.district)
 WHERE f.subdist = 0 AND s.subdist > 0
 ORDER BY f.org, f.district, f.name

