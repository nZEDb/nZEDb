DROP SEQUENCE IF EXISTS "logging_id_seq" CASCADE;
CREATE SEQUENCE "logging_id_seq" INCREMENT BY 1
                                  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('logging_id_seq', 1, true);
ALTER TABLE `logging` ADD id bigint DEFAULT nextval('logging_id_seq'::regclass) NOT NULL

UPDATE `site` set `value` = '181' where `setting` = 'sqlpatch';
