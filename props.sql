UPDATE s4titles SET shortname='Prop', seats=0 WHERE office in ('prop', 'town-prop') OR org='mi-prop';

DELETE FROM s4titles WHERE office='letter';

UPDATE s4titles SET miv_title='City&nbsp;Prop'    WHERE org='city' AND office='prop';
UPDATE s4titles SET miv_title='County&nbsp;Prop'  WHERE org='cnty' AND office='prop';
UPDATE s4titles SET miv_title='Village&nbsp;Prop' WHERE org='vil'  AND office='prop';

DELETE FROM s4titles WHERE org='cnty' AND office='prop-state';

INSERT INTO s4titles (org, office, miv_title, ballot_order, shortname, seats)
  values ('cnty', 'prop-state', 'State Prop', 200010, 'Prop-State', 0);

UPDATE s4titles SET shortname='Prop-County' WHERE org='cnty' and office='prop';

UPDATE s4titles SET miv_title='State&nbsp;Prop', seats=0 WHERE org='mi-prop';
