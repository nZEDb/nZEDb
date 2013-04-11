alter table groups add `minsizetoformrelease` BIGINT NULL;
alter table site add `minsizetoformrelease` BIGINT NOT NULL DEFAULT 0;

