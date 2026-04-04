
DROP TABLE IF EXISTS v4courts;

CREATE TABLE v4courts LIKE court;
INSERT INTO  v4courts SELECT * FROM court;

ALTER TABLE  v4courts MODIFY COLUMN county_id tinyint      NOT NULL DEFAULT  0;
ALTER TABLE  v4courts MODIFY COLUMN type      varchar(  8) NOT NULL DEFAULT '';
ALTER TABLE  v4courts MODIFY COLUMN name      varchar( 40) NOT NULL DEFAULT '';
ALTER TABLE  v4courts MODIFY COLUMN shortname varchar(  8) NOT NULL DEFAULT '';
ALTER TABLE  v4courts MODIFY COLUMN url       varchar(120) NOT NULL DEFAULT '';
ALTER TABLE  v4courts MODIFY COLUMN ranking   tinyint      NOT NULL DEFAULT  0;

UPDATE v4courts SET type='crt-a'  WHERE type='A';
UPDATE v4courts SET type='crt-c'  WHERE type='C';
UPDATE v4courts SET type='crt-d'  WHERE type='D';
UPDATE v4courts SET type='crt-m'  WHERE type='M';
UPDATE v4courts SET type='crt-p'  WHERE type='P';
UPDATE v4courts SET type='crt-pd' WHERE type='PD';

/*
| id        | int      | NO   | PRI | NULL    | auto_increment |
| county_id | int      | YES  | MUL | NULL    |                |
| type      | tinytext | YES  |     | NULL    |                |
| name      | text     | YES  |     | NULL    |                |
| shortname | tinytext | YES  |     | NULL    |                |
| url       | text     | YES  |     | NULL    |                |
| ranking   | smallint | NO   |     | 0       |                |

| C    |
| D    |
| P    |
| PD   |
| M    |
| A    |
*/

