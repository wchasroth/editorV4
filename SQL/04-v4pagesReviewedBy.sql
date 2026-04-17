
ALTER TABLE v4pagesReviewed ADD column who varchar(60) NOT NULL DEFAULT '';

ALTER TABLE v4pagesReviewed ADD column dt  datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP;
