DELETE FROM collection_regexes WHERE id BETWEEN 586 AND 589;
INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  586,
  '^alt\\.binaries\\.sounds\\.(flac|lossless)$',
  '/^.+dream-of-usenet.+[-_ ]{0,3}\\[\\d+\\/(?P<match0>\\d+\\])[-_ ]{0,3}("|#34;)(?P<match1>.+?)(\\.part\d*|\\.rar)?(\\.vol.+ \\(\\d+\\/\\d+\) "|\\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/i',
  1,
  '(dream-of-usenet.info) - [01/21] - "A_Perfect_Circle-aMOTION-CD-FLAC-2004-SCORN.nfo" yEnc',
  5
), (
  587,
  '^alt\\.binaries\\.sounds\\.flac$',
  '/^.+www\\.EliteNZB\\.net.+[-_ ]{0,3}\[\\d+\\/(?P<match0>\\d+\\])[-_ ]{0,3}("|#34;)(?P<match1>.+?)(\\.part\d*|\\.rar)?(\\.vol.+ \\(\\d+\\/\\d+\) "|\\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/i',
  1,
  'The Elite Team Presents www.EliteNZB.net, Powered by 4UX.NL, Only The Best 4 You!!!! [01/19] - "Habariyaasubuhi_12352_TvPYrS1128.par2" yEnc',
  15
), (
  588,
  '^alt\\.binaries\\.sounds\\.flac$',
  '/^("|#34;)(?P<match1>.+?)(\\.part\d*|\\.rar)?(\\.vol.+ \\(\\d+\\/\\d+\) "|\\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}\\[\\d+\\/(?P<match0>\\d+\\]).+www\\.EliteNZB\\.net.+[-_ ]{0,3}yEnc$/i',
  1,
  '"Jinsi_12187_v807.par2" [01/13] - The Elite Team Presents www.EliteNZB.net, Powered by 4UX.NL, Only The Best 4 You!!!! yEnc',
  20
), (
  589,
  '^alt\\.binaries\\.mp3(\\.full_albums)?$',
  '/^JN Dutplanet.+[-_ ]{0,3}\\[\\d+\\/(?P<match0>\\d+\\])[-_ ]{0,3}("|#34;)(?P<match1>.+?)(\\.part\d*|\\.rar)?(\\.vol.+ \\(\\d+\\/\\d+\) "|\\.[A-Za-z0-9]{2,4}("|#34;))[-_ ]{0,3}yEnc$/i',
  1,
  'JN Dutplanet.net - [02/16] - "JN Dutplanet.net Foreigner - I Want To Know What Love Is - The Ballads [2014]FLAC.part1.rar" yEnc',
  5
);