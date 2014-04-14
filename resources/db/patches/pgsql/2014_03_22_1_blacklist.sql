INSERT INTO binaryblacklist (id, groupname, regex, msgcol, optype, status, description) VALUES (10, 'alt\\.binaries\\.(boneless|movies\\.divx)', '((Frkz|info)@XviD2?|x?VIDZ?@pwrpst|movies@movies?)\\.net|(hsv\\.stoned@hotmail|unequal87@gmail|ilove@movies)\\.com', 2, 1, 0, 'Virus codec posters.');

UPDATE site set value = '186' where setting = 'sqlpatch';