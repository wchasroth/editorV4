DROP   TABLE IF EXISTS v4filings;

CREATE TABLE           v4filings (
   id          char   (  32) NOT NULL DEFAULT '', PRIMARY KEY(id),
   org         varchar(  10) NOT NULL DEFAULT '', INDEX(org),
   office      varchar(  20) NOT NULL DEFAULT '', INDEX(office),
   district    varchar(  10) NOT NULL DEFAULT '', INDEX(district),
   subdist     tinyint       NOT NULL DEFAULT  0,
   seatnum     tinyint       NOT NULL DEFAULT  0,

   name         varchar( 100) NOT NULL DEFAULT '',
   party        char(1)       NOT NULL DEFAULT '',
   web          varchar( 200) NOT NULL DEFAULT '',
   email        varchar( 100) NOT NULL DEFAULT '',
   phone        varchar(  36) NOT NULL DEFAULT '',
   headshot_url varchar( 240) NOT NULL DEFAULT '',
   description  varchar(8000) NOT NULL DEFAULT '',

   termyears   tinyint       NOT NULL DEFAULT  0,
   partialterm tinyint       NOT NULL DEFAULT  0,
   partialend  smallint      NOT NULL DEFAULT  0,
   incumbent   tinyint       NOT NULL DEFAULT  0,
   termend     mediumint     NOT NULL DEFAULT  0
)

/*
   address?

===================================
| termlen   | tinyint     | NO   |     | 0       |                |
| termcycle | smallint    | NO   |     | 0       |                |
| is_open   | tinyint     | NO   |     | 0       |                |
*/
