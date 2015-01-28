DROP TABLE IF EXISTS tmp_pre;
CREATE TABLE tmp_pre (
  title      VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  nfo        VARCHAR(255) COLLATE utf8_unicode_ci          DEFAULT NULL,
  size       VARCHAR(50)  COLLATE utf8_unicode_ci          DEFAULT NULL,
  category   VARCHAR(255) COLLATE utf8_unicode_ci          DEFAULT NULL,
  predate    DATETIME                         DEFAULT NULL,
  source     VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  requestid  INT(10) UNSIGNED        NOT NULL DEFAULT '0',
  group_id   INT(10) UNSIGNED        NOT NULL DEFAULT '0' COMMENT 'FK to groups',
  nuked      TINYINT(1)              NOT NULL DEFAULT '0' COMMENT 'Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked',
  nukereason VARCHAR(255) COLLATE utf8_unicode_ci          DEFAULT NULL COMMENT 'If this pre is nuked, what is the reason?',
  files      VARCHAR(50) COLLATE utf8_unicode_ci          DEFAULT NULL COMMENT 'How many files does this pre have ?',
  filename   VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  searched   TINYINT(1)              NOT NULL DEFAULT '0',
  groupname  VARCHAR(255)  COLLATE utf8_unicode_ci          DEFAULT NULL
)
  ENGINE =MYISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;
