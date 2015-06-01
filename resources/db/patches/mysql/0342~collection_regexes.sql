INSERT INTO collection_regexes (id, group_regex, regex, status, description, ordinal)
VALUES (
  590,
  '(audiobooks|abooks)',
  '#^(?P<match0>.{8,}? )[\\[)]?\\d+(?P<match1>(?:/| of )\\d+[\\[)]?(?:[0-9\\WBKMG]+)?").+?"(?:[0-9\\WBKMG]+)? yEnc$#i',
  1,
  'As requested Diana Gabaldon 1of 3 parts - [025/356] - "Diana Gabaldon - (Outlander 06) A Breath Of Snow And Ashes - D01.07-18.mp3" yEnc',
  80
), (
  591,
  '(audiobooks|abooks)',
  '#^(?P<match0>.{8,}? ").+?" [\\[)]?\\d+(?P<match1>/\\d+[\\[)]? yEnc)$#i',
  1,
  'Dragonlance Second Generation - Dragons of Summer Flame (NMR 32 kbps)  "Dragonlance Second Generation - Dragons of Summer Flame.nzb" 000/141 yEnc',
  85
);
