DROP   TABLE IF EXISTS s4libraries;

CREATE TABLE           s4libraries (
    id        int         AUTO_INCREMENT PRIMARY KEY,
    in_county smallint    NOT NULL DEFAULT 0,
    name      varchar(40) NOT NULL DEFAULT '',
    school    int         NOT NULL DEFAULT 0,
    juris     int         NOT NULL DEFAULT 0,
    county    smallint    NOT NULL DEFAULT 0
);

INSERT INTO s4libraries (in_county, name, school) VALUES (81, 'AADL', 2820);

DELETE FROM s4titles WHERE org LIKE 'lib%';

INSERT INTO s4titles (org, miv_title, ballot_order, shortname, miv_title_es, shortname_es)
   VALUES ('lib', 'Library Board', 160500, 'Board', 'Junta de la biblioteca', 'Junta');
