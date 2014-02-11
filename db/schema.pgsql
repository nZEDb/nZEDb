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
  "partcheck" bigint DEFAULT 0 NOT NULL,
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
  "filecheck" bigint DEFAULT 0 NOT NULL,
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

-- Table: logging
DROP TABLE IF EXISTS "logging" CASCADE;
CREATE TABLE "logging" (
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
  "adddate" timestamp without time zone,
  "source" character varying(50) DEFAULT ''::character varying NOT NULL,
  "md5" character varying(255) DEFAULT '0'::character varying NOT NULL,
  "requestid" integer DEFAULT 0 NOT NULL,
  "groupid" integer DEFAULT 0 NOT NULL

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
  "preid" integer,
  "grabs" bigint DEFAULT 0 NOT NULL,
  "comments" integer DEFAULT 0 NOT NULL,
  "passwordstatus" smallint DEFAULT 0 NOT NULL,
  "rarinnerfilecount" integer DEFAULT 0 NOT NULL,
  "haspreview" smallint DEFAULT 0 NOT NULL,
  "nfostatus" smallint DEFAULT 0 NOT NULL,
  "bitwise" smallint DEFAULT 0 NOT NULL,
  "jpgstatus" smallint DEFAULT 0 NOT NULL,
  "videostatus" smallint DEFAULT 0 NOT NULL,
  "audiostatus" smallint DEFAULT 0 NOT NULL,
  "dehashstatus" smallint DEFAULT 0 NOT NULL,
  "reqidstatus" smallint DEFAULT 0 NOT NULL,
  "nzb_guid" character varying(50)
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
DROP TABLE IF EXISTS "country" CASCADE;
CREATE TABLE "country" (
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
  "cp_api" character varying(255)
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


INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (1, 'alt.binaries.*','(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|latin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[\\)]?( \\-)?[ \\-\\.]((19|20)\\d\\d|(480|720|1080)(i|p)|3d|5\\.1|dts|ac3|truehd|(bd|dvd|hd|sat|vhs|web)\\.?rip|(bd.)?(h|x).?2?64|divx|xvid|bluray|svcd|board|custom|"|(d|h|p|s)d?v?tv|m?dvd(-|sc)?r|int(ernal)?|nzb|par2|\\b(((dc|ld|md|ml|dl|hr|se)[.])|(anime\\.)|(fs|ws)|dsr|pal|ntsc|iso|complete|cracked|ebook|extended|dirfix|festival|proper|game|limited|read.?nfo|real|rerip|repack|remastered|retail|samplefix|scan|screener|theatrical|uncut|unrated|incl|winall)\\b|doku|doc|dub|sub|\\(uncut\\))', 1, 1, 0, 'Blacklists non-english releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (2, 'alt.binaries.*','[ -.](bl|cz|de|es|fr|ger|heb|hu|hun|ita|ko|kor|nl|pl|se)[ -.]((19|20)\\d\\d|(480|720|1080)(i|p)|(bd|dvd.?|sat|vhs)?rip?|(bd|dl)mux|( -.)?(dub|sub)(ed|bed)?|complete|convert|(d|h|p|s)d?tv|dirfix|docu|dual|dvbs|dvdscr|eng|(h|x).?2?64|int(ernal)?|pal|proper|repack|xbox)', 1, 1, 0, 'Blacklists non-english abbreviated releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (3, 'alt.binaries.*','[ -.]((19|20)\\d\\d|(bd|dvd.?|sat|vhs)?rip?|custom|divx|dts)[ -.](bl|cz|de|es|fr|ger|heb|hu|ita|ko|kor|nl|pl|se)[ -.]', 1, 1, 0, 'Blacklists non-english abbreviated (reversed) releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (4, 'alt.binaries.*','[ -.](chinese.subbed|dksubs|fansubs?|finsub|hebdub|hebsub|korsub|norsub|nordicsubs|nl( -.)?sub(ed|bed|s)?|nlvlaams|pldub|plsub|slosinh|swesub|truefrench|vost(fr)?)[ -.]', 1, 1, 0, 'Blacklists non-english subtitled releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (5, 'alt.binaries.*','[ -._](4u\\.nl|nov[ a]+rip|realco|videomann|vost)[ -._]', 1, 1, 0, 'Blacklists non-english (release group specific) releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (6, 'alt.binaries.*','[ -.]((bd|dl)mux|doku|\\[foreign\\]|seizoen|staffel)[ -.]', 1, 1, 0, 'Blacklists non-english (lang specific) releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (7, 'alt.binaries.*','[ -.](imageset|pictureset|xxx)[ -.]', 1, 1, 0, 'Blacklists porn releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (8, 'alt.binaries.*','hdnectar|nzbcave', 1, 1, 0, 'Bad releases.');
INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (9, 'alt.binaries.*','Passworded', 1, 1, 0, 'Removes passworded releases.');


INSERT INTO category (id, title) VALUES (1000, 'Console');
INSERT INTO category (id, title) VALUES (2000, 'Movies');
INSERT INTO category (id, title) VALUES (3000, 'Audio');
INSERT INTO category (id, title) VALUES (4000, 'PC');
INSERT INTO category (id, title) VALUES (5000, 'TV');
INSERT INTO category (id, title) VALUES (6000, 'XXX');
INSERT INTO category (id, title) VALUES (7000, 'Other');
INSERT INTO category (id, title) VALUES (8000, 'Books');

INSERT INTO category (id, title, parentid) VALUES (1010, 'NDS', 1000);
INSERT INTO category (id, title, parentid) VALUES (1020, 'PSP', 1000);
INSERT INTO category (id, title, parentid) VALUES (1030, 'Wii', 1000);
INSERT INTO category (id, title, parentid) VALUES (1040, 'Xbox', 1000);
INSERT INTO category (id, title, parentid) VALUES (1050, 'Xbox 360', 1000);
INSERT INTO category (id, title, parentid) VALUES (1060, 'WiiWare/VC', 1000);
INSERT INTO category (id, title, parentid) VALUES (1070, 'XBOX 360 DLC', 1000);
INSERT INTO category (id, title, parentid) VALUES (1080, 'PS3', 1000);
INSERT INTO category (id, title, parentid) VALUES (1090, 'Other', 1000);

INSERT INTO category (id, title, parentid) VALUES (2010, 'Foreign', 2000);
INSERT INTO category (id, title, parentid) VALUES (2020, 'Other', 2000);
INSERT INTO category (id, title, parentid) VALUES (2030, 'SD', 2000);
INSERT INTO category (id, title, parentid) VALUES (2040, 'HD', 2000);
INSERT INTO category (id, title, parentid) VALUES (2050, '3D', 2000);
INSERT INTO category (id, title, parentid) VALUES (2060, 'BluRay', 2000);
INSERT INTO category (id, title, parentid) VALUES (2070, 'DVD', 2000);

INSERT INTO category (id, title, parentid) VALUES (3010, 'MP3', 3000);
INSERT INTO category (id, title, parentid) VALUES (3020, 'Video', 3000);
INSERT INTO category (id, title, parentid) VALUES (3030, 'Audiobook', 3000);
INSERT INTO category (id, title, parentid) VALUES (3040, 'Lossless', 3000);
INSERT INTO category (id, title, parentid) VALUES (3050, 'Other', 3000);
INSERT INTO category (id, title, parentid) VALUES (3060, 'Foreign', 3000);

INSERT INTO category (id, title, parentid) VALUES (4010, '0day', 4000);
INSERT INTO category (id, title, parentid) VALUES (4020, 'ISO', 4000);
INSERT INTO category (id, title, parentid) VALUES (4030, 'Mac', 4000);
INSERT INTO category (id, title, parentid) VALUES (4040, 'Phone\-Other', 4000);
INSERT INTO category (id, title, parentid) VALUES (4050, 'Games', 4000);
INSERT INTO category (id, title, parentid) VALUES (4060, 'Phone\-IOS', 4000);
INSERT INTO category (id, title, parentid) VALUES (4070, 'Phone\-Android', 4000);

INSERT INTO category (id, title, parentid) VALUES (5010, 'WEB\-DL', 5000);
INSERT INTO category (id, title, parentid) VALUES (5020, 'Foreign', 5000);
INSERT INTO category (id, title, parentid) VALUES (5030, 'SD', 5000);
INSERT INTO category (id, title, parentid) VALUES (5040, 'HD', 5000);
INSERT INTO category (id, title, parentid) VALUES (5050, 'Other', 5000);
INSERT INTO category (id, title, parentid) VALUES (5060, 'Sport', 5000);
INSERT INTO category (id, title, parentid) VALUES (5070, 'Anime', 5000);
INSERT INTO category (id, title, parentid) VALUES (5080, 'Documentary', 5000);

INSERT INTO category (id, title, parentid) VALUES (6010, 'DVD', 6000);
INSERT INTO category (id, title, parentid) VALUES (6020, 'WMV', 6000);
INSERT INTO category (id, title, parentid) VALUES (6030, 'XviD', 6000);
INSERT INTO category (id, title, parentid) VALUES (6040, 'x264', 6000);
INSERT INTO category (id, title, parentid) VALUES (6050, 'Other', 6000);
INSERT INTO category (id, title, parentid) VALUES (6060, 'Imageset', 6000);
INSERT INTO category (id, title, parentid) VALUES (6070, 'Packs', 6000);

INSERT INTO category (id, title, parentid) VALUES (7010, 'Misc', 7000);

INSERT INTO category (id, title, parentid) VALUES (8010, 'Ebook', 8000);
INSERT INTO category (id, title, parentid) VALUES (8020, 'Comics', 8000);
INSERT INTO category (id, title, parentid) VALUES (8030, 'Magazines', 8000);
INSERT INTO category (id, title, parentid) VALUES (8040, 'Technical', 8000);
INSERT INTO category (id, title, parentid) VALUES (8050, 'Other', 8000);
INSERT INTO category (id, title, parentid) VALUES (8060, 'Foreign', 8000);


INSERT INTO content (title, body, contenttype, status, metadescription, metakeywords, showinmenu, ordinal)
VALUES ('Welcome to nZEDb.','<p>Since nZEDb is a fork of newznab, the API is compatible with nzbdrone, sickbeard, couchpotato, etc...</p>', 3, 1, '', '', 0, 0);
INSERT INTO content (title, url, body, contenttype, status, showinmenu, metadescription, metakeywords, ordinal)
VALUES ('example content','/great/seo/content/page/','<p>this is an example content page</p>', 2, 1, 1, '', '', 1);
INSERT INTO content (title, url, body, contenttype, status, showinmenu, metadescription, metakeywords, ordinal)
VALUES ('another example','/another/great/seo/content/page/','<p>this is another example content page</p>', 2, 1, 1, '', '', 0);

INSERT INTO forumpost (forumid, parentid,  userid,  subject,  message, locked, sticky,replies,  createddate, updateddate)
VALUES (1 ,0, 1, 'Welcome to nZEDb!', 'Feel free to leave a message.', 0, 0, 0, NOW(), NOW());


INSERT INTO genres
(
  title, type
) VALUES
  ('Blues', 3000),
  ('Classic Rock', 3000),
  ('Country', 3000),
  ('Dance', 3000),
  ('Disco', 3000),
  ('Funk', 3000),
  ('Grunge', 3000),
  ('Hip-Hop', 3000),
  ('Jazz', 3000),
  ('Metal', 3000),
  ('New Age', 3000),
  ('Oldies', 3000),
  ('Other', 3000),
  ('Pop', 3000),
  ('R&B', 3000),
  ('Rap', 3000),
  ('Reggae', 3000),
  ('Rock', 3000),
  ('Techno', 3000),
  ('Industrial', 3000),
  ('Alternative', 3000),
  ('Ska', 3000),
  ('Death Metal', 3000),
  ('Pranks', 3000),
  ('Soundtrack', 3000),
  ('Euro-Techno', 3000),
  ('Ambient', 3000),
  ('Trip-Hop', 3000),
  ('Vocal', 3000),
  ('Jazz+Funk', 3000),
  ('Fusion', 3000),
  ('Trance', 3000),
  ('Classical', 3000),
  ('Instrumental', 3000),
  ('Acid', 3000),
  ('House', 3000),
  ('Game', 3000),
  ('Sound Clip', 3000),
  ('Gospel', 3000),
  ('Noise', 3000),
  ('Alternative Rock', 3000),
  ('Bass', 3000),
  ('Soul', 3000),
  ('Punk', 3000),
  ('Space', 3000),
  ('Meditative', 3000),
  ('Instrumental Pop', 3000),
  ('Instrumental Rock', 3000),
  ('Ethnic', 3000),
  ('Gothic', 3000),
  ('Darkwave', 3000),
  ('Techno-Industrial', 3000),
  ('Electronic', 3000),
  ('Pop-Folk', 3000),
  ('Eurodance', 3000),
  ('Dream', 3000),
  ('Southern Rock', 3000),
  ('Comedy', 3000),
  ('Cult', 3000),
  ('Gangsta', 3000),
  ('Top 40', 3000),
  ('Christian Rap', 3000),
  ('Pop/Funk', 3000),
  ('Jungle', 3000),
  ('Native US', 3000),
  ('Cabaret', 3000),
  ('New Wave', 3000),
  ('Psychadelic', 3000),
  ('Rave', 3000),
  ('Showtunes', 3000),
  ('Trailer', 3000),
  ('Lo-Fi', 3000),
  ('Tribal', 3000),
  ('Acid Punk', 3000),
  ('Acid Jazz', 3000),
  ('Polka', 3000),
  ('Retro', 3000),
  ('Musical', 3000),
  ('Rock & Roll', 3000),
  ('Hard Rock', 3000),
  ('Folk', 3000),
  ('Folk-Rock', 3000),
  ('National Folk', 3000),
  ('Swing', 3000),
  ('Fast Fusion', 3000),
  ('Bebob', 3000),
  ('Latin', 3000),
  ('Revival', 3000),
  ('Celtic', 3000),
  ('Bluegrass', 3000),
  ('Avantgarde', 3000),
  ('Gothic Rock', 3000),
  ('Progressive Rock', 3000),
  ('Psychedelic Rock', 3000),
  ('Symphonic Rock', 3000),
  ('Slow Rock', 3000),
  ('Big Band', 3000),
  ('Chorus', 3000),
  ('Easy Listening', 3000),
  ('Acoustic', 3000),
  ('Humour', 3000),
  ('Speech', 3000),
  ('Chanson', 3000),
  ('Opera', 3000),
  ('Chamber Music', 3000),
  ('Sonata', 3000),
  ('Symphony', 3000),
  ('Booty Bass', 3000),
  ('Primus', 3000),
  ('Porn Groove', 3000),
  ('Satire', 3000),
  ('Slow Jam', 3000),
  ('Club', 3000),
  ('Tango', 3000),
  ('Samba', 3000),
  ('Folklore', 3000),
  ('Ballad', 3000),
  ('Power Ballad', 3000),
  ('Rhytmic Soul', 3000),
  ('Freestyle', 3000),
  ('Duet', 3000),
  ('Punk Rock', 3000),
  ('Drum Solo', 3000),
  ('Acapella', 3000),
  ('Euro-House', 3000),
  ('Dance Hall', 3000),
  ('Goa', 3000),
  ('Drum & Bass', 3000),
  ('Club-House', 3000),
  ('Hardcore', 3000),
  ('Terror', 3000),
  ('Indie', 3000),
  ('BritPop', 3000),
  ('Negerpunk', 3000),
  ('Polsk Punk', 3000),
  ('Beat', 3000),
  ('Christian Gangsta', 3000),
  ('Heavy Metal', 3000),
  ('Black Metal', 3000),
  ('Crossover', 3000),
  ('Contemporary C', 3000),
  ('Christian Rock', 3000),
  ('Merengue', 3000),
  ('Salsa', 3000),
  ('Thrash Metal', 3000),
  ('Anime', 3000),
  ('JPop', 3000),
  ('SynthPop', 3000),
  ('Electronica', 3000);


INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.0day.stuffz','This group contains mostly 0day software.', 2, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.anime','This group contains mostly Anime Television.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.astronomy','This group contains mostly movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ath','This group contains a variety of Music. Some Foreign.', 8, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.barbarella','This group contains a variety of German content.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.audio.warez','Theres some old stuff in here, but this group is pretty much dead.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.b4e','This group contains 0day and has some foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.big','This group contains XVID Movies. Mostly Foreign.', NULL,NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.bloaf','This group contains a variety. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.blu-ray','This group contains blu-ray movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.boneless','This group contains XVID and X264 Movies. Some Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.british.drama','This group contains British TV shows.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.cartoons.french','This group contains French cartoons.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.cats','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.cd.image.linux','This group contains Linux distributions.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.cd.image','This group contains PC-ISO.', 4, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.cd.lossless','This group contains a variety of lossless Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.chello','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.classic.tv.shows','This group contains Classic TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.comics.dcp','This group contains Comic Books', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.comp','This group contains Warez. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.console.ps3','This group contains PS3 Games.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.cores','This group contains a variety including Nintendo DS. Lots of Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.country.mp3','This group contains Country Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.dc','This group contains XVID and X264 Movies and TV. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.dgma','This group contains XVID Movies. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.divx.french','This group contains French XVID Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.documentaries','This group contains Documentaries TV and Movies.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.documentaries.french','This group contains French Documentaries TV and Movies.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.downunder','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.dvd','This group contains DVD Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.dvd.movies','This group contains DVDR Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.dvdr','This group contains DVD Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.dvd-r','This group contains DVD Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.e-book.flood','This group contains E-Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.e-book.technical','This group contains E-Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.e-book','This group contains E-Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ebook','This group contains Ebooks.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.erotica.divx','This group contains XXX.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.erotica','This group contains XXX.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.etc','This group contains a variety of items.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.font','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.french-tv','This group contains French TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.frogs','This group contains a variety.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ftn','This group contains a variety of Music and TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.games.nintendods','This group contains Nintendo DS Games ', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.games','This group contains PC and Console Games.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.games.wii','This group contains Nintendo WII Games, WII-Ware, and VC.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.games.xbox360','This group contains XBOX 360 Games and DLC.', 4, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.games.xbox','This group contains original XBOX Games.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ghosts','This group contains XVID TV and Movies. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.hdtv','This group contains mostly HDTV 1080i rips.', 2, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.hdtv.german','This group contains German HDTV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.hdtv.x264','This group contains X264 Movies and HDTV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.highspeed','This group contains XVID Movies. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.hou','This group contains a variety of content. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ijsklontje','This group contains XXX.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.inner-sanctum','This group contains PC and Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.illuminaten','This group contains mostly German.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ipod.videos','This group contains Mobile TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.linux.iso','This group contains Linux distributions.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.lou','This group contains mostly german TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mac','This group contains MAC/OSX Software.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mac.applications','This group contains MAC/OSX Software.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.milo','This group contains mostly TV, some german.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.misc','This group contains a variety of items.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mojo','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mma','This group contains MMA/TNA Sport TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mom','This group contains a variety. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.moovee','This group contains XVID and X264 Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.movies.divx','This group contains XVID Movies', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.movies.erotica','This group contains XXX', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.movies.french','This group contains French Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.movies','This group contains an assortment of Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.movies.xvid','This group contains XVID Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mp3.audiobooks','This group contains Audio Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mp3.bootlegs','This group contains Bootleg Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mp3.full_albums','This group contains a variety of Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mp3','This group contains a variety of Music.', 11, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.mpeg.video.music','This group contains a variety of Music Videos.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.anime.highspeed','This group contains Anime Television.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.anime.repost','This group contains Anime Television.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.anime','This group contains Anime TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.cartoons','This group contains Cartoon TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.classic-films','This group contains Classic TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.comedy.british','This group contains British Comedy TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.disney','This group contains Disney TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.documentaries','This group contains Documentary Movies and TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.erotica','This group contains XXX.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.erotica.amateur','This group contains XXX.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.scifi','This group contains science-fiction TV and movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.scifi-and-fantasy','This group contains science-fiction and fantasy TV and movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.sitcoms','This group contains Sitcom TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.sports','This group contains Sports TV and Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia','This group contains TV, Movies, and Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.tv','This group contains XVID and X264 TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.vintage-film','This group contains Vintage Movies pre 1960.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.multimedia.vintage-film.post-1960','This group contains Vintage Movies post 1960.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.music.flac','This group contains a variety of lossless Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.music.opera','This group contains Opera Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.nintendo.ds','This group contains Nintendo DS Games.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.nospam.cheerleaders','This group contains various.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.pictures.comics.complete','This group contains comics.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.pictures.comics.dcp','This group contains Comic Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.pictures.comics.reposts','This group contains Comic Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.pictures.comics.repost','This group contains Comic Books.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.pro-wrestling','This group contains WWE Sport TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.scary.exe.files','This group contains XVID and X264 Movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.series.tv.divx.french','This group contains French DIVX TV shows.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sony.psp','This group contains PSP Games.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sound.audiobooks','This group contains Audiobooks.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sound.mp3','This group contains a variety of Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.1960s.mp3','This group contains Music from the 1960s.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.1970s.mp3','This group contains Music from the 1970s.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.audiobooks.repost','This group contains Audiobooks.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.country.mp3','', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.flac.jazz','This group contains lossless Jazz Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.jpop','This group contains mostly Jpop music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.lossless.1960s','This group contains lossless 1960s Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.lossless.classical','This group contains lossless Classical Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.lossless.country','This group contains lossless Country Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.lossless','This group contains a variety of Lossless Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.1950s','This group contains Music from the 1950s.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.1970s','This group contains Music from the 1970s.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.1980s','This group contains Music from the 1980s.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.1990s','This group contains Music from the 1990s.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.2000s','This group contains Music from the 2000s.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.acoustic','This group contains Accoustic Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.audiobooks','This group contains Audiobooks.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.bluegrass','This group contains Bluegrass Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.christian','This group contains Christian Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.classical','This group contains Classical Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.comedy','This group contains Comedy Audio.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.complete_cd','This group contains a variety of Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.country','This group contains mostly country music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.dance','This group contains Dance Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.disco','This group contains Disco Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.emo','This group contains Emo Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.full_albums','This group contains a variety of Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.heavy-metal','This group contains Heavy Metal Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.jazz','This group contains Jazz Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.jazz.vocals','This group contains Jazz Vocal Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.musicals','This group contains Musicals Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.nospam','This group contains a variety of Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.opera','This group contains Opera Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.progressive-country','This group contains Country Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.rap-hiphop.full-albums','This group contains Rap and Hip-Hop Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.rap-hiphop','This group contains Rap and Hip-Hop Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3.rock','This group contains Rock Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.mp3','This group contains a variety of Music.', 5, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.radio.bbc','This group contains BBC Radio Music', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.radio.british','This group contains British Radio Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.sounds.whitburn.pop','This group contains Pop Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.tatu','This group contains mostly French TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.teevee','This group contains X264 and XVID TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.test','This group contains a variety of content.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.town','This group contains XVID TV and Movies. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.triballs','This group contains various.', 2, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.tun','This group contains various.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.tvseries','This group contains X264 and XVID TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.tv','This group contains XVID TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.u-4all','This group contains XVID TV and Movies. Mostly Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.u4e','This group contains a variery, mostly German movies.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ucc','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.ufg','This group contains mostly TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.uzenet','This group contains XXX. Some Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.warez.ibm-pc.0-day','This group contains PC-0Day.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.warez.quebec-hackers','This group contains PC-0day. Some Foreign.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.warez.smartphone','This group contains Mobile Phone Apps.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.warez','This group contains PC 0DAY, PC ISO, and PC PHONE.', 5, 1000000);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.warez.uk.mp3','This group contains a variety of Music.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.wii','This group contains Nintendo WII Games, WII-Ware, and VC.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.wmvhd','This group contains WMVHD Movies.', NULL, '40000000');
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.worms','I have no idea what this group contains besides a lot of U4ALL which isnt really usable.', 2, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.x264','This group contains X264 Movies and TV.', NULL, NULL);
INSERT INTO groups (name, description, minfilestoformrelease, minsizetoformrelease) VALUES ('alt.binaries.x','This group contains a variety of content. Some Foreign.', NULL, NULL);
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.multimedia.erotica.anime', NULL, NULL, 'erotica anime');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.multimedia.erotica.asian', NULL, NULL, 'erotica Asian');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.mp3.audiobooks.scifi-fantasy', NULL, NULL, 'Audiobooks');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.sounds.audiobooks.scifi-fantasy', NULL, NULL, 'Audiobooks');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.mp3.abooks', NULL, NULL, 'Audiobooks');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.town.xxx', NULL, NULL, 'XXX videos');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.software', NULL, NULL, 'Software');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.nzbpirates', NULL, NULL, 'Misc, mostly Erotica, and foreign movies ');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.town.cine', NULL, NULL, 'Movies');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.usenet-space-cowboys', NULL, NULL, 'Misc, mostly German');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.warez.ibm-pc.games', NULL, NULL, 'misc, mostly games and applications');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.warez.games', NULL, NULL, 'misc, mostly games and applications');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.e-book.magazines', NULL, NULL, 'magazines, mostly english');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.sounds.anime', NULL, NULL, 'music from Anime');
INSERT INTO groups (name, minfilestoformrelease, minsizetoformrelease, description) VALUES ('alt.binaries.pictures.erotica.anime', NULL, NULL, 'Anime Manga, Adult');

INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('search','Advanced Search','Search for releases.', 1, 10);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('browsegroup','Groups List','Browse by Group.', 1, 25);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('movies','Movies','Browse Movies.', 1, 40);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('upcoming','Theatres','Movies currently in theatres.', 1, 45);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('series','TV Series','Browse TV Series.', 1, 50);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('predb','PreDB','Browse PreDB.', 1, 51);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('calendar','TV Calendar','View whats on TV.', 1, 53);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('anime','Anime','Browse Anime', 1, 55);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('music','Music','Browse Music.', 1, 60);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('console','Console','Browse Games.', 1, 65);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('books','Books','Browse Books.', 1, 67);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('admin','Admin','Admin', 2, 70);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('cart','My Cart','Your Nzb cart.', 1, 75);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('myshows','My Shows','Your TV shows.', 1, 77);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('mymovies','My Movies','Your Movie Wishlist.', 1, 78);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('apihelp','API','Information on the API.', 1, 79);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('rss','RSS','RSS Feeds.', 1, 80);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('forum','Forum','Browse Forum.', 1, 85);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('login','Login','Login.', 0, 100);
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('register','Register','Register.', 0, 110);
INSERT INTO menu (href, title, tooltip, role, ordinal, menueval ) VALUES ('queue','Sab Queue','View Your Sabnzbd Queue.', 1, 81, '{if $sabapikeytype!=2}-1{/if}');
INSERT INTO menu (href, title, tooltip, role, ordinal ) VALUES ('newposterwall', 'New Releases', 'Newest Releases Poster Wall', 1, 11);


INSERT INTO site
	(setting, value)
	VALUES
	('code','nZEDb'),
	('title','nZEDb'),
	('strapline','A great usenet indexer'),
	('metatitle','An indexer'),
	('metadescription','A usenet indexing website'),
	('metakeywords','usenet,nzbs,cms,community'),
	('footer','Usenet binary indexer.'),
	('email',''),
	('google_adsense_search',''),
	('google_analytics_acc',''),
	('google_adsense_acc',''),
	('tandc','<p>All information within this database is indexed by an automated process, without any human intervention. It is obtained from global Usenet newsgroups over which this site has no control. We cannot prevent that you might find obscene or objectionable material by using this service. If you do come across obscene, incorrect or objectionable results, let us know by using the contact form.</p>'),
	('registerstatus', 0),
	('style','Default'),
	('home_link','/'),
	('dereferrer_link',''),
	('nzbpath','/your/path/to/nzbs/'),
	('coverspath','/your/path/to/covers/'),
	('lookuptvrage', 1),
	('lookupimdb', 1),
	('lookupnfo', 1),
	('lookupmusic', 1),
	('lookupgames', 1),
	('lookupbooks', 1),
	('lookupanidb', 0),
	('maxaddprocessed', 25),
	('maxnfoprocessed', 100),
	('maxrageprocessed', 75),
	('maximdbprocessed', 100),
	('maxanidbprocessed', 100),
	('maxmusicprocessed', 150),
	('maxgamesprocessed', 150),
	('maxbooksprocessed', 300),
	('maxnzbsprocessed', 1000),
	('maxpartrepair', 15000),
	('binarythreads', 1),
	('backfillthreads', 1),
	('postthreads', 1),
	('releasethreads', 1),
	('nzbthreads', 1),
	('amazonpubkey','AKIAIPDNG5EU7LB4AD3Q'),
	('amazonprivkey','B58mVwyj+T/MEucxWugJ3GQ0CcW2kQq16qq/1WpS'),
	('amazonassociatetag','n01369-20'),
	('tmdbkey','9a4e16adddcd1e86da19bcaf5ff3c2a3'),
	('rottentomatokey','qxbxyngtujprvw7jxam2m6na'),
	('rottentomatoquality', 'profile'),
	('trakttvkey',''),
	('fanarttvkey', ''),
	('compressedheaders', 0),
	('partrepair', 1),
	('maxmssgs', 20000),
	('newgroupscanmethod', 0),
	('newgroupdaystoscan', 1),
	('newgroupmsgstoscan', 100000),
	('sabintegrationtype', 2),
	('saburl',''),
	('sabapikey',''),
	('sabapikeytype', 1),
	('sabpriority', 0),
	('storeuserips', 0),
	('minfilestoformrelease', 1),
	('minsizetoformrelease', 0),
	('maxsizetoformrelease', 0),
	('maxsizetopostprocess', 100),
	('releaseretentiondays', 0),
	('checkpasswordedrar', 0),
	('showpasswordedrelease', 0),
	('deletepasswordedrelease', 0),
	('releasecompletion', 0),
	('unrarpath',''),
	('mediainfopath',''),
	('ffmpegpath',''),
	('tmpunrarpath',''),
	('adheader',''),
	('adbrowse',''),
	('addetail',''),
	('grabstatus', 1),
	('nzbsplitlevel', 1),
	('categorizeforeign', 1),
	('menuposition', 2),
	('crossposttime', 2),
	('maxpartsprocessed', 3),
	('catlanguage', 0),
	('amazonsleep', 1000),
	('passchkattempts', 1),
	('catwebdl', 0),
	('safebackfilldate','2012-06-24'),
	('processjpg', 0),
	('hashcheck', 1),
	('debuginfo', 0),
	('processvideos', 0),
	('imdburl', 0),
	('imdblanguage','en'),
	('partretentionhours', 72),
	('postdelay', 300),
	('processaudiosample', 0),
	('predbversion', 1),
	('deletepossiblerelease', 0),
	('miscotherretentionhours',0),
	('grabnzbs', '0'),
	('alternate_nntp', '0'),
	('postthreadsamazon', '1'),
	('postthreadsnon', '1'),
	('currentppticket', '0'),
	('nextppticket', '0'),
	('segmentstodownload', '2'),
	('ffmpeg_duration', '5'),
	('ffmpeg_image_time', '5'),
	('request_url', 'http://predb_irc.nzedb.com/predb_irc.php?reqid=[REQUEST_ID]&group=[GROUP_NM]'),
	('lookup_reqids', '1'),
	('request_hours', '1'),
	('grabnzbthreads', '1'),
	('loggingopt', '2'),
	('logfile', '/var/www/nZEDb/failed-login.log'),
	('zippath',''),
	('lookuppar2','0'),
	('delaytime','2'),
	('addpar2', '0'),
	('fixnamethreads', '1'),
	('fixnamesperrun', '10'),
	('tablepergroup', '0'),
	('nntpproxy', 0),
	('releasesthreads', '1'),
	('replacenzbs', '0'),
	('anidbkey', ''),
	('safepartrepair', '0'),
	('nntpretries', '10'),
	('maxgrabnzbs', '100'),
	('showdroppedyencparts', '0'),
	('book_reqids', '8010'),
	('showbacks', '0'),
	('sqlpatch','174');


INSERT INTO tmux (setting, value) values ('defrag_cache','900'),
	('monitor_delay','30'),
	('tmux_session','nZEDb'),
	('niceness','19'),
	('binaries','0'),
	('backfill','0'),
	('import','0'),
	('nzbs','/path/to/nzbs'),
	('running','0'),
	('sequential','0'),
	('nfos','0'),
	('post','0'),
	('releases','0'),
	('releases_threaded','0'),
	('fix_names','0'),
	('seq_timer','30'),
	('bins_timer','30'),
	('back_timer','30'),
	('import_timer','30'),
	('rel_timer','30'),
	('fix_timer','30'),
	('post_timer','30'),
	('import_bulk','0'),
	('backfill_qty','100000'),
	('collections_kill','0'),
	('postprocess_kill','0'),
	('crap_timer','30'),
	('fix_crap','0'),
	('tv_timer','43200'),
	('update_tv','0'),
	('htop','0'),
	('nmon','0'),
	('bwmng','0'),
	('mytop','0'),
	('console','0'),
	('vnstat','0'),
	('vnstat_args',NULL),
	('tcptrack','0'),
	('tcptrack_args','-i eth0 port 443'),
	('backfill_groups','4'),
	('post_kill_timer','300'),
	('optimize','0'),
	('optimize_timer','86400'),
	('monitor_path', NULL),
	('write_logs','0'),
	('sorter','0'),
	('sorter_timer', 30),
	('powerline','0'),
	('patchdb','0'),
	('patchdb_timer','21600'),
	('progressive','0'),
	('dehash', '0'),
	('dehash_timer','30'),
	('backfill_order','2'),
	('backfill_days', '1'),
	('post_amazon', '0'),
	('post_non', '0'),
	('post_timer_amazon', '30'),
	('post_timer_non', '30'),
	('colors_start', '1'),
	('colors_end', '250'),
	('colors_exc', '4, 8, 9, 11, 15, 16, 17, 18, 19, 46, 47, 48, 49, 50, 51, 52, 53, 59, 60'),
	('monitor_path_a', NULL),
	('monitor_path_b', NULL),
	('colors', '0'),
	('showquery', '0'),
	('fix_crap_opt', 'Disabled'),
	('showprocesslist', '0'),
	('processupdate', '2');


INSERT INTO userroles (id, name, apirequests, downloadrequests, defaultinvites, isdefault, canpreview) VALUES
(1, 'Guest', 0, 0, 0, 0, 0),
(2, 'User', 10, 10, 1, 1, 0),
(3, 'Admin', 1000, 1000, 1000, 0, 1),
(4, 'Disabled', 0, 0, 0, 0, 0),
(5, 'Moderator', 1000, 1000, 1000, 0, 1),
(6, 'Friend', 100, 100, 5, 0, 1);

UPDATE userroles SET  id =  id-1;

INSERT INTO country (code, name) VALUES ( 'AF', 'Afghanistan' ),
	( 'AX', 'Aland Islands' ),
	( 'AL', 'Albania' ),
	( 'DZ', 'Algeria' ),
	( 'AS', 'American Samoa' ),
	( 'AD', 'Andorra' ),
	( 'AO', 'Angola' ),
	( 'AI', 'Anguilla' ),
	( 'AQ', 'Antarctica' ),
	( 'AG', 'Antigua and Barbuda' ),
	( 'AR', 'Argentina' ),
	( 'AM', 'Armenia' ),
	( 'AW', 'Aruba' ),
	( 'AU', 'Australia' ),
	( 'AT', 'Austria' ),
	( 'AZ', 'Azerbaijan' ),
	( 'BS', 'Bahamas' ),
	( 'BH', 'Bahrain' ),
	( 'BD', 'Bangladesh' ),
	( 'BB', 'Barbados' ),
	( 'BY', 'Belarus' ),
	( 'BE', 'Belgium' ),
	( 'BZ', 'Belize' ),
	( 'BJ', 'Benin' ),
	( 'BM', 'Bermuda' ),
	( 'BT', 'Bhutan' ),
	( 'BO', 'Bolivia' ),
	( 'BA', 'Bosnia and Herzegovina' ),
	( 'BW', 'Botswana' ),
	( 'BV', 'Bouvetoya' ),
	( 'BR', 'Brazil' ),
	( 'VG', 'British Virgin Islands' ),
	( 'BN', 'Brunei Darussalam' ),
	( 'BG', 'Bulgaria' ),
	( 'BF', 'Burkina Faso' ),
	( 'BI', 'Burundi' ),
	( 'KH', 'Cambodia' ),
	( 'CM', 'Cameroon' ),
	( 'CA', 'Canada' ),
	( 'CV', 'Cape Verde' ),
	( 'KY', 'Cayman Islands' ),
	( 'CF', 'Central African Republic' ),
	( 'TD', 'Chad' ),
	( 'CL', 'Chile' ),
	( 'CN', 'China' ),
	( 'CX', 'Christmas Island' ),
	( 'CC', 'Cocos (Keeling) Islands' ),
	( 'CO', 'Colombia' ),
	( 'KM', 'Comoros the' ),
	( 'CD', 'Congo' ),
	( 'CG', 'Congo the' ),
	( 'CK', 'Cook Islands' ),
	( 'CR', 'Costa Rica' ),
	( 'CI', 'Cote d''Ivoire' ),
	( 'HR', 'Croatia' ),
	( 'CU', 'Cuba' ),
	( 'CY', 'Cyprus' ),
	( 'CZ', 'Czech Republic' ),
	( 'DK', 'Denmark' ),
	( 'DJ', 'Djibouti' ),
	( 'DM', 'Dominica' ),
	( 'DO', 'Dominican Republic' ),
	( 'EC', 'Ecuador' ),
	( 'EG', 'Egypt' ),
	( 'SV', 'El Salvador' ),
	( 'GQ', 'Equatorial Guinea' ),
	( 'ER', 'Eritrea' ),
	( 'EE', 'Estonia' ),
	( 'ET', 'Ethiopia' ),
	( 'FO', 'Faroe Islands' ),
	( 'FK', 'Falkland Islands' ),
	( 'FJ', 'Fiji' ),
	( 'FI', 'Finland' ),
	( 'FR', 'France' ),
	( 'GF', 'French Guiana' ),
	( 'PF', 'French Polynesia' ),
	( 'GA', 'Gabon' ),
	( 'GM', 'Gambia' ),
	( 'GE', 'Georgia' ),
	( 'DE', 'Germany' ),
	( 'GH', 'Ghana' ),
	( 'GI', 'Gibraltar' ),
	( 'GR', 'Greece' ),
	( 'GL', 'Greenland' ),
	( 'GD', 'Grenada' ),
	( 'GP', 'Guadeloupe' ),
	( 'GU', 'Guam' ),
	( 'GT', 'Guatemala' ),
	( 'GG', 'Guernsey' ),
	( 'GN', 'Guinea' ),
	( 'GW', 'Guinea-Bissau' ),
	( 'GY', 'Guyana' ),
	( 'HT', 'Haiti' ),
	( 'HN', 'Honduras' ),
	( 'HK', 'Hong Kong' ),
	( 'HU', 'Hungary' ),
	( 'IS', 'Iceland' ),
	( 'IN', 'India' ),
	( 'ID', 'Indonesia' ),
	( 'IR', 'Iran' ),
	( 'IQ', 'Iraq' ),
	( 'IE', 'Ireland' ),
	( 'IM', 'Isle of Man' ),
	( 'IL', 'Israel' ),
	( 'IT', 'Italy' ),
	( 'JM', 'Jamaica' ),
	( 'JP', 'Japan' ),
	( 'JE', 'Jersey' ),
	( 'JO', 'Jordan' ),
	( 'KZ', 'Kazakhstan' ),
	( 'KE', 'Kenya' ),
	( 'KI', 'Kiribati' ),
	( 'KP', 'Korea' ),
	( 'KR', 'Korea' ),
	( 'KW', 'Kuwait' ),
	( 'KG', 'Kyrgyz Republic' ),
	( 'LA', 'Lao' ),
	( 'LV', 'Latvia' ),
	( 'LB', 'Lebanon' ),
	( 'LS', 'Lesotho' ),
	( 'LR', 'Liberia' ),
	( 'LY', 'Libyan Arab Jamahiriya' ),
	( 'LI', 'Liechtenstein' ),
	( 'LT', 'Lithuania' ),
	( 'LU', 'Luxembourg' ),
	( 'MO', 'Macao' ),
	( 'MK', 'Macedonia' ),
	( 'MG', 'Madagascar' ),
	( 'MW', 'Malawi' ),
	( 'MY', 'Malaysia' ),
	( 'MV', 'Maldives' ),
	( 'ML', 'Mali' ),
	( 'MT', 'Malta' ),
	( 'MH', 'Marshall Islands' ),
	( 'MQ', 'Martinique' ),
	( 'MR', 'Mauritania' ),
	( 'MU', 'Mauritius' ),
	( 'YT', 'Mayotte' ),
	( 'MX', 'Mexico' ),
	( 'FM', 'Micronesia' ),
	( 'MD', 'Moldova' ),
	( 'MC', 'Monaco' ),
	( 'MN', 'Mongolia' ),
	( 'ME', 'Montenegro' ),
	( 'MS', 'Montserrat' ),
	( 'MA', 'Morocco' ),
	( 'MZ', 'Mozambique' ),
	( 'MM', 'Myanmar' ),
	( 'NA', 'Namibia' ),
	( 'NR', 'Nauru' ),
	( 'NP', 'Nepal' ),
	( 'AN', 'Netherlands Antilles' ),
	( 'NL', 'Netherlands' ),
	( 'NC', 'New Caledonia' ),
	( 'NZ', 'New Zealand' ),
	( 'NI', 'Nicaragua' ),
	( 'NE', 'Niger' ),
	( 'NG', 'Nigeria' ),
	( 'NU', 'Niue' ),
	( 'NF', 'Norfolk Island' ),
	( 'MP', 'Northern Mariana Islands' ),
	( 'NO', 'Norway' ),
	( 'OM', 'Oman' ),
	( 'PK', 'Pakistan' ),
	( 'PW', 'Palau' ),
	( 'PS', 'Palestinian Territory' ),
	( 'PA', 'Panama' ),
	( 'PG', 'Papua New Guinea' ),
	( 'PY', 'Paraguay' ),
	( 'PE', 'Peru' ),
	( 'PH', 'Philippines' ),
	( 'PN', 'Pitcairn Islands' ),
	( 'PL', 'Poland' ),
	( 'PT', 'Portugal' ),
	( 'PR', 'Puerto Rico' ),
	( 'QA', 'Qatar' ),
	( 'RE', 'Reunion' ),
	( 'RO', 'Romania' ),
	( 'RU', 'Russian Federation' ),
	( 'RW', 'Rwanda' ),
	( 'BL', 'Saint Barthelemy' ),
	( 'SH', 'Saint Helena' ),
	( 'KN', 'Saint Kitts' ),
	( 'LC', 'Saint Lucia' ),
	( 'MF', 'Saint Martin' ),
	( 'PM', 'Saint Pierre' ),
	( 'VC', 'Saint Vincent' ),
	( 'WS', 'Samoa' ),
	( 'SM', 'San Marino' ),
	( 'ST', 'Sao Tome' ),
	( 'SA', 'Saudi Arabia' ),
	( 'SN', 'Senegal' ),
	( 'RS', 'Serbia' ),
	( 'SC', 'Seychelles' ),
	( 'SL', 'Sierra Leone' ),
	( 'SG', 'Singapore' ),
	( 'SK', 'Slovakia' ),
	( 'SI', 'Slovenia' ),
	( 'SB', 'Solomon Islands' ),
	( 'SO', 'Somalia' ),
	( 'ZA', 'South Africa' ),
	( 'ES', 'Spain' ),
	( 'LK', 'Sri Lanka' ),
	( 'SD', 'Sudan' ),
	( 'SR', 'Suriname' ),
	( 'SZ', 'Swaziland' ),
	( 'SE', 'Sweden' ),
	( 'CH', 'Switzerland' ),
	( 'SY', 'Syrian Arab Republic' ),
	( 'TW', 'Taiwan' ),
	( 'TJ', 'Tajikistan' ),
	( 'TZ', 'Tanzania' ),
	( 'TH', 'Thailand' ),
	( 'TL', 'Timor-Leste' ),
	( 'TG', 'Togo' ),
	( 'TK', 'Tokelau' ),
	( 'TO', 'Tonga' ),
	( 'TT', 'Trinidad and Tobago' ),
	( 'TN', 'Tunisia' ),
	( 'TR', 'Turkey' ),
	( 'TM', 'Turkmenistan' ),
	( 'TV', 'Tuvalu' ),
	( 'UG', 'Uganda' ),
	( 'UA', 'Ukraine' ),
	( 'AE', 'United Arab Emirates' ),
	( 'GB', 'United Kingdom' ),
	( 'US', 'United States' ),
	( 'VI', 'United States Virgin Islands' ),
	( 'UY', 'Uruguay' ),
	( 'UZ', 'Uzbekistan' ),
	( 'VU', 'Vanuatu' ),
	( 'VE', 'Venezuela' ),
	( 'VN', 'Vietnam' ),
	( 'WF', 'Wallis and Futuna' ),
	( 'EH', 'Western Sahara' ),
	( 'YE', 'Yemen' ),
	( 'ZM', 'Zambia' ),
	( 'ZW', 'Zimbabwe' );

DROP INDEX IF EXISTS "animetitles_title" CASCADE;
CREATE UNIQUE INDEX "animetitles_title" ON "animetitles" ("title");ALTER TABLE "binaries" ADD CONSTRAINT "binaries_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "binaries_binaryhash" CASCADE;
CREATE INDEX "binaries_binaryhash" ON "binaries" ("binaryhash");
DROP INDEX IF EXISTS "binaries_partcheck" CASCADE;
CREATE INDEX "binaries_partcheck" ON "binaries" ("partcheck");
DROP INDEX IF EXISTS "binaries_collectionid" CASCADE;
CREATE INDEX "binaries_collectionid" ON "binaries" ("collectionid");ALTER TABLE "binaryblacklist" ADD CONSTRAINT "binaryblacklist_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "binaryblacklist_groupname" CASCADE;
CREATE INDEX "binaryblacklist_groupname" ON "binaryblacklist" ("groupname");
DROP INDEX IF EXISTS "binaryblacklist_status" CASCADE;
CREATE INDEX "binaryblacklist_status" ON "binaryblacklist" ("status");ALTER TABLE "bookinfo" ADD CONSTRAINT "bookinfo_id_pkey" PRIMARY KEY("id");ALTER TABLE "category" ADD CONSTRAINT "category_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "category_status" CASCADE;
CREATE INDEX "category_status" ON "category" ("status");
DROP INDEX IF EXISTS "category_parentid" CASCADE;
CREATE INDEX "category_parentid" ON "category" ("parentid");ALTER TABLE "collections" ADD CONSTRAINT "collections_id_pkey" PRIMARY KEY("id");
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
CREATE INDEX "collections_releaseid" ON "collections" ("releaseid");ALTER TABLE "consoleinfo" ADD CONSTRAINT "consoleinfo_id_pkey" PRIMARY KEY("id");ALTER TABLE "content" ADD CONSTRAINT "content_id_pkey" PRIMARY KEY("id");ALTER TABLE "forumpost" ADD CONSTRAINT "forumpost_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "forumpost_parentid" CASCADE;
CREATE INDEX "forumpost_parentid" ON "forumpost" ("parentid");
DROP INDEX IF EXISTS "forumpost_userid" CASCADE;
CREATE INDEX "forumpost_userid" ON "forumpost" ("userid");
DROP INDEX IF EXISTS "forumpost_createddate" CASCADE;
CREATE INDEX "forumpost_createddate" ON "forumpost" ("createddate");
DROP INDEX IF EXISTS "forumpost_updateddate" CASCADE;
CREATE INDEX "forumpost_updateddate" ON "forumpost" ("updateddate");ALTER TABLE "genres" ADD CONSTRAINT "genres_id_pkey" PRIMARY KEY("id");ALTER TABLE "groups" ADD CONSTRAINT "groups_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "groups_name" CASCADE;
CREATE UNIQUE INDEX "groups_name" ON "groups" ("name");
DROP INDEX IF EXISTS "groups_active" CASCADE;
CREATE INDEX "groups_active" ON "groups" ("active");
DROP INDEX IF EXISTS "groups_id" CASCADE;
CREATE INDEX "groups_id" ON "groups" ("id");ALTER TABLE "menu" ADD CONSTRAINT "menu_id_pkey" PRIMARY KEY("id");ALTER TABLE "movieinfo" ADD CONSTRAINT "movieinfo_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "movieinfo_imdbid" CASCADE;
CREATE UNIQUE INDEX "movieinfo_imdbid" ON "movieinfo" ("imdbid");
DROP INDEX IF EXISTS "movieinfo_title" CASCADE;
CREATE INDEX "movieinfo_title" ON "movieinfo" ("title");ALTER TABLE "musicinfo" ADD CONSTRAINT "musicinfo_id_pkey" PRIMARY KEY("id");ALTER TABLE "nzbs" ADD CONSTRAINT "id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "nzbs_partnumber" CASCADE;
CREATE INDEX "nzbs_partnumber" ON "nzbs" ("partnumber");
DROP INDEX IF EXISTS "nzbs_message" CASCADE;
CREATE UNIQUE INDEX "nzbs_message" ON "nzbs" ("message_id");
DROP INDEX IF EXISTS "nzbs_collectionhash" CASCADE;
CREATE INDEX "nzbs_collectionhash" ON "nzbs" ("collectionhash");ALTER TABLE "partrepair" ADD CONSTRAINT "partrepair_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "partrepair_numberid_groupid" CASCADE;
CREATE UNIQUE INDEX "partrepair_numberid_groupid" ON "partrepair" ("numberid", "groupid");
DROP INDEX IF EXISTS "partrepair_attempts" CASCADE;
CREATE INDEX "partrepair_attempts" ON "partrepair" ("attempts");ALTER TABLE "parts" ADD CONSTRAINT "parts_id_pkey" PRIMARY KEY("id");
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
ALTER TABLE "predb" ADD CONSTRAINT "predb_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "predb_title" CASCADE;
CREATE INDEX "predb_title" ON "predb" ("title");
DROP INDEX IF EXISTS "predb_nfo" CASCADE;
CREATE INDEX "predb_nfo" ON "predb" ("nfo");
DROP INDEX IF EXISTS "predb_predate" CASCADE;
CREATE INDEX "predb_predate" ON "predb" ("predate");
DROP INDEX IF EXISTS "predb_adddate" CASCADE;
CREATE INDEX "predb_adddate" ON "predb" ("adddate");
DROP INDEX IF EXISTS "predb_source" CASCADE;
CREATE INDEX "predb_source" ON "predb" ("source");
DROP INDEX IF EXISTS "predb_requestid" CASCADE;
CREATE INDEX predb_requestid on predb(requestid, groupid);
DROP INDEX IF EXISTS "predb_md5" CASCADE;
CREATE UNIQUE INDEX "predb_md5" ON "predb" ("md5");ALTER TABLE "releaseaudio" ADD CONSTRAINT "releaseaudio_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "releaseaudio_releaseid_audioid" CASCADE;
CREATE UNIQUE INDEX "releaseaudio_releaseid_audioid" ON "releaseaudio" ("releaseid", "audioid");ALTER TABLE "releasecomment" ADD CONSTRAINT "releasecomment_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "releasecomment_releaseid" CASCADE;
CREATE INDEX "releasecomment_releaseid" ON "releasecomment" ("releaseid");
DROP INDEX IF EXISTS "releasecomment_userid" CASCADE;
CREATE INDEX "releasecomment_userid" ON "releasecomment" ("userid");ALTER TABLE "releaseextrafull" ADD CONSTRAINT "releaseextrafull_releaseid_pkey" PRIMARY KEY("releaseid");ALTER TABLE "releasefiles" ADD CONSTRAINT "releasefiles_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "releasefiles_name_releaseid" CASCADE;
CREATE UNIQUE INDEX "releasefiles_name_releaseid" ON "releasefiles" ("name", "releaseid");
DROP INDEX IF EXISTS "releasefiles_releaseid" CASCADE;
CREATE INDEX "releasefiles_releaseid" ON "releasefiles" ("releaseid");
DROP INDEX IF EXISTS "releasefiles_name" CASCADE;
CREATE INDEX "releasefiles_name" ON "releasefiles" ("name");ALTER TABLE "releasenfo" ADD CONSTRAINT "releasenfo_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "releasenfo_releaseid" CASCADE;
CREATE UNIQUE INDEX "releasenfo_releaseid" ON "releasenfo" ("releaseid");ALTER TABLE "releases" ADD CONSTRAINT "releases_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "releases_adddate" CASCADE;
CREATE INDEX "releases_adddate" ON "releases" ("adddate");
DROP INDEX IF EXISTS "releases_postdate" CASCADE;
CREATE INDEX "releases_postdate" ON "releases" ("postdate");
DROP INDEX IF EXISTS "releases_categoryid" CASCADE;
CREATE INDEX "releases_categoryid" ON "releases" ("categoryid");
DROP INDEX IF EXISTS "releases_rageid" CASCADE;
CREATE INDEX "releases_rageid" ON "releases" ("rageid");
DROP INDEX IF EXISTS "releases_imdbid" CASCADE;
CREATE INDEX "releases_imdbid" ON "releases" ("imdbid");
DROP INDEX IF EXISTS "releases_preid" CASCADE;
CREATE INDEX "releases_preid" ON "releases" ("preid");
DROP INDEX IF EXISTS "releases_guid" CASCADE;
CREATE INDEX "releases_guid" ON "releases" ("guid");
DROP INDEX IF EXISTS "releases_name" CASCADE;
CREATE INDEX "releases_name" ON "releases" ("name");
DROP INDEX IF EXISTS "releases_searchname" CASCADE;
CREATE INDEX "releases_searchname" ON "releases" ("searchname");
DROP INDEX IF EXISTS "releases_groupid" CASCADE;
CREATE INDEX "releases_groupid" ON "releases" ("groupid");
DROP INDEX IF EXISTS "releases_bitwise" CASCADE;
CREATE INDEX "releases_bitwise" ON "releases" ("bitwise");
DROP INDEX IF EXISTS "releases_passwordstatus" CASCADE;
CREATE INDEX "releases_passwordstatus" ON "releases" ("passwordstatus");
DROP INDEX IF EXISTS "releases_dehashstatus" CASCADE;
CREATE INDEX "releases_dehashstatus" ON "releases" ("dehashstatus");
DROP INDEX IF EXISTS "releases_reqidstatus" CASCADE;
CREATE INDEX "releases_reqidstatus" ON "releases" ("reqidstatus");
DROP INDEX IF EXISTS "releases_nfostatus" CASCADE;
CREATE INDEX "releases_nfostatus" ON "releases" ("nfostatus");
DROP INDEX IF EXISTS "releases_musicinfoid" CASCADE;
CREATE INDEX "releases_musicinfoid" ON "releases" ("musicinfoid");
DROP INDEX IF EXISTS "releases_consoleinfoid" CASCADE;
CREATE INDEX "releases_consoleinfoid" ON "releases" ("consoleinfoid");
DROP INDEX IF EXISTS "releases_bookinfoid" CASCADE;
CREATE INDEX "releases_bookinfoid" ON "releases" ("bookinfoid");
DROP INDEX IF EXISTS "releases_haspreview" CASCADE;
CREATE INDEX "releases_haspreview" ON "releases" ("haspreview");ALTER TABLE "releasesubs" ADD CONSTRAINT "releasesubs_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "releasesubs_releaseid_subsid" CASCADE;
CREATE UNIQUE INDEX "releasesubs_releaseid_subsid" ON "releasesubs" ("releaseid", "subsid");ALTER TABLE "releasevideo" ADD CONSTRAINT "releasevideo_releaseid_pkey" PRIMARY KEY("releaseid");ALTER TABLE "site" ADD CONSTRAINT "site_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "site_setting" CASCADE;
CREATE UNIQUE INDEX "site_setting" ON "site" ("setting");ALTER TABLE "tmux" ADD CONSTRAINT "tmux_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "tmux_setting" CASCADE;
CREATE UNIQUE INDEX "tmux_setting" ON "tmux" ("setting");ALTER TABLE "tvrage" ADD CONSTRAINT "tvrage_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "tvrage_rageid_releasetitle" CASCADE;
CREATE UNIQUE INDEX "tvrage_rageid_releasetitle" ON "tvrage" ("rageid", "releasetitle");
DROP INDEX IF EXISTS "tvrage_rageid" CASCADE;
CREATE INDEX "tvrage_rageid" ON "tvrage" ("rageid");
DROP INDEX IF EXISTS "tvrage_releasetitle" CASCADE;
CREATE INDEX "tvrage_releasetitle" ON "tvrage" ("releasetitle");ALTER TABLE "tvrageepisodes" ADD CONSTRAINT "tvrageepisodes_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "tvrageepisodes_rageid_fullep" CASCADE;
CREATE UNIQUE INDEX "tvrageepisodes_rageid_fullep" ON "tvrageepisodes" ("rageid", "fullep");ALTER TABLE "upcoming" ADD CONSTRAINT "upcoming_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "upcoming_source_typeid" CASCADE;
CREATE UNIQUE INDEX "upcoming_source_typeid" ON "upcoming" ("source", "typeid");ALTER TABLE "usercart" ADD CONSTRAINT "usercart_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "usercart_userid_releaseid" CASCADE;
CREATE UNIQUE INDEX "usercart_userid_releaseid" ON "usercart" ("userid", "releaseid");ALTER TABLE "userdownloads" ADD CONSTRAINT "userdownloads_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "userdownloads_userid" CASCADE;
CREATE INDEX "userdownloads_userid" ON "userdownloads" ("userid");
DROP INDEX IF EXISTS "userdownloads_timestamp" CASCADE;
CREATE INDEX "userdownloads_timestamp" ON "userdownloads" ("timestamp");ALTER TABLE "userexcat" ADD CONSTRAINT "userexcat_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "userexcat_userid_categoryid" CASCADE;
CREATE UNIQUE INDEX "userexcat_userid_categoryid" ON "userexcat" ("userid", "categoryid");ALTER TABLE "userinvite" ADD CONSTRAINT "userinvite_id_pkey" PRIMARY KEY("id");ALTER TABLE "usermovies" ADD CONSTRAINT "usermovies_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "usermovies_userid_imdbid" CASCADE;
CREATE INDEX "usermovies_userid_imdbid" ON "usermovies" ("userid", "imdbid");ALTER TABLE "userrequests" ADD CONSTRAINT "userrequests_id_pkey" PRIMARY KEY("id");
DROP INDEX IF EXISTS "userrequests_userid" CASCADE;
CREATE INDEX "userrequests_userid" ON "userrequests" ("userid");
DROP INDEX IF EXISTS "userrequests_timestamp" CASCADE;
CREATE INDEX "userrequests_timestamp" ON "userrequests" ("timestamp");ALTER TABLE "userroles" ADD CONSTRAINT "userroles_id_pkey" PRIMARY KEY("id");ALTER TABLE "users" ADD CONSTRAINT "users_id_pkey" PRIMARY KEY("id");ALTER TABLE "userseries" ADD CONSTRAINT "userseries_id_pkey" PRIMARY KEY("id");
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
DROP INDEX IF EXISTS "ix_releases_status" CASCADE;
CREATE INDEX ix_releases_status ON releases (id, nfostatus, bitwise, passwordstatus, dehashstatus, reqidstatus, musicinfoid, consoleinfoid, bookinfoid, haspreview, categoryid);
DROP INDEX IF EXISTS "ix_releases_postdate" CASCADE;
CREATE INDEX ix_releases_postdate ON releases (name, searchname, id, postdate);
DROP INDEX IF EXISTS "ix_releases_postdate_searchname" CASCADE;
CREATE INDEX ix_releases_postdate_searchname ON releases (postdate, searchname);
DROP INDEX IF EXISTS "ix_releases_postdate_name" CASCADE;
CREATE INDEX ix_releases_postdate_name ON releases (postdate, name);
DROP INDEX IF EXISTS "ix_releases_nzb_guid" CASCADE;
CREATE INDEX ix_releases_nzb_guid ON releases (nzb_guid);

CREATE FUNCTION hash_check() RETURNS trigger AS $hash_check$ BEGIN IF NEW.searchname ~ '[a-fA-F0-9]{32}' OR NEW.name ~ '[a-fA-F0-9]{32}' THEN SET NEW.bitwise = "((NEW.bitwise & ~512)|512)"; END IF; END; $hash_check$ LANGUAGE plpgsql;
CREATE FUNCTION request_check() RETURNS trigger AS $request_check$ BEGIN IF NEW.searchname ~'^\\[[[:digit:]]+\\]' OR NEW.name ~'^\\[[[:digit:]]+\\]' THEN SET NEW.bitwise = "((NEW.bitwise & ~1024)|1024)"; END IF; END; $request_check$ LANGUAGE plpgsql;
CREATE TRIGGER request_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE request_check();
CREATE TRIGGER hash_check BEFORE INSERT OR UPDATE ON releases FOR EACH ROW EXECUTE PROCEDURE hash_check();
