DROP   TABLE IF EXISTS v4redirects;

CREATE TABLE           v4redirects (
   timestamp  datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, index(timestamp),
   ip         varchar(30) NOT NULL DEFAULT '',
   ip_method  varchar( 8) NOT NULL DEFAULT '',
   redirect   varchar( 8) NOT NULL DEFAULT ''
);

