DELETE FROM collection_regexes WHERE id = 130;
INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  130,
  '^alt\\.binaries\\.cd\\.image$',
  '/^(?P<match0>\\[\\d+\\]-+\\[.+?\\]-\\[.+?\\]-)(\\s?\\[\\d+\\/\\d+\\])?[- ]{0,3}".+?"( - \\d+[,.]\\d+ [mMkKgG][bB] -)? yEnc$/i',
  1,
  '//[27849]-[altbinEFNet]-[Full]- "ppt-sogz.001" - 7,62 GB - yEnc ::: //[27925]--[altbinEFNet]-[Full]- "unl_p2rd.par2" yEnc ::: //[27608]-[FULL]-[#altbin@EFNet]-[007/136] "27608-1.005" yEnc ::: //[30605]-[altbinEFNet]-[FULL]- [10/165] - "plaza-the.witcher.3.wild.hunt.r09" yEnc',
  5
);