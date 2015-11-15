REPLACE INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  588,
  '^alt\\.binaries\\.sounds\\.flac$',
  '/^("|#34;)(?P<match1>.+?)(\\.part\\d*|\\.rar)?(\\.vol.+ \\(\\d+\\/\\d+\) "|\\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}\\[\\d+\\/(?P<match0>\\d+\\]).+www\\.EliteNZB\\.net.+[-_ ]{0,3}yEnc$/i',
  1,
  '"Jinsi_12187_v807.par2" [01/13] - The Elite Team Presents www.EliteNZB.net, Powered by 4UX.NL, Only The Best 4 You!!!! yEnc',
  20
);