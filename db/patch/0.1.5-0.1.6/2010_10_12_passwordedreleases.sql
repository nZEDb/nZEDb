alter table releases add passwordstatus int not null default -1;
alter table site add checkpasswordedrar int not null default 1;
alter table site add showpasswordedrelease int not null default 0;
