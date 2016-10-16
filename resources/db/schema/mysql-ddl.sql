DROP TABLE IF EXISTS allgroups;
CREATE TABLE allgroups (
  id           INT(11)         NOT NULL AUTO_INCREMENT,
  name         VARCHAR(255)    NOT NULL DEFAULT '',
  first_record BIGINT UNSIGNED NOT NULL DEFAULT '0',
  last_record  BIGINT UNSIGNED NOT NULL DEFAULT '0',
  updated      DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX ix_allgroups_name (name)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS anidb_episodes;
CREATE TABLE anidb_episodes (
  anidbid       INT(10) UNSIGNED        NOT NULL
  COMMENT 'ID of title from AniDB',
  episodeid     INT(10) UNSIGNED        NOT NULL DEFAULT '0'
  COMMENT 'anidb id for this episode',
  episode_no    SMALLINT(5) UNSIGNED    NOT NULL
  COMMENT 'Numeric version of episode (leave 0 for combined episodes).',
  episode_title VARCHAR(255)
                COLLATE utf8_unicode_ci NOT NULL
  COMMENT 'Title of the episode (en, x-jat)',
  airdate       DATE                    NOT NULL,
  PRIMARY KEY (anidbid, episodeid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;


DROP TABLE IF EXISTS anidb_info;
CREATE TABLE anidb_info (
  anidbid     INT(10) UNSIGNED NOT NULL
  COMMENT 'ID of title from AniDB',
  type        VARCHAR(32)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  startdate   DATE                    DEFAULT NULL,
  enddate     DATE                    DEFAULT NULL,
  updated     TIMESTAMP               DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  related     VARCHAR(1024)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  similar     VARCHAR(1024)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  creators    VARCHAR(1024)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  description TEXT
              COLLATE utf8_unicode_ci DEFAULT NULL,
  rating      VARCHAR(5)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  picture     VARCHAR(255)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  categories  VARCHAR(1024)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  characters  VARCHAR(1024)
              COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (anidbid),
  KEY ix_anidb_info_datetime (startdate, enddate, updated)
)
  ENGINE = MYISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;


DROP TABLE IF EXISTS anidb_titles;
CREATE TABLE anidb_titles (
  anidbid INT(10) UNSIGNED        NOT NULL
  COMMENT 'ID of title from AniDB',
  type    VARCHAR(25)
          COLLATE utf8_unicode_ci NOT NULL
  COMMENT 'type of title.',
  lang    VARCHAR(25)
          COLLATE utf8_unicode_ci NOT NULL,
  title   VARCHAR(255)
          COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (anidbid, type, lang, title)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS audio_data;
CREATE TABLE audio_data (
  id               INT(11)     UNSIGNED AUTO_INCREMENT,
  releases_id      INT(11)     UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  audioid          INT(2)      UNSIGNED NOT NULL,
  audioformat      VARCHAR(50) DEFAULT NULL,
  audiomode        VARCHAR(50) DEFAULT NULL,
  audiobitratemode VARCHAR(50) DEFAULT NULL,
  audiobitrate     VARCHAR(10) DEFAULT NULL,
  audiochannels    VARCHAR(25) DEFAULT NULL,
  audiosamplerate  VARCHAR(25) DEFAULT NULL,
  audiolibrary     VARCHAR(50) DEFAULT NULL,
  audiolanguage    VARCHAR(50) DEFAULT NULL,
  audiotitle       VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_releaseaudio_releaseid_audioid (releases_id, audioid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS binaries;
CREATE TABLE binaries (
  id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name          VARCHAR(1000)       NOT NULL DEFAULT '',
  collection_id INT(11) UNSIGNED    NOT NULL DEFAULT 0,
  filenumber    INT UNSIGNED        NOT NULL DEFAULT '0',
  totalparts    INT(11) UNSIGNED    NOT NULL DEFAULT 0,
  currentparts  INT UNSIGNED        NOT NULL DEFAULT 0,
  binaryhash    BINARY(16)          NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  partcheck     TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  partsize      BIGINT UNSIGNED     NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_binary_binaryhash (binaryhash),
  INDEX ix_binary_partcheck  (partcheck),
  INDEX ix_binary_collection (collection_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS binaryblacklist;
CREATE TABLE binaryblacklist (
  id            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  groupname     VARCHAR(255)     NULL,
  regex         VARCHAR(2000)    NOT NULL,
  msgcol        INT(11) UNSIGNED NOT NULL DEFAULT '1',
  optype        INT(11) UNSIGNED NOT NULL DEFAULT '1',
  status        INT(11) UNSIGNED NOT NULL DEFAULT '1',
  description   VARCHAR(1000) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci    NULL,
  last_activity	DATE             NULL,
  PRIMARY KEY (id),
  INDEX ix_binaryblacklist_groupname (groupname),
  INDEX ix_binaryblacklist_status    (status)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1000001;


DROP TABLE IF EXISTS bookinfo;
CREATE TABLE bookinfo (
  id          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)        NOT NULL,
  author      VARCHAR(255)        NOT NULL,
  asin        VARCHAR(128)     DEFAULT NULL,
  isbn        VARCHAR(128)     DEFAULT NULL,
  ean         VARCHAR(128)     DEFAULT NULL,
  url         VARCHAR(1000)    DEFAULT NULL,
  salesrank   INT(10) UNSIGNED DEFAULT NULL,
  publisher   VARCHAR(255)     DEFAULT NULL,
  publishdate DATETIME         DEFAULT NULL,
  pages       VARCHAR(128)     DEFAULT NULL,
  overview    VARCHAR(3000)    DEFAULT NULL,
  genre       VARCHAR(255)        NOT NULL,
  cover       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  createddate DATETIME            NOT NULL,
  updateddate DATETIME            NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT INDEX ix_bookinfo_author_title_ft (author, title),
  UNIQUE INDEX ix_bookinfo_asin (asin)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS categories;
CREATE TABLE categories (
  id             INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  title          VARCHAR(255)     NOT NULL,
  parentid       INT              NULL,
  status         INT              NOT NULL DEFAULT '1',
  description    VARCHAR(255)     NULL,
  disablepreview TINYINT(1)       NOT NULL DEFAULT '0',
  minsize        BIGINT UNSIGNED  NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  INDEX ix_categories_status   (status),
  INDEX ix_categories_parentid (parentid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1000001;


DROP TABLE IF EXISTS category_regexes;
CREATE TABLE category_regexes (
  id          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)        NOT NULL DEFAULT ''     COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000)       NOT NULL DEFAULT ''     COMMENT 'Regex used to match a release name to categorize it',
  status      TINYINT(1) UNSIGNED NOT NULL DEFAULT '1'    COMMENT '1=ON 0=OFF',
  description VARCHAR(1000)       NOT NULL DEFAULT ''     COMMENT 'Optional extra details on this regex',
  ordinal     INT SIGNED          NOT NULL DEFAULT '0'    COMMENT 'Order to run the regex in',
  categories_id SMALLINT UNSIGNED   NOT NULL DEFAULT '0010' COMMENT 'Which categories id to put the release in',
  PRIMARY KEY (id),
  INDEX ix_category_regexes_group_regex (group_regex),
  INDEX ix_category_regexes_status      (status),
  INDEX ix_category_regexes_ordinal     (ordinal),
  INDEX ix_category_regexes_categories_id (categories_id)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;


DROP TABLE IF EXISTS collections;
CREATE TABLE         collections (
  id             INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  subject        VARCHAR(255)        NOT NULL DEFAULT '',
  fromname       VARCHAR(255)        NOT NULL DEFAULT '',
  date           DATETIME            DEFAULT NULL,
  xref           VARCHAR(255)        NOT NULL DEFAULT '',
  totalfiles     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  group_id       INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  collectionhash VARCHAR(255)        NOT NULL DEFAULT '0',
  dateadded      DATETIME            DEFAULT NULL,
  added          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  filecheck      TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
  filesize       BIGINT UNSIGNED     NOT NULL DEFAULT '0',
  releaseid      INT                 NULL,
  noise          CHAR(32)            NOT NULL DEFAULT '',
  PRIMARY KEY                               (id),
  INDEX        fromname                     (fromname),
  INDEX        date                         (date),
  INDEX        group_id                     (group_id),
  INDEX        ix_collection_filecheck      (filecheck),
  INDEX        ix_collection_dateadded      (dateadded),
  INDEX        ix_collection_releaseid      (releaseid),
  UNIQUE INDEX ix_collection_collectionhash (collectionhash)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;


DROP TABLE IF EXISTS collection_regexes;
CREATE TABLE collection_regexes (
  id          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)        NOT NULL DEFAULT ''  COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000)       NOT NULL DEFAULT ''  COMMENT 'Regex used for collection grouping',
  status      TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1=ON 0=OFF',
  description VARCHAR(1000)       CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Optional extra details on this regex',
  ordinal     INT SIGNED          NOT NULL DEFAULT '0' COMMENT 'Order to run the regex in',
  PRIMARY KEY (id),
  INDEX ix_collection_regexes_group_regex (group_regex),
  INDEX ix_collection_regexes_status      (status),
  INDEX ix_collection_regexes_ordinal     (ordinal)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;


DROP TABLE IF EXISTS consoleinfo;
CREATE TABLE consoleinfo (
  id          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)        NOT NULL,
  asin        VARCHAR(128)                 DEFAULT NULL,
  url         VARCHAR(1000)                DEFAULT NULL,
  salesrank   INT(10) UNSIGNED             DEFAULT NULL,
  platform    VARCHAR(255)                 DEFAULT NULL,
  publisher   VARCHAR(255)                 DEFAULT NULL,
  genre_id    INT(10)             NULL     DEFAULT NULL,
  esrb        VARCHAR(255)        NULL     DEFAULT NULL,
  releasedate DATETIME                     DEFAULT NULL,
  review      VARCHAR(3000)                DEFAULT NULL,
  cover       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  createddate DATETIME            NOT NULL,
  updateddate DATETIME            NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT INDEX ix_consoleinfo_title_platform_ft (title, platform),
  UNIQUE INDEX ix_consoleinfo_asin (asin)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS countries;
CREATE TABLE countries (
  id      CHAR(2) COLLATE utf8_unicode_ci NOT NULL COMMENT '2 character code.',
  iso3    CHAR(3) COLLATE utf8_unicode_ci NOT NULL COMMENT '3 character code.',
  country VARCHAR(180) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the country.',
  PRIMARY KEY (id),
  UNIQUE KEY code3 (iso3),
  UNIQUE KEY country (country)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS dnzb_failures;
CREATE TABLE dnzb_failures (
  release_id   INT(11) UNSIGNED  NOT NULL,
  userid      INT(11) UNSIGNED  NOT NULL,
  failed      INT UNSIGNED      NOT NULL DEFAULT '0',
  PRIMARY KEY (release_id, userid)
)
  ENGINE =MYISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;

DROP TABLE IF EXISTS forum_posts;
CREATE TABLE forum_posts (
  id          INT(11) UNSIGNED    NOT NULL AUTO_INCREMENT,
  forumid     INT(11)             NOT NULL DEFAULT '1',
  parentid    INT(11)             NOT NULL DEFAULT '0',
  user_id INT(11) UNSIGNED    NOT NULL,
  subject     VARCHAR(255)        NOT NULL,
  message     TEXT                NOT NULL,
  locked      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  sticky      TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  replies     INT(11) UNSIGNED    NOT NULL DEFAULT '0',
  createddate DATETIME            NOT NULL,
  updateddate DATETIME            NOT NULL,
  PRIMARY KEY (id),
  KEY parentid    (parentid),
  KEY userid      (user_id),
  KEY createddate (createddate),
  KEY updateddate (updateddate)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS gamesinfo;
CREATE TABLE gamesinfo (
  id          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)        NOT NULL,
  asin        VARCHAR(128)                 DEFAULT NULL,
  url         VARCHAR(1000)                DEFAULT NULL,
  publisher   VARCHAR(255)                 DEFAULT NULL,
  genre_id    INT(10)             NULL     DEFAULT NULL,
  esrb        VARCHAR(255)        NULL     DEFAULT NULL,
  releasedate DATETIME                     DEFAULT NULL,
  review      VARCHAR(3000)                DEFAULT NULL,
  cover       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  backdrop    TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  trailer     VARCHAR(1000)       NOT NULL DEFAULT '',
  classused   VARCHAR(10)         NOT NULL DEFAULT 'steam',
  createddate DATETIME            NOT NULL,
  updateddate DATETIME            NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX  ix_gamesinfo_asin (asin),
  INDEX         ix_title (title)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS genres;
CREATE TABLE genres (
  id       INT(11)      NOT NULL AUTO_INCREMENT,
  title    VARCHAR(255) NOT NULL,
  type     INT(4) DEFAULT NULL,
  disabled TINYINT(1)   NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1000001;


DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
  id                    INT(11)         NOT NULL AUTO_INCREMENT,
  name                  VARCHAR(255)    NOT NULL DEFAULT '',
  backfill_target       INT(4)          NOT NULL DEFAULT '1',
  first_record          BIGINT UNSIGNED NOT NULL DEFAULT '0',
  first_record_postdate DATETIME                 DEFAULT NULL,
  last_record           BIGINT UNSIGNED NOT NULL DEFAULT '0',
  last_record_postdate  DATETIME                 DEFAULT NULL,
  last_updated          DATETIME                 DEFAULT NULL,
  minfilestoformrelease INT(4)          NULL,
  minsizetoformrelease  BIGINT          NULL,
  active                TINYINT(1)      NOT NULL DEFAULT '0',
  backfill              TINYINT(1)      NOT NULL DEFAULT '0',
  description           VARCHAR(255)    NULL     DEFAULT '',
  PRIMARY KEY (id),
  KEY active                  (active),
  UNIQUE INDEX ix_groups_name (name)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1000001;


DROP TABLE IF EXISTS invitations;
CREATE TABLE invitations (
  id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  guid        VARCHAR(50)      NOT NULL,
  user_id INT(11) UNSIGNED NOT NULL,
  createddate DATETIME         NOT NULL,
  PRIMARY KEY (id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS logging;
CREATE TABLE logging (
  id       INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  time     DATETIME    DEFAULT NULL,
  username VARCHAR(50) DEFAULT NULL,
  host     VARCHAR(40) DEFAULT NULL,
  PRIMARY KEY (id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS menu_items;
CREATE TABLE menu_items (
  id        INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  href      VARCHAR(2000)    NOT NULL DEFAULT '',
  title     VARCHAR(2000)    NOT NULL DEFAULT '',
  newwindow INT(1) UNSIGNED  NOT NULL DEFAULT '0',
  tooltip   VARCHAR(2000)    NOT NULL DEFAULT '',
  role      INT(11) UNSIGNED NOT NULL,
  ordinal   INT(11) UNSIGNED NOT NULL,
  menueval  VARCHAR(2000)    NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX ix_role_ordinal (role, ordinal)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1000001;


DROP TABLE IF EXISTS missed_parts;
CREATE TABLE missed_parts (
  id       INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  numberid BIGINT UNSIGNED  NOT NULL,
  group_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'FK to groups.id',
  attempts TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  INDEX ix_missed_parts_attempts                  (attempts),
  INDEX ix_missed_parts_groupid_attempts          (group_id, attempts),
  INDEX ix_missed_parts_numberid_groupsid_attempts (numberid, group_id, attempts),
  UNIQUE INDEX ix_missed_parts_numberid_groupsid          (numberid, group_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS movieinfo;
CREATE TABLE movieinfo (
  id          INT(10) UNSIGNED               NOT NULL AUTO_INCREMENT,
  imdbid      MEDIUMINT(7) UNSIGNED ZEROFILL NOT NULL,
  tmdbid      INT(10) UNSIGNED               NOT NULL DEFAULT 0,
  title       VARCHAR(255)                   NOT NULL DEFAULT '',
  tagline     VARCHAR(1024)                  NOT NULL DEFAULT '',
  rating      VARCHAR(4)                     NOT NULL DEFAULT '',
  plot        VARCHAR(1024)                  NOT NULL DEFAULT '',
  year        VARCHAR(4)                     NOT NULL DEFAULT '',
  genre       VARCHAR(64)                    NOT NULL DEFAULT '',
  type        VARCHAR(32)                    NOT NULL DEFAULT '',
  director    VARCHAR(64)                    NOT NULL DEFAULT '',
  actors      VARCHAR(2000)                  NOT NULL DEFAULT '',
  language    VARCHAR(64)                    NOT NULL DEFAULT '',
  cover       TINYINT(1) UNSIGNED            NOT NULL DEFAULT '0',
  backdrop    TINYINT(1) UNSIGNED            NOT NULL DEFAULT '0',
  createddate DATETIME                       NOT NULL,
  updateddate DATETIME                       NOT NULL,
  trailer     VARCHAR(255)                   NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  INDEX ix_movieinfo_title  (title),
  UNIQUE INDEX ix_movieinfo_imdbid (imdbid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS musicinfo;
CREATE TABLE musicinfo (
  id          INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)        NOT NULL,
  asin        VARCHAR(128)        NULL,
  url         VARCHAR(1000)       NULL,
  salesrank   INT(10) UNSIGNED    NULL,
  artist      VARCHAR(255)        NULL,
  publisher   VARCHAR(255)        NULL,
  releasedate DATETIME            NULL,
  review      VARCHAR(3000)       NULL,
  year        VARCHAR(4)          NOT NULL,
  genre_id INT(10)             NULL,
  tracks      VARCHAR(3000)       NULL,
  cover       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  createddate DATETIME            NOT NULL,
  updateddate DATETIME            NOT NULL,
  PRIMARY KEY (id),
  FULLTEXT INDEX ix_musicinfo_artist_title_ft (artist, title),
  UNIQUE INDEX ix_musicinfo_asin (asin)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS page_contents;
CREATE TABLE page_contents (
  id              INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
  title           VARCHAR(255)    NOT NULL,
  url             VARCHAR(2000)   NULL,
  body            TEXT            NULL,
  metadescription VARCHAR(1000)   NOT NULL,
  metakeywords    VARCHAR(1000)   NOT NULL,
  contenttype     INT             NOT NULL,
  showinmenu      INT             NOT NULL,
  status          INT             NOT NULL,
  ordinal         INT             NULL,
  role            INT             NOT NULL DEFAULT '0',
  INDEX ix_showinmenu_status_contenttype_role (showinmenu, status, contenttype, role)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1000001;


DROP TABLE IF EXISTS parts;
CREATE TABLE parts (
  binaryid      BIGINT(20) UNSIGNED                      NOT NULL DEFAULT '0',
  messageid     VARCHAR(255)        CHARACTER SET latin1 NOT NULL DEFAULT '',
  number        BIGINT UNSIGNED                          NOT NULL DEFAULT '0',
  partnumber    MEDIUMINT UNSIGNED                       NOT NULL DEFAULT '0',
  size          MEDIUMINT UNSIGNED                       NOT NULL DEFAULT '0',
  PRIMARY KEY (binaryid,number)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS predb;
CREATE TABLE predb (
  id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
  title      VARCHAR(255)     NOT NULL DEFAULT '',
  nfo        VARCHAR(255)     NULL,
  size       VARCHAR(50)      NULL,
  category   VARCHAR(255)     NULL,
  predate    DATETIME                  DEFAULT NULL,
  source     VARCHAR(50)      NOT NULL DEFAULT '',
  requestid  INT(10) UNSIGNED NOT NULL DEFAULT '0',
  groups_id  INT(10) UNSIGNED NOT NULL DEFAULT '0'  COMMENT 'FK to groups',
  nuked      TINYINT(1)       NOT NULL DEFAULT '0'  COMMENT 'Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked',
  nukereason VARCHAR(255)     NULL  COMMENT 'If this pre is nuked, what is the reason?',
  files      VARCHAR(50)      NULL  COMMENT 'How many files does this pre have ?',
  filename   VARCHAR(255)     NOT NULL DEFAULT '',
  searched   TINYINT(1)       NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  UNIQUE INDEX ix_predb_title     (title),
  INDEX ix_predb_nfo       (nfo),
  INDEX ix_predb_predate   (predate),
  INDEX ix_predb_source    (source),
  INDEX ix_predb_requestid (requestid, groups_id),
  INDEX ix_predb_filename  (filename),
  INDEX ix_predb_searched  (searched)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS predb_hashes;
CREATE TABLE predb_hashes (
  predb_id INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'id, of the predb entry, this hash belongs to',
  hash VARBINARY(20)      NOT NULL DEFAULT '',
  PRIMARY KEY (hash)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;


DROP TABLE IF EXISTS predb_imports;
CREATE TABLE predb_imports (
  title      VARCHAR(255)
               COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  nfo        VARCHAR(255)
               COLLATE utf8_unicode_ci          DEFAULT NULL,
  size       VARCHAR(50)
               COLLATE utf8_unicode_ci          DEFAULT NULL,
  category   VARCHAR(255)
               COLLATE utf8_unicode_ci          DEFAULT NULL,
  predate    DATETIME                         DEFAULT NULL,
  source     VARCHAR(50)
               COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  requestid  INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  groups_id   INT(10) UNSIGNED        NOT NULL DEFAULT '0' COMMENT 'FK to groups',
  nuked      TINYINT(1)              NOT NULL DEFAULT '0'  COMMENT 'Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked',
  nukereason VARCHAR(255)
               COLLATE utf8_unicode_ci          DEFAULT NULL
  COMMENT 'If this pre is nuked, what is the reason?',
  files      VARCHAR(50)
               COLLATE utf8_unicode_ci          DEFAULT NULL
  COMMENT 'How many files does this pre have ?',
  filename   VARCHAR(255)
               COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  searched   TINYINT(1)              NOT NULL DEFAULT '0',
  groupname  VARCHAR(255)
               COLLATE utf8_unicode_ci          DEFAULT NULL
)
  ENGINE =MYISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;


DROP TABLE IF EXISTS releases;
CREATE TABLE         releases (
  id                INT(11) UNSIGNED               NOT NULL AUTO_INCREMENT,
  name              VARCHAR(255)                   NOT NULL DEFAULT '',
  searchname        VARCHAR(255)                   NOT NULL DEFAULT '',
  totalpart         INT                            DEFAULT '0',
  groups_id         INT(11) UNSIGNED               NOT NULL DEFAULT '0' COMMENT 'FK to groups.id',
  size              BIGINT UNSIGNED                NOT NULL DEFAULT '0',
  postdate          DATETIME                       DEFAULT NULL,
  adddate           DATETIME                       DEFAULT NULL,
  updatetime        TIMESTAMP                      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  guid              VARCHAR(40)                    NOT NULL,
  leftguid          CHAR(1)                        NOT NULL COMMENT 'The first letter of the release guid',
  fromname          VARCHAR(255)                   NULL,
  completion        FLOAT                          NOT NULL DEFAULT '0',
  categories_id     INT                            NOT NULL DEFAULT '0010',
  videos_id         MEDIUMINT(11) UNSIGNED         NOT NULL DEFAULT '0' COMMENT 'FK to videos.id of the parent series.',
  tv_episodes_id    MEDIUMINT(11) SIGNED           NOT NULL DEFAULT '0' COMMENT 'FK to tv_episodes.id for the episode.',
  imdbid            MEDIUMINT(7) UNSIGNED ZEROFILL NULL,
  xxxinfo_id        INT SIGNED                     NOT NULL DEFAULT '0',
  musicinfo_id      INT(11) SIGNED               NULL COMMENT 'FK to musicinfo.id',
  consoleinfo_id    INT(11) SIGNED               NULL COMMENT 'FK to consoleinfo.id',
  gamesinfo_id      INT SIGNED                     NOT NULL DEFAULT '0',
  bookinfo_id       INT(11) SIGNED               NULL COMMENT 'FK to bookinfo.id',
  anidbid           INT                            NULL COMMENT 'FK to anidb_titles.anidbid',
  predb_id          INT(11) UNSIGNED               NOT NULL DEFAULT '0' COMMENT 'FK to predb.id',
  grabs             INT UNSIGNED                   NOT NULL DEFAULT '0',
  comments          INT                            NOT NULL DEFAULT '0',
  passwordstatus    TINYINT                        NOT NULL DEFAULT '0',
  rarinnerfilecount INT                            NOT NULL DEFAULT '0',
  haspreview        TINYINT                        NOT NULL DEFAULT '0',
  nfostatus         TINYINT                        NOT NULL DEFAULT '0',
  jpgstatus         TINYINT(1)                     NOT NULL DEFAULT '0',
  videostatus       TINYINT(1)                     NOT NULL DEFAULT '0',
  audiostatus       TINYINT(1)                     NOT NULL DEFAULT '0',
  dehashstatus      TINYINT(1)                     NOT NULL DEFAULT '0',
  reqidstatus       TINYINT(1)                     NOT NULL DEFAULT '0',
  nzb_guid          BINARY(16)                     NULL,
  nzbstatus         TINYINT(1)                     NOT NULL DEFAULT '0',
  iscategorized     TINYINT(1)                     NOT NULL DEFAULT '0',
  isrenamed         TINYINT(1)                     NOT NULL DEFAULT '0',
  ishashed          TINYINT(1)                     NOT NULL DEFAULT '0',
  isrequestid       TINYINT(1)                     NOT NULL DEFAULT '0',
  proc_pp           TINYINT(1)                     NOT NULL DEFAULT '0',
  proc_sorter       TINYINT(1)                     NOT NULL DEFAULT '0',
  proc_par2         TINYINT(1)                     NOT NULL DEFAULT '0',
  proc_nfo          TINYINT(1)                     NOT NULL DEFAULT '0',
  proc_files        TINYINT(1)                     NOT NULL DEFAULT '0',
  proc_uid          TINYINT(1)                     NOT NULL DEFAULT '0',
  PRIMARY KEY                                 (id, categories_id),
  INDEX ix_releases_name                      (name),
  INDEX ix_releases_groupsid                  (groups_id,passwordstatus),
  INDEX ix_releases_postdate_searchname       (postdate,searchname),
  INDEX ix_releases_guid                      (guid),
  INDEX ix_releases_leftguid                  (leftguid ASC, predb_id),
  INDEX ix_releases_nzb_guid                  (nzb_guid),
  INDEX ix_releases_videos_id                 (videos_id),
  INDEX ix_releases_tv_episodes_id            (tv_episodes_id),
  INDEX ix_releases_imdbid                    (imdbid),
  INDEX ix_releases_xxxinfo_id                (xxxinfo_id),
  INDEX ix_releases_musicinfo_id              (musicinfo_id,passwordstatus),
  INDEX ix_releases_consoleinfo_id            (consoleinfo_id),
  INDEX ix_releases_gamesinfo_id              (gamesinfo_id),
  INDEX ix_releases_bookinfo_id               (bookinfo_id),
  INDEX ix_releases_anidbid                   (anidbid),
  INDEX ix_releases_predb_id_searchname       (predb_id,searchname),
  INDEX ix_releases_haspreview_passwordstatus (haspreview,passwordstatus),
  INDEX ix_releases_passwordstatus            (passwordstatus),
  INDEX ix_releases_nfostatus                 (nfostatus,size),
  INDEX ix_releases_dehashstatus              (dehashstatus,ishashed),
  INDEX ix_releases_reqidstatus               (adddate,reqidstatus,isrequestid)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1
  PARTITION BY RANGE (categories_id) (
    PARTITION misc    VALUES LESS THAN (1000),
    PARTITION console VALUES LESS THAN (2000),
    PARTITION movies  VALUES LESS THAN (3000),
    PARTITION audio   VALUES LESS THAN (4000),
    PARTITION pc      VALUES LESS THAN (5000),
    PARTITION tv      VALUES LESS THAN (6000),
    PARTITION xxx     VALUES LESS THAN (7000),
    PARTITION books   VALUES LESS THAN (9000)
  );


DROP TABLE IF EXISTS release_comments;
CREATE TABLE release_comments (
  id          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  releases_id INT(11) UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  text        VARCHAR(2000)    NOT NULL DEFAULT '',
  text_hash   VARCHAR(32)      NOT NULL DEFAULT '',
  username    VARCHAR(255)     NOT NULL DEFAULT '',
  user_id     INT(11) UNSIGNED NOT NULL,
  createddate DATETIME DEFAULT NULL,
  host        VARCHAR(15)      NULL,
  shared      TINYINT(1)       NOT NULL DEFAULT '0',
  shareid     VARCHAR(40)      NOT NULL DEFAULT '',
  siteid      VARCHAR(40)      NOT NULL DEFAULT '',
  nzb_guid    BINARY(16)       NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  PRIMARY KEY (id),
  UNIQUE INDEX ix_release_comments_hash_releases_id(text_hash, releases_id),
  INDEX ix_releasecomment_releases_id (releases_id),
  INDEX ix_releasecomment_userid    (user_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;

  DROP TABLE IF EXISTS release_unique;
  CREATE TABLE release_unique (
  releases_id   INT(11) UNSIGNED  NOT NULL COMMENT 'FK to releases.id.',
  uniqueid BINARY(16)  NOT NULL DEFAULT '0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0' COMMENT 'Unique_ID from mediainfo.',
  PRIMARY KEY (releases_id, uniqueid)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS releaseextrafull;
CREATE TABLE releaseextrafull (
  releases_id INT(11) UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  mediainfo   TEXT NULL,
  PRIMARY KEY (releases_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS release_files;
CREATE TABLE release_files (
  releases_id int(11) unsigned NOT NULL COMMENT 'FK to releases.id',
  name varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  size bigint(20) unsigned NOT NULL DEFAULT '0',
  ishashed tinyint(1) NOT NULL DEFAULT '0',
  createddate datetime DEFAULT NULL,
  passworded tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (releases_id, name),
  KEY ix_releasefiles_ishashed (ishashed)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS release_naming_regexes;
CREATE TABLE release_naming_regexes (
  id          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
  group_regex VARCHAR(255)        NOT NULL DEFAULT ''  COMMENT 'This is a regex to match against usenet groups',
  regex       VARCHAR(5000)       CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''  COMMENT 'Regex used for extracting name from subject',
  status      TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT '1=ON 0=OFF',
  description VARCHAR(1000)       CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''  COMMENT 'Optional extra details on this regex',
  ordinal     INT SIGNED          NOT NULL DEFAULT '0' COMMENT 'Order to run the regex in',
  PRIMARY KEY (id),
  INDEX ix_release_naming_regexes_group_regex (group_regex),
  INDEX ix_release_naming_regexes_status      (status),
  INDEX ix_release_naming_regexes_ordinal     (ordinal)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 100000;


DROP TABLE IF EXISTS release_nfos;
CREATE TABLE release_nfos (
  releases_id INT(11) UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  nfo       BLOB             NULL DEFAULT NULL,
  PRIMARY KEY (releases_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS release_search_data;
CREATE TABLE release_search_data (
  id         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  releases_id  INT(11) UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  guid       VARCHAR(50)      NOT NULL,
  name       VARCHAR(255)     NOT NULL DEFAULT '',
  searchname VARCHAR(255)     NOT NULL DEFAULT '',
  fromname   VARCHAR(255)     NULL,
  PRIMARY KEY                                        (id),
  FULLTEXT INDEX ix_releasesearch_name_ft (name),
  FULLTEXT INDEX ix_releasesearch_searchname_ft (searchname),
  FULLTEXT INDEX ix_releasesearch_fromname_ft (fromname),
  INDEX          ix_releasesearch_releases_id          (releases_id),
  INDEX          ix_releasesearch_guid               (guid)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;


DROP TABLE IF EXISTS release_subtitles;
CREATE TABLE release_subtitles (
  id           INT(11)     UNSIGNED AUTO_INCREMENT,
  releases_id    INT(11)     UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  subsid       INT(2)      UNSIGNED NOT NULL,
  subslanguage VARCHAR(50) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_releasesubs_releases_id_subsid (releases_id, subsid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS settings;
CREATE TABLE settings (
  section    VARCHAR(25)   NOT NULL DEFAULT '',
  subsection VARCHAR(25)   NOT NULL DEFAULT '',
  name       VARCHAR(25)   NOT NULL DEFAULT '',
  value      VARCHAR(1000) NOT NULL DEFAULT '',
  hint       TEXT          NOT NULL,
  setting    VARCHAR(64)   NOT NULL DEFAULT '',
  PRIMARY KEY (section, subsection, name),
  UNIQUE KEY ui_settings_setting (setting)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS sharing;
CREATE TABLE sharing (
  site_guid      VARCHAR(40)        NOT NULL DEFAULT '',
  site_name      VARCHAR(255)       NOT NULL DEFAULT '',
  username       VARCHAR(255)       NOT NULL DEFAULT '',
  enabled        TINYINT(1)         NOT NULL DEFAULT '0',
  posting        TINYINT(1)         NOT NULL DEFAULT '0',
  fetching       TINYINT(1)         NOT NULL DEFAULT '1',
  auto_enable    TINYINT(1)         NOT NULL DEFAULT '1',
  start_position TINYINT(1)         NOT NULL DEFAULT '0',
  hide_users     TINYINT(1)         NOT NULL DEFAULT '1',
  last_article   BIGINT UNSIGNED    NOT NULL DEFAULT '0',
  max_push       MEDIUMINT UNSIGNED NOT NULL DEFAULT '40',
  max_download   MEDIUMINT UNSIGNED NOT NULL DEFAULT '150',
  max_pull       MEDIUMINT UNSIGNED NOT NULL DEFAULT '20000',
  PRIMARY KEY (site_guid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS sharing_sites;
CREATE TABLE sharing_sites (
  id         INT(11) UNSIGNED   NOT NULL AUTO_INCREMENT,
  site_name  VARCHAR(255)       NOT NULL DEFAULT '',
  site_guid  VARCHAR(40)        NOT NULL DEFAULT '',
  last_time  DATETIME DEFAULT NULL,
  first_time DATETIME DEFAULT NULL,
  enabled    TINYINT(1)         NOT NULL DEFAULT '0',
  comments   MEDIUMINT UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS short_groups;
CREATE TABLE short_groups (
  id           INT(11)         NOT NULL AUTO_INCREMENT,
  name         VARCHAR(255)    NOT NULL DEFAULT '',
  first_record BIGINT UNSIGNED NOT NULL DEFAULT '0',
  last_record  BIGINT UNSIGNED NOT NULL DEFAULT '0',
  updated      DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX ix_shortgroups_name (name)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS tmux;
CREATE TABLE tmux (
  id          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  setting     VARCHAR(64)      NOT NULL,
  value       VARCHAR(19000)            DEFAULT NULL,
  updateddate TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_tmux_setting (setting)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS tv_episodes;
CREATE TABLE tv_episodes (
  id           INT(11)        UNSIGNED NOT NULL AUTO_INCREMENT,
  videos_id    MEDIUMINT(11)  UNSIGNED  NOT NULL COMMENT 'FK to videos.id of the parent series.',
  series       SMALLINT(5)    UNSIGNED    NOT NULL DEFAULT '0' COMMENT 'Number of series/season.',
  episode      SMALLINT(5)    UNSIGNED    NOT NULL DEFAULT '0' COMMENT 'Number of episode within series',
  se_complete  VARCHAR(10)    COLLATE utf8_unicode_ci NOT NULL COMMENT 'String version of Series/Episode as taken from release subject (i.e. S02E21+22).',
  title        VARCHAR(180)   CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Title of the episode.',
  firstaired   DATE           NOT NULL COMMENT 'Date of original airing/release.',
  summary      TEXT           CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description/summary of the episode.',
  PRIMARY KEY (id),
  UNIQUE KEY (videos_id, series, episode, firstaired)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT  = 1;

DROP TABLE IF EXISTS tv_info;
CREATE TABLE tv_info (
  videos_id MEDIUMINT(11) UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'FK to video.id',
  summary   TEXT          CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Description/summary of the show.',
  publisher VARCHAR(50)   CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'The channel/network of production/release (ABC, BBC, Showtime, etc.).',
  localzone VARCHAR(50)   CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '' COMMENT 'The linux tz style identifier',
  image     TINYINT(1)    UNSIGNED  NOT NULL DEFAULT '0' COMMENT 'Does the video have a cover image?',
  PRIMARY KEY          (videos_id),
  KEY ix_tv_info_image (image)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

DROP TABLE IF EXISTS upcoming_releases;
CREATE TABLE upcoming_releases (
  id          INT(10)                               NOT NULL AUTO_INCREMENT,
  source      VARCHAR(20)                           NOT NULL,
  typeid      INT(10)                               NOT NULL,
  info        TEXT                                  NULL,
  updateddate TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_upcoming_source (source, typeid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id             INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(50)      NOT NULL,
  firstname      VARCHAR(255)              DEFAULT NULL,
  lastname       VARCHAR(255)              DEFAULT NULL,
  email          VARCHAR(255)     NOT NULL,
  password       VARCHAR(255)     NOT NULL,
  role           INT              NOT NULL DEFAULT '1',
  host           VARCHAR(40)      NULL,
  grabs          INT              NOT NULL DEFAULT '0',
  rsstoken       VARCHAR(32)      NOT NULL,
  createddate    DATETIME         NOT NULL,
  resetguid      VARCHAR(50)      NULL,
  lastlogin      DATETIME                  DEFAULT NULL,
  apiaccess      DATETIME                  DEFAULT NULL,
  invites        INT              NOT NULL DEFAULT '0',
  invitedby      INT              NULL,
  movieview      INT              NOT NULL DEFAULT '1',
  xxxview        INT              NOT NULL DEFAULT '1',
  musicview      INT              NOT NULL DEFAULT '1',
  consoleview    INT              NOT NULL DEFAULT '1',
  bookview       INT              NOT NULL DEFAULT '1',
  gameview       INT              NOT NULL DEFAULT 1,
  saburl         VARCHAR(255)     NULL     DEFAULT NULL,
  sabapikey      VARCHAR(255)     NULL     DEFAULT NULL,
  sabapikeytype  TINYINT(1)       NULL     DEFAULT NULL,
  sabpriority    TINYINT(1)       NULL     DEFAULT NULL,
  queuetype      TINYINT(1)       NOT NULL DEFAULT '1'
  COMMENT 'Type of queue, Sab or NZBGet',
  nzbgeturl      VARCHAR(255)     NULL     DEFAULT NULL,
  nzbgetusername VARCHAR(255)     NULL     DEFAULT NULL,
  nzbgetpassword VARCHAR(255)     NULL     DEFAULT NULL,
  userseed       VARCHAR(50)      NOT NULL,
  cp_url         VARCHAR(255)     NULL     DEFAULT NULL,
  cp_api         VARCHAR(255)     NULL     DEFAULT NULL,
  style          VARCHAR(255)     NULL     DEFAULT NULL,
  PRIMARY KEY (id),
  INDEX ix_role (role)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS users_releases;
CREATE TABLE users_releases (
  id          INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT              NOT NULL,
  releases_id   INT              NOT NULL COMMENT 'FK to releases.id',
  createddate DATETIME         NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_usercart_userrelease (user_id, releases_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS user_downloads;
CREATE TABLE user_downloads (
  id        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(16)          NOT NULL,
  timestamp DATETIME         NOT NULL,
  PRIMARY KEY (id),
  KEY userid    (user_id),
  KEY timestamp (timestamp)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS user_excluded_categories;
CREATE TABLE user_excluded_categories (
  id          INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT              NOT NULL,
  categories_id  INT              NOT NULL,
  createddate DATETIME         NOT NULL,
  PRIMARY KEY (id),
  UNIQUE INDEX ix_userexcat_usercat (user_id, categories_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;

DROP TABLE IF EXISTS role_excluded_categories;
CREATE TABLE role_excluded_categories
(
    id              INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
    role            INT(11) NOT NULL,
    categories_id   INT(11),
    createddate     DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE INDEX ix_roleexcat_rolecat (role, categories_id)
)
    ENGINE = MyISAM
    DEFAULT CHARSET = utf8
    COLLATE = utf8_unicode_ci
    AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS user_movies;
CREATE TABLE user_movies (
  id          INT(16) UNSIGNED               NOT NULL AUTO_INCREMENT,
  user_id     INT(16)                        NOT NULL,
  imdbid      MEDIUMINT(7) UNSIGNED ZEROFILL NULL,
  categories  VARCHAR(64)                    NULL DEFAULT NULL COMMENT 'List of categories for user movies',
  createddate DATETIME                       NOT NULL,
  PRIMARY KEY (id),
  INDEX ix_usermovies_userid (user_id, imdbid)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS user_requests;
CREATE TABLE user_requests (
  id        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(16)          NOT NULL,
  request   VARCHAR(255)     NOT NULL,
  timestamp DATETIME         NOT NULL,
  PRIMARY KEY (id),
  KEY userid    (user_id),
  KEY timestamp (timestamp)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS user_roles;
CREATE TABLE user_roles (
  id               INT(10)             NOT NULL AUTO_INCREMENT,
  name             VARCHAR(32)         NOT NULL,
  apirequests      INT(10) UNSIGNED    NOT NULL,
  downloadrequests INT(10) UNSIGNED    NOT NULL,
  defaultinvites   INT(10) UNSIGNED    NOT NULL,
  isdefault        TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  canpreview       TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 4;


DROP TABLE IF EXISTS user_series;
CREATE TABLE user_series (
  id          INT(16) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT(16)          NOT NULL,
  videos_id   INT(16)          NOT NULL COMMENT 'FK to videos.id',
  categories  VARCHAR(64)      NULL DEFAULT NULL COMMENT 'List of categories for user tv shows',
  createddate DATETIME         NOT NULL,
  PRIMARY KEY (id),
  INDEX ix_userseries_videos_id (user_id, videos_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 1;


DROP TABLE IF EXISTS video_data;
CREATE TABLE video_data (
  releases_id       INT(11) UNSIGNED NOT NULL COMMENT 'FK to releases.id',
  containerformat VARCHAR(50) DEFAULT NULL,
  overallbitrate  VARCHAR(20) DEFAULT NULL,
  videoduration   VARCHAR(20) DEFAULT NULL,
  videoformat     VARCHAR(50) DEFAULT NULL,
  videocodec      VARCHAR(50) DEFAULT NULL,
  videowidth      INT(10)     DEFAULT NULL,
  videoheight     INT(10)     DEFAULT NULL,
  videoaspect     VARCHAR(10) DEFAULT NULL,
  videoframerate  FLOAT(7, 4) DEFAULT NULL,
  videolibrary    VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (releases_id)
)
  ENGINE = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS videos;
CREATE TABLE videos (
  id           MEDIUMINT(11) UNSIGNED  NOT NULL AUTO_INCREMENT COMMENT 'Show\'s ID to be used in other tables as reference.',
  type         TINYINT(1) UNSIGNED     NOT NULL DEFAULT '0' COMMENT '0 = TV, 1 = Film, 2 = Anime',
  title        VARCHAR(180) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Name of the video.',
  countries_id CHAR(2) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'Two character country code (FK to countries table).',
  started      DATETIME                NOT NULL COMMENT 'Date (UTC) of production''s first airing.',
  anidb        MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0'  COMMENT 'ID number for anidb site',
  imdb         MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for IMDB site (without the ''tt'' prefix).',
  tmdb         MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TMDB site.',
  trakt        MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TraktTV site.',
  tvdb         MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TVDB site',
  tvmaze       MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TVMaze site.',
  tvrage       MEDIUMINT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'ID number for TVRage site.',
  source       TINYINT(1)    UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Which site did we use for info?',
  PRIMARY KEY    (id),
  UNIQUE KEY     ix_videos_title (title, type, started, countries_id),
  KEY            ix_videos_imdb (imdb),
  KEY            ix_videos_tmdb (tmdb),
  KEY            ix_videos_trakt (trakt),
  KEY            ix_videos_tvdb (tvdb),
  KEY            ix_videos_tvmaze (tvmaze),
  KEY            ix_videos_tvrage (tvrage),
  KEY            ix_videos_type_source (type, source)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT = 10000000;

DROP TABLE IF EXISTS videos_aliases;
CREATE TABLE videos_aliases (
  videos_id   MEDIUMINT(11) UNSIGNED  NOT NULL COMMENT 'FK to videos.id of the parent title.',
  title VARCHAR(180) CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AKA of the video.',
  PRIMARY KEY (videos_id, title)
)
  ENGINE = MyISAM
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci
  AUTO_INCREMENT  = 10000000;

DROP TABLE IF EXISTS xxxinfo;
CREATE TABLE         xxxinfo (
  id          INT(10) UNSIGNED               NOT NULL AUTO_INCREMENT,
  title       VARCHAR(255)                   NOT NULL,
  tagline     VARCHAR(1024)                  NOT NULL,
  plot        BLOB                           NULL DEFAULT NULL,
  genre       VARCHAR(64)                    NOT NULL,
  director    VARCHAR(64)                    DEFAULT NULL,
  actors      VARCHAR(2500)                  NOT NULL,
  extras      TEXT                           DEFAULT NULL,
  productinfo TEXT                           DEFAULT NULL,
  trailers    TEXT                           DEFAULT NULL,
  directurl   VARCHAR(2000)                  NOT NULL,
  classused   VARCHAR(4)                     NOT NULL DEFAULT 'ade',
  cover       TINYINT(1) UNSIGNED            NOT NULL DEFAULT '0',
  backdrop    TINYINT(1) UNSIGNED            NOT NULL DEFAULT '0',
  createddate DATETIME                       NOT NULL,
  updateddate DATETIME                       NOT NULL,
  PRIMARY KEY                   (id),
  UNIQUE INDEX ix_xxxinfo_title (title)
)
  ENGINE          = MYISAM
  DEFAULT CHARSET = utf8
  COLLATE         = utf8_unicode_ci
  AUTO_INCREMENT  = 1;



DELIMITER $$
CREATE TRIGGER check_insert BEFORE INSERT ON releases FOR EACH ROW
  BEGIN
    IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}'
      THEN SET NEW.ishashed = 1;
    ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\s?\\['
      THEN SET NEW.isrequestid = 1;
    END IF;
  END; $$

CREATE TRIGGER check_update BEFORE UPDATE ON releases FOR EACH ROW
  BEGIN
    IF NEW.searchname REGEXP '[a-fA-F0-9]{32}' OR NEW.name REGEXP '[a-fA-F0-9]{32}'
      THEN SET NEW.ishashed = 1;
    ELSEIF NEW.name REGEXP '^\\[ ?([[:digit:]]{4,6}) ?\\]|^REQ\\s*([[:digit:]]{4,6})|^([[:digit:]]{4,6})-[[:digit:]]{1}\\s?\\['
      THEN SET NEW.isrequestid = 1;
    END IF;
  END; $$

CREATE TRIGGER check_rfinsert BEFORE INSERT ON release_files FOR EACH ROW
  BEGIN
    IF NEW.name REGEXP '[a-fA-F0-9]{32}'
      THEN SET NEW.ishashed = 1;
    END IF;
  END; $$

CREATE TRIGGER check_rfupdate BEFORE UPDATE ON release_files FOR EACH ROW
  BEGIN
    IF NEW.name REGEXP '[a-fA-F0-9]{32}'
      THEN SET NEW.ishashed = 1;
    END IF;
  END; $$

CREATE TRIGGER insert_search AFTER INSERT ON releases FOR EACH ROW
  BEGIN
    INSERT INTO release_search_data (releases_id, guid, name, searchname, fromname) VALUES (NEW.id, NEW.guid, NEW.name, NEW.searchname, NEW.fromname);
  END; $$

CREATE TRIGGER update_search AFTER UPDATE ON releases FOR EACH ROW
  BEGIN
    IF NEW.guid != OLD.guid
      THEN UPDATE release_search_data SET guid = NEW.guid WHERE releases_id = OLD.id;
    END IF;
    IF NEW.name != OLD.name
      THEN UPDATE release_search_data SET name = NEW.name WHERE releases_id = OLD.id;
    END IF;
    IF NEW.searchname != OLD.searchname
      THEN UPDATE release_search_data SET searchname = NEW.searchname WHERE releases_id = OLD.id;
    END IF;
    IF NEW.fromname != OLD.fromname
      THEN UPDATE release_search_data SET fromname = NEW.fromname WHERE releases_id = OLD.id;
    END IF;
  END; $$

CREATE TRIGGER delete_search AFTER DELETE ON releases FOR EACH ROW
  BEGIN
    DELETE FROM release_search_data WHERE releases_id = OLD.id;
  END; $$

CREATE TRIGGER insert_hashes AFTER INSERT ON predb FOR EACH ROW
  BEGIN
    INSERT INTO predb_hashes (hash, predb_id) VALUES (UNHEX(MD5(NEW.title)), NEW.id), (UNHEX(MD5
                                                                                            (MD5(NEW.title))), NEW.id), ( UNHEX(SHA1(NEW.title)), NEW.id);
  END; $$

CREATE TRIGGER update_hashes AFTER UPDATE ON predb FOR EACH ROW
  BEGIN
    IF NEW.title != OLD.title
      THEN
         DELETE FROM predb_hashes WHERE hash IN ( UNHEX(md5(OLD.title)), UNHEX(md5(md5(OLD.title))), UNHEX(sha1(OLD.title)) ) AND predb_id = OLD.id;
         INSERT INTO predb_hashes (hash, predb_id) VALUES ( UNHEX(MD5(NEW.title)), NEW.id ), ( UNHEX(MD5(MD5(NEW.title))), NEW.id ), ( UNHEX(SHA1(NEW.title)), NEW.id );
    END IF;
  END; $$

CREATE TRIGGER delete_hashes AFTER DELETE ON predb FOR EACH ROW
  BEGIN
    DELETE FROM predb_hashes WHERE hash IN ( UNHEX(md5(OLD.title)), UNHEX(md5(md5(OLD.title))), UNHEX(sha1(OLD.title)) ) AND predb_id = OLD.id;
  END; $$

CREATE TRIGGER insert_MD5 BEFORE INSERT ON release_comments FOR EACH ROW
  SET
    NEW.text_hash = MD5(NEW.text);
    $$

DELIMITER ;
