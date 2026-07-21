SELECT c.id, c.name, c.email, s.org, s.office, s.district
  FROM      v4candidates AS c
  LEFT JOIN v4incumbents AS i  ON (c.seat_id = i.seat_id)
  LEFT JOIN v4seats      AS s  ON (c.seat_id = s.id)
 WHERE c.email = i.email
   AND c.email != ''
;
