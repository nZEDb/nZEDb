DROP SEQUENCE IF EXISTS "nzbs_id_seq" CASCADE;
CREATE SEQUENCE "nzbs_id_seq" INCREMENT BY 1
                                  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('nzbs_id_seq', 1, true);

-- Table: nzbs
DROP TABLE IF EXISTS "nzbs" CASCADE;
CREATE TABLE "nzbs" (
  "id" bigint DEFAULT nextval('nzbs_id_seq'::regclass) NOT NULL,
  "message_id" character varying(255) DEFAULT ''::character varying NOT NULL,
  "groupname" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "subject" character varying(1000) DEFAULT '0'::character varying NOT NULL,
  "collectionhash" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "filesize" numeric(20, 0) DEFAULT 0 NOT NULL,
  "partnumber" bigint DEFAULT 0 NOT NULL,
  "totalparts" bigint DEFAULT 0 NOT NULL,
  "postdate" timestamp without time zone,
  "dateadded" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL
)
WITHOUT OIDS;

ALTER TABLE "nzbs" ADD CONSTRAINT "id_pkey" PRIMARY KEY("id");
CREATE INDEX "nzbs_partnumber" ON "nzbs" ("partnumber");
CREATE INDEX "nzbs_collectionhash" ON "nzbs" ("collectionhash");

UPDATE site SET value = '117' WHERE setting = 'sqlpatch';
