ALTER TABLE releases DROP COLUMN passwordstatus ;
alter table releases add passwordstatus int not null default 0;

