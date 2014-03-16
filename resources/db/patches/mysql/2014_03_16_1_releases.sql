ALTER TABLE releases CHANGE COLUMN nzbstatus nzbstatus TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN iscategorized iscategorized TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN isrenamed isrenamed TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN ishashed ishashed TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN isrequestid isrequestid TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN jpgstatus jpgstatus TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN videostatus videostatus TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN audiostatus audiostatus TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN proc_pp proc_pp TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN proc_sorter proc_sorter TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN proc_par2 proc_par2 TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN proc_nfo proc_nfo TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE releases CHANGE COLUMN proc_files proc_files TINYINT(1) NOT NULL DEFAULT 0;

UPDATE site set value = '184' where setting = 'sqlpatch';
