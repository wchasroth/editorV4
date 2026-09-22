SELECT 'Filings' AS type, org, office, district, subdist, name, phone, email
  FROM v4filings
 WHERE party='D'
   AND (phone!='' OR email!='')

UNION ALL

SELECT 'Candidates' AS type, s.org, s.office, s.district, s.subdist, c.name, c.phone, c.email
  FROM      v4seats      AS s
  LEFT JOIN v4candidates AS c  ON (c.seat_id = s.id)
 WHERE c.party = 'D'
   AND c.name  != ''
   AND c.name  IS NOT NULL
   AND (c.phone!='' OR c.email!='')

UNION ALL

SELECT 'Incumbents' AS type, s.org, s.office, s.district, s.subdist, c.name, c.phone, c.email
  FROM      v4seats      AS s
  LEFT JOIN v4incumbents AS c  ON (c.seat_id = s.id)
 WHERE c.party = 'D'
   AND c.name  != ''
   AND c.name  IS NOT NULL
   AND (c.phone!='' OR c.email!='')

ORDER BY type, name, org, office, district, subdist;

/*
   SELECT 'Filings' AS type, count(*) from v4filings;
   SELECT 'Candidates' AS type, count(*) from v4candidates;
   SELECT 'Incumbents' AS type, count(*) from v4incumbents;
*/
