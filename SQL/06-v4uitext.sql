
DROP   TABLE IF EXISTS v4uitext;
CREATE TABLE           v4uitext (
   id       varchar(20)    NOT NULL DEFAULT '', primary key(id),
   what     varchar(40)    NOT NULL DEFAULT '',
   who      varchar(60)    NOT NULL DEFAULT '',
   modified datetime       NOT NULL DEFAULT CURRENT_TIMESTAMP,
   text     varchar(10000) NOT NULL DEFAULT ''
);

INSERT INTO v4uitext (id, what, who, modified, text)
   SELECT id, what, who, modified, text FROM uitext
    WHERE id IN ('editorhome', 'petitions', 'whatcanido', 'maintenance');
