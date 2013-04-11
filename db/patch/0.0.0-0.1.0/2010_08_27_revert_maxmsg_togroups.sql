alter table site add `maxmssgs` INT NOT NULL DEFAULT 20000;
alter table groups drop column `maxmsgs`;
