alter table site drop column `maxmssgs`;
alter table groups add `maxmsgs` INT NOT NULL DEFAULT 20000;