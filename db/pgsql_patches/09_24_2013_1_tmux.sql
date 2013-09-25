INSERT IGNORE INTO tmux (setting, value) VALUE ('SHOWQUERY', 'FALSE');

DROP SEQUENCE IF EXISTS "allgroups_id_seq" CASCADE;
CREATE SEQUENCE "allgroups_id_seq" INCREMENT BY 1
                                  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('allgroups_id_seq', 1, true);

-- Table: allgroups
DROP TABLE IF EXISTS "allgroups" CASCADE;
CREATE TABLE "allgroups" (
  "id" int(11) NOT NULL AUTO_INCREMENT,
  "name" varchar(255) NOT NULL DEFAULT "",
  "first_record" bigint UNSIGNED NOT NULL DEFAULT "0",
  "last_record" bigint UNSIGNED NOT NULL DEFAULT "0",
  "updated" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP INDEX IF EXISTS "ix_allgroups_id" CASCADE;
CREATE INDEX ix_allgroups_id ON allgroups(id);
DROP INDEX IF EXISTS "ix_allgroups_name" CASCADE;
CREATE INDEX ix_allgroups_name ON allgroups(name);

UPDATE site SET value = '126' WHERE setting = 'sqlpatch';
