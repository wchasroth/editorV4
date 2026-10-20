/*
| Field     | Type         | Null | Key | Default           | Extra                                         |
+-----------+--------------+------+-----+-------------------+-----------------------------------------------+
| id        | int          | NO   | PRI | NULL              | auto_increment                                |
| county    | tinyint      | NO   | MUL | NULL              |                                               |
| web       | varchar(80)  | NO   |     |                   |                                               |
| facebook  | varchar(120) | NO   |     |                   |                                               |
| twitter   | varchar(60)  | NO   |     |                   |                                               |
| instagram | varchar(60)  | NO   |     |                   |                                               |
| email     | varchar(60)  | NO   |     |                   |                                               |
| volunteer | varchar(60)  | NO   |     |                   |                                               |
| bluesky   | varchar(60)  | NO   |     |                   |                                               |
| tiktok    | varchar(60)  | NO   |     |                   |                                               |
| shortdesc | varchar(60)  | NO   |     |                   |                                               |
| priority  | tinyint      | NO   |     | 0                 |                                               |
| modified  | datetime     | YES  |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |
*/


SELECT c.name, s.id, s.shortdesc, s.email, s.web, s.facebook, s.instagram, s.tiktok, s.twitter, s.bluesky, s.modified
  FROM      s4county_contacts AS s
  LEFT JOIN s4counties        AS c  ON (c.id = s.county)
 ORDER BY c.id ASC, s.priority DESC
