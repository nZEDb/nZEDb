INSERT INTO tmux (setting, value) VALUES ('SHOWQUERY', 'FALSE');

DROP SEQUENCE IF EXISTS "allgroups_id_seq" CASCADE;
CREATE SEQUENCE "allgroups_id_seq" INCREMENT BY 1
                                  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('allgroups_id_seq', 1, true);

-- Table: allgroups
DROP TABLE IF EXISTS "allgroups" CASCADE;
CREATE TABLE "allgroups" (
  "id" bigint DEFAULT nextval('allgroups_id_seq'::regclass) NOT NULL,
  "name" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "first_record" bigint DEFAULT 0 NOT NULL,
  "last_record" bigint DEFAULT 0 NOT NULL,
  "updated" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP INDEX IF EXISTS "ix_allgroups_id" CASCADE;
CREATE INDEX ix_allgroups_id ON allgroups(id);
DROP INDEX IF EXISTS "ix_allgroups_name" CASCADE;
CREATE INDEX ix_allgroups_name ON allgroups(name);

UPDATE site SET value = '126' WHERE setting = 'sqlpatch';
