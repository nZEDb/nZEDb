SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;

-- Table: anidb
DROP TABLE IF EXISTS "anidb" CASCADE;
CREATE TABLE "anidb" (
  "anidbid" integer NOT NULL,
  "imdbid" integer NOT NULL,
  "tvdbid" integer NOT NULL,
  "title" character varying(255) NOT NULL,
  "type" character varying(32) NOT NULL,
  "startdate" date,
  "enddate" date,
  "related" character varying(1024) NOT NULL,
  "creators" character varying(1024) NOT NULL,
  "description" text NOT NULL,
  "rating" character varying(5) NOT NULL,
  "picture" character varying(16) NOT NULL,
  "categories" character varying(1024) NOT NULL,
  "characters" character varying(1024) NOT NULL,
  "epnos" character varying(2048) NOT NULL,
  "airdates" text NOT NULL,
  "episodetitles" text NOT NULL,
  "unixtime" bigint NOT NULL
)
WITHOUT OIDS;
ALTER TABLE "anidb" ADD CONSTRAINT "anidb_id_pkey" PRIMARY KEY("anidbid");

-- Table: animetitles
DROP TABLE IF EXISTS "animetitles" CASCADE;
CREATE TABLE "animetitles" (
  "anidbid" bigint NOT NULL,
  "title" character varying(255) NOT NULL,
  "unixtime" bigint NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "binaries_id_seq" CASCADE;
CREATE SEQUENCE "binaries_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('binaries_id_seq', 1, true);

-- Table: binaries
DROP TABLE IF EXISTS "binaries" CASCADE;
CREATE TABLE "binaries" (
  "id" numeric(20, 0) DEFAULT nextval('binaries_id_seq'::regclass) NOT NULL,
  "name" character varying(1000) DEFAULT ''::character varying NOT NULL,
  "collectionid" bigint DEFAULT 0 NOT NULL,
  "filenumber" bigint DEFAULT 0 NOT NULL,
  "totalparts" bigint DEFAULT 0 NOT NULL,
  "binaryhash" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "partcheck" smallint DEFAULT 0 NOT NULL,
  "partsize" numeric(20, 0) DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "binaryblacklist_id_seq" CASCADE;
CREATE SEQUENCE "binaryblacklist_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('binaryblacklist_id_seq', 10, true);

-- Table: binaryblacklist
DROP TABLE IF EXISTS "binaryblacklist" CASCADE;
CREATE TABLE "binaryblacklist" (
  "id" bigint DEFAULT nextval('binaryblacklist_id_seq'::regclass) NOT NULL,
  "groupname" character varying(255),
  "regex" character varying(2000) NOT NULL,
  "msgcol" bigint DEFAULT 1 NOT NULL,
  "optype" bigint DEFAULT 1 NOT NULL,
  "status" bigint DEFAULT 1 NOT NULL,
  "description" character varying(1000)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "bookinfo_id_seq" CASCADE;
CREATE SEQUENCE "bookinfo_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('bookinfo_id_seq', 1, true);

-- Table: bookinfo
DROP TABLE IF EXISTS "bookinfo" CASCADE;
CREATE TABLE "bookinfo" (
  "id" bigint DEFAULT nextval('bookinfo_id_seq'::regclass) NOT NULL,
  "title" character varying(255) NOT NULL,
  "author" character varying(255) NOT NULL,
  "asin" character varying(128),
  "isbn" character varying(128),
  "ean" character varying(128),
  "url" character varying(1000),
  "salesrank" bigint,
  "publisher" character varying(255),
  "publishdate" timestamp without time zone,
  "pages" character varying(128),
  "overview" character varying(3000),
  "genre" character varying(255) NOT NULL,
  "cover" smallint DEFAULT 0 NOT NULL,
  "createddate" timestamp without time zone NOT NULL,
  "updateddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "category_id_seq" CASCADE;
CREATE SEQUENCE "category_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('category_id_seq', 8061, true);

-- Table: category
DROP TABLE IF EXISTS "category" CASCADE;
CREATE TABLE "category" (
  "id" integer DEFAULT nextval('category_id_seq'::regclass) NOT NULL,
  "title" character varying(255) NOT NULL,
  "parentid" integer,
  "status" integer DEFAULT 1 NOT NULL,
  "description" character varying(255),
  "disablepreview" smallint DEFAULT 0 NOT NULL,
  "minsize" numeric(20, 0) DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "collections_id_seq" CASCADE;
CREATE SEQUENCE "collections_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('collections_id_seq', 1, true);

-- Table: collections
DROP TABLE IF EXISTS "collections" CASCADE;
CREATE TABLE "collections" (
  "id" bigint DEFAULT nextval('collections_id_seq'::regclass) NOT NULL,
  "subject" character varying(255) DEFAULT ''::character varying NOT NULL,
  "fromname" character varying(255) DEFAULT ''::character varying NOT NULL,
  "date" timestamp without time zone,
  "xref" character varying(255) DEFAULT ''::character varying NOT NULL,
  "totalfiles" bigint DEFAULT 0 NOT NULL,
  "groupid" bigint DEFAULT 0 NOT NULL,
  "collectionhash" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "dateadded" timestamp without time zone,
  "filecheck" smallint DEFAULT 0 NOT NULL,
  "filesize" numeric(20, 0) DEFAULT 0 NOT NULL,
  "releaseid" integer
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "consoleinfo_id_seq" CASCADE;
CREATE SEQUENCE "consoleinfo_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('consoleinfo_id_seq', 1, true);

-- Table: consoleinfo
DROP TABLE IF EXISTS "consoleinfo" CASCADE;
CREATE TABLE "consoleinfo" (
  "id" bigint DEFAULT nextval('consoleinfo_id_seq'::regclass) NOT NULL,
  "title" character varying(255) NOT NULL,
  "asin" character varying(128),
  "url" character varying(1000),
  "salesrank" bigint,
  "platform" character varying(255),
  "publisher" character varying(255),
  "genreid" integer,
  "esrb" character varying(255),
  "releasedate" timestamp without time zone,
  "review" character varying(3000),
  "cover" smallint DEFAULT 0 NOT NULL,
  "createddate" timestamp without time zone NOT NULL,
  "updateddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "content_id_seq" CASCADE;
CREATE SEQUENCE "content_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('content_id_seq', 4, true);

-- Table: content
DROP TABLE IF EXISTS "content" CASCADE;
CREATE TABLE "content" (
  "id" integer DEFAULT nextval('content_id_seq'::regclass) NOT NULL,
  "title" character varying(255) NOT NULL,
  "url" character varying(2000),
  "body" text,
  "metadescription" character varying(1000) NOT NULL,
  "metakeywords" character varying(1000) NOT NULL,
  "contenttype" integer NOT NULL,
  "showinmenu" integer NOT NULL,
  "status" integer NOT NULL,
  "ordinal" integer,
  "role" integer DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "forumpost_id_seq" CASCADE;
CREATE SEQUENCE "forumpost_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('forumpost_id_seq', 2, true);

-- Table: forumpost
DROP TABLE IF EXISTS "forumpost" CASCADE;
CREATE TABLE "forumpost" (
  "id" bigint DEFAULT nextval('forumpost_id_seq'::regclass) NOT NULL,
  "forumid" integer DEFAULT 1 NOT NULL,
  "parentid" integer DEFAULT 0 NOT NULL,
  "userid" bigint NOT NULL,
  "subject" character varying(255) NOT NULL,
  "message" text NOT NULL,
  "locked" smallint DEFAULT 0 NOT NULL,
  "sticky" smallint DEFAULT 0 NOT NULL,
  "replies" bigint DEFAULT 0 NOT NULL,
  "createddate" timestamp without time zone NOT NULL,
  "updateddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "genres_id_seq" CASCADE;
CREATE SEQUENCE "genres_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('genres_id_seq', 150, true);

-- Table: genres
DROP TABLE IF EXISTS "genres" CASCADE;
CREATE TABLE "genres" (
  "id" integer DEFAULT nextval('genres_id_seq'::regclass) NOT NULL,
  "title" character varying(255) NOT NULL,
  "type" integer,
  "disabled" smallint DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "groups_id_seq" CASCADE;
CREATE SEQUENCE "groups_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('groups_id_seq', 177, true);

-- Table: groups
DROP TABLE IF EXISTS "groups" CASCADE;
CREATE TABLE "groups" (
  "id" integer DEFAULT nextval('groups_id_seq'::regclass) NOT NULL,
  "name" character varying(255) DEFAULT ''::character varying NOT NULL,
  "backfill_target" integer DEFAULT 1 NOT NULL,
  "first_record" numeric(20, 0) DEFAULT 0 NOT NULL,
  "first_record_postdate" timestamp without time zone,
  "last_record" numeric(20, 0) DEFAULT 0 NOT NULL,
  "last_record_postdate" timestamp without time zone,
  "last_updated" timestamp without time zone,
  "minfilestoformrelease" integer,
  "minsizetoformrelease" bigint,
  "active" smallint DEFAULT 0 NOT NULL,
  "backfill" smallint DEFAULT 0 NOT NULL,
  "description" character varying(255) DEFAULT ''::character varying
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "logging_id_seq" CASCADE;
CREATE SEQUENCE "logging_id_seq" INCREMENT BY 1
                                  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('logging_id_seq', 1, true);

-- Table: logging
DROP TABLE IF EXISTS "logging" CASCADE;
CREATE TABLE "logging" (
  "id" bigint DEFAULT nextval('logging_id_seq'::regclass) NOT NULL,
  "time" timestamp without time zone,
  "username" character varying(50),
  "host" character varying(40)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "menu_id_seq" CASCADE;
CREATE SEQUENCE "menu_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('menu_id_seq', 22, true);

-- Table: menu
DROP TABLE IF EXISTS "menu" CASCADE;
CREATE TABLE "menu" (
  "id" bigint DEFAULT nextval('menu_id_seq'::regclass) NOT NULL,
  "href" character varying(2000) DEFAULT ''::character varying NOT NULL,
  "title" character varying(2000) DEFAULT ''::character varying NOT NULL,
  "newwindow" bigint DEFAULT 0 NOT NULL,
  "tooltip" character varying(2000) DEFAULT ''::character varying NOT NULL,
  "role" bigint NOT NULL,
  "ordinal" bigint NOT NULL,
  "menueval" character varying(2000) DEFAULT ''::character varying NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "movieinfo_id_seq" CASCADE;
CREATE SEQUENCE "movieinfo_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('movieinfo_id_seq', 1, true);

-- Table: movieinfo
DROP TABLE IF EXISTS "movieinfo" CASCADE;
CREATE TABLE "movieinfo" (
  "id" bigint DEFAULT nextval('movieinfo_id_seq'::regclass) NOT NULL,
  "imdbid" integer NOT NULL,
  "tmdbid" bigint,
  "title" character varying(255) NOT NULL,
  "tagline" character varying(1024) NOT NULL,
  "rating" character varying(4) NOT NULL,
  "plot" character varying(1024) NOT NULL,
  "year" character varying(4) NOT NULL,
  "genre" character varying(64) NOT NULL,
  "type" character varying(32) NOT NULL,
  "director" character varying(64) NOT NULL,
  "actors" character varying(2000) NOT NULL,
  "language" character varying(64) NOT NULL,
  "cover" smallint DEFAULT 0 NOT NULL,
  "backdrop" smallint DEFAULT 0 NOT NULL,
  "createddate" timestamp without time zone NOT NULL,
  "updateddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "musicinfo_id_seq" CASCADE;
CREATE SEQUENCE "musicinfo_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('musicinfo_id_seq', 1, true);

-- Table: musicinfo
DROP TABLE IF EXISTS "musicinfo" CASCADE;
CREATE TABLE "musicinfo" (
  "id" bigint DEFAULT nextval('musicinfo_id_seq'::regclass) NOT NULL,
  "title" character varying(255) NOT NULL,
  "asin" character varying(128),
  "url" character varying(1000),
  "salesrank" bigint,
  "artist" character varying(255),
  "publisher" character varying(255),
  "releasedate" timestamp without time zone,
  "review" character varying(3000),
  "year" character varying(4) NOT NULL,
  "genreid" integer,
  "tracks" character varying(3000),
  "cover" smallint DEFAULT 0 NOT NULL,
  "createddate" timestamp without time zone NOT NULL,
  "updateddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

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

DROP SEQUENCE IF EXISTS "shortgroups_id_seq" CASCADE;
CREATE SEQUENCE "shortgroups_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('shortgroups_id_seq', 1, true);

-- Table: shortgroups
DROP TABLE IF EXISTS "shortgroups" CASCADE;
CREATE TABLE "shortgroups" (
  "id" bigint DEFAULT nextval('shortgroups_id_seq'::regclass) NOT NULL,
  "name" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "first_record" bigint DEFAULT 0 NOT NULL,
  "last_record" bigint DEFAULT 0 NOT NULL,
  "updated" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "partrepair_id_seq" CASCADE;
CREATE SEQUENCE "partrepair_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('partrepair_id_seq', 1, true);

-- Table: partrepair
DROP TABLE IF EXISTS "partrepair" CASCADE;
CREATE TABLE "partrepair" (
  "id" bigint DEFAULT nextval('partrepair_id_seq'::regclass) NOT NULL,
  "numberid" numeric(20, 0) NOT NULL,
  "groupid" bigint NOT NULL,
  "attempts" smallint DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "parts_id_seq" CASCADE;
CREATE SEQUENCE "parts_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('parts_id_seq', 1, true);

-- Table: parts
DROP TABLE IF EXISTS "parts" CASCADE;
CREATE TABLE "parts" (
  "id" numeric(20, 0) DEFAULT nextval('parts_id_seq'::regclass) NOT NULL,
  "binaryid" numeric(20, 0) DEFAULT 0 NOT NULL,
  "messageid" character varying(255) DEFAULT ''::character varying NOT NULL,
  "number" numeric(20, 0) DEFAULT 0 NOT NULL,
  "partnumber" bigint DEFAULT 0 NOT NULL,
  "size" numeric(20, 0) DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "predb_id_seq" CASCADE;
CREATE SEQUENCE "predb_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('predb_id_seq', 1, true);

-- Table: predb
DROP TABLE IF EXISTS "predb" CASCADE;
CREATE TABLE "predb" (
  "id" bigint DEFAULT nextval('predb_id_seq'::regclass) NOT NULL,
  "title" character varying(255) DEFAULT ''::character varying NOT NULL,
  "nfo" character varying(500),
  "size" character varying(50),
  "category" character varying(255),
  "predate" timestamp without time zone,
  "source" character varying(50) DEFAULT ''::character varying NOT NULL,
  "md5" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "requestid" integer DEFAULT 0 NOT NULL,
  "groupid" integer DEFAULT 0 NOT NULL,
  /* Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked */
  "nuked" smallint DEFAULT 0 NOT NULL,
  /* If this pre is nuked, what is the reason? */
  "nukereason" character varying(255),
  /* How many files does this pre have ? */
  "files" character varying(50)

)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "releaseaudio_id_seq" CASCADE;
CREATE SEQUENCE "releaseaudio_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('releaseaudio_id_seq', 1, true);

-- Table: releaseaudio
DROP TABLE IF EXISTS "releaseaudio" CASCADE;
CREATE TABLE "releaseaudio" (
  "id" bigint DEFAULT nextval('releaseaudio_id_seq'::regclass) NOT NULL,
  "releaseid" bigint NOT NULL,
  "audioid" bigint NOT NULL,
  "audioformat" character varying(50),
  "audiomode" character varying(50),
  "audiobitratemode" character varying(50),
  "audiobitrate" character varying(10),
  "audiochannels" character varying(25),
  "audiosamplerate" character varying(25),
  "audiolibrary" character varying(50),
  "audiolanguage" character varying(50),
  "audiotitle" character varying(50)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "releasecomment_id_seq" CASCADE;
CREATE SEQUENCE "releasecomment_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('releasecomment_id_seq', 1, true);

-- Table: releasecomment
DROP TABLE IF EXISTS "releasecomment" CASCADE;
CREATE TABLE "releasecomment" (
  "id" bigint DEFAULT nextval('releasecomment_id_seq'::regclass) NOT NULL,
  "releaseid" bigint NOT NULL,
  "text" character varying(2000) DEFAULT ''::character varying NOT NULL,
  "userid" bigint NOT NULL,
  "createddate" timestamp without time zone,
  "host" character varying(15)
)
WITHOUT OIDS;

-- Table: releaseextrafull
DROP TABLE IF EXISTS "releaseextrafull" CASCADE;
CREATE TABLE "releaseextrafull" (
  "releaseid" bigint NOT NULL,
  "mediainfo" text
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "releasefiles_id_seq" CASCADE;
CREATE SEQUENCE "releasefiles_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('releasefiles_id_seq', 1, true);

-- Table: releasefiles
DROP TABLE IF EXISTS "releasefiles" CASCADE;
CREATE TABLE "releasefiles" (
  "id" integer DEFAULT nextval('releasefiles_id_seq'::regclass) NOT NULL,
  "releaseid" bigint NOT NULL,
  "name" character varying(255),
  "size" numeric(20, 0) DEFAULT 0 NOT NULL,
  "createddate" timestamp without time zone,
  "passworded" smallint DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "releasenfo_id_seq" CASCADE;
CREATE SEQUENCE "releasenfo_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('releasenfo_id_seq', 1, true);

-- Table: releasenfo
DROP TABLE IF EXISTS "releasenfo" CASCADE;
CREATE TABLE "releasenfo" (
  "id" bigint DEFAULT nextval('releasenfo_id_seq'::regclass) NOT NULL,
  "releaseid" bigint NOT NULL,
  "nfo" text
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "releases_id_seq" CASCADE;
CREATE SEQUENCE "releases_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('releases_id_seq', 1, true);

-- Table: releases
DROP TABLE IF EXISTS "releases" CASCADE;
CREATE TABLE "releases" (
  "id" bigint DEFAULT nextval('releases_id_seq'::regclass) NOT NULL,
  "name" character varying(255) DEFAULT ''::character varying NOT NULL,
  "searchname" character varying(255) DEFAULT ''::character varying NOT NULL,
  "totalpart" integer DEFAULT 0,
  "groupid" bigint DEFAULT 0 NOT NULL,
  "size" numeric(20, 0) DEFAULT 0 NOT NULL,
  "postdate" timestamp without time zone,
  "adddate" timestamp without time zone,
  "updatetime" timestamp without time zone DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "guid" character varying(50) NOT NULL,
  "fromname" character varying(255),
  "completion" real DEFAULT 0 NOT NULL,
  "categoryid" integer NOT NULL DEFAULT 7010,
  "rageid" integer,
  "seriesfull" character varying(15),
  "season" character varying(10),
  "episode" character varying(10),
  "tvtitle" character varying(255),
  "tvairdate" timestamp without time zone,
  "imdbid" integer,
  "musicinfoid" integer,
  "consoleinfoid" integer,
  "bookinfoid" integer,
  "anidbid" integer,
  "preid" bigint DEFAULT 0 NOT NULL,
  "grabs" bigint DEFAULT 0 NOT NULL,
  "comments" integer DEFAULT 0 NOT NULL,
  "passwordstatus" smallint DEFAULT 0 NOT NULL,
  "rarinnerfilecount" integer DEFAULT 0 NOT NULL,
  "haspreview" smallint DEFAULT 0 NOT NULL,
  "nfostatus" smallint DEFAULT 0 NOT NULL,
  "jpgstatus" smallint DEFAULT 0 NOT NULL,
  "videostatus" smallint DEFAULT 0 NOT NULL,
  "audiostatus" smallint DEFAULT 0 NOT NULL,
  "dehashstatus" smallint DEFAULT 0 NOT NULL,
  "reqidstatus" smallint DEFAULT 0 NOT NULL,
  "nzb_guid" character varying(50),
  "nzbstatus" smallint DEFAULT 0 NOT NULL,
  "iscategorized" smallint DEFAULT 0 NOT NULL,
  "isrenamed" smallint DEFAULT 0 NOT NULL,
  "ishashed" smallint DEFAULT 0 NOT NULL,
  "isrequestid" smallint DEFAULT 0 NOT NULL,
  "proc_pp" smallint DEFAULT 0 NOT NULL,
  "proc_sorter" smallint DEFAULT 0 NOT NULL,
  "proc_par2" smallint DEFAULT 0 NOT NULL,
  "proc_nfo" smallint DEFAULT 0 NOT NULL,
  "proc_files" smallint DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "releasesubs_id_seq" CASCADE;
CREATE SEQUENCE "releasesubs_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('releasesubs_id_seq', 1, true);

-- Table: releasesubs
DROP TABLE IF EXISTS "releasesubs" CASCADE;
CREATE TABLE "releasesubs" (
  "id" bigint DEFAULT nextval('releasesubs_id_seq'::regclass) NOT NULL,
  "releaseid" bigint NOT NULL,
  "subsid" bigint NOT NULL,
  "subslanguage" character varying(50) NOT NULL
)
WITHOUT OIDS;

-- Table: releasevideo
DROP TABLE IF EXISTS "releasevideo" CASCADE;
CREATE TABLE "releasevideo" (
  "releaseid" bigint NOT NULL,
  "containerformat" character varying(50),
  "overallbitrate" character varying(20),
  "videoduration" character varying(20),
  "videoformat" character varying(50),
  "videocodec" character varying(50),
  "videowidth" integer,
  "videoheight" integer,
  "videoaspect" character varying(10),
  "videoframerate" real,
  "videolibrary" character varying(50)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "site_id_seq" CASCADE;
CREATE SEQUENCE "site_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('site_id_seq', 115, true);

-- Table: site
DROP TABLE IF EXISTS "site" CASCADE;
CREATE TABLE "site" (
  "id" bigint DEFAULT nextval('site_id_seq'::regclass) NOT NULL,
  "setting" character varying(64) NOT NULL,
  "value" character varying(19000),
  "updateddate" timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "tmux_id_seq" CASCADE;
CREATE SEQUENCE "tmux_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('tmux_id_seq', 65, true);

-- Table: tmux
DROP TABLE IF EXISTS "tmux" CASCADE;
CREATE TABLE "tmux" (
  "id" bigint DEFAULT nextval('tmux_id_seq'::regclass) NOT NULL,
  "setting" character varying(64) NOT NULL,
  "value" character varying(19000),
  "updateddate" timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "country_id_seq" CASCADE;
CREATE SEQUENCE "country_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('country_id_seq', 1, true);

-- Table: country
DROP TABLE IF EXISTS "countries" CASCADE;
CREATE TABLE "countries" (
  "id" bigint DEFAULT nextval('country_id_seq'::regclass) NOT NULL,
  "name" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "code" character varying(2) NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "tvrage_id_seq" CASCADE;
CREATE SEQUENCE "tvrage_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('tvrage_id_seq', 10025, true);

-- Table: tvrage
DROP TABLE IF EXISTS "tvrage" CASCADE;
CREATE TABLE "tvrage" (
  "id" bigint DEFAULT nextval('tvrage_id_seq'::regclass) NOT NULL,
  "rageid" integer NOT NULL,
  "tvdbid" integer DEFAULT 0 NOT NULL,
  "releasetitle" character varying(255) DEFAULT ''::character varying NOT NULL,
  "description" character varying(10000),
  "genre" character varying(64),
  "country" character varying(2),
  "imgdata" bytea,
  "createddate" timestamp without time zone,
  "prevdate" timestamp without time zone,
  "previnfo" character varying(255),
  "nextdate" timestamp without time zone,
  "nextinfo" character varying(255)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "tvrageepisodes_id_seq" CASCADE;
CREATE SEQUENCE "tvrageepisodes_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('tvrageepisodes_id_seq', 1, true);

-- Table: tvrageepisodes
DROP TABLE IF EXISTS "tvrageepisodes" CASCADE;
CREATE TABLE "tvrageepisodes" (
  "id" bigint DEFAULT nextval('tvrageepisodes_id_seq'::regclass) NOT NULL,
  "rageid" bigint NOT NULL,
  "showtitle" character varying(255),
  "airdate" timestamp without time zone NOT NULL,
  "link" character varying(255),
  "fullep" character varying(20) NOT NULL,
  "eptitle" character varying(255)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "upcoming_id_seq" CASCADE;
CREATE SEQUENCE "upcoming_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('upcoming_id_seq', 1, true);

-- Table: upcoming
DROP TABLE IF EXISTS "upcoming" CASCADE;
CREATE TABLE "upcoming" (
  "id" integer DEFAULT nextval('upcoming_id_seq'::regclass) NOT NULL,
  "source" character varying(20) NOT NULL,
  "typeid" integer NOT NULL,
  "info" text,
  "updateddate" timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "usercart_id_seq" CASCADE;
CREATE SEQUENCE "usercart_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('usercart_id_seq', 1, true);

-- Table: usercart
DROP TABLE IF EXISTS "usercart" CASCADE;
CREATE TABLE "usercart" (
  "id" bigint DEFAULT nextval('usercart_id_seq'::regclass) NOT NULL,
  "userid" integer NOT NULL,
  "releaseid" integer NOT NULL,
  "createddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "userdownloads_id_seq" CASCADE;
CREATE SEQUENCE "userdownloads_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('userdownloads_id_seq', 1, true);

-- Table: userdownloads
DROP TABLE IF EXISTS "userdownloads" CASCADE;
CREATE TABLE "userdownloads" (
  "id" bigint DEFAULT nextval('userdownloads_id_seq'::regclass) NOT NULL,
  "userid" integer NOT NULL,
  "timestamp" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "userexcat_id_seq" CASCADE;
CREATE SEQUENCE "userexcat_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('userexcat_id_seq', 1, true);

-- Table: userexcat
DROP TABLE IF EXISTS "userexcat" CASCADE;
CREATE TABLE "userexcat" (
  "id" bigint DEFAULT nextval('userexcat_id_seq'::regclass) NOT NULL,
  "userid" integer NOT NULL,
  "categoryid" integer NOT NULL,
  "createddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "userinvite_id_seq" CASCADE;
CREATE SEQUENCE "userinvite_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('userinvite_id_seq', 1, true);

-- Table: userinvite
DROP TABLE IF EXISTS "userinvite" CASCADE;
CREATE TABLE "userinvite" (
  "id" bigint DEFAULT nextval('userinvite_id_seq'::regclass) NOT NULL,
  "guid" character varying(50) NOT NULL,
  "userid" bigint NOT NULL,
  "createddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "usermovies_id_seq" CASCADE;
CREATE SEQUENCE "usermovies_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('usermovies_id_seq', 1, true);

-- Table: usermovies
DROP TABLE IF EXISTS "usermovies" CASCADE;
CREATE TABLE "usermovies" (
  "id" bigint DEFAULT nextval('usermovies_id_seq'::regclass) NOT NULL,
  "userid" integer NOT NULL,
  "imdbid" integer,
  "categoryid" character varying(64),
  "createddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "userrequests_id_seq" CASCADE;
CREATE SEQUENCE "userrequests_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('userrequests_id_seq', 1, true);

-- Table: userrequests
DROP TABLE IF EXISTS "userrequests" CASCADE;
CREATE TABLE "userrequests" (
  "id" bigint DEFAULT nextval('userrequests_id_seq'::regclass) NOT NULL,
  "userid" integer NOT NULL,
  "request" character varying(255) NOT NULL,
  "timestamp" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "userroles_id_seq" CASCADE;
CREATE SEQUENCE "userroles_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('userroles_id_seq', 6, true);

-- Table: userroles
DROP TABLE IF EXISTS "userroles" CASCADE;
CREATE TABLE "userroles" (
  "id" integer DEFAULT nextval('userroles_id_seq'::regclass) NOT NULL,
  "name" character varying(32) NOT NULL,
  "apirequests" bigint NOT NULL,
  "downloadrequests" bigint NOT NULL,
  "defaultinvites" bigint NOT NULL,
  "isdefault" smallint DEFAULT 0 NOT NULL,
  "canpreview" smallint DEFAULT 0 NOT NULL
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "users_id_seq" CASCADE;
CREATE SEQUENCE "users_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('users_id_seq', 1, true);

-- Table: users
DROP TABLE IF EXISTS "users" CASCADE;
CREATE TABLE "users" (
  "id" bigint DEFAULT nextval('users_id_seq'::regclass) NOT NULL,
  "username" character varying(50) NOT NULL,
  "firstname" character varying(255),
  "lastname" character varying(255),
  "email" character varying(255) NOT NULL,
  "password" character varying(255) NOT NULL,
  "role" integer DEFAULT 1 NOT NULL,
  "host" character varying(40),
  "grabs" integer DEFAULT 0 NOT NULL,
  "rsstoken" character varying(32) NOT NULL,
  "createddate" timestamp without time zone NOT NULL,
  "resetguid" character varying(50),
  "lastlogin" timestamp without time zone,
  "apiaccess" timestamp without time zone,
  "invites" integer DEFAULT 0 NOT NULL,
  "invitedby" integer,
  "movieview" integer DEFAULT 1 NOT NULL,
  "musicview" integer DEFAULT 1 NOT NULL,
  "consoleview" integer DEFAULT 1 NOT NULL,
  "bookview" integer DEFAULT 1 NOT NULL,
  "saburl" character varying(255),
  "sabapikey" character varying(255),
  "sabapikeytype" smallint DEFAULT 0 NOT NULL,
  "sabpriority" smallint DEFAULT 0 NOT NULL,
  "userseed" character varying(50) NOT NULL,
  "cp_url" character varying(255),
  "cp_api" CHARACTER VARYING(255),
  "style" CHARACTER VARYING(255)
)
WITHOUT OIDS;

DROP SEQUENCE IF EXISTS "userseries_id_seq" CASCADE;
CREATE SEQUENCE "userseries_id_seq" INCREMENT BY 1
								  NO MAXVALUE NO MINVALUE CACHE 1;
SELECT pg_catalog.setval('userseries_id_seq', 1, true);

-- Table: userseries
DROP TABLE IF EXISTS "userseries" CASCADE;
CREATE TABLE "userseries" (
  "id" bigint DEFAULT nextval('userseries_id_seq'::regclass) NOT NULL,
  "userid" integer NOT NULL,
  "rageid" integer NOT NULL,
  "categoryid" character varying(64),
  "createddate" timestamp without time zone NOT NULL
)
WITHOUT OIDS;

ALTER TABLE "binaries" ADD CONSTRAINT "binaries_id_pkey" PRIMARY KEY("id");
ALTER TABLE "binaryblacklist" ADD CONSTRAINT "binaryblacklist_id_pkey" PRIMARY KEY("id");
ALTER TABLE "bookinfo" ADD CONSTRAINT "bookinfo_id_pkey" PRIMARY KEY("id");
ALTER TABLE "category" ADD CONSTRAINT "category_id_pkey" PRIMARY KEY("id");
ALTER TABLE "collections" ADD CONSTRAINT "collections_id_pkey" PRIMARY KEY("id");
ALTER TABLE "consoleinfo" ADD CONSTRAINT "consoleinfo_id_pkey" PRIMARY KEY("id");
ALTER TABLE "content" ADD CONSTRAINT "content_id_pkey" PRIMARY KEY("id");
ALTER TABLE "forumpost" ADD CONSTRAINT "forumpost_id_pkey" PRIMARY KEY("id");
ALTER TABLE "genres" ADD CONSTRAINT "genres_id_pkey" PRIMARY KEY("id");
ALTER TABLE "groups" ADD CONSTRAINT "groups_id_pkey" PRIMARY KEY("id");
ALTER TABLE "menu" ADD CONSTRAINT "menu_id_pkey" PRIMARY KEY("id");
ALTER TABLE "movieinfo" ADD CONSTRAINT "movieinfo_id_pkey" PRIMARY KEY("id");
ALTER TABLE "musicinfo" ADD CONSTRAINT "musicinfo_id_pkey" PRIMARY KEY("id");
ALTER TABLE "nzbs" ADD CONSTRAINT "id_pkey" PRIMARY KEY("id");
ALTER TABLE "partrepair" ADD CONSTRAINT "partrepair_id_pkey" PRIMARY KEY("id");
ALTER TABLE "parts" ADD CONSTRAINT "parts_id_pkey" PRIMARY KEY("id");
ALTER TABLE "predb" ADD CONSTRAINT "predb_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releaseaudio" ADD CONSTRAINT "releaseaudio_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releasecomment" ADD CONSTRAINT "releasecomment_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releaseextrafull" ADD CONSTRAINT "releaseextrafull_releaseid_pkey" PRIMARY KEY("releaseid");
ALTER TABLE "releasefiles" ADD CONSTRAINT "releasefiles_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releasenfo" ADD CONSTRAINT "releasenfo_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releases" ADD CONSTRAINT "ix_releases_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releasesubs" ADD CONSTRAINT "releasesubs_id_pkey" PRIMARY KEY("id");
ALTER TABLE "releasevideo" ADD CONSTRAINT "releasevideo_releaseid_pkey" PRIMARY KEY("releaseid");
ALTER TABLE "site" ADD CONSTRAINT "site_id_pkey" PRIMARY KEY("id");
ALTER TABLE "tmux" ADD CONSTRAINT "tmux_id_pkey" PRIMARY KEY("id");
ALTER TABLE "tvrage" ADD CONSTRAINT "tvrage_id_pkey" PRIMARY KEY("id");
ALTER TABLE "tvrageepisodes" ADD CONSTRAINT "tvrageepisodes_id_pkey" PRIMARY KEY("id");
ALTER TABLE "upcoming" ADD CONSTRAINT "upcoming_id_pkey" PRIMARY KEY("id");
ALTER TABLE "usercart" ADD CONSTRAINT "usercart_id_pkey" PRIMARY KEY("id");
ALTER TABLE "userdownloads" ADD CONSTRAINT "userdownloads_id_pkey" PRIMARY KEY("id");
ALTER TABLE "userexcat" ADD CONSTRAINT "userexcat_id_pkey" PRIMARY KEY("id");
ALTER TABLE "userinvite" ADD CONSTRAINT "userinvite_id_pkey" PRIMARY KEY("id");
ALTER TABLE "usermovies" ADD CONSTRAINT "usermovies_id_pkey" PRIMARY KEY("id");
ALTER TABLE "userrequests" ADD CONSTRAINT "userrequests_id_pkey" PRIMARY KEY("id");
ALTER TABLE "userroles" ADD CONSTRAINT "userroles_id_pkey" PRIMARY KEY("id");
ALTER TABLE "users" ADD CONSTRAINT "users_id_pkey" PRIMARY KEY("id");
ALTER TABLE "userseries" ADD CONSTRAINT "userseries_id_pkey" PRIMARY KEY("id");

DROP INDEX IF EXISTS "animetitles_title" CASCADE;
CREATE UNIQUE INDEX "animetitles_title" ON "animetitles" ("title");
DROP INDEX IF EXISTS "binaries_binaryhash" CASCADE;
CREATE INDEX "binaries_binaryhash" ON "binaries" ("binaryhash");
DROP INDEX IF EXISTS "binaries_partcheck" CASCADE;
CREATE INDEX "binaries_partcheck" ON "binaries" ("partcheck");
DROP INDEX IF EXISTS "binaries_collectionid" CASCADE;
CREATE INDEX "binaries_collectionid" ON "binaries" ("collectionid");
DROP INDEX IF EXISTS "binaryblacklist_groupname" CASCADE;
CREATE INDEX "binaryblacklist_groupname" ON "binaryblacklist" ("groupname");
DROP INDEX IF EXISTS "binaryblacklist_status" CASCADE;
CREATE INDEX "binaryblacklist_status" ON "binaryblacklist" ("status");
DROP INDEX IF EXISTS "bookinfo_asin" CASCADE;
CREATE UNIQUE INDEX "bookinfo_asin" ON "bookinfo" ("asin");
DROP INDEX IF EXISTS "category_status" CASCADE;
CREATE INDEX "category_status" ON "category" ("status");
DROP INDEX IF EXISTS "category_parentid" CASCADE;
CREATE INDEX "category_parentid" ON "category" ("parentid");
DROP INDEX IF EXISTS "collections_fromname" CASCADE;
CREATE INDEX "collections_fromname" ON "collections" ("fromname");
DROP INDEX IF EXISTS "collections_date" CASCADE;
CREATE INDEX "collections_date" ON "collections" ("date");
DROP INDEX IF EXISTS "collections_groupid" CASCADE;
CREATE INDEX "collections_groupid" ON "collections" ("groupid");
DROP INDEX IF EXISTS "collections_filecheck" CASCADE;
CREATE INDEX "collections_filecheck" ON "collections" ("filecheck");
DROP INDEX IF EXISTS "collections_dateadded" CASCADE;
CREATE INDEX "collections_dateadded" ON "collections" ("dateadded");
DROP INDEX IF EXISTS "collections_collectionhash" CASCADE;
CREATE UNIQUE INDEX "collections_collectionhash" ON "collections" ("collectionhash");
DROP INDEX IF EXISTS "collections_releaseid" CASCADE;
CREATE INDEX "collections_releaseid" ON "collections" ("releaseid");
DROP INDEX IF EXISTS "consoleinfo_asin" CASCADE;
CREATE UNIQUE INDEX "consoleinfo_asin" ON "consoleinfo" ("asin");
DROP INDEX IF EXISTS "forumpost_parentid" CASCADE;
CREATE INDEX "forumpost_parentid" ON "forumpost" ("parentid");
DROP INDEX IF EXISTS "forumpost_userid" CASCADE;
CREATE INDEX "forumpost_userid" ON "forumpost" ("userid");
DROP INDEX IF EXISTS "forumpost_createddate" CASCADE;
CREATE INDEX "forumpost_createddate" ON "forumpost" ("createddate");
DROP INDEX IF EXISTS "forumpost_updateddate" CASCADE;
CREATE INDEX "forumpost_updateddate" ON "forumpost" ("updateddate");
DROP INDEX IF EXISTS "groups_name" CASCADE;
CREATE UNIQUE INDEX "groups_name" ON "groups" ("name");
DROP INDEX IF EXISTS "groups_active" CASCADE;
CREATE INDEX "groups_active" ON "groups" ("active");
DROP INDEX IF EXISTS "groups_id" CASCADE;
CREATE INDEX "groups_id" ON "groups" ("id");
DROP INDEX IF EXISTS "movieinfo_imdbid" CASCADE;
CREATE UNIQUE INDEX "movieinfo_imdbid" ON "movieinfo" ("imdbid");
DROP INDEX IF EXISTS "movieinfo_title" CASCADE;
CREATE INDEX "movieinfo_title" ON "movieinfo" ("title");
DROP INDEX IF EXISTS "musicinfo_asin" CASCADE;
CREATE UNIQUE INDEX "musicinfo_asin" ON "musicinfo" ("asin");
DROP INDEX IF EXISTS "nzbs_partnumber" CASCADE;
CREATE INDEX "nzbs_partnumber" ON "nzbs" ("partnumber");
DROP INDEX IF EXISTS "nzbs_message" CASCADE;
CREATE UNIQUE INDEX "nzbs_message" ON "nzbs" ("message_id");
DROP INDEX IF EXISTS "nzbs_collectionhash" CASCADE;
CREATE INDEX "nzbs_collectionhash" ON "nzbs" ("collectionhash");
DROP INDEX IF EXISTS "partrepair_numberid_groupid" CASCADE;
CREATE UNIQUE INDEX "partrepair_numberid_groupid" ON "partrepair" ("numberid", "groupid");
DROP INDEX IF EXISTS "partrepair_attempts" CASCADE;
CREATE INDEX "partrepair_attempts" ON "partrepair" ("attempts");
DROP INDEX IF EXISTS "partrepair_groupid_attempts" CASCADE;
CREATE INDEX "partrepair_groupid_attempts" ON "partrepair" ("groupid", "attempts");
DROP INDEX IF EXISTS "partrepair_numberid_groupid_attempts" CASCADE;
CREATE INDEX "partrepair_numberid_groupid_attempts" ON "partrepair" ("numberid", "groupid", "attempts");
DROP INDEX IF EXISTS "parts_binaryid" CASCADE;
CREATE INDEX "parts_binaryid" ON "parts" ("binaryid");
DROP INDEX IF EXISTS "parts_number" CASCADE;
CREATE INDEX "parts_number" ON "parts" ("number");
DROP INDEX IF EXISTS "parts_messageid" CASCADE;
CREATE INDEX "parts_messageid" ON "parts" ("messageid");
DROP INDEX IF EXISTS "predb_title" CASCADE;
CREATE INDEX "predb_title" ON "predb" ("title");
DROP INDEX IF EXISTS "predb_nfo" CASCADE;
CREATE INDEX "predb_nfo" ON "predb" ("nfo");
DROP INDEX IF EXISTS "predb_predate" CASCADE;
CREATE INDEX "predb_predate" ON "predb" ("predate");
DROP INDEX IF EXISTS "predb_source" CASCADE;
CREATE INDEX "predb_source" ON "predb" ("source");
DROP INDEX IF EXISTS "predb_requestid" CASCADE;
CREATE INDEX predb_requestid on predb(requestid, groupid);
DROP INDEX IF EXISTS "predb_md5" CASCADE;
CREATE UNIQUE INDEX "predb_md5" ON "predb" ("md5");
DROP INDEX IF EXISTS "releaseaudio_releaseid_audioid" CASCADE;
CREATE UNIQUE INDEX "releaseaudio_releaseid_audioid" ON "releaseaudio" ("releaseid", "audioid");
DROP INDEX IF EXISTS "releasecomment_releaseid" CASCADE;
CREATE INDEX "releasecomment_releaseid" ON "releasecomment" ("releaseid");
DROP INDEX IF EXISTS "releasecomment_userid" CASCADE;
CREATE INDEX "releasecomment_userid" ON "releasecomment" ("userid");
DROP INDEX IF EXISTS "releasefiles_name_releaseid" CASCADE;
CREATE UNIQUE INDEX "releasefiles_name_releaseid" ON "releasefiles" ("name", "releaseid");
DROP INDEX IF EXISTS "releasefiles_releaseid" CASCADE;
CREATE INDEX "releasefiles_releaseid" ON "releasefiles" ("releaseid");
DROP INDEX IF EXISTS "releasefiles_name" CASCADE;
CREATE INDEX "releasefiles_name" ON "releasefiles" ("name");
DROP INDEX IF EXISTS "releasenfo_releaseid" CASCADE;
CREATE UNIQUE INDEX "releasenfo_releaseid" ON "releasenfo" ("releaseid");
DROP INDEX IF EXISTS "ix_releases_adddate" CASCADE;
CREATE INDEX "ix_releases_adddate" ON "releases" ("adddate");
DROP INDEX IF EXISTS "ix_releases_rageid" CASCADE;
CREATE INDEX "ix_releases_rageid" ON "releases" ("rageid");
DROP INDEX IF EXISTS "ix_releases_imdbid" CASCADE;
CREATE INDEX "ix_releases_imdbid" ON "releases" ("imdbid");
DROP INDEX IF EXISTS "ix_releases_guid" CASCADE;
CREATE INDEX "ix_releases_guid" ON "releases" ("guid");
DROP INDEX IF EXISTS "ix_releases_name" CASCADE;
CREATE INDEX "ix_releases_name" ON "releases" ("name");
DROP INDEX IF EXISTS "ix_releases_groupid" CASCADE;
CREATE INDEX "ix_releases_groupid" ON "releases" ("groupid");
DROP INDEX IF EXISTS "ix_releases_dehashstatus" CASCADE;
CREATE INDEX "ix_releases_dehashstatus" ON "releases" ("dehashstatus");
DROP INDEX IF EXISTS "ix_releases_reqidstatus" CASCADE;
CREATE INDEX "ix_releases_reqidstatus" ON "releases" ("reqidstatus");
DROP INDEX IF EXISTS "ix_releases_nfostatus" CASCADE;
CREATE INDEX "ix_releases_nfostatus" ON "releases" ("nfostatus");
DROP INDEX IF EXISTS "ix_releases_musicinfoid" CASCADE;
CREATE INDEX "ix_releases_musicinfoid" ON "releases" ("musicinfoid");
DROP INDEX IF EXISTS "ix_releases_consoleinfoid" CASCADE;
CREATE INDEX "ix_releases_consoleinfoid" ON "releases" ("consoleinfoid");
DROP INDEX IF EXISTS "ix_releases_bookinfoid" CASCADE;
CREATE INDEX "ix_releases_bookinfoid" ON "releases" ("bookinfoid");
DROP INDEX IF EXISTS "ix_releases_haspreview_passwordstatus" CASCADE;
CREATE INDEX "ix_releases_haspreview_passwordstatus" ON "releases" (haspreview, passwordstatus);
DROP INDEX IF EXISTS "ix_releases_status" CASCADE;
CREATE INDEX ix_releases_status ON releases (nzbstatus, iscategorized, isrenamed, nfostatus, ishashed, isrequestid, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid, imdbid, rageid);
DROP INDEX IF EXISTS "ix_releases_postdate_searchname" CASCADE;
CREATE INDEX ix_releases_postdate_searchname ON releases (postdate, searchname);
DROP INDEX IF EXISTS "ix_releases_nzb_guid" CASCADE;
CREATE INDEX ix_releases_nzb_guid ON releases (nzb_guid);
DROP INDEX IF EXISTS "ix_releases_preid_searchname" CASCADE;
CREATE INDEX ix_releases_preid_searchname ON releases (preid, searchname);
DROP INDEX IF EXISTS "releasesubs_releaseid_subsid" CASCADE;
CREATE UNIQUE INDEX "releasesubs_releaseid_subsid" ON "releasesubs" ("releaseid", "subsid");
DROP INDEX IF EXISTS "site_setting" CASCADE;
CREATE UNIQUE INDEX "site_setting" ON "site" ("setting");
DROP INDEX IF EXISTS "tmux_setting" CASCADE;
CREATE UNIQUE INDEX "tmux_setting" ON "tmux" ("setting");
DROP INDEX IF EXISTS "tvrage_rageid_releasetitle" CASCADE;
CREATE UNIQUE INDEX "tvrage_rageid_releasetitle" ON "tvrage" ("rageid", "releasetitle");
DROP INDEX IF EXISTS "tvrage_rageid" CASCADE;
CREATE INDEX "tvrage_rageid" ON "tvrage" ("rageid");
DROP INDEX IF EXISTS "tvrage_releasetitle" CASCADE;
CREATE INDEX "tvrage_releasetitle" ON "tvrage" ("releasetitle");
DROP INDEX IF EXISTS "tvrageepisodes_rageid_fullep" CASCADE;
CREATE UNIQUE INDEX "tvrageepisodes_rageid_fullep" ON "tvrageepisodes" ("rageid", "fullep");
DROP INDEX IF EXISTS "upcoming_source_typeid" CASCADE;
CREATE UNIQUE INDEX "upcoming_source_typeid" ON "upcoming" ("source", "typeid");
DROP INDEX IF EXISTS "usercart_userid_releaseid" CASCADE;
CREATE UNIQUE INDEX "usercart_userid_releaseid" ON "usercart" ("userid", "releaseid");
DROP INDEX IF EXISTS "userdownloads_userid" CASCADE;
CREATE INDEX "userdownloads_userid" ON "userdownloads" ("userid");
DROP INDEX IF EXISTS "userdownloads_timestamp" CASCADE;
CREATE INDEX "userdownloads_timestamp" ON "userdownloads" ("timestamp");
DROP INDEX IF EXISTS "userexcat_userid_categoryid" CASCADE;
CREATE UNIQUE INDEX "userexcat_userid_categoryid" ON "userexcat" ("userid", "categoryid");
DROP INDEX IF EXISTS "usermovies_userid_imdbid" CASCADE;
CREATE INDEX "usermovies_userid_imdbid" ON "usermovies" ("userid", "imdbid");
DROP INDEX IF EXISTS "userrequests_userid" CASCADE;
CREATE INDEX "userrequests_userid" ON "userrequests" ("userid");
DROP INDEX IF EXISTS "userrequests_timestamp" CASCADE;
CREATE INDEX "userrequests_timestamp" ON "userrequests" ("timestamp");
DROP INDEX IF EXISTS "userseries_userid_rageid" CASCADE;
CREATE INDEX "userseries_userid_rageid" ON "userseries" ("userid", "rageid");
DROP INDEX IF EXISTS "ix_allgroups_id" CASCADE;
CREATE INDEX ix_allgroups_id ON allgroups(id);
DROP INDEX IF EXISTS "ix_allgroups_name" CASCADE;
CREATE INDEX ix_allgroups_name ON allgroups(name);
DROP INDEX IF EXISTS "ix_country_name" CASCADE;
CREATE INDEX ix_country_name ON country(name);
DROP INDEX IF EXISTS "ix_shortgroups_id" CASCADE;
CREATE INDEX ix_shortgroups_id ON shortgroups(id);
DROP INDEX IF EXISTS "ix_shortgroups_name" CASCADE;
CREATE INDEX ix_shortgroups_name ON shortgroups(name);

CREATE OR REPLACE FUNCTION check_hashreqid() RETURNS trigger AS $hash_reqid$
BEGIN
	IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}'
	THEN SET NEW.ishashed = 1;
        ELSE SET NEW.ishashed = 0;
	END IF;

        IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]'
        THEN SET NEW.isrequestid = 1;
        ELSE SET NEW.isrequestid = 0;
        END IF;

	RETURN NEW;

END;
$hash_reqid$
LANGUAGE plpgsql;

CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW EXECUTE PROCEDURE check_hashreqid();
CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE check_hashreqid();
