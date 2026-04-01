/*
 org          | varchar(10) | NO   | PRI |         |       |
| office       | varchar(20) | NO   | PRI |         |       |
| miv_title    | varchar(50) | NO   |     |         |       |
| api_title    | varchar(80) | NO   |     |         |       |
| ballot_order | int         | NO   | MUL | 0       |       |
| shortname    | varchar(20) | NO   |     |         |       |
| termlen      | tinyint     | NO   |     | 0       |       |
| termcycle    | smallint    | NO   |     | 0       |       |
| seats        | tinyint     | NO   |     | 0       |       |
*/

INSERT INTO v4titles (org, office, miv_title, ballot_order, shortname, seats)
   VALUES ('city', 'clerk-appt', 'Clerk (Appointed)', 76210, 'Clerk (appt)', 1);
