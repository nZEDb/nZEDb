#Remove previus regex

DELETE FROM release_naming_regexes WHERE id = 1160;

#Add updated regex
INSERT INTO release_naming_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  1160,
  '^alt\\.binaries\\.multimedia\\.anime\\.highspeed$',
  '/^\\[[\\w\\s]+\\]\\s*(?P<match0>.+?\\[\\d+p\\].*?)\\.?([-_](proof|sample|thumbs?))*(\\.part\\d*(\\.rar)?|\\.rar|\\.7z)?(\\d{1,3}\\.rev|\\.vol.+?|\\.[A-Za-z0-9]{2,4})?\\s+\\[\\d+\\/\\d+\\]\\s+[-_]\\s+".+?"\\s+yEnc/i',
  1,
  '//[HorribleSubs] Bungou Stray Dogs - 07 [1080p] [04/19] - "[HorribleSubs] Bungou Stray Dogs - 07 [1080p].mkv.004" yEnc',
  10
);
