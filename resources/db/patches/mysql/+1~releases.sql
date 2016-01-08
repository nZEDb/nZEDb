#Add proc_srr column to releases table, it might take some time
ALTER TABLE releases ADD proc_srr TINYINT(1) NOT NULL DEFAULT '0';
