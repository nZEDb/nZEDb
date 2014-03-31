/* Is this pre nuked? 0 no 2 yes 1 un nuked 3 mod nuked */
ALTER TABLE predb ADD COLUMN nuked TINYINT(1) NOT NULL DEFAULT '0';

/* If this pre is nuked, what is the reason? */
ALTER TABLE predb ADD COLUMN nukereason VARCHAR(255) NULL;

/* How many files does this pre have ? */
ALTER TABLE predb ADD COLUMN files VARCHAR(50) NULL;

UPDATE site SET value = '191' WHERE setting = 'sqlpatch';