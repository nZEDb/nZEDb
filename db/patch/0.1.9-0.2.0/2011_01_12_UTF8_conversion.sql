
ALTER TABLE  `binaries` CHANGE  `name`  `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `fromname`  `fromname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `xref`  `xref` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `relname`  `relname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `importname`  `importname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `binaries` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `binaryblacklist` CHANGE  `groupname`  `groupname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `regex`  `regex` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `description`  `description` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `binaryblacklist` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `category` CHANGE  `title`  `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `description`  `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `category` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `content` CHANGE  `title`  `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `url`  `url` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `body`  `body` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `metadescription`  `metadescription` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `metakeywords`  `metakeywords` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE  `content` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `groups` CHANGE  `name`  `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `description`  `description` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `groups` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `menu` CHANGE  `href`  `href` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `title`  `title` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `tooltip`  `tooltip` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '' ,
CHANGE  `menueval`  `menueval` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '';

ALTER TABLE  `menu` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `movieinfo` CHANGE  `title`  `title` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `tagline`  `tagline` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `rating`  `rating` VARCHAR( 4 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `plot`  `plot` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `year`  `year` VARCHAR( 4 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `genre`  `genre` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `director`  `director` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `actors`  `actors` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `language`  `language` VARCHAR( 64 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE  `movieinfo` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `musicgenre` CHANGE  `title`  `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE  `musicgenre` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;


ALTER TABLE  `musicinfo` CHANGE  `title`  `title` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `asin`  `asin` VARCHAR( 128 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `url`  `url` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `artist`  `artist` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `publisher`  `publisher` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `review`  `review` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `year`  `year` VARCHAR( 4 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `tracks`  `tracks` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `musicinfo` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `partrepair` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `parts` CHANGE  `messageID`  `messageID` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '';

ALTER TABLE  `parts` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `releasecomment` CHANGE  `text`  `text` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '',
CHANGE  `host`  `host` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `releasecomment` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `releasenfo` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `releaseregex` CHANGE  `groupname`  `groupname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `regex`  `regex` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `description`  `description` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `releaseregex` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `releases` DROP INDEX  `searchname`;

ALTER TABLE  `releases` CHANGE  `name`  `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '',
CHANGE  `searchname`  `searchname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '',
CHANGE  `guid`  `guid` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `fromname`  `fromname` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `seriesfull`  `seriesfull` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `season`  `season` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `episode`  `episode` VARCHAR( 10 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `tvtitle`  `tvtitle` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `releases` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE `site` CHANGE `code` `code` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `title` `title` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `strapline` `strapline` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `metatitle` `metatitle` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `metadescription` `metadescription` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `metakeywords` `metakeywords` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `footer` `footer` VARCHAR(2000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `email` `email` VARCHAR(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL , 
CHANGE  `google_adsense_search` `google_adsense_search` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `google_adsense_sidepanel`  `google_adsense_sidepanel` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `google_analytics_acc`  `google_analytics_acc` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `google_adsense_acc`  `google_adsense_acc` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `siteseed`  `siteseed` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `tandc`  `tandc` VARCHAR( 5000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `style`  `style` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `dereferrer_link`  `dereferrer_link` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `nzbpath`  `nzbpath` VARCHAR( 500 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `amazonpubkey`  `amazonpubkey` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `amazonprivkey`  `amazonprivkey` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `tmdbkey`  `tmdbkey` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `reqidurl`  `reqidurl` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  'http://allfilled.newznab.com/query.php?t=[GROUP]&reqid=[REQID]',
CHANGE  `latestregexurl`  `latestregexurl` VARCHAR( 1000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  'http://www.newznab.com/latestregex.sql';

ALTER TABLE  `site` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `tvrage` CHANGE  `releasetitle`  `releasetitle` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT  '',
CHANGE  `description`  `description` VARCHAR( 2000 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `tvrage` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `usercart` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `userexcat` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `userinvite` CHANGE  `guid`  `guid` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;

ALTER TABLE  `userinvite` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;



ALTER TABLE  `users` CHANGE  `username`  `username` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `email`  `email` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `password`  `password` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `host`  `host` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ,
CHANGE  `rsstoken`  `rsstoken` VARCHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
CHANGE  `resetguid`  `resetguid` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

ALTER TABLE  `users` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;