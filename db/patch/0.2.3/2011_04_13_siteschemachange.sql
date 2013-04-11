RENAME TABLE `site` TO `site_old`;

CREATE TABLE `site` (
`ID` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
`setting` VARCHAR(64) NOT NULL,
`value` VARCHAR(19000) NULL,
`updateddate` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
UNIQUE (
`setting`
)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

INSERT INTO site (setting, value) VALUES ('code', (SELECT code FROM site_old));
INSERT INTO site (setting, value) VALUES ('title', (SELECT title FROM site_old));
INSERT INTO site (setting, value) VALUES ('strapline', (SELECT strapline FROM site_old));
INSERT INTO site (setting, value) VALUES ('metatitle', (SELECT metatitle FROM site_old));
INSERT INTO site (setting, value) VALUES ('metadescription', (SELECT metadescription FROM site_old));
INSERT INTO site (setting, value) VALUES ('metakeywords', (SELECT metakeywords FROM site_old));
INSERT INTO site (setting, value) VALUES ('footer', (SELECT footer FROM site_old));
INSERT INTO site (setting, value) VALUES ('email', (SELECT email FROM site_old));
INSERT INTO site (setting, value) VALUES ('google_adsense_search', (SELECT google_adsense_search FROM site_old));
INSERT INTO site (setting, value) VALUES ('google_analytics_acc', (SELECT google_analytics_acc FROM site_old));
INSERT INTO site (setting, value) VALUES ('google_adsense_acc', (SELECT google_adsense_acc FROM site_old));
INSERT INTO site (setting, value) VALUES ('siteseed', (SELECT siteseed FROM site_old));
INSERT INTO site (setting, value) VALUES ('tandc', (SELECT tandc FROM site_old));
INSERT INTO site (setting, value) VALUES ('registerstatus', (SELECT registerstatus FROM site_old));
INSERT INTO site (setting, value) VALUES ('style', (SELECT style FROM site_old));
INSERT INTO site (setting, value) VALUES ('menuposition', (SELECT menuposition FROM site_old));
INSERT INTO site (setting, value) VALUES ('dereferrer_link', (SELECT dereferrer_link FROM site_old));
INSERT INTO site (setting, value) VALUES ('nzbpath', (SELECT nzbpath FROM site_old));
INSERT INTO site (setting, value) VALUES ('rawretentiondays', (SELECT rawretentiondays FROM site_old));
INSERT INTO site (setting, value) VALUES ('attemptgroupbindays', (SELECT attemptgroupbindays FROM site_old));
INSERT INTO site (setting, value) VALUES ('lookuptvrage', (SELECT lookuptvrage FROM site_old));
INSERT INTO site (setting, value) VALUES ('lookupimdb', (SELECT lookupimdb FROM site_old));
INSERT INTO site (setting, value) VALUES ('lookupnfo', (SELECT lookupnfo FROM site_old));
INSERT INTO site (setting, value) VALUES ('lookupmusic', (SELECT lookupmusic FROM site_old));
INSERT INTO site (setting, value) VALUES ('lookupgames', (SELECT lookupgames FROM site_old));
INSERT INTO site (setting, value) VALUES ('lookupanidb', (SELECT lookupanidb FROM site_old));
INSERT INTO site (setting, value) VALUES ('amazonpubkey', (SELECT amazonpubkey FROM site_old));
INSERT INTO site (setting, value) VALUES ('amazonprivkey', (SELECT amazonprivkey FROM site_old));
INSERT INTO site (setting, value) VALUES ('tmdbkey', (SELECT tmdbkey FROM site_old));
INSERT INTO site (setting, value) VALUES ('compressedheaders', (SELECT compressedheaders FROM site_old));
INSERT INTO site (setting, value) VALUES ('maxmssgs', (SELECT maxmssgs FROM site_old));
INSERT INTO site (setting, value) VALUES ('newgroupscanmethod', (SELECT newgroupscanmethod FROM site_old));
INSERT INTO site (setting, value) VALUES ('newgroupdaystoscan', (SELECT newgroupdaystoscan FROM site_old));
INSERT INTO site (setting, value) VALUES ('newgroupmsgstoscan', (SELECT newgroupmsgstoscan FROM site_old));
INSERT INTO site (setting, value) VALUES ('storeuserips', (SELECT storeuserips FROM site_old));
INSERT INTO site (setting, value) VALUES ('minfilestoformrelease', (SELECT minfilestoformrelease FROM site_old));
INSERT INTO site (setting, value) VALUES ('minsizetoformrelease', (SELECT minsizetoformrelease FROM site_old));
INSERT INTO site (setting, value) VALUES ('reqidurl', (SELECT reqidurl FROM site_old));
INSERT INTO site (setting, value) VALUES ('latestregexurl', (SELECT latestregexurl FROM site_old));
INSERT INTO site (setting, value) VALUES ('latestregexrevision', (SELECT latestregexrevision FROM site_old));
INSERT INTO site (setting, value) VALUES ('releaseretentiondays', (SELECT releaseretentiondays FROM site_old));
INSERT INTO site (setting, value) VALUES ('checkpasswordedrar', (SELECT checkpasswordedrar FROM site_old));
INSERT INTO site (setting, value) VALUES ('showpasswordedrelease', (SELECT showpasswordedrelease FROM site_old));
INSERT INTO site (setting, value) VALUES ('deletepasswordedrelease', (SELECT deletepasswordedrelease FROM site_old));
INSERT INTO site (setting, value) VALUES ('unrarpath', (SELECT unrarpath FROM site_old));
INSERT INTO site (setting, value) VALUES ('mediainfopath', (SELECT mediainfopath FROM site_old));
INSERT INTO site (setting, value) VALUES ('ffmpegpath', (SELECT ffmpegpath FROM site_old));
INSERT INTO site (setting, value) VALUES ('tmpunrarpath', (SELECT tmpunrarpath FROM site_old));
INSERT INTO site (setting, value) VALUES ('newznabID', (SELECT newznabID FROM site_old));
INSERT INTO site (setting, value) VALUES ('adheader', (SELECT adheader FROM site_old));
INSERT INTO site (setting, value) VALUES ('adbrowse', (SELECT adbrowse FROM site_old));
INSERT INTO site (setting, value) VALUES ('addetail', (SELECT addetail FROM site_old));

